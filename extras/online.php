<?php
/**
 * 在线人数接口
 *   POST /online.php?do=ping&pv=1   页面浏览上报 + 心跳
 *   POST /online.php?do=ping        纯心跳
 *   POST /online.php?do=leave       关闭页面，立即下线
 *
 * 身份完全由服务端判定：
 *   带 Typecho 登录 cookie → 博主
 *   邮箱 / 网址命中友链表  → 朋友
 *   其余                   → 游客
 */
define('__TYPECHO_ROOT_DIR__', __DIR__);

require_once __DIR__ . '/var/Typecho/Common.php';
\Typecho\Common::init();
require_once __DIR__ . '/config.inc.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

/** 在线窗口（秒）：心跳 45s，窗口 90s；异常退出最迟 90 秒后消失 */
const ONLINE_WINDOW = 90;

$now = time();
$do  = isset($_GET['do']) ? (string)$_GET['do'] : 'ping';

// 只接受 POST，避免预取、爬虫和图片预加载把在线人数刷上去
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => 0, 'error' => 'method']);
    exit;
}

try {
    $db      = \Typecho\Db::get();
    $prefix  = $db->getPrefix();
    $adapter = $db->getAdapter();
} catch (\Throwable $e) {
    echo json_encode(['ok' => 0, 'error' => 'db']);
    exit;
}

$qv = function ($v) use ($adapter) { return $adapter->quoteValue((string)$v); };
$qw = function ($sql) use ($db) { return $db->query($sql, \Typecho\Db::WRITE); };

/* ------------------------------ 会话标识 ------------------------------ */
$sid = isset($_COOKIE['qqx_sid']) ? preg_replace('/[^a-f0-9]/', '', (string)$_COOKIE['qqx_sid']) : '';
if (strlen($sid) !== 32) {
    $sid = bin2hex(random_bytes(16));
    setcookie('qqx_sid', $sid, [
        'expires'  => $now + 31536000,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off'),
    ]);
}

/* ------------------------------ 身份判定 ------------------------------ */
/** Typecho 会在 cookie 名前面拼 md5(rootUrl)，所以只能按后缀找 */
function qqx_cookie(string $suffix): string
{
    $len = strlen($suffix);
    foreach ($_COOKIE as $k => $v) {
        if (strlen($k) >= $len && substr($k, -$len) === $suffix) {
            return is_string($v) ? $v : '';
        }
    }
    return '';
}

function qqx_role(\Typecho\Db $db): string
{
    $uid  = qqx_cookie('__typecho_uid');
    $auth = qqx_cookie('__typecho_authCode');
    if ($uid !== '' && $auth !== '') {
        // 确认这个 uid 真的存在，避免伪造 cookie 冒充博主
        $exists = $db->fetchRow($db->select('uid')->from('table.users')->where('uid = ?', (int)$uid));
        if ($exists) return 'blogger';
    }

    $mail = trim(qqx_cookie('__typecho_remember_mail'));
    $url  = trim(qqx_cookie('__typecho_remember_url'));
    if ($mail === '' && $url === '') {
        return 'guest';
    }

    try {
        foreach ($db->fetchAll($db->select('user', 'url')->from('table.links')) as $r) {
            if ($mail !== '' && !empty($r['user']) && strcasecmp(trim($r['user']), $mail) === 0) {
                return 'friend';
            }
            if ($url !== '' && !empty($r['url'])) {
                $a = parse_url($r['url'], PHP_URL_HOST);
                $b = parse_url($url, PHP_URL_HOST);
                if ($a && $b && strcasecmp($a, $b) === 0) {
                    return 'friend';
                }
            }
        }
    } catch (\Throwable $e) {
    }
    return 'guest';
}

/* -------------------------------- 下线 -------------------------------- */
if ($do === 'leave') {
    try {
        $qw("DELETE FROM {$prefix}online WHERE sid = " . $qv($sid));
    } catch (\Throwable $e) {
    }
    echo json_encode(['ok' => 1]);
    exit;
}

/* -------------------------------- 心跳 -------------------------------- */
$role = qqx_role($db);

// 记录博主最后活跃时间，供侧栏展示
if ($role === 'blogger') {
    try {
        $qw("INSERT INTO {$prefix}online_meta (k, v) VALUES ('last_blogger', {$now})
             ON DUPLICATE KEY UPDATE v = {$now}");
    } catch (\Throwable $e) {
    }
}
$ip   = isset($_SERVER['REMOTE_ADDR']) ? substr((string)$_SERVER['REMOTE_ADDR'], 0, 45) : '';
$agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
$path = '';
if (!empty($_SERVER['HTTP_REFERER'])) {
    $path = substr((string)parse_url((string)$_SERVER['HTTP_REFERER'], PHP_URL_PATH), 0, 120);
}

try {
    $qw(
        "INSERT INTO {$prefix}online (sid, role, ip, agent, path, first_seen, last_seen)
         VALUES ({$qv($sid)}, {$qv($role)}, {$qv($ip)}, {$qv($agent)}, {$qv($path)}, {$now}, {$now})
         ON DUPLICATE KEY UPDATE role = VALUES(role), agent = VALUES(agent), path = VALUES(path), last_seen = {$now}"
    );

    // 过期会话清理：2% 概率触发，避免每次心跳都扫表
    if (mt_rand(1, 50) === 1) {
        $qw("DELETE FROM {$prefix}online WHERE last_seen < " . ($now - 600));
        $qw("DELETE FROM {$prefix}online_visitor WHERE day < DATE_SUB(CURDATE(), INTERVAL 400 DAY)");
    }
} catch (\Throwable $e) {
    echo json_encode(['ok' => 0, 'error' => 'write']);
    exit;
}

/* ------------------------------ 今日聚合 ------------------------------ */
$today = date('Y-m-d');
try {
    $qw("INSERT IGNORE INTO {$prefix}online_visitor (day, sid) VALUES ('{$today}', " . $qv($sid) . ")");

    $row = $db->fetchObject("SELECT COUNT(*) AS n FROM {$prefix}online_visitor WHERE day = '{$today}'");
    $uniques = $row ? (int)$row->n : 0;

    $pv = (isset($_GET['pv']) && $_GET['pv'] === '1') ? 1 : 0;
    $qw(
        "INSERT INTO {$prefix}online_daily (day, uniques, views, peak, peak_at)
         VALUES ('{$today}', {$uniques}, {$pv}, 0, 0)
         ON DUPLICATE KEY UPDATE uniques = {$uniques}, views = views + {$pv}"
    );
} catch (\Throwable $e) {
}

/* ------------------------------ 在线人数 ------------------------------ */
$counts = ['blogger' => 0, 'friend' => 0, 'guest' => 0];
try {
    $rows = $db->fetchAll(
        $db->select('role', ['COUNT(sid)' => 'n'])->from('table.online')
            ->where('last_seen > ?', $now - ONLINE_WINDOW)->group('role')
    );
    foreach ($rows as $r) {
        $k = $r['role'];
        if (isset($counts[$k])) {
            $counts[$k] = (int)$r['n'];
        }
    }
} catch (\Throwable $e) {
}

$total = $counts['blogger'] + $counts['friend'] + $counts['guest'];

try {
    $qw(
        "UPDATE {$prefix}online_daily
         SET peak_at = IF({$total} > peak, {$now}, peak_at),
             peak = GREATEST(peak, {$total})
         WHERE day = '{$today}'"
    );
} catch (\Throwable $e) {
}

/* 今日数据一并返回，页面上的数字可以实时刷新 */
$todayRow = null;
try {
    $todayRow = $db->fetchObject(
        "SELECT uniques, views, peak, peak_at FROM {$prefix}online_daily WHERE day = '{$today}'"
    );
} catch (\Throwable $e) {
}

echo json_encode([
    'ok'     => 1,
    'role'   => $role,
    'counts' => $counts,
    'total'  => $total,
    'today'  => $todayRow ? [
        'uniques' => (int)$todayRow->uniques,
        'views'   => (int)$todayRow->views,
        'peak'    => (int)$todayRow->peak,
        'peak_at' => (int)$todayRow->peak_at,
    ] : null,
], JSON_UNESCAPED_UNICODE);

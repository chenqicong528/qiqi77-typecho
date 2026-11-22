<?php
/**
 * 独立 sitemap：放到站点根目录（与 index.php 同级），访问 /sitemap.xml
 * 若服务器不支持重写，可直接访问 /sitemap.php
 */
declare(strict_types=1);

require __DIR__ . '/config.inc.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

$options = \Widget\Options::alloc();
$db = \Typecho\Db::get();
$site = rtrim((string) $options->siteUrl, '/');
$rewrite = !empty($options->rewrite);
$base = $site . ($rewrite ? '' : '/index.php');

/* 让 Router 认识本站的路由格式（/archives/1/ 或 /1/ 等） */
try {
    \Typecho\Router::setRoutes((array) $options->routingTable);
} catch (\Throwable $e) {
    /* 失败就退回拼接 */
}

$url = static function (string $route, array $params, string $fallback) use ($site, $base): string {
    try {
        $u = (string) \Typecho\Router::url($route, $params, $base);
        if ($u !== '' && $u[0] === '/') {
            $u = $base . $u;
        }
        return $u !== '' && $u !== '#' ? $u : $base . $fallback;
    } catch (\Throwable $e) {
        return $site . $fallback;
    }
};

$entries = [];

/* 文章 */
try {
    $rows = $db->fetchAll(
        $db->select('cid', 'modified', 'created')->from('table.contents')
            ->where('type = ?', 'post')->where('status = ?', 'publish')
            ->order('created', \Typecho\Db::SORT_DESC)->limit(5000)
    );
    foreach ($rows as $row) {
        $entries[] = [
            'loc' => $url('post', ['cid' => (int) $row['cid']], '/archives/' . (int) $row['cid'] . '/'),
            'lastmod' => gmdate('c', (int) ($row['modified'] ?: $row['created'])),
            'priority' => '0.8',
        ];
    }
} catch (\Throwable $e) {
}

/* 独立页面 */
try {
    $rows = $db->fetchAll(
        $db->select('cid', 'slug', 'modified', 'created')->from('table.contents')
            ->where('type = ?', 'page')->where('status = ?', 'publish')
            ->order('created', \Typecho\Db::SORT_DESC)->limit(500)
    );
    foreach ($rows as $row) {
        $entries[] = [
            'loc' => $url('page', ['slug' => (string) $row['slug']], '/' . $row['slug'] . '.html'),
            'lastmod' => gmdate('c', (int) ($row['modified'] ?: $row['created'])),
            'priority' => '0.5',
        ];
    }
} catch (\Throwable $e) {
}

/* 分类 */
try {
    $rows = $db->fetchAll(
        $db->select('slug', 'count')->from('table.metas')
            ->where('type = ?', 'category')->order('count', \Typecho\Db::SORT_DESC)->limit(200)
    );
    foreach ($rows as $row) {
        $entries[] = [
            'loc' => $url('category', ['slug' => (string) $row['slug']], '/category/' . $row['slug'] . '/'),
            'priority' => '0.4',
        ];
    }
} catch (\Throwable $e) {
}

/* 首页 */
array_unshift($entries, ['loc' => $site . '/', 'priority' => '1.0']);

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($entries as $e) {
    echo "  <url>\n";
    echo '    <loc>', htmlspecialchars($e['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8'), "</loc>\n";
    if (!empty($e['lastmod'])) {
        echo '    <lastmod>', $e['lastmod'], "</lastmod>\n";
    }
    echo '    <priority>', $e['priority'] ?? '0.5', "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";

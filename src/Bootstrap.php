<?php
/**
 * 用途：装配容器（Db → 各服务）与主题生命周期钩子
 * 依赖：Typecho\Db、Content\Metrics、Comment\Thread、Site\Online、Site\Nav
 * 被调用：functions.php、模板
 */
declare(strict_types=1);

namespace qiqi77;

use qiqi77\Comment\Thread;
use qiqi77\Content\Metrics;
use qiqi77\Site\Drawer;
use qiqi77\Site\Nav;
use qiqi77\Site\Online;
use Typecho\Db;

final class Bootstrap
{
    private static array $box = [];

    /** 取容器里的服务实例：单例，避免一个请求里重复查库 */
    public static function get(string $id): object
    {
        if (isset(self::$box[$id])) {
            return self::$box[$id];
        }
        $db = Db::get();
        switch ($id) {
            case 'metrics':
                self::$box[$id] = new Metrics($db);
                break;
            case 'thread':
                self::$box[$id] = new Thread($db);
                break;
            case 'online':
                self::$box[$id] = new Online($db);
                break;
            case 'nav':
                self::$box[$id] = new Nav($db);
                break;
            case 'drawer':
                self::$box[$id] = new Drawer($db);
                break;
            default:
                throw new \RuntimeException('unknown service: ' . $id);
        }
        return self::$box[$id];
    }

    /** 主题初始化：Typecho 在每次请求早期调用一次 */
    public static function onInit($archive): void
    {
        // 深色偏好交给前端 cookie，服务端只把「跟随系统」标记带给模板
        if (!defined('QIQI77_READY')) {
            define('QIQI77_READY', true);
        }

        /* 多域名：输出阶段把主域换成本次访问的域 */
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            /* 不判断 ob_get_level：PHP 默认就有一层输出缓冲，判断了反而永远不生效 */
            ob_start([\qiqi77\Support\Url::class, 'rewrite']);
        }

        self::ensurePages();

        // 评论提交兜底：前端没补上时，服务端按 QQ 号补昵称与邮箱
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($_POST['qq'])) {
            $qq = preg_replace('/\D/', '', (string) $_POST['qq']);
            if (preg_match('/^[1-9]\d{4,10}$/', $qq)) {
                if (trim((string) ($_POST['mail'] ?? '')) === '') {
                    $_POST['mail'] = $qq . '@qq.com';
                }
                if (trim((string) ($_POST['author'] ?? '')) === '') {
                    $_POST['author'] = $qq;
                }
            }
        }
    }

    /** 首次访问时补建「在线访客」「站点统计」两个页面，避免 /online /stats 404 */
    private static function ensurePages(): void
    {
        try {
            $db = \Typecho\Db::get();
            $flag = $db->fetchRow(
                $db->select('value')->from('table.options')->where('name = ?', 'theme:qiqi77Pages')
            );
            if ($flag) {
                return;
            }

            $pages = [
                ['slug' => 'online', 'title' => '在线访客', 'template' => 'tpl-online.php'],
                ['slug' => 'stats',  'title' => '站点统计', 'template' => 'tpl-stats.php'],
            ];

            foreach ($pages as $p) {
                $exists = $db->fetchRow(
                    $db->select('cid')->from('table.contents')
                        ->where('type = ?', 'page')->where('slug = ?', $p['slug'])->limit(1)
                );
                if ($exists) {
                    continue;
                }
                $now = time();
                $db->query($db->insert('table.contents')->rows([
                    'title'        => $p['title'],
                    'slug'         => $p['slug'],
                    'created'      => $now,
                    'modified'     => $now,
                    'text'         => '<!--markdown-->',
                    'order'        => 0,
                    'authorId'     => 1,
                    'template'     => $p['template'],
                    'type'         => 'page',
                    'status'       => 'publish',
                    'password'     => null,
                    'commentsNum'  => 0,
                    'allowComment' => 0,
                    'allowPing'    => 0,
                    'allowFeed'    => 1,
                    'parent'       => 0,
                ]));
            }

            $db->query($db->insert('table.options')->rows([
                'name'  => 'theme:qiqi77Pages',
                'user'  => 0,
                'value' => '1',
            ]));
        } catch (\Throwable $e) {
            /* 建页失败不影响正文渲染 */
        }
    }
    /** 后台主题设置页打开时执行一次性迁移，前台不触发写库 */
    public static function prepareAdmin(): void
    {
        Options::migrate();
    }
}

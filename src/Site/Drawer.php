<?php
/**
 * 用途：左侧抽屉的数据构建（菜单节点 / 社交图标 / 计数 / 署名）
 * 依赖：Typecho\Db、Options
 * 被调用：views/chrome/drawer
 */
declare(strict_types=1);

namespace qiqi77\Site;

use qiqi77\Options;
use Typecho\Db;

final class Drawer
{
    /** 社交平台 → URL 前缀（旧站抽屉里支持的九种） */
    private const SOCIAL_BASE = [
        'qq'            => 'https://wpa.qq.com/msgrd?v=3&site=qq&menu=yes&uin=',
        'weibo'         => 'https://weibo.com/',
        'bilibili'      => 'https://space.bilibili.com/',
        'github'        => 'https://github.com/',
        'twitter'       => 'https://twitter.com/',
        'telegram'      => 'https://t.me/',
        'email'         => 'mailto:',
        'netease-music' => 'https://music.163.com/#/user/home?id=',
        'zhihu'         => 'https://www.zhihu.com/people/',
        'douyin'        => 'https://v.douyin.com/',
    ];

    public function __construct(private Db $db)
    {
    }

    /**
     * 解析菜单配置：优先 JSON（旧站格式），退化为「名称|链接|图标」行文本
     * 返回统一结构的节点数组，模板只负责渲染
     */
    public function nodes(): array
    {
        $raw = trim((string) Options::get('qq_menu_extra', ''));
        if ($raw === '') {
            return [];
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            $json = [];
            foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
                $parts = array_map('trim', explode('|', $line));
                if (count($parts) >= 2 && $parts[1] !== '') {
                    $json[] = ['name' => $parts[0], 'link' => $parts[1], 'icon' => $parts[2] ?? 'link', 'type' => '0'];
                }
            }
        }

        $out = [];
        foreach ($json as $item) {
            $type = (string) ($item['type'] ?? '0');
            $label = (string) ($item['name'] ?? '');
            $icon = (string) ($item['icon'] ?? 'link');

            switch ($type) {
                case '1':                       // 按月归档
                    $out[] = ['kind' => 'fold', 'label' => $label, 'icon' => $icon, 'items' => $this->months()];
                    break;
                case '2':                       // 分类
                    $out[] = ['kind' => 'fold', 'label' => $label, 'icon' => $icon, 'items' => $this->categories()];
                    break;
                case '3':                       // 独立页面
                    $out[] = ['kind' => 'fold', 'label' => $label, 'icon' => $icon, 'items' => $this->pages()];
                    break;
                case '4':                       // 自定义子列表
                    $items = [];
                    foreach ((array) ($item['list'] ?? []) as $sub) {
                        $items[] = ['name' => (string) ($sub['name'] ?? ''), 'url' => (string) ($sub['link'] ?? '#'), 'extra' => ''];
                    }
                    $out[] = ['kind' => 'fold', 'label' => $label, 'icon' => $icon, 'items' => $items];
                    break;
                case '5':                       // 分隔线
                    $out[] = ['kind' => 'split'];
                    break;
                case '6':                       // RSS
                    $out[] = ['kind' => 'link', 'label' => 'RSS 订阅', 'icon' => 'rss_feed',
                              'url' => (string) \Helper::options()->feedUrl, 'blank' => true];
                    break;
                default:                        // 普通链接
                    $out[] = ['kind' => 'link', 'label' => $label, 'icon' => $icon,
                              'url' => (string) ($item['link'] ?? '#'), 'blank' => false];
            }
        }
        return $out;
    }

    /** 社交图标：{qq: "号码", github: "用户名"} → 完整链接 */
    public function socials(): array
    {
        $raw = trim((string) Options::get('qq_drawer_contacts', ''));
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ($data as $key => $value) {
            $value = trim((string) $value);
            if ($value === '' || !isset(self::SOCIAL_BASE[$key])) {
                continue;
            }
            $out[] = [
                'icon' => 'icon-' . $key,
                'url'  => self::SOCIAL_BASE[$key] . $value,
                'name' => $key,
            ];
        }
        return $out;
    }

    /** 抽屉底部图标：{iconName: url}，加署名行 */
    public function footer(): array
    {
        $raw = trim((string) Options::get('qq_drawer_footer', ''));
        $icons = [];
        $data = json_decode($raw, true);
        if (is_array($data)) {
            foreach ($data as $icon => $url) {
                if (trim((string) $url) !== '') {
                    $icons[] = ['name' => (string) $icon, 'url' => (string) $url];
                }
            }
        }
        return ['icons' => $icons, 'credit' => ''];
    }

    /** 文章 / 评论 / 分类 三个数 */
    public function counters(): array
    {
        $out = ['posts' => 0, 'comments' => 0, 'cats' => 0];
        try {
            $out['posts'] = (int) $this->db->fetchRow(
                $this->db->select(['COUNT(cid)' => 'n'])->from('table.contents')
                    ->where('type = ?', 'post')->where('status = ?', 'publish')
            )['n'];
            $out['comments'] = (int) $this->db->fetchRow(
                $this->db->select(['COUNT(coid)' => 'n'])->from('table.comments')->where('status = ?', 'approved')
            )['n'];
            $out['cats'] = (int) $this->db->fetchRow(
                $this->db->select(['COUNT(mid)' => 'n'])->from('table.metas')->where('type = ?', 'category')
            )['n'];
        } catch (\Throwable $e) {
        }
        return $out;
    }

    /** 按月归档：一次分组查询，取最近 12 个月 */
    private function months(): array
    {
        try {
            $rows = $this->db->fetchAll(
                'SELECT DATE_FORMAT(FROM_UNIXTIME(created), "%Y-%m") AS ym, COUNT(*) AS n '
                . 'FROM ' . $this->db->getPrefix() . 'contents '
                . 'WHERE type = "post" AND status = "publish" '
                . 'GROUP BY ym ORDER BY ym DESC LIMIT 12'
            );
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            [$y, $m] = explode('-', (string) $row['ym']);
            $out[] = [
                'name'  => $y . ' 年 ' . (int) $m . ' 月',
                'url'   => Options::url('/' . $y . '/' . str_pad((string) (int) $m, 2, '0', STR_PAD_LEFT) . '/'),
                'extra' => (string) (int) $row['n'],
            ];
        }
        return $out;
    }

    private function categories(): array
    {
        try {
            $rows = $this->db->fetchAll(
                $this->db->select('name', 'slug', 'count')->from('table.metas')
                    ->where('type = ?', 'category')->order('count', Db::SORT_DESC)
            );
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'name'  => (string) $row['name'],
                'url'   => Options::url('/category/' . rawurlencode((string) $row['slug']) . '/'),
                'extra' => (string) (int) $row['count'],
            ];
        }
        return $out;
    }

    private function pages(): array
    {
        try {
            $rows = $this->db->fetchAll(
                $this->db->select('cid', 'title', 'slug')->from('table.contents')
                    ->where('type = ?', 'page')->where('status = ?', 'publish')
                    ->order('order', Db::SORT_ASC)
            );
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'name'  => (string) $row['title'],
                'url'   => '/' . ($row['slug'] !== '' ? $row['slug'] : $row['cid']),
                'extra' => '',
            ];
        }
        return $out;
    }
}

<?php
/**
 * 用途：导航与抽屉的数据构建（分类/页面/额外菜单/社交）
 * 依赖：Typecho\Db、Options
 * 被调用：views/chrome/drawer、archive 侧栏
 */
declare(strict_types=1);

namespace qiqi77\Site;

use Typecho\Db;
use qiqi77\Options;

final class Nav
{
    public function __construct(private Db $db)
    {
    }

    /** 主菜单：固定项 + 额外项（JSON 行：标题|链接|图标） */
    public function main(string $currentPath = '/'): array
    {
        $items = [
            ['label' => '首页', 'href' => Options::url('/'), 'icon' => 'home', 'active' => $currentPath === '/'],
            ['label' => '归档', 'href' => Options::url('/archive'), 'icon' => 'inventory_2', 'active' => str_starts_with($currentPath, '/archive')],
            ['label' => '统计', 'href' => Options::url('/stats'), 'icon' => 'insights', 'active' => str_starts_with($currentPath, '/stats')],
            ['label' => '友链', 'href' => Options::url('/links'), 'icon' => 'diversity_3', 'active' => str_starts_with($currentPath, '/links')],
        ];

        foreach ($this->extra() as $one) {
            $items[] = [
                'label'  => $one['label'],
                'href'   => $one['href'],
                'icon'   => $one['icon'] ?: 'link',
                'active' => $currentPath === parse_url($one['href'], PHP_URL_PATH),
            ];
        }
        return $items;
    }

    /** 分类树（平铺即可，抽屉里缩进展示） */
    public function categories(int $limit = 20): array
    {
        try {
            $rows = $this->db->fetchAll(
                $this->db->select('mid', 'name', 'slug', 'count')->from('table.metas')
                    ->where('type = ?', 'category')
                    ->order('order', Db::SORT_ASC)
                    ->limit($limit)
            );
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'name'  => (string) $row['name'],
                'href'  => '/category/' . rawurlencode((string) $row['slug']) . '/',
                'count' => (int) $row['count'],
            ];
        }
        return $out;
    }

    /** 独立页面 */
    public function pages(): array
    {
        try {
            $rows = $this->db->fetchAll(
                $this->db->select('cid', 'title', 'slug')->from('table.contents')
                    ->where('type = ?', 'page')
                    ->where('status = ?', 'publish')
                    ->order('order', Db::SORT_ASC)
            );
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'name' => (string) $row['title'],
                'href' => Options::url('/' . ($row['slug'] !== '' ? $row['slug'] : $row['cid'])),
            ];
        }
        return $out;
    }

    /** 社交图标：每行 `图标名|链接`，图标名对应 iconfont */
    public function contacts(): array
    {
        $raw = trim((string) Options::get('qq_drawer_contacts', ''));
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '|') === false) {
                continue;
            }
            [$icon, $href] = array_map('trim', explode('|', $line, 2));
            if ($href === '') {
                continue;
            }
            $out[] = ['icon' => 'icon-' . ltrim($icon, 'icon-'), 'href' => $href];
        }
        return $out;
    }

    /** 额外菜单解析 */
    private function extra(): array
    {
        $raw = trim((string) Options::get('qq_menu_extra', ''));
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 2 || $parts[1] === '') {
                continue;
            }
            $out[] = [
                'label' => $parts[0],
                'href'  => $parts[1],
                'icon'  => $parts[2] ?? '',
            ];
        }
        return $out;
    }
}

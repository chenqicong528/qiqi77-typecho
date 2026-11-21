<?php
/**
 * 用途：评论树构建与渲染数据（显式栈，不用递归）
 * 依赖：Typecho\Db、Avatar、Ua
 * 被调用：views/partials/comment、post.php
 */
declare(strict_types=1);

namespace qiqi77\Comment;

use Typecho\Db;

final class Thread
{
    public function __construct(private Db $db)
    {
    }

    /**
     * 拉取一篇内容的已通过评论，按时间正序
     * 返回扁平数组，每项自带 depth，渲染时不再递归
     */
    public function build(int $cid, int $ownerId = 0): array
    {
        try {
            $rows = $this->db->fetchAll(
                $this->db->select()->from('table.comments')
                    ->where('cid = ?', $cid)
                    ->where('status = ?', 'approved')
                    ->order('created', Db::SORT_ASC)
                    ->order('coid', Db::SORT_ASC)
            );
        } catch (\Throwable $e) {
            return [];
        }
        if (!$rows) {
            return [];
        }

        $children = [];
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['coid']] = $row;
            $children[(int) $row['parent']][] = (int) $row['coid'];
        }

        // 显式栈深度优先：根节点的 parent=0
        $flat = [];
        $floor = 0;
        $stack = [];
        /* 栈是后进先出：反向压入，弹出时才是时间正序，1 楼 = 最早那条 */
        foreach (array_reverse($children[0] ?? []) as $rootId) {
            $stack[] = [$rootId, 0];
        }
        while ($stack) {
            [$coid, $depth] = array_pop($stack);
            $row = $byId[$coid] ?? null;
            if ($row === null) {
                continue;
            }
            $flat[] = $this->normalize($row, $depth, ++$floor, $ownerId);

            $kids = $children[$coid] ?? [];
            for ($i = count($kids) - 1; $i >= 0; $i--) {
                $stack[] = [$kids[$i], $depth + 1];
            }
        }
        return $flat;
    }

    /** 单行转视图数据：头像、UA、时间、是否博主 */
    private function normalize(array $row, int $depth, int $floor, int $ownerId): array
    {
        $agent = (string) ($row['agent'] ?? '');
        $mail = (string) ($row['mail'] ?? '');
        $author = (string) ($row['author'] ?? '访客');

        return [
            'id'      => (int) $row['coid'],
            'parent'  => (int) $row['parent'],
            'depth'   => min($depth, 4),
            'floor'   => $floor,
            'author'  => $author !== '' ? $author : '访客',
            'url'     => self::safeUrl((string) ($row['url'] ?? '')),
            'avatar'  => Avatar::resolve((string) ($row['avatar'] ?? ''), $mail, $author),
            'when'    => date('Y-m-d H:i', (int) $row['created']),
            'iso'     => date('c', (int) $row['created']),
            'html'    => $this->renderText((string) $row['text']),
            'ua'      => Ua::parse($agent),
            'isOwner' => (int) ($row['authorId'] ?? 0) > 0 && (int) $row['authorId'] === $ownerId,
            'isFriend'=> in_array((string) ($row['agent'] ?? ''), ['zhihu-sync', 'zhihu-dom', 'bilibili-sync'], true),
        ];
    }

    /**
     * 评论正文：先整体转义，再按白名单还原极少数标签
     * - 只放行无属性的 b/strong/em/i/u/s/del/code
     * - a 只接受 http(s)/mailto 的 href，其余（javascript:/data:/onclick…）一律保持转义
     */
    private function renderText(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // 带 href 的链接：必须引号包裹，且协议受限
        $text = preg_replace_callback(
            '~&lt;a\s+href=&quot;((?:https?://|mailto:)(?:[^&]|&amp;)*)&quot;\s*&gt;~i',
            static function (array $m): string {
                $href = str_replace('&amp;', '&', $m[1]);
                if (preg_match('~^\s*(javascript|data|vbscript):~i', $href)) {
                    return $m[0];
                }
                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
                    . '" rel="nofollow noopener ugc" target="_blank">';
            },
            $text
        ) ?? $text;

        // 无属性行内标签
        $text = preg_replace('~&lt;(/?)(b|strong|em|i|u|s|del|code)&gt;~i', '<$1$2>', $text) ?? $text;

        // 链接闭合标签
        $text = preg_replace('~&lt;/\s*a\s*&gt;~i', '</a>', $text) ?? $text;

        return nl2br($text, false);
    }
    /** 统计：总条数 / 参与人数，给评论区标题用 */
    public function stats(array $flat): array
    {
        $authors = [];
        foreach ($flat as $one) {
            $authors[$one['author']] = true;
        }
        return ['total' => count($flat), 'people' => count($authors)];
    }

    /** 评论者站点：只放行 http/https，其余（javascript:/data:）一律当空 */
    private static function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (!preg_match('~^https?://~i', $url)) {
            return '';
        }
        // 过滤控制字符与引号，避免属性闭合
        $url = preg_replace('~[\x00-\x20"\'<>]~', '', $url) ?? '';
        return $url;
    }
}

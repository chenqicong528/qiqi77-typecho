<?php
/**
 * 用途：浏览量、阅读时长、置顶状态
 * 依赖：Typecho\Db
 * 被调用：Content\Card、模板 post/index
 */
declare(strict_types=1);

namespace qiqi77\Content;

use Typecho\Db;

final class Metrics
{
    private const SEEN_COOKIE = 'lingxi_seen';

    public function __construct(private Db $db)
    {
    }

    /** 计数 +1：同一篇文章在一个会话里只算一次，避免刷新刷数 */
    public function touch(int $cid): void
    {
        if ($cid <= 0 || $this->alreadySeen($cid)) {
            return;
        }
        try {
            $this->db->query(
                'UPDATE ' . $this->db->getPrefix() . 'contents SET views = views + 1 WHERE cid = ' . $cid
            );
        } catch (\Throwable $e) {
            return;
        }
        $this->remember($cid);
    }

    /** 读取浏览量；表里没有 views 列时返回 0 而不是报错 */
    public function views(int $cid): int
    {
        try {
            $row = $this->db->fetchRow(
                $this->db->select('views')->from('table.contents')->where('cid = ?', $cid)->limit(1)
            );
            return (int) ($row['views'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** 阅读时长：按字符数折算，中文场景比按词数准 */
    public function minutes(string $html): int
    {
        $chars = mb_strlen(trim(strip_tags($html)), 'UTF-8');
        return max(1, (int) ceil($chars / 400));
    }

    /** 置顶：order 值大于 0 视为置顶，用于列表排序与角标 */
    public function pinned(array $row): bool
    {
        return (int) ($row['order'] ?? 0) > 0;
    }

    private function alreadySeen(int $cid): bool
    {
        $raw = $_COOKIE[self::SEEN_COOKIE] ?? '';
        if ($raw === '') {
            return false;
        }
        return in_array((string) $cid, explode('.', $raw), true);
    }

    /** 记录已浏览：只保留最近 40 条，cookie 不会无限膨胀 */
    private function remember(int $cid): void
    {
        if (headers_sent()) {
            return;
        }
        $list = array_filter(explode('.', $_COOKIE[self::SEEN_COOKIE] ?? ''));
        $list[] = (string) $cid;
        $list = array_slice(array_unique($list), -40);
        setcookie(self::SEEN_COOKIE, implode('.', $list), [
            'expires'  => time() + 86400,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off'),
        ]);
    }
}

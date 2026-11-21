<?php
/**
 * 用途：在线人数与访问趋势读取（写入端在站点根 online.php）
 * 依赖：Typecho\Db
 * 被调用：stats.php、online.php、views/partials/sidebar
 */
declare(strict_types=1);

namespace qiqi77\Site;

use Typecho\Db;

final class Online
{
    /** 90 秒内有心跳即算在线，与写入端保持一致 */
    public const WINDOW = 90;

    public function __construct(private Db $db)
    {
    }

    /** 按角色拆分的在线人数 */
    public function live(): array
    {
        $out = ['owner' => 0, 'friend' => 0, 'guest' => 0, 'total' => 0];
        try {
            $rows = $this->db->fetchAll(
                $this->db->select('role', ['COUNT(sid)' => 'n'])->from('table.online')
                    ->where('last_seen > ?', time() - self::WINDOW)
                    ->group('role')
            );
            foreach ($rows as $row) {
                $role = (string) $row['role'];
                $key = $role === 'blogger' ? 'owner' : ($role === 'friend' ? 'friend' : 'guest');
                $out[$key] = (int) $row['n'];
            }
        } catch (\Throwable $e) {
            return $out;
        }
        $out['total'] = $out['owner'] + $out['friend'] + $out['guest'];
        return $out;
    }

    /** 今日汇总 */
    public function today(): array
    {
        $default = ['uniques' => 0, 'views' => 0, 'peak' => 0, 'peak_at' => 0, 'day' => date('Y-m-d')];
        try {
            $row = $this->db->fetchRow(
                $this->db->select()->from('table.online_daily')->where('day = ?', date('Y-m-d'))->limit(1)
            );
        } catch (\Throwable $e) {
            return $default;
        }
        if (!$row) {
            return $default;
        }
        return [
            'uniques' => (int) $row['uniques'],
            'views'   => (int) $row['views'],
            'peak'    => (int) $row['peak'],
            'peak_at' => (int) $row['peak_at'],
            'day'     => (string) $row['day'],
        ];
    }

    /** 最近 N 天，按日期升序 */
    public function recent(int $days = 14): array
    {
        try {
            $rows = $this->db->fetchAll(
                $this->db->select()->from('table.online_daily')
                    ->order('day', Db::SORT_DESC)->limit($days)
            );
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['day']] = [
                'uniques' => (int) $row['uniques'],
                'views'   => (int) $row['views'],
                'peak'    => (int) $row['peak'],
            ];
        }
        ksort($out);
        return $out;
    }

    /** 历史峰值日 */
    public function peaks(int $limit = 5): array
    {
        try {
            return $this->db->fetchAll(
                $this->db->select()->from('table.online_daily')
                    ->where('peak > 0')
                    ->order('peak', Db::SORT_DESC)
                    ->order('uniques', Db::SORT_DESC)
                    ->limit($limit)
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** 当前在线明细，最多 N 条 */
    public function visitors(int $limit = 12): array
    {
        try {
            return $this->db->fetchAll(
                $this->db->select()->from('table.online')
                    ->where('last_seen > ?', time() - self::WINDOW)
                    ->order('last_seen', Db::SORT_DESC)
                    ->limit($limit)
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** 博主最后一次活动时间（写入端在根 online.php） */
    public function lastOwner(): int
    {
        try {
            $row = $this->db->fetchRow(
                $this->db->select('v')->from('table.online_meta')->where('k = ?', 'last_blogger')
            );
            return $row ? (int) $row['v'] : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** 相对时间：刚刚 / N 分钟前 / N 小时前 / 具体日期 */
    public static function humanize(int $ts): string
    {
        if ($ts <= 0) {
            return '—';
        }
        $d = time() - $ts;
        if ($d < 60) return '刚刚';
        if ($d < 3600) return floor($d / 60) . ' 分钟前';
        if ($d < 86400) return floor($d / 3600) . ' 小时前';
        if ($d < 86400 * 7) return floor($d / 86400) . ' 天前';
        return date('Y-m-d H:i', $ts);
    }

}

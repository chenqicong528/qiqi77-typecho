<?php
/**
 * 用途：字符串处理集合（摘要、时长、相对地址、随机封面）
 * 依赖：Options
 * 被调用：Content\Card、Content\Metrics、Site\Seo
 */
declare(strict_types=1);

namespace qiqi77\Support;

use qiqi77\Options;

final class Str
{
    /** 去标签 + 折叠空白，摘要与 meta description 共用 */
    public static function plain(string $html): string
    {
        $text = strip_tags($html);
        $text = str_replace(["\xc2\xa0", "\u{3000}"], ' ', $text);
        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /** 按字符截断，中文优先；超出补省略号 */
    public static function clip(string $text, int $limit = 90, string $tail = '…'): string
    {
        if ($limit <= 0) {
            return '';
        }
        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $limit, 'UTF-8')) . $tail;
    }

    /** 字数：中英混排统一按字符算，比 str_word_count 稳 */
    public static function chars(string $html): int
    {
        return mb_strlen(self::plain($html), 'UTF-8');
    }

    /** 时间格式：今天/昨天用相对，其余按年补全 */
    public static function when(int $ts): string
    {
        $day = date('Y-m-d', $ts);
        $today = date('Y-m-d');
        if ($day === $today) {
            return '今天 ' . date('H:i', $ts);
        }
        if ($day === date('Y-m-d', strtotime('-1 day'))) {
            return '昨天 ' . date('H:i', $ts);
        }
        return date(date('Y', $ts) === date('Y') ? 'n月j日' : 'Y年n月j日', $ts);
    }

    /** ISO 时间，给 <time datetime> 与 JSON-LD 用 */
    public static function iso(int $ts): string
    {
        return date('c', $ts);
    }

    /** 站内相对地址：同域自动去掉 scheme+host，省一个 DNS 判断 */
    public static function local(string $url): string
    {
        $site = (string) Options::get('siteUrl', '');
        if ($site !== '' && strncmp($url, $site, strlen($site)) === 0) {
            $rest = substr($url, strlen($site));
            return $rest === '' ? '/' : $rest;
        }
        return $url;
    }

    /** 文件名安全化，避免中文/空格进 URL 后各种转义 */
    public static function slug(string $raw): string
    {
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $raw) ?? '';
        return trim(mb_strtolower($slug, 'UTF-8'), '-');
    }

    /**
     * 封面候选：字段 → 正文首图 → 随机图库
     * 走 Options 的 qq_cover_source，支持 local / custom / cdn
     */
    public static function cover(?string $field, string $content = '', int $seed = 0): string
    {
        $field = trim((string) $field);
        if ($field !== '') {
            return $field;
        }
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $m)) {
            return $m[1];
        }
        return self::randomCover($seed);   // 可能是空串：卡片走渐变占位
    }

    /** 随机封面：三种来源策略，未配置时回落到主题内置图 */
    /**
     * 随机封面：按后台配置取图
     * local  → assets/images/random 里随机一张
     * cdn    → 按模板替换 {rand}，一行一个地址
     * none   → 返回空串，卡片用渐变占位
     */
    public static function randomCover(int $seed = 0): string
    {
        $mode = (string) Options::get('qq_cover_mode', 'none');

        if ($mode === 'local') {
            $dir = __DIR__ . '/../../assets/images/random';
            $pool = [];
            foreach (glob($dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [] as $file) {
                $pool[] = Options::asset('images/random/' . basename($file));
            }
            if ($pool) {
                return $pool[$seed % count($pool)];
            }
            return '';
        }

        if ($mode === 'cdn') {
            $raw = trim((string) Options::get('qq_cover_cdn', ''));
            $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: [])));
            if (!$lines) {
                return '';
            }
            $line = $lines[$seed % count($lines)];
            return str_replace('{rand}', (string) random_int(1, 9999), $line);
        }

        return '';
    }}

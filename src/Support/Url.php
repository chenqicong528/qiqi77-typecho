<?php

namespace qiqi77\Support;

use qiqi77\Options;

/**
 * 多域名支持
 * 主域仍以后台 siteUrl 为准，其余域名在输出阶段替换成当前访问域。
 * canonical / og:url / 结构化数据保持主域，避免多域重复收录。
 */
final class Url
{
    /** 后台配置的主域 */
    public static function primary(): string
    {
        try {
            return rtrim((string) \Helper::options()->siteUrl, '/');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** 允许的域名：主域 + 备用域（后台配置） */
    public static function hosts(): array
    {
        $list = [];

        $host = parse_url(self::primary(), PHP_URL_HOST);
        if ($host) {
            $list[] = strtolower($host);
        }

        /* 不传 fallback：让 Options::get 走 SCHEMA 里的默认值 */
        $extra = '';
        if (trim($extra) === '') {
            $extra = '';
        }
        foreach (preg_split('/[\s,]+/', $extra) ?: [] as $item) {
            $item = strtolower(trim((string) $item));
            if ($item !== '' && preg_match('/^[a-z0-9.-]+$/', $item)) {
                $list[] = $item;
            }
        }

        return array_values(array_unique($list));
    }

    /** 当前请求的 origin；不在白名单就退回主域（防 Host 注入） */
    public static function current(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $primary = self::primary();
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $host = (string) preg_replace('/:\d+$/', '', $host);

        if ($host === '' || !in_array($host, self::hosts(), true)) {
            return $cached = $primary;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            ? 'https'
            : 'http';

        return $cached = $scheme . '://' . $host;
    }

    public static function isPrimary(): bool
    {
        return self::current() === self::primary();
    }

    /** 输出改写：把主域替换成当前域，再修正 SEO 字段 */
    public static function rewrite(string $html): string
    {
        try {
            $primary = self::primary();
            $current = self::current();

            if ($primary === '' || $current === '' || $primary === $current || $html === '') {
                return $html;
            }

            $html = str_replace($primary, $current, $html);

            // canonical / og:url 还原成主域
            $html = (string) preg_replace_callback(
                '~(<link rel="canonical" href=")([^"]*)(")~',
                static function ($m) use ($current, $primary) {
                    return $m[1] . str_replace($current, $primary, $m[2]) . $m[3];
                },
                $html
            );
            $html = (string) preg_replace_callback(
                '~(<meta property="og:url" content=")([^"]*)(")~',
                static function ($m) use ($current, $primary) {
                    return $m[1] . str_replace($current, $primary, $m[2]) . $m[3];
                },
                $html
            );

            // 结构化数据里的 url / @id 也保持主域
            $html = (string) preg_replace_callback(
                '~(<script type="application/ld\+json">)(.*?)(</script>)~s',
                static function ($m) use ($current, $primary) {
                    return $m[1] . str_replace($current, $primary, $m[2]) . $m[3];
                },
                $html
            );

            return $html;
        } catch (\Throwable $e) {
            return $html;
        }
    }
}

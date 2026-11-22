<?php

namespace qiqi77\Support;

/**
 * 多域名支持
 * 主域以后台 siteUrl 为准；其它域名访问时，只把「协议+主机+端口」换成当前访问域，
 * 路径部分原样保留。canonical / og:url / 结构化数据始终用主域。
 */
final class Url
{
    /** 后台配置的站点地址（含子目录） */
    public static function primary(): string
    {
        try {
            return rtrim((string) \Helper::options()->siteUrl, '/');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** 主域 origin（协议 + 主机 + 端口），不含路径 */
    public static function primaryOrigin(): string
    {
        $url = self::primary();
        if ($url === '') {
            return '';
        }
        $scheme = (string) (parse_url($url, PHP_URL_SCHEME) ?: 'http');
        $host   = (string) parse_url($url, PHP_URL_HOST);
        $port   = parse_url($url, PHP_URL_PORT);
        if ($host === '') {
            return '';
        }
        return $scheme . '://' . $host . ($port ? ':' . $port : '');
    }

    /** 当前请求的 origin；Host 不在白名单就退回主域 */
    public static function current(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $primaryOrigin = self::primaryOrigin();
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

        if ($host === '' || $primaryOrigin === '') {
            return $cached = $primaryOrigin;
        }

        /* 只允许主域本身，防止 Host 注入 */
        $primaryHost = strtolower((string) parse_url($primaryOrigin, PHP_URL_HOST));
        $reqHost = strtolower((string) preg_replace('/:\d+$/', '', $host));
        if ($reqHost !== $primaryHost) {
            return $cached = $primaryOrigin;
        }

        $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);

        return $cached = ($https ? 'https://' : 'http://') . $host;
    }

    public static function isPrimary(): bool
    {
        return self::current() === self::primaryOrigin();
    }

    /** 输出改写：只换 origin，路径与子目录保持不动 */
    public static function rewrite(string $html): string
    {
        try {
            $primary = self::primaryOrigin();
            $current = self::current();

            if ($primary === '' || $current === '' || $primary === $current || $html === '') {
                return $html;
            }

            $html = str_replace($primary, $current, $html);

            // canonical / og:url / 结构化数据还原成主域
            $restore = static function (string $pattern, string $html) use ($current, $primary): string {
                return (string) preg_replace_callback(
                    $pattern,
                    static fn (array $m): string => $m[1] . str_replace($current, $primary, $m[2]) . $m[3],
                    $html
                );
            };

            $html = $restore('~(<link rel="canonical" href=")([^"]*)(")~', $html);
            $html = $restore('~(<meta property="og:url" content=")([^"]*)(")~', $html);
            $html = $restore('~(<script type="application/ld\+json">)(.*?)(</script>)~s', $html);

            return $html;
        } catch (\Throwable $e) {
            return $html;
        }
    }
}

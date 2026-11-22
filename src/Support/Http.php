<?php
/**
 * 用途：最小可用的远端请求，带文件缓存（后台版本检查用）
 * 依赖：无
 * 被调用：Site\Seo、views\admin\settings
 */
declare(strict_types=1);

namespace qiqi77\Support;

final class Http
{
    private static string $cacheDir = '';

    /** 带缓存 GET：命中缓存直接返回，过期后再发请求 */
    public static function get(string $url, int $ttl = 3600, int $timeout = 4): ?string
    {
        $cache = self::cachePath($url);
        if ($cache !== '' && is_file($cache) && (time() - filemtime($cache)) < $ttl) {
            $cached = (string) @file_get_contents($cache);
            if ($cached !== '') {
                return $cached;
            }
        }

        $body = self::request($url, $timeout);
        if ($body !== null && $cache !== '') {
            @file_put_contents($cache, $body, LOCK_EX);
        }
        return $body;
    }

    /** 真正发请求；失败静默返回 null，调用方决定怎么兜底 */
    public static function request(string $url, int $timeout = 4): ?string
    {
        if (!preg_match('#^https?://#i', $url)) {
            return null;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT      => 'qiqi77/1.0 (+theme)',
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ($code >= 200 && $code < 300 && is_string($body)) ? $body : null;
        }

        $ctx = stream_context_create(['http' => ['timeout' => $timeout, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $ctx);
        return is_string($body) && $body !== '' ? $body : null;
    }

    /** 缓存文件路径：系统临时目录，站点目录保持干净 */
    private static function cachePath(string $url): string
    {
        if (self::$cacheDir === '') {
            self::$cacheDir = rtrim(sys_get_temp_dir(), '/\\') . '/qiqi77-cache';
            if (!is_dir(self::$cacheDir)) {
                @mkdir(self::$cacheDir, 0700, true);
            }
        }
        return is_dir(self::$cacheDir) ? self::$cacheDir . '/' . md5($url) . '.txt' : '';
    }
}

<?php
/**
 * 用途：命名空间自动加载，替代 Composer
 * 依赖：无
 * 被调用：functions.php
 */
declare(strict_types=1);

namespace qiqi77;

final class Autoload
{
    private const PREFIX = 'qiqi77\\';
    private static bool $ready = false;

    public static function register(): void
    {
        if (self::$ready) {
            return;
        }
        self::$ready = true;

        spl_autoload_register(static function (string $class): void {
            if (strncmp($class, self::PREFIX, strlen(self::PREFIX)) !== 0) {
                return;
            }
            $relative = substr($class, strlen(self::PREFIX));
            $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        });
    }
}

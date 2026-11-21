<?php
/**
 * 用途：视图渲染入口，视图里只允许拿到数组
 * 依赖：无
 * 被调用：模板、partials
 */
declare(strict_types=1);

namespace qiqi77;

final class View
{
    /** 渲染 views/ 下的模板，$data 展开成局部变量 */
    public static function render(string $tpl, array $data = []): string
    {
        $file = __DIR__ . '/../views/' . ltrim($tpl, '/') . '.php';
        if (!is_file($file)) {
            return '';
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } finally {
            $html = (string) ob_get_clean();
        }
        return $html;
    }

    /** 直接输出，模板里用得最多 */
    public static function show(string $tpl, array $data = []): void
    {
        echo self::render($tpl, $data);
    }
}

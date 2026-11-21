<?php
/**
 * 用途：HTML 输出的唯一通道，所有转义都从这里过
 * 依赖：无
 * 被调用：views/*、Content\Card、Comment\Thread
 */
declare(strict_types=1);

namespace qiqi77\Support;

final class Html
{
    /** 文本转义：属性与正文共用，避免两套规则漂移 */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * 属性串拼装：['class' => 'a b', 'href' => '/x'] → ' class="a b" href="/x"'
     * null / false / '' 直接跳过，模板里就不用写一堆三元判断
     */
    public static function attrs(array $map): string
    {
        $out = '';
        foreach ($map as $key => $val) {
            if ($val === null || $val === false || $val === '') {
                continue;
            }
            if ($val === true) {
                $out .= ' ' . $key;
                continue;
            }
            $out .= ' ' . $key . '="' . self::e($val) . '"';
        }
        return $out;
    }

    /** 条件类名：自带去重与空值过滤 */
    public static function cls(string ...$tokens): string
    {
        $keep = [];
        foreach ($tokens as $token) {
            foreach (preg_split('/\s+/', trim($token)) ?: [] as $one) {
                if ($one !== '') {
                    $keep[$one] = true;
                }
            }
        }
        return implode(' ', array_keys($keep));
    }

    /** 内联样式：数值型自动补单位，避免手拼 px */
    public static function style(array $rules): string
    {
        $out = [];
        foreach ($rules as $prop => $val) {
            if ($val === null || $val === '') {
                continue;
            }
            $out[] = $prop . ':' . $val;
        }
        return $out ? ' style="' . self::e(implode(';', $out)) . '"' : '';
    }

    /** 安全 URL：只放行 http(s) / 站内相对 / mailto，挡掉 javascript: */
    public static function url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('/^\s*(javascript|data|vbscript):/i', $url)) {
            return '';
        }
        if ($url[0] === '/' || $url[0] === '#' || $url[0] === '?') {
            return $url;
        }
        if (preg_match('/^(https?:|mailto:|tel:)/i', $url)) {
            return $url;
        }
        return '//' . ltrim($url, '/');
    }

    /** 输出缓冲包一层，视图里就能直接写 HTML */
    public static function capture(callable $fn): string
    {
        ob_start();
        try {
            $fn();
        } finally {
            $html = (string) ob_get_clean();
        }
        return $html;
    }
}

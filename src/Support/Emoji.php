<?php
/**
 * 用途：表情映射（短代码 ↔ 原生 emoji）与内容里的表情替换
 * 依赖：无
 * 被调用：Content\Parser
 */
declare(strict_types=1);

namespace qiqi77\Support;

final class Emoji
{
    /** 短代码 → 原生 emoji：只保留符号，不再加载任何图片表情包 */
    private const MAP = [
        '微笑' => '🙂', '开心' => '😄', '大笑' => '😆', '偷笑' => '🤭',
        '流泪' => '😭', '大哭' => '😭', '委屈' => '🥺', '生气' => '😠',
        '酷' => '😎', '得意' => '😏', '困' => '😴', '疑问' => '❓',
        '支持' => '💪', '打call' => '📣', '妙啊' => '👍', '赞' => '👍',
        '感谢' => '🙏', '吃瓜' => '🍉', '鼓掌' => '👏', '加油' => '🔥',
        '心' => '❤️', '星星' => '✨', '烟花' => '🎉', '咖啡' => '☕',
    ];

    /** 面板里展示的表情，顺序即 UI 顺序 */
    public static function palette(): array
    {
        return array_values(array_unique(self::MAP));
    }

    /** 把 [微笑] 这类短代码换成 emoji；已转义的反斜杠一并兼容 */
    public static function translate(string $text): string
    {
        foreach (self::MAP as $code => $emoji) {
            $text = str_replace(['\\[' . $code . '\\]', '[' . $code . ']'], $emoji, $text);
        }
        return $text;
    }

    /** 统计正文里用了几个表情，用于卡片角标 */
    public static function count(string $text): int
    {
        $n = 0;
        foreach (self::palette() as $emoji) {
            $n += substr_count($text, $emoji);
        }
        return $n;
    }
}

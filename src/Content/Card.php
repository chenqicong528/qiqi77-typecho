<?php
/**
 * 用途：列表卡片的视图模型（三种形态：封面卡 / 日常 / 纯文字）
 * 依赖：Metrics、Str、Options
 * 被调用：index.php、archive.php、views/partials/card
 */
declare(strict_types=1);

namespace qiqi77\Content;

use qiqi77\Options;
use qiqi77\Support\Str;

final class Card
{
    /**
     * $post 需要的键：cid / title / text / created / comments / order / url / cover / type / remark
     * type 取值：article（默认，封面卡）、daily（时间线短文）、normal（纯文字卡）
     */
    public static function make(array $post, Metrics $metrics): array
    {
        $cid = (int) ($post['cid'] ?? 0);
        $text = (string) ($post['text'] ?? '');
        $type = (string) ($post['type'] ?? 'article');
        if ($type === '') {
            $type = 'article';
        }

        $plain = Str::plain($text);
        $cover = Str::cover((string) ($post['cover'] ?? ''), $text, $cid);

        return [
            'id'       => $cid,
            'type'     => $type,
            'url'      => (string) ($post['url'] ?? '#'),
            'title'    => (string) ($post['title'] ?? '无标题'),
            'cover'    => $cover,
            'excerpt'  => Str::clip($plain, 78),
            'remark'   => trim((string) ($post['remark'] ?? '')),
            'when'     => Str::when((int) ($post['created'] ?? time())),
            'iso'      => Str::iso((int) ($post['created'] ?? time())),
            'comments' => (int) ($post['comments'] ?? 0),
            'views'    => $metrics->views($cid),
            'minutes'  => $metrics->minutes($text),
            'pinned'   => (int) ($post['order'] ?? 0) > 0,
            'hasCover' => $cover !== '' && $type === 'article',
            'showMeta' => Options::flag('qq_show_comments', true),
        ];
    }

    /** 日常形态要输出正文，加工后塞回数组 */
    public static function withBody(array $card, string $html): array
    {
        $card['body'] = $html;
        return $card;
    }
}

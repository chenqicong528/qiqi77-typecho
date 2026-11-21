<?php
/**
 * 用途：评论头像解析链（数据里存的 → QQ → Gravatar → 默认）
 * 依赖：Options、Support\Html
 * 被调用：Comment\Thread、views/partials/comment
 */
declare(strict_types=1);

namespace qiqi77\Comment;

use qiqi77\Options;

final class Avatar
{
    /** 解析头像地址；$stored 是评论表里的自定义头像列 */
    public static function resolve(string $stored, string $mail, string $author = ''): string
    {
        $stored = trim($stored);
        if ($stored !== '' && self::looksLikeImage($stored)) {
            return $stored;
        }

        $qq = self::qqFromMail($mail);
        $preferQQ = Options::get('qq_avatar_source', 'qq') !== 'gravatar';

        if ($qq !== '' && $preferQQ) {
            return 'https://q1.qlogo.cn/g?b=qq&nk=' . $qq . '&s=160';
        }

        $hash = md5(strtolower(trim($mail)));
        $cdn = trim((string) Options::get('qq_avatar_cdn', 'https://dn-qiniu-avatar.qbox.me/avatar/'));
        $url = $cdn !== '' ? rtrim($cdn, '/') . '/' . $hash : 'https://secure.gravatar.com/avatar/' . $hash;

        if ($qq !== '') {
            return 'https://q1.qlogo.cn/g?b=qq&nk=' . $qq . '&s=160';
        }

        return $url . '?s=100&d=mp&r=g';
    }

    /** 从邮箱里抠 QQ 号：只认数字 @qq.com，别把普通邮箱当 QQ */
    public static function qqFromMail(string $mail): string
    {
        if (preg_match('/^([1-9]\d{4,9})@qq\.com$/i', trim($mail), $m)) {
            return $m[1];
        }
        return '';
    }

    /** 头像地址的基本校验，挡掉 javascript: 与空白 */
    private static function looksLikeImage(string $url): bool
    {
        return (bool) preg_match('#^(https?:)?//|^/#i', $url);
    }
}

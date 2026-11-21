<?php
/**
 * 用途：SEO 元信息与结构化数据（OG / Twitter / canonical / JSON-LD）
 * 依赖：Options、Support\Str、Support\Html
 * 被调用：views/chrome/head
 */
declare(strict_types=1);

namespace qiqi77\Site;

use qiqi77\Options;
use qiqi77\Support\Str;

final class Seo
{
    /**
     * 构造 meta 列表：模板只负责输出，不再拼字符串
     * $ctx 允许键：type(page|post|home|archive)、title、desc、url、image、published、updated、author
     */
    public static function meta(array $ctx): array
    {
        $site = rtrim((string) \Helper::options()->siteUrl, '/');
        $title = trim((string) ($ctx['title'] ?? ''));
        $desc = Str::clip(trim((string) ($ctx['desc'] ?? '')), 150);
        $desc = $desc !== '' ? $desc : trim((string) Options::get('qq_description', ''));
        $url = (string) ($ctx['url'] ?? $site . '/');
        $image = (string) ($ctx['image'] ?? '');
        $type = (string) ($ctx['type'] ?? 'website');

        $tags = [
            ['name' => 'description', 'content' => $desc],
            ['name' => 'robots', 'content' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'],
            ['property' => 'og:site_name', 'content' => (string) \Helper::options()->title],
            ['property' => 'og:type', 'content' => $type === 'post' ? 'article' : 'website'],
            ['property' => 'og:title', 'content' => $title],
            ['property' => 'og:description', 'content' => $desc],
            ['property' => 'og:url', 'content' => $url],
            ['name' => 'twitter:card', 'content' => $image !== '' ? 'summary_large_image' : 'summary'],
            ['name' => 'twitter:title', 'content' => $title],
            ['name' => 'twitter:description', 'content' => $desc],
        ];

        if ($image !== '') {
            $tags[] = ['property' => 'og:image', 'content' => $image];
            $tags[] = ['name' => 'twitter:image', 'content' => $image];
        }
        if (!empty($ctx['published'])) {
            $tags[] = ['property' => 'article:published_time', 'content' => $ctx['published']];
        }
        if (!empty($ctx['updated'])) {
            $tags[] = ['property' => 'article:modified_time', 'content' => $ctx['updated']];
        }

        return $tags;
    }

    /** 站点级 JSON-LD：WebSite + Person */
    public static function siteLd(): array
    {
        $site = rtrim((string) \Helper::options()->siteUrl, '/');
        $name = (string) \Helper::options()->title;

        return [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type'      => 'WebSite',
                    '@id'        => $site . '/#site',
                    'url'        => $site . '/',
                    'name'       => $name,
                    'description'=> (string) Options::get('qq_description', ''),
                    'inLanguage' => 'zh-CN',
                    'potentialAction' => [
                        '@type'       => 'SearchAction',
                        'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => $site . '/search/{query}'],
                        'query-input' => 'required name=query',
                    ],
                ],
                [
                    '@type' => 'Person',
                    '@id'   => $site . '/#author',
                    'name'  => $name,
                    'url'   => $site . '/',
                    'image' => \qiqi77\Options::ownerAvatar(),
                ],
            ],
        ];
    }

    /** 文章级 JSON-LD */
    public static function postLd(array $ctx): array
    {
        $site = rtrim((string) \Helper::options()->siteUrl, '/');
        return [
            '@context'         => 'https://schema.org',
            '@type'            => 'BlogPosting',
            'headline'         => (string) ($ctx['title'] ?? ''),
            'description'      => Str::clip((string) ($ctx['desc'] ?? ''), 160),
            'image'            => (string) ($ctx['image'] ?? ''),
            'datePublished'    => (string) ($ctx['published'] ?? ''),
            'dateModified'     => (string) ($ctx['updated'] ?? $ctx['published'] ?? ''),
            'inLanguage'       => 'zh-CN',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => (string) ($ctx['url'] ?? '')],
            'author'           => [
                '@type' => 'Person',
                'name'  => (string) \Helper::options()->title,
                'url'   => $site . '/',
            ],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => (string) \Helper::options()->title,
                'logo'  => ['@type' => 'ImageObject', 'url' => \qiqi77\Options::ownerAvatar()],
            ],
        ];
    }
}

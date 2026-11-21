<?php
/**
 * 用途：正文后处理（懒加载、外链、图片域名、标题锚点、代码块、公式开关）
 * 依赖：Options、Support\Emoji
 * 被调用：post.php、page.php、Content\Card
 */
declare(strict_types=1);

namespace qiqi77\Content;

use qiqi77\Options;
use qiqi77\Support\Emoji;

final class Parser
{
    /** 主入口：一次性把正文加工成可直接输出的 HTML */
    public function run(string $html): string
    {
        $html = Emoji::translate($html);
        $html = $this->anchorHeadings($html);
        $html = $this->decorateImages($html);
        $html = $this->decorateLinks($html);
        $html = $this->wrapCode($html);
        return $html;
    }

    /** 给 h2/h3 补 id，供目录跳转；重复标题自动加序号 */
    private function anchorHeadings(string $html): string
    {
        $used = [];
        return preg_replace_callback(
            '/<h([23])([^>]*)>(.*?)<\/h\1>/is',
            function (array $m) use (&$used): string {
                $text = trim(strip_tags($m[3]));
                $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text) ?: 'section';
                $slug = trim(mb_strtolower($slug, 'UTF-8'), '-');
                if ($slug === '') {
                    $slug = 'section';
                }
                if (isset($used[$slug])) {
                    $slug .= '-' . (++$used[$slug]);
                } else {
                    $used[$slug] = 0;
                }
                $level = $m[1];
                $attrs = $m[2];
                if (strpos($attrs, 'id=') === false) {
                    $attrs .= ' id="' . $slug . '"';
                }
                return '<h' . $level . $attrs . '>' . $m[3] . '</h' . $level . '>';
            },
            $html
        ) ?? $html;
    }

    /** 图片：懒加载 + 异步解码 + 可选域名替换；首图不懒加载避免 LCP 变差 */
    private function decorateImages(string $html): string
    {
        $rewrite = Options::flag('qq_image_host_switch') ? trim((string) Options::get('qq_image_host_target', '')) : '';
        static $seen = 0;
        $seen = 0;

        return preg_replace_callback('/<img([^>]*)>/i', function (array $m) use ($rewrite, &$seen): string {
            $attrs = $m[1];
            $seen++;
            if ($rewrite !== '' && preg_match('/src=["\']([^"\']+)["\']/i', $attrs, $src)) {
                $attrs = str_replace($src[0], 'src="' . str_replace(
                    rtrim((string) \Helper::options()->siteUrl, '/'),
                    rtrim($rewrite, '/'),
                    $src[1]
                ) . '"', $attrs);
            }
            if ($seen > 1 && stripos($attrs, 'loading=') === false) {
                $attrs .= ' loading="lazy"';
            }
            if (stripos($attrs, 'decoding=') === false) {
                $attrs .= ' decoding="async"';
            }
            if (stripos($attrs, 'alt=') === false) {
                $attrs .= ' alt=""';
            }
            return '<img' . $attrs . '>';
        }, $html) ?? $html;
    }

    /** 外链补 target/rel，站内链接保持原样以便 PJAX 接管 */
    private function decorateLinks(string $html): string
    {
        $host = parse_url((string) \Helper::options()->siteUrl, PHP_URL_HOST) ?: '';

        return preg_replace_callback('/<a([^>]*)>/i', function (array $m) use ($host): string {
            $attrs = $m[1];
            if (!preg_match('/href=["\']([^"\']+)["\']/i', $attrs, $href)) {
                return $m[0];
            }
            $url = $href[1];
            $isLocal = $url === '' || $url[0] === '/' || $url[0] === '#' ||
                ($host !== '' && strpos($url, $host) !== false);
            if ($isLocal) {
                return $m[0];
            }
            if (stripos($attrs, 'target=') === false) {
                $attrs .= ' target="_blank"';
            }
            if (stripos($attrs, 'rel=') === false) {
                $attrs .= ' rel="noopener nofollow"';
            }
            return '<a' . $attrs . '>';
        }, $html) ?? $html;
    }

    /** 代码块：补语言标签容器与复制按钮挂点，复制逻辑在 app.js */
    private function wrapCode(string $html): string
    {
        return preg_replace_callback(
            '/<pre(\s[^>]*)?>\s*<code([^>]*)>/i',
            function (array $m): string {
                $attrs = $m[2];
                $lang = '';
                if (preg_match('/class=["\'][^"\']*language-([\w+-]+)/i', $attrs, $lm)) {
                    $lang = strtolower($lm[1]);
                }
                $label = $lang !== '' ? '<span class="qq-code__lang">' . htmlspecialchars($lang, ENT_QUOTES) . '</span>' : '';
                return '<pre' . ($m[1] ?? '') . ' data-qq-code="1">'
                    . $label
                    . (\qiqi77\Options::flag('qq_code_copy', true)
                        ? '<button type="button" class="qq-code__copy" data-qq-copy aria-label="复制代码">复制</button>'
                        : '')
                    . '<code' . $attrs . '>';
            },
            $html
        ) ?? $html;
    }

    /** 是否需要加载公式/流程图运行时（只在用到时才拉，省 300KB+） */
    public function needsMath(string $html): bool
    {
        return (bool) preg_match('/\$\$.+?\$\$|\\\\\(|\\\\\[/s', $html);
    }

    public function needsMermaid(string $html): bool
    {
        return (bool) preg_match('/```mermaid|<pre[^>]*class=["\'][^"\']*mermaid/i', $html);
    }
}

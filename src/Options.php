<?php
/**
 * 用途：主题配置的读写、默认值、旧键迁移、静态资源地址
 * 依赖：Typecho\Db
 * 被调用：Bootstrap、views/*、Support\Str、Site\*
 */
declare(strict_types=1);

namespace qiqi77;

use Typecho\Db;

final class Options
{
    /** 新键 → 旧键（QiQi 时代的序列化数组键名），只用于读取兼容 */
    private const LEGACY = [
        'qq_description'       => 'describe',
        'qq_drawer_contacts'   => 'drawerContact',
        'qq_primary_hue'       => 'primaryColor',
        'qq_accent_hue'        => 'accentColor',
        'qq_favicon_url'       => 'favicon',
        'qq_logo_url'          => 'logoUrl',
        'qq_sidebar_cover'     => 'sidebarBg',
        'qq_backdrop_url'      => 'bgUrl',
        'qq_backdrop_mobile'   => 'bgphoneUrl',
        'qq_backdrop_filter'   => 'bgFilter',
        'qq_loader_image'      => 'loadingUrl',
        'qq_editor_bg'         => 'textareaBG',
        'qq_show_comments'     => 'showComments',
        'qq_qq_autofill'       => 'qqAutoFill',
        'qq_asset_origin'      => 'assetMode',
        'qq_cover_source'      => 'randimg',
        'qq_cover_source_cdn'  => 'randimgCdn',
        'qq_image_host_switch' => 'isImageRewrite',
        'qq_image_host_target' => 'imageRewriteUrl',
        'qq_avatar_source'     => 'gravatar',
        'qq_avatar_cdn'        => 'gravatarCdn',
        'qq_pinned_notice'     => 'sticky',
        'qq_analytics_code'    => 'statisticsBaidu',
        'qq_qr_enable'         => 'qrcode',
        'qq_tag_cloud'         => 'tagCloud',
        'qq_dark_schedule'     => 'brightTime',
        'qq_footer_note'       => 'Footer',
        'qq_font_weight'       => 'globalFontWeight',
        'qq_menu_extra'        => 'otherMenu',
        'qq_drawer_footer'     => 'drawerBottom',
        'qq_drawer_credit'     => 'drawerFooter',
        'qq_inject_head'       => 'otherHeader',
        'qq_inject_body'       => 'otherFooter',
        'qq_inject_css'        => 'otherCss',
        'qq_inject_js'         => 'otherJs',
        'qq_pjax_scope'        => 'otherPjax',
        'qq_font_url'          => 'fontUrl',
        'qq_font_family'       => 'globalFont',
        'qq_brand_font'        => 'logoFont',
        'qq_links_page_size'   => 'linksIndexNum',
        'qq_links_shuffle'     => 'linksshuffle',
    ];

    /** 表单 schema：key => [类型, 标题, 默认值, 说明, 选项] */
    /** 设置分组：key => [图标, 标题, 说明] */
    public const GROUPS = [
        'basic'   => ['◈', '基础信息', '站点名称、图标、建站日期、备案与版权'],
        'look'    => ['◐', '外观与配色', '主题色、圆角、字体、背景壁纸、暗色时间'],
        'home'    => ['▤', '首页与卡片', '卡片高度、封面来源、置顶公告、顶栏二维码'],
        'post'    => ['▦', '文章页', '阅读进度、目录、打赏码、代码复制'],
        'comment' => ['◍', '评论', 'QQ 号快捷填充、头像来源与加速'],
        'drawer'  => ['☰', '抽屉与菜单', '社交链接、自定义菜单、侧栏封面'],
        'seo'     => ['◎', '收录与统计', '各搜索引擎验证码、Clarity 统计'],
        'adv'     => ['⚙', '性能与高级', 'CDN 开关、图片加速、PJAX、列表分页'],
        'fine'    => ['✦', '细节微调', '配色、字号、行高、宽度、圆角之外的尺寸'],
        'copy'    => ['✎', '文案微调', '搜索提示、404 文案、页面水印'],
    ];

    /** 字段定义：类型 / 标签 / 默认值 / 说明 / 选项 / 分组 / 控件提示 */
    private const SCHEMA = [
        /* ---------- 基础 ---------- */
        'qq_description'       => ['textarea', '站点简介', '', '用于 SEO description 与侧栏', [], 'basic'],
        'qq_logo_url'          => ['text', '头像地址', '', '留空用主题自带 logo', [], 'basic'],
        'qq_favicon_url'       => ['text', '站点图标', '', '支持绝对地址', [], 'basic'],
        'qq_footer_note'       => ['text', '页脚说明', '', '显示在页脚顶部', [], 'basic'],
        'qq_uptime_since'      => ['text', '建站日期', '', '侧栏「本站已硬撑」的起算日，留空不显示', [], 'basic'],
        'qq_icp_number'        => ['text', 'ICP 备案号', '', '页脚展示并链接到工信部', [], 'basic'],
        'qq_police_number'     => ['text', '公安备案号', '', '例如 粤公网安备 440xxxxxxx 号', [], 'basic'],
        'qq_moe_number'        => ['text', '萌 ICP 备案号', '', '留空不显示', [], 'basic'],
        'qq_contact_email'     => ['text', '侵权联系邮箱', '', '页脚显示「侵权删除请联系」，留空不显示', [], 'basic'],
        'qq_wechat_qr'         => ['text', '微信搜一搜二维码', 'https://xgjalbum.oss-cn-hangzhou.aliyuncs.com/_oss/6af87ebc7aa179082d257c62/album_app_image/3bddc5a5_1795169501049.jpg', '图片地址，留空则不显示', [], 'basic'],

        /* ---------- 外观 ---------- */
        'qq_accent_color'      => ['text', '主题色', '#ff4081', '任意 CSS 颜色，例如 #ff4081', [], 'look', 'color'],
        'qq_radius'            => ['text', '圆角', '14', '单位 px，卡片与面板共用', [], 'look', 'range:0:28'],
        'qq_card_height'       => ['text', '卡片高度', '260', '单位 px，手机端自动减 50', [], 'look', 'range:160:400'],
        'qq_card_veil'         => ['text', '卡片遮罩强度', '84', '0-100，封面越花越需要压暗', [], 'look', 'range:0:100'],
        'qq_backdrop_dim'      => ['text', '背景压暗', '42', '0-100，配合背景图使用', [], 'look', 'range:0:100'],
        'qq_backdrop_show'     => ['text', '背景图可见度', '30', '0 几乎看不到壁纸，100 完全露出；默认 30', [], 'look', 'range:0:100'],
        'qq_backdrop_blur'     => ['text', '背景模糊', '0', '单位 px，0 清晰，24 磨砂', [], 'look', 'range:0:24'],
        'qq_card_cols'        => ['select', '首页每行卡片数', '2', '仅桌面端生效', ['2' => '2 列', '3' => '3 列', '4' => '4 列'], 'home'],
        'qq_topbar_alpha'      => ['text', '顶栏不透明度', '82', '0-100', [], 'look', 'range:0:100'],
        'qq_surface_alpha'     => ['text', '前景不透明度', '100', '卡片/面板的透明程度，调低能透出背景壁纸', [], 'look', 'range:40:100'],
        'qq_backdrop_url'      => ['text', '桌面背景图', '', '留空则用纯色渐变', [], 'look'],
        'qq_backdrop_mobile'   => ['text', '手机背景图', '', '', [], 'look'],
        'qq_dark_schedule'     => ['text', '深色时段', '19-7', '跟随系统时，在这个区间自动切深色', [], 'look'],
        'qq_font_family'       => ['text', '正文字体', '', '留空用系统字体', [], 'look'],
        'qq_font_url'          => ['text', '字体文件', '', 'woff2 地址，可选', [], 'look'],

        /* ---------- 首页 ---------- */
        'qq_pinned_notice'     => ['textarea', '置顶文章', '', '填文章 cid，逗号分隔，置顶在列表最前', [], 'home'],
        'qq_cover_mode'        => ['select', '封面来源', 'none', '无自定义封面时怎么补图', ['none' => '不补图（渐变卡片）', 'local' => '本地图库', 'cdn' => '随机图 CDN'], 'home'],
        'qq_cover_cdn'         => ['textarea', '随机图地址', '', '每行一条，{rand} 会换成随机数', [], 'home'],
        'qq_show_comments'     => ['select', '卡片显示评论数', 'on', '', ['on' => '显示', 'off' => '隐藏'], 'home'],
        'qq_qr_enable'         => ['select', '顶栏二维码', 'on', '', ['on' => '开', 'off' => '关'], 'home'],

        /* ---------- 文章 ---------- */
        'qq_reading_pct'       => ['select', '阅读进度百分比', 'on', '右上角显示正文阅读进度', ['on' => '开', 'off' => '关'], 'post'],
        'qq_toc_fallback'      => ['select', '目录策略', 'auto', '文章未单独设置时使用', ['auto' => '标题≥3 才显示', 'true' => '总是显示', 'off' => '关闭'], 'post'],
        'qq_reward_wechat'     => ['text', '微信赞赏码', 'https://xgjalbum.oss-cn-hangzhou.aliyuncs.com/_oss/6af87ebc7aa179082d257c62/album_app_image/8ac82a61_1795171148426.webp', '图片地址，留空不显示打赏', [], 'post'],
        'qq_reward_alipay'     => ['text', '支付宝赞赏码', 'https://xgjalbum.oss-cn-hangzhou.aliyuncs.com/_oss/6af87ebc7aa179082d257c62/album_app_image/cea069b2_1795171148135.webp', '图片地址', [], 'post'],
        'qq_code_copy'         => ['select', '代码块复制按钮', 'on', '', ['on' => '开', 'off' => '关'], 'post'],

        /* ---------- 评论 ---------- */
        'qq_qq_autofill'       => ['select', 'QQ 号快捷填充', 'on', '填号自动补昵称与邮箱', ['on' => '开', 'off' => '关'], 'comment'],
        'qq_avatar_source'     => ['select', '头像来源', 'qq', '', ['qq' => '优先 QQ 头像', 'gravatar' => '优先 Gravatar'], 'comment'],
        'qq_avatar_cdn'        => ['text', 'Gravatar 镜像', 'https://dn-qiniu-avatar.qbox.me/avatar/', '', [], 'comment'],
        'qq_comment_page'      => ['text', '评论每页条数', '20', '', [], 'comment', 'range:5:50'],
        'qq_reward_qq'        => ['text', 'QQ 赞赏码', 'https://xgjalbum.oss-cn-hangzhou.aliyuncs.com/_oss/6af87ebc7aa179082d257c62/album_app_image/2c76c374_1795171148578.webp', '图片地址', [], 'post'],

        /* ---------- 抽屉 ---------- */
        'qq_drawer_contacts'   => ['textarea', '社交链接', '', 'JSON：{"qq":"号码","github":"用户名"}，用下方勾选面板生成', [], 'drawer', 'socials'],
        'qq_menu_extra'        => ['textarea', '额外菜单', '', 'JSON 数组，type 0 链接 / 1 归档 / 2 分类 / 3 页面 / 5 分隔 / 6 RSS', [], 'drawer'],
        'qq_drawer_footer'     => ['text', '抽屉底部图标', '', 'JSON：{"iconName":"链接"}', [], 'drawer'],
        'qq_drawer_credit'     => ['text', '抽屉署名', '', '留空显示版权行', [], 'drawer'],
        'qq_sidebar_cover'     => ['text', '侧栏封面图', '', '资料卡顶部那张图', [], 'drawer'],

        /* ---------- 高级 ---------- */
        'qq_baidu_token'      => ['text', '百度准入密钥', 'ZY1qs5xnGOpiiMXR', '百度搜索资源平台 → 普通收录 → 推送接口里的 token', [], 'seo'],
        'qq_baidu_auto'       => ['select', '发布自动推送', 'on', '新文章发布后自动推一条给百度', ['on' => '开', 'off' => '关'], 'seo'],
        'qq_clarity_id'        => ['text', 'Clarity 项目 ID', 'wz8lxlamw3', '留空则不加载微软统计', [], 'seo'],
        'qq_asset_origin'      => ['select', '静态资源来源', 'local', '', ['local' => '本地文件', 'cloud' => 'BootCDN'], 'adv'],
        'qq_image_host_switch' => ['select', '图片域名替换', 'off', '', ['on' => '开', 'off' => '关'], 'adv'],
        'qq_image_host_target' => ['text', '替换为', '', '例如 https://cdn.example.com', [], 'adv'],
        'qq_pjax_scope'        => ['text', 'PJAX 容器', '#qq-shell', '改错会导致热加载失效', [], 'adv'],
        'qq_links_page_size'   => ['text', '友链每页', '20', '', [], 'adv'],
        'qq_links_shuffle'     => ['select', '友链随机排序', 'off', '', ['on' => '开', 'off' => '关'], 'adv'],
        'qq_inject_css'        => ['textarea', '自定义 CSS', '', '输出到 </head> 前', [], 'adv'],
        'qq_inject_js'         => ['textarea', '自定义 JS', '', '输出到 </body> 前', [], 'adv'],
        'qq_inject_head'       => ['textarea', '页头注入', '', 'meta / link 等', [], 'adv'],
        'qq_inject_body'       => ['textarea', '页脚注入', '', '统计脚本放这里', [], 'adv'],
        'qq_analytics_code'    => ['textarea', '统计代码', '', '直接输出到 </body> 前', [], 'seo'],

        /* ---- 细节微调 ---- */
        'qq_color_bg'          => ['text', '页面底色', '#f4f6fa', '整站背景色，深色模式下不生效', [], 'fine', 'color'],
        'qq_color_surface'     => ['text', '卡片底色', '#ffffff', '文章卡片、面板的背景色', [], 'fine', 'color'],
        'qq_color_text'        => ['text', '正文颜色', '#1c1f26', '标题与正文的主色', [], 'fine', 'color'],
        'qq_color_muted'       => ['text', '次要文字', '#96a0b0', '时间、浏览量、说明文字', [], 'fine', 'color'],
        'qq_font_size'         => ['text', '正文字号', '15.5', '单位 px，13–18 之间比较舒服', [], 'fine', 'range:13:18:0.5'],
        'qq_line_height'       => ['text', '正文行高', '1.92', '1.5 紧凑，2.2 宽松', [], 'fine', 'range:1.5:2.3:0.05'],
        'qq_container_w'       => ['text', '内容区宽度', '1180', '单位 px，宽屏可到 1440', [], 'fine', 'range:960:1440:20'],
        'qq_card_gap'          => ['text', '卡片间距', '18', '单位 px，首页卡片之间的空隙', [], 'fine', 'range:8:32'],
        'qq_sidebar_w'         => ['text', '侧栏宽度', '300', '单位 px，仅桌面端', [], 'fine', 'range:240:360'],
        'qq_topbar_h'          => ['text', '顶栏高度', '60', '单位 px', [], 'fine', 'range:52:80'],
        'qq_shadow_alpha'      => ['text', '阴影强度', '100', '0 完全扁平，100 默认', [], 'fine', 'range:0:100'],
        'qq_avatar_shape'      => ['select', '头像形状', 'rounded', '', ['circle' => '圆形', 'rounded' => '圆角方形'], 'fine'],

        /* ---- 文案 ---- */
        'qq_search_ph'         => ['text', '搜索框提示', '搜索一下…', '顶栏搜索框的占位文字', [], 'copy'],
        'qq_404_title'         => ['text', '404 标题', '页面走丢了', '', [], 'copy'],
        'qq_404_desc'          => ['text', '404 说明', '链接可能失效了，试试下面的搜索或者回首页', '', [], 'copy'],
        'qq_watermark_text'    => ['text', '页面水印文字', 'Qicong 柒葱', '铺在背景上的浅色斜水印，留空关闭', [], 'copy'],
    ];

    private static ?array $data = null;
    private static ?array $legacy = null;

    /** 一次性把旧主题的配置搬过来；带版本标记，重复调用无副作用 */
    public static function migrate(): void
    {
        try {
            $db = Db::get();
            $row = $db->fetchRow($db->select('value')->from('table.options')->where('name = ?', 'theme:' . self::themeName()));
            $own = $row && is_array($saved = self::decodeValue((string) $row['value'])) ? $saved : [];
            if (isset($own['qq_schema']) && (int) $own['qq_schema'] >= 1) {
                return;
            }

            $old = self::legacyData();
            foreach (self::LEGACY as $new => $oldKey) {
                if (!isset($own[$new]) && isset($old[$oldKey]) && $old[$oldKey] !== '') {
                    $own[$new] = $old[$oldKey];
                }
            }
            $own['qq_schema'] = 1;

            $db->query($db->update('table.options')
                ->rows(['value' => self::encodeValue($own)])
                ->where('name = ?', 'theme:' . self::themeName()));
            self::$data = $own;
        } catch (\Throwable $e) {
            // 迁移失败不能挡住前台，读配置时会自动回落到旧键
        }
    }

    /** 读单个配置：新键 → 默认值 → 旧键 */
    public static function get(string $key, mixed $fallback = null): mixed
    {
        $all = self::all();
        if (isset($all[$key]) && $all[$key] !== '') {
            return $all[$key];
        }
        $legacyKey = self::LEGACY[$key] ?? null;
        if ($legacyKey !== null) {
            $old = self::legacyData();
            if (isset($old[$legacyKey]) && $old[$legacyKey] !== '') {
                return $old[$legacyKey];
            }
        }
        if ($fallback !== null) {
            return $fallback;
        }
        $schema = self::SCHEMA[$key] ?? null;
        return $schema[2] ?? '';
    }

    public static function flag(string $key, bool $default = false): bool
    {
        $val = self::get($key, $default ? 'on' : 'off');
        return in_array($val, ['on', '1', 1, true], true);
    }

    /** 全部配置：缓存一次，避免每行卡片都查库 */
    public static function all(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }
        self::$data = [];
        try {
            $db = Db::get();
            $row = $db->fetchRow($db->select('value')->from('table.options')->where('name = ?', 'theme:' . self::themeName()));
            if ($row) {
                $raw = (string) $row['value'];
                $decoded = null;
                // Typecho 1.3 存 JSON，1.2 存 PHP serialize
                if ($raw !== '' && ($raw[0] === '{' || $raw[0] === '[')) {
                    $decoded = json_decode($raw, true);
                } else {
                    $decoded = @unserialize($raw);
                }
                if (is_array($decoded)) {
                    self::$data = $decoded;
                }
            }
        } catch (\Throwable $e) {
            self::$data = [];
        }
        return self::$data;
    }

    /** 静态资源地址：本地带版本号，云端走 BootCDN */
    /**
     * 主题资源根地址
     * siteUrl 没带子目录时（Typecho 装在 /blog/ 但站点地址填了域名根），
     * 按当前请求脚本路径把安装目录补回去，避免 CSS/JS 404
     */
    /**
     * 安装目录（纯路径，不含域名）
     * 根目录安装返回 ''，子目录安装返回 '/blog'
     * 这样资源与站内链接不受站点地址、域名、端口、http/https 影响
     */
    public static function siteBase(): string
    {
        /* 方案一：按当前脚本路径推（/blog/index.php → /blog） */
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        /* PATH_INFO：/blog/index.php/archives/1 → /blog/index.php */
        $script = (string) preg_replace('~\.php/.*$~', '.php', $script);
        $dir = (string) preg_replace('~/(admin|action|install)(/.*)?$~', '', rtrim(str_replace('\\', '/', dirname($script)), '/'));
        if ($dir === '/') {
            $dir = '';
        }
        if ($dir !== '') {
            return $dir;
        }

        /* 方案二：反代把路径剥掉时，按文件系统算 */
        if (defined('__TYPECHO_ROOT_DIR__')) {
            $rootDir = str_replace('\\', '/', (string) __TYPECHO_ROOT_DIR__);
            $docRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
            if ($docRoot !== '' && strpos($rootDir, $docRoot) === 0) {
                $install = substr($rootDir, strlen($docRoot));
                return $install === '/' ? '' : rtrim($install, '/');
            }
        }

        /* 方案三：退回站点地址里的路径部分 */
        $path = (string) parse_url((string) \Helper::options()->siteUrl, PHP_URL_PATH);
        return rtrim($path, '/');
    }

    /** 站内绝对路径：url('/stats') → /blog/stats */
    public static function url(string $path = '/'): string
    {
        return self::siteBase() . '/' . ltrim($path, '/');
    }

    /** 当前请求路径（去掉安装目录），用于导航高亮 */
    public static function here(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
        $base = self::siteBase();
        if ($base !== '' && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }
        return $path === '' ? '/' : $path;
    }

    /** 主题资源目录（纯路径） */
    public static function themeBase(): string
    {
        return self::siteBase() . '/usr/themes/' . basename(dirname(__DIR__));
    }

    public static function origin(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
        $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
        return ($https ? 'https://' : 'http://') . $host . self::siteBase();
    }

    /** 读 Typecho 路由表里的格式，拼出路径（/archives/6/） */
    private static function routePath(string $name, array $params): string
    {
        try {
            $route = \Typecho\Router::get($name);
            if (!$route || empty($route['format'])) {
                return '';
            }
            $values = [];
            foreach (($route['params'] ?? []) as $key => $param) {
                $pname = is_array($param) ? (string) ($param['name'] ?? '') : (string) $param;
                if ($pname === '') {
                    $pname = (string) $key;
                }
                $values[$pname] = (string) ($params[$pname] ?? '');
            }
            return (string) vsprintf((string) $route['format'], $values);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** 文章地址 */
    public static function postUrl(int $cid): string
    {
        $p = self::routePath('post', ['cid' => $cid]);
        return rtrim((string) \Helper::options()->index, '/') . ($p !== '' ? $p : '/archives/' . $cid . '/');
    }

    /** 分类地址 */
    public static function catUrl(string $slug): string
    {
        $p = self::routePath('category', ['slug' => $slug]);
        return rtrim((string) \Helper::options()->index, '/') . ($p !== '' ? $p : '/category/' . rawurlencode($slug) . '/');
    }

    /** 独立页面地址 */
    public static function pageUrl(string $slug): string
    {
        $p = self::routePath('page', ['slug' => $slug]);
        return rtrim((string) \Helper::options()->index, '/') . ($p !== '' ? $p : '/' . $slug . '.html');
    }
    /** 站长头像：Typecho 第一个用户的邮箱，QQ 邮箱走 QQ 头像 */
    public static function ownerAvatar(): string
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $mail = '';
        try {
            $row = \Typecho\Db::get()->fetchRow(
                \Typecho\Db::get()->select('mail')->from('table.users')->order('uid', \Typecho\Db::SORT_ASC)->limit(1)
            );
            $mail = (string) ($row['mail'] ?? '');
        } catch (\Throwable $e) {
        }
        $cache = \qiqi77\Comment\Avatar::resolve('', $mail, (string) \Helper::options()->title);
        return $cache;
    }

    /** 连接诊断面板：告诉你这台机器该贴哪段配置 */
    public static function linkPanel(): string
    {
        $server = strtolower((string) ($_SERVER['SERVER_SOFTWARE'] ?? ''));
        if (strpos($server, 'nginx') !== false) {
            $kind = 'nginx';
        } elseif (strpos($server, 'apache') !== false) {
            $kind = 'apache';
        } elseif (strpos($server, 'kangle') !== false) {
            $kind = 'kangle';
        } else {
            $kind = '其他';
        }

        $base = self::siteBase();                 // '' 或 /blog
        $dir = $base === '' ? '/' : $base . '/';
        $rewrite = !empty(\Helper::options()->rewrite);
        $pathInfo = isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] !== '';

        $nginx = "location {$dir} {\n"
            . "    try_files \$uri \$uri/ {$base}/index.php\$uri;\n"
            . "}\n\n"
            . "location ~ \\.php(/|$) {\n"
            . "    fastcgi_pass   127.0.0.1:9000;      # 按面板实际端口改\n"
            . "    fastcgi_index  index.php;\n"
            . "    fastcgi_split_path_info ^(.+\\.php)(/.*)$;\n"
            . "    fastcgi_param  PATH_INFO \$fastcgi_path_info;\n"
            . "    fastcgi_param  SCRIPT_FILENAME \$document_root\$fastcgi_script_name;\n"
            . "    include        fastcgi_params;\n"
            . "}";

        $apache = "RewriteEngine On\n"
            . "RewriteBase {$dir}\n"
            . "RewriteCond %{REQUEST_FILENAME} !-f\n"
            . "RewriteCond %{REQUEST_FILENAME} !-d\n"
            . "RewriteRule ^(.*)$ index.php/\$1 [L]";

        $rows = [
            ['Web 服务器', $kind . ($server !== '' ? '（' . htmlspecialchars($server, ENT_QUOTES) . '）' : '')],
            ['安装目录', $base === '' ? '域名根目录' : $base . '/'],
            ['当前链接', $rewrite ? '伪静态（/archives/1/）' : '带 index.php（/index.php/archives/1/）'],
            ['PATH_INFO', $pathInfo ? '服务器已传，可直接用伪静态' : '未传，开启伪静态前先补配置'],
        ];

        $list = '';
        foreach ($rows as $row) {
            $list .= '<div class="qq-bk__kv"><span>' . $row[0] . '</span><b>' . $row[1] . '</b></div>';
        }

        $block = static function (string $title, string $code) {
            return '<div class="qq-bk__sub">' . $title . '</div>'
                . '<div class="qq-bk__row">'
                . '<code class="qq-bk__url" style="flex:1;white-space:pre-wrap">' . htmlspecialchars($code, ENT_QUOTES) . '</code>'
                . '<button type="button" class="qq-bk-btn" data-qq-copy="' . htmlspecialchars($code, ENT_QUOTES) . '">复制</button>'
                . '</div>';
        };

        return '<div class="qq-bk qq-bk--link">'
            . '<div class="qq-bk__title">站点连接</div>'
            . '<div class="qq-bk__note">伪静态不是必须的。下面按你当前环境给出可直接粘贴的配置。</div>'
            . '<div class="qq-bk__grid">' . $list . '</div>'
            . $block('Nginx（' . ($kind === 'nginx' ? '当前服务器' : '如用 Nginx') . '）', $nginx)
            . $block('Apache .htaccess', $apache)
            . '<div class="qq-bk__note" style="margin-top:8px">'
            . 'Nginx 改完执行 <code>nginx -t</code> 再 reload；Apache 需要 <code>mod_rewrite</code>。'
            . 'Windows 版 Nginx 不支持 unix socket，<code>fastcgi_pass</code> 用 127.0.0.1:端口。'
            . '</div>'
            . '</div>';
    }
    public static function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $base = self::themeBase();

        if (self::get('qq_asset_origin', 'local') === 'cloud') {
            $cdn = [
                'js/mdui.min.js'            => 'https://cdn.bootcdn.net/ajax/libs/mdui/1.0.1/js/mdui.min.js',
                'css/mdui.min.css'          => 'https://cdn.bootcdn.net/ajax/libs/mdui/1.0.1/css/mdui.min.css',
                'js/pjax.min.js'            => 'https://cdn.bootcdn.net/ajax/libs/pjax/0.2.8/pjax.min.js',
                'js/nprogress.min.js'       => 'https://cdn.bootcdn.net/ajax/libs/nprogress/0.2.0/nprogress.min.js',
                'js/tocbot.min.js'          => 'https://cdn.bootcdn.net/ajax/libs/tocbot/4.21.0/tocbot.min.js',
                'css/tocbot.min.css'        => 'https://cdn.bootcdn.net/ajax/libs/tocbot/4.21.0/tocbot.css',
                'js/highlight.min.js'       => 'https://cdn.bootcdn.net/ajax/libs/highlight.js/11.7.0/highlight.min.js',
                'css/atom-one-dark.min.css' => 'https://cdn.bootcdn.net/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css',
                'js/clipboard.min.js'       => 'https://cdn.bootcdn.net/ajax/libs/clipboard.js/2.0.11/clipboard.min.js',
                'js/lazyload.min.js'        => 'https://cdn.bootcdn.net/ajax/libs/vanilla-lazyload/19.1.3/lazyload.min.js',
                'js/qrcode.min.js'          => 'https://cdn.bootcdn.net/ajax/libs/qrcodejs/1.0.0/qrcode.min.js',
            ];
            if (isset($cdn[$path])) {
                return $cdn[$path];
            }
        }

        $abs = __DIR__ . '/../assets/' . $path;
        $url = $base . '/assets/' . $path;
        if (is_file($abs)) {
            $url .= '?v=' . filemtime($abs);
        }
        return $url;
    }

    /** 后台表单：按 schema 注册元素，Typecho 负责保存 */
    /** 渲染设置表单：按分组输出，分组标记供前端折成卡片 */
    public static function form($form): void
    {
        $buckets = [];
        foreach (self::SCHEMA as $key => $def) {
            $group = $def[5] ?? 'basic';
            $buckets[$group][$key] = $def;
        }

        foreach (self::GROUPS as $gid => $meta) {
            if (empty($buckets[$gid])) {
                continue;
            }

            foreach ($buckets[$gid] as $key => $def) {
                [$type, $label, $default, $desc] = [$def[0], $def[1], $def[2] ?? '', $def[3] ?? ''];
                $options = $def[4] ?? [];
                $hint = $def[6] ?? '';
                $value = (string) self::get($key, $default);

                if ($type === 'select') {
                    $element = new \Typecho\Widget\Helper\Form\Element\Select($key, $options, $value, $label, $desc);
                } elseif ($type === 'textarea') {
                    $element = new \Typecho\Widget\Helper\Form\Element\Textarea($key, null, $value, $label, $desc);
                } else {
                    $element = new \Typecho\Widget\Helper\Form\Element\Text($key, null, $value, $label, $desc);
                }

                if ($hint !== '' && isset($element->input) && method_exists($element->input, 'setAttribute')) {
                    $element->input->setAttribute('data-qq-hint', $hint);
                }
                $form->addInput($element);
            }
        }

        /* 分组表：前端据此把字段排进对应卡片（不再依赖隐藏锚点） */
        $groupMap = [];
        foreach (self::GROUPS as $gid => $meta) {
            if (empty($buckets[$gid])) {
                continue;
            }
            $groupMap[$gid] = [
                'title'  => $meta[1],
                'desc'   => $meta[2],
                'fields' => array_keys($buckets[$gid]),
            ];
        }
        echo '<script type="application/json" id="qq-groups">'
            . json_encode($groupMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . '</script>';
        /* 首屏引导：让第一次进设置页的人知道先改什么 */
        echo '<div class="qq-set__hero">'
            . '<div class="qq-set__hero-main">'
            . '<h2>qiqi77 · 外观设置</h2>'
            . '<p>所有改动都在本页完成，点最下方「保存设置」生效。拿不准的项就保持默认，留空的项会自动回落到默认值。</p>'
            . '<ol class="qq-set__steps">'
            . '<li><b>先填基础</b>：站点标题、Logo、页脚版权</li>'
            . '<li><b>再调外观</b>：主题色、圆角、卡片高度，右侧有实时数值</li>'
            . '<li><b>最后补细节</b>：社交链接、打赏码、统计代码</li>'
            . '</ol>'
            . '</div>'
            . '<div class="qq-set__hero-side">'
            . '<span class="qq-set__badge">v' . QIQI77_VERSION . '</span>'
            . '<span class="qq-set__badge qq-set__badge--ghost">' . count(self::SCHEMA) . ' 项可调</span>'
            . '</div>'
            . '</div>';

        /* 默认值表：前端「恢复默认」用 */
        $defaults = [];
        foreach (self::SCHEMA as $key => $def) {
            $defaults[$key] = (string) ($def[2] ?? '');
        }
        echo '<script type="application/json" id="qq-defaults">'
            . json_encode($defaults, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . '</script>';
    }

    /** 搜索引擎收录面板：sitemap 状态 + 各家提交入口 + 百度推送 */
    public static function searchPanel(): string
    {
        $db = Db::get();
        $site = rtrim((string) ($db->fetchRow($db->select('value')->from('table.options')->where('name = ?', 'siteUrl'))['value'] ?? ''), '/');
        $sitemap = $site . '/sitemap.xml';

        /* sitemap 概况 */
        $count = 0;
        $newest = 0;
        try {
            $row = $db->fetchRow($db->select('cid', ['COUNT(cid)' => 'n'])->from('table.contents')
                ->where('type = ?', 'post')->where('status = ?', 'publish'));
            $count = (int) ($row['n'] ?? 0);

            $last = $db->fetchRow($db->select('modified', 'created')->from('table.contents')
                ->where('type = ?', 'post')->where('status = ?', 'publish')
                ->order('modified', Db::SORT_DESC)->limit(1));
            $newest = $last ? (int) ($last['modified'] ?: $last['created']) : 0;
        } catch (\Throwable $e) {
        }

        /* 百度推送：手动触发 */
        $act = (string) ($_GET['qq_baidu'] ?? '');
        $note = '';
        if ($act !== '') {
            $limit = $act === 'recent' ? 10 : 100;
            $r = \qiqi77\Site\Baidu::push(\qiqi77\Site\Baidu::urls($limit));
            $note = ($r['ok'] ? '✓ ' : '✗ ') . $r['message'];
        }

        $last = \qiqi77\Site\Baidu::last();
        if ($note === '' && $last) {
            $note = ($last['ok'] ? '上次成功' : '上次失败') . '：' . (string) ($last['message'] ?? '')
                . '（' . date('m-d H:i', (int) ($last['time'] ?? 0)) . '）';
        }
        if ($note === '') {
            $note = '还没推过。百度每天 10 条额度，建议一天推一次「最近 10 篇」。';
        }

        $base = (string) \Helper::options()->adminUrl . 'options-theme.php';
        $btn = static function (string $a, string $label, string $cls = '') use ($base): string {
            return '<a class="qq-bk-btn ' . $cls . '" href="'
                . htmlspecialchars($base . '?qq_baidu=' . $a, ENT_QUOTES)
                . '">' . $label . '</a>';
        };

        $hasSitemap = is_file(__TYPECHO_ROOT_DIR__ . '/sitemap.xml') || is_file(__TYPECHO_ROOT_DIR__ . '/sitemap.php');
        $info = '文章 ' . $count . ' 篇'
            . ($hasSitemap ? ' · sitemap 已就绪' : ' · 未检测到 sitemap.xml，把 extras/sitemap.php 放到站点根即可')
            . ($newest ? ' · 最近更新 ' . date('m-d H:i', $newest) : '');

        return '<div class="qq-bk qq-bk--search">'
            . '<div class="qq-bk__title">搜索引擎收录</div>'
            . '<div class="qq-bk__note">' . htmlspecialchars($info, ENT_QUOTES) . '</div>'
            . '<div class="qq-bk__row">'
            . '<code class="qq-bk__url">' . htmlspecialchars($sitemap, ENT_QUOTES) . '</code>'
            . '<button type="button" class="qq-bk-btn" data-qq-copy="' . htmlspecialchars($sitemap, ENT_QUOTES) . '">复制</button>'
            . '<a class="qq-bk-btn" href="' . htmlspecialchars($sitemap, ENT_QUOTES) . '" target="_blank" rel="noopener">预览</a>'
            . '</div>'
            . '<div class="qq-bk__row">'
            . '<span class="qq-bk__tag">提交入口</span>'
            . '<a class="qq-bk-btn" href="https://zhanzhang.so.com/sitetool/site_sitemap" target="_blank" rel="noopener">360</a>'
            . '<a class="qq-bk-btn" href="https://zhanzhang.sogou.com/index.php/site/sitemap" target="_blank" rel="noopener">搜狗</a>'
            . '<a class="qq-bk-btn" href="https://search.google.com/search-console/sitemaps" target="_blank" rel="noopener">Google</a>'
            . '<a class="qq-bk-btn" href="https://www.bing.com/webmasters/sitemaps" target="_blank" rel="noopener">Bing</a>'
            . '</div>'
            . '<div class="qq-bk__row">'
            . '<span class="qq-bk__tag">百度主动推送</span>'
            . '<span class="qq-bk__note">' . htmlspecialchars($note, ENT_QUOTES) . '</span>'
            . '<div class="qq-bk__btns">'
            . '<span class="qq-bk__tag">剩余 ' . \qiqi77\Site\Baidu::budget() . '</span>'
            . $btn('recent', '推最近 10 篇', 'is-primary')
            . $btn('all', '推全部')
            . '</div>'
            . '</div>'
            . '</div>';
    }
    /** 配置备份面板：存到 options 表的 theme:<主题>Backup 行；用 GET 触发，避免干扰 Typecho 的表单保存 */
    public static function backupPanel(): string
    {
        $flag = (string) ($_GET['qq_backup'] ?? '');
        $note = '';
        if ($flag !== '') {
            try {
                $db = Db::get();
                $name = 'theme:' . self::themeName();
                $bak = $name . 'Backup';
                if ($flag === 'save') {
                    $row = $db->fetchRow($db->select('value')->from('table.options')->where('name = ?', $name));
                    $val = (string) ($row['value'] ?? '');
                    $exists = $db->fetchRow($db->select('name')->from('table.options')->where('name = ?', $bak));
                    if ($exists) {
                        $db->query($db->update('table.options')->rows(['value' => $val])->where('name = ?', $bak));
                    } elseif ($val !== '') {
                        $db->query($db->insert('table.options')->rows(['name' => $bak, 'user' => 0, 'value' => $val]));
                    }
                    $note = '已备份当前配置';
                } elseif ($flag === 'restore') {
                    $row = $db->fetchRow($db->select('value')->from('table.options')->where('name = ?', $bak));
                    if ($row) {
                        $db->query($db->update('table.options')->rows(['value' => (string) $row['value']])->where('name = ?', $name));
                        $note = '已从备份还原，刷新后生效';
                    } else {
                        $note = '还没有备份过';
                    }
                } elseif ($flag === 'delete') {
                    $db->query($db->delete('table.options')->where('name = ?', $bak));
                    $note = '备份已删除';
                }
            } catch (\Throwable $e) {
                $note = '操作失败：' . $e->getMessage();
            }
        }

        $base = (string) \Helper::options()->adminUrl . 'options-theme.php';
        $btn = static function (string $act, string $label, string $cls = '') use ($base): string {
            return '<a class="qq-bk-btn ' . $cls . '" href="'
                . htmlspecialchars($base . '?qq_backup=' . $act, ENT_QUOTES)
                . '">' . $label . '</a>';
        };

        return '<div class="qq-bk">'
            . '<div class="qq-bk__title">配置备份</div>'
            . ($note !== '' ? '<div class="qq-bk__note">' . htmlspecialchars($note, ENT_QUOTES) . '</div>' : '')
            . '<div class="qq-bk__btns">'
            . $btn('save', '备份当前', 'is-primary')
            . $btn('restore', '还原备份')
            . $btn('delete', '删除备份', 'is-danger')
            . '</div>'
            . '<div class="qq-bk__tip">备份存在 options 表，切换主题不受影响。</div>'
            . '</div>';
    }
    /** 文章自定义字段：沿用 wzimg，老数据不用迁移 */
    public static function fields($layout): void
    {
        $layout->addItem(new \Typecho\Widget\Helper\Form\Element\Select(
            'articleType',
            ['article' => '文章', 'daily' => '日常', 'normal' => '无封面'],
            'article',
            '文章类型'
        ));
        $layout->addItem(new \Typecho\Widget\Helper\Form\Element\Text(
            'wzimg', null, null, '封面图', '不填则自动取正文首图'
        ));
        $layout->addItem(new \Typecho\Widget\Helper\Form\Element\Text(
            'subtitle', null, null, '副标题', '显示在标题下方'
        ));
        $layout->addItem(new \Typecho\Widget\Helper\Form\Element\Text(
            'remark', null, null, '吐槽', '无封面卡片上的短句'
        ));
    }
    private static function themeName(): string
    {
        static $name = null;
        if ($name !== null) {
            return $name;
        }
        try {
            $db = Db::get();
            $row = $db->fetchRow($db->select('value')->from('table.options')->where('name = ?', 'theme'));
            $name = (string) ($row['value'] ?? '');
        } catch (\Throwable $e) {
            $name = '';
        }
        if ($name === '') {
            $name = defined('THEME_NAME') ? THEME_NAME : 'qiqi77';
        }
        return $name;
    }

    /** 旧主题序列化数组，只在需要兼容时读一次 */
    private static function legacyData(): array
    {
        if (self::$legacy !== null) {
            return self::$legacy;
        }
        self::$legacy = [];
        try {
            $db = Db::get();
            $row = $db->fetchRow($db->select('value')->from('table.options')->where('name = ?', 'theme:QiQi'));
            if ($row) {
                $decoded = self::decodeValue((string) $row['value']);
                if (is_array($decoded)) {
                    self::$legacy = $decoded;
                }
            }
        } catch (\Throwable $e) {
            self::$legacy = [];
        }
        return self::$legacy;
    }


    /** 按核心版本选择存储格式：1.3+ 用 JSON，1.2 仍用 serialize */
    private static function encodeValue(array $data): string
    {
        if (version_compare((string) \Typecho\Common::VERSION, '1.3.0', '>=')) {
            return (string) json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        return serialize($data);
    }

    /** 兼容 1.2 的 serialize 和 1.3 的 JSON */
    private static function decodeValue(string $raw): mixed
    {
        if ($raw === '') {
            return null;
        }
        if ($raw[0] === '{' || $raw[0] === '[') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }
        return @unserialize($raw);
    }
}

<?php
/**
 * 用途：UA 字符串 → 系统/浏览器图标与名称（规则表，无分支堆叠）
 * 依赖：无
 * 被调用：Comment\Thread、Site\Online
 */
declare(strict_types=1);

namespace qiqi77\Comment;

final class Ua
{
    /** 系统规则：命中即停，顺序即优先级 */
    private const OS = [
        ['/^zhihu/i',      'icon-zhihu',   '知乎同步'],
        ['/^bilibili/i',   'icon-bilibili', 'B站同步'],
        ['/MicroMessenger/i', 'icon-qq',     '微信'],
        ['/QQBrowser|\bQQ\//i', 'icon-qq',   'QQ'],
        ['/Windows NT 10/i',  'icon-windows', 'Windows 10'],
        ['/Windows NT 6\.3/i','icon-windows', 'Windows 8.1'],
        ['/Windows NT 6\.1/i','icon-windows', 'Windows 7'],
        ['/Windows/i',        'icon-windows', 'Windows'],
        ['/iPhone|iPad|iPod/i', 'icon-ios',  'iOS'],
        ['/Mac OS X/i',       'icon-ios',     'macOS'],
        ['/Android/i',        'icon-android', 'Android'],
        ['/Xiaomi|MIUI/i',    'icon-xiaomi',  'MIUI'],
        ['/HarmonyOS/i',      'icon-os',      'HarmonyOS'],
        ['/Linux/i',          'icon-linux',   'Linux'],
        ['/CentOS/i',         'icon-centos',  'CentOS'],
    ];

    /** 浏览器规则 */
    private const BROWSER = [
        ['/^zhihu/i',      'icon-zhihu',   '知乎同步'],
        ['/^bilibili/i',   'icon-bilibili', 'B站同步'],
        ['/MicroMessenger/i', 'icon-qq',      '微信内置'],
        ['/Edg\//i',          'icon-edge',    'Edge'],
        ['/OPR\/|Opera/i',    'icon-os',      'Opera'],
        ['/Firefox/i',        'icon-firefox', 'Firefox'],
        ['/Chrome\//i',       'icon-chrome',  'Chrome'],
        ['/Safari\//i',       'icon-safari',  'Safari'],
        ['/curl|Wget|python|Go-http/i', 'icon-os', '脚本'],
        ['/bot|spider|crawl/i', 'icon-os',    '爬虫'],
    ];

    /** 返回 ['os'=>['icon','name'], 'browser'=>['icon','name']] */
    public static function parse(string $agent): array
    {
        $agent = $agent !== '' ? $agent : 'Mozilla/5.0';

        return [
            'os'      => self::match($agent, self::OS),
            'browser' => self::match($agent, self::BROWSER),
            'device'  => self::device($agent),
        ];
    }

    /** 规则遍历：第一条命中的就是结果 */
    private static function match(string $agent, array $rules): array
    {
        foreach ($rules as [$pattern, $icon, $name]) {
            if (preg_match($pattern, $agent)) {
                return ['icon' => $icon, 'name' => $name];
            }
        }
        return ['icon' => 'icon-os', 'name' => '未知'];
    }

    /** 一句话摘要，列表里用 */
    public static function summary(string $agent): string
    {
        $p = self::parse($agent);
        return $p['os']['name'] . ' · ' . $p['browser']['name'];
    }

    /** 设备类型：图标用 iconfont 里已有的那几个 */
    private static function device(string $ua): array
    {
        if (preg_match('/bot|spider|crawl|slurp|bingpreview|headlesschrome|phantomjs|facebookexternalhit|scrapy|go-http-client|okhttp|python-requests|python-urllib|curl\/|wget\/|axios|node-fetch|java\//i', $ua)) {
            return ['type' => 'bot', 'name' => '爬虫', 'icon' => 'icon-os'];
        }
        if (preg_match('/iPad|Tablet|PlayBook|Silk|Kindle/i', $ua)) {
            return ['type' => 'tablet', 'name' => '平板', 'icon' => 'icon-ios'];
        }
        if (preg_match('/Mobile|Android|iPhone|iPod|Windows Phone|HarmonyOS/i', $ua)) {
            return ['type' => 'mobile', 'name' => '手机', 'icon' => 'icon-android'];
        }
        return ['type' => 'desktop', 'name' => '电脑', 'icon' => 'icon-windows'];
    }
}

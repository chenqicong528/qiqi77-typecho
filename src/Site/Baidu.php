<?php
/**
 * 用途：百度普通收录（主动推送）
 * 依赖：Typecho\Db、Options
 * 被调用：Options::searchPanel()（后台手动推送）、functions.php 的发布钩子
 */
declare(strict_types=1);

namespace qiqi77\Site;

use qiqi77\Options;
use Typecho\Db;

final class Baidu
{
    private const API = 'http://data.zz.baidu.com/urls';
    private const LOG_KEY = 'theme:qiqi77Baidu';
    private const BATCH = 10;

    /** 站点根地址（直接读 options 表，避免依赖 Helper） */
    public static function site(): string
    {
        try {
            $row = Db::get()->fetchRow(
                Db::get()->select('value')->from('table.options')->where('name = ?', 'siteUrl')
            );
            return rtrim((string) ($row['value'] ?? ''), '/');
        } catch (\Throwable $e) {
            return '';
        }
    }

    public static function token(): string
    {
        return trim((string) Options::get('qq_baidu_token'));
    }

    public static function enabled(): bool
    {
        return self::token() !== '' && self::site() !== '';
    }

    /** 已发布文章的地址，按更新时间倒序 */
    public static function urls(int $limit = 10): array
    {
        $site = self::site();
        if ($site === '') {
            return [];
        }
        try {
            $rows = Db::get()->fetchAll(
                Db::get()->select('cid')->from('table.contents')
                    ->where('type = ?', 'post')
                    ->where('status = ?', 'publish')
                    ->order('modified', Db::SORT_DESC)
                    ->limit(max(1, min(100, $limit)))
            );
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $out[] = $site . '/' . (int) $row['cid'];
        }
        return $out;
    }

    /** 今天还剩多少额度：优先用上次返回的 remain，跨天则重置为 BATCH */
    public static function budget(): int
    {
        $last = self::last();
        if ($last && (string) date('Y-m-d', (int) ($last['time'] ?? 0)) === date('Y-m-d')) {
            $remain = (int) ($last['remain'] ?? -1);
            if ($remain >= 0) {
                return min(self::BATCH, $remain);
            }
        }
        return self::BATCH;
    }

    /**
     * 推送一批地址：按今日剩余额度截断，一次请求发完
     * @return array{ok:bool,message:string,remain:int,sent:int,http:int}
     */
    public static function push(array $urls): array
    {
        $urls = array_values(array_unique(array_filter(array_map('trim', $urls))));
        $summary = ['ok' => false, 'message' => '', 'remain' => -1, 'sent' => 0, 'http' => 0];

        if (self::token() === '' || self::site() === '') {
            $summary['message'] = '还没填准入密钥';
            return $summary;
        }
        if (!$urls) {
            $summary['message'] = '没有可推送的地址';
            return $summary;
        }

        $budget = self::budget();
        if ($budget <= 0) {
            $summary['message'] = '今日配额已用完，明天再推';
            $summary['remain'] = 0;
            return $summary;
        }

        $batch = array_slice($urls, 0, $budget);
        $r = self::request($batch);

        $summary['http'] = $r['http'];
        $summary['ok'] = $r['ok'];
        $summary['sent'] = $r['sent'];
        $summary['remain'] = $r['remain'];

        if ($r['ok']) {
            $left = count($urls) - $r['sent'];
            $summary['message'] = '成功推送 ' . $r['sent'] . ' 条'
                . ($r['remain'] >= 0 ? '，今日剩余 ' . $r['remain'] . ' 条' : '')
                . ($left > 0 ? '；还有 ' . $left . ' 条没推，明天继续' : '');
        } else {
            $summary['message'] = $r['message'];
        }

        self::remember($summary);
        return $summary;
    }
    /**
     * 单次 HTTP 请求（百度单次上限 100，实测配额通常更小，所以外层按 10 条切）
     * @return array{ok:bool,message:string,remain:int,sent:int,http:int}
     */
    private static function request(array $urls): array
    {
        $api = self::API . '?site=' . urlencode((string) parse_url(self::site(), PHP_URL_HOST))
            . '&token=' . urlencode(self::token());

        $ch = curl_init($api);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => implode("\n", $urls),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => ['Content-Type: text/plain'],
            CURLOPT_USERAGENT      => 'qiqi77/1.0',
        ]);
        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $fail = ['ok' => false, 'message' => '', 'remain' => -1, 'sent' => 0, 'http' => $http];

        if ($err !== '') {
            return ['message' => '请求失败：' . $err] + $fail;
        }

        $json = json_decode((string) $body, true);
        if (!is_array($json)) {
            return ['message' => '返回无法解析：' . mb_substr((string) $body, 0, 120)] + $fail;
        }

        if (isset($json['error'])) {
            $map = [
                400 => '配额已用完',
                401 => '准入密钥无效',
                403 => '站点未验证通过',
                404 => '接口地址不对',
            ];
            $code = (int) $json['error'];
            $msg = $map[$code] ?? '推送失败';
            if (isset($json['message'])) {
                $msg .= '（' . $json['message'] . '）';
            }
            return ['message' => $msg, 'remain' => 0] + $fail;
        }

        return [
            'ok'      => true,
            'message' => '成功推送 ' . (int) ($json['success'] ?? 0) . ' 条',
            'remain'  => (int) ($json['remain'] ?? -1),
            'sent'    => (int) ($json['success'] ?? 0),
            'http'    => $http,
        ];
    }
    /** 推送单个地址（发布新文章时调用） */
    public static function pushOne(string $url): array
    {
        /* 配额用完就别推了，免得把面板的"上次结果"刷成失败 */
        if (self::budget() <= 0) {
            return ['ok' => false, 'message' => '今日配额已用完，跳过', 'remain' => 0, 'sent' => 0, 'http' => 0];
        }
        return self::push([$url]);
    }

    private static function remember(array $r): void
    {
        try {
            $db = Db::get();
            $value = json_encode([
                'time'    => time(),
                'ok'      => (bool) $r['ok'],
                'message' => (string) $r['message'],
                'sent'    => (int) $r['sent'],
                'remain'  => (int) $r['remain'],
            ], JSON_UNESCAPED_UNICODE);

            $exists = $db->fetchRow($db->select('name')->from('table.options')->where('name = ?', self::LOG_KEY));
            if ($exists) {
                $db->query($db->update('table.options')->rows(['value' => $value])->where('name = ?', self::LOG_KEY));
            } else {
                $db->query($db->insert('table.options')->rows(['name' => self::LOG_KEY, 'user' => 0, 'value' => $value]));
            }
        } catch (\Throwable $e) {
            // 记录失败不影响推送结果
        }
    }

    public static function last(): array
    {
        try {
            $row = Db::get()->fetchRow(Db::get()->select('value')->from('table.options')->where('name = ?', self::LOG_KEY));
            $data = json_decode((string) ($row['value'] ?? ''), true);
            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** 发布钩子：只在新文章发布时推一条 */
    public static function onPublish($contents, $edit = null): void
    {
        if ((string) Options::get('qq_baidu_auto') !== 'on') {
            return;
        }
        $cid = (int) ($contents['cid'] ?? 0);
        if ($cid <= 0) {
            return;
        }
        $site = self::site();
        if ($site === '') {
            return;
        }
        self::pushOne($site . '/' . $cid);
    }
}

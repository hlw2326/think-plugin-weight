<?php

declare(strict_types=1);

namespace plugin\weight\service;

use Hlw\Collect\Dy;
use Hlw\Collect\Ks;
use plugin\weight\model\WeightQueryLog;
use Throwable;

/**
 * 查询平台账号数据并写入记录。
 */
class WeightQueryService
{
    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public static function query(array $data): array
    {
        $started = microtime(true);
        $platform = self::platform((string) ($data['platform'] ?? ''));
        $input = trim((string) ($data['input'] ?? ''));
        $ip = trim((string) ($data['ip'] ?? ''));

        if ($input === '') {
            $channel = trim((string) ($data['channel'] ?? 'auto')) ?: 'auto';
            $userAgent = trim((string) ($data['user_agent'] ?? ''));
            return self::failure($platform, $channel, $input, '请输入账号链接或分享文本', $started, $ip, $userAgent, $data);
        }

        if (isset(CookiesService::platforms()[$platform])) {
            $data = CookiesService::mergeQueryData($platform, $data);
        }

        $channel = trim((string) ($data['channel'] ?? 'auto')) ?: 'auto';
        $userAgent = trim((string) ($data['user_agent'] ?? ''));

        try {
            $payload = match ($platform) {
                'dy' => self::queryDouyin($input, $data),
                'ks' => self::queryKuaishou($input, $data),
                default => throw new \RuntimeException('当前平台暂未支持自动查询'),
            };

            $feedList = is_array($payload['feed_list'] ?? null) ? $payload['feed_list'] : [];
            $userInfo = is_array($payload['user_info'] ?? null) ? $payload['user_info'] : [];
            $userInfo = self::completeUserInfo($userInfo, $feedList, $platform);
            $analysis = WeightScoreService::analyze($userInfo, $feedList);
            $cookiesConfig = self::cookiesConfig($data);

            $logId = self::saveLog([
                ...self::baseLog($platform, (string) ($payload['channel'] ?? $channel), $input, $started, $ip, $userAgent, $data),
                ...self::accountFields($userInfo),
                'fan_count' => $analysis['fan_count'],
                'follow_count' => $analysis['follow_count'],
                'work_count' => $analysis['work_count'],
                'like_count' => $analysis['like_count'],
                'collect_count' => $analysis['collect_count'],
                'sample_feed_count' => $analysis['sample_feed_count'],
                'avg_like_count' => $analysis['avg_like_count'],
                'avg_comment_count' => $analysis['avg_comment_count'],
                'avg_share_count' => $analysis['avg_share_count'],
                'avg_collect_count' => $analysis['avg_collect_count'],
                'avg_play_count' => $analysis['avg_play_count'],
                'interaction_rate' => $analysis['interaction_rate'],
                'weight_score' => $analysis['score'],
                'weight_grade' => $analysis['grade'],
                'analysis_summary' => $analysis['summary'],
                'status' => WeightQueryLog::STATUS_SUCCESS,
                'fail_reason' => '',
                'raw_result' => self::json([
                    'user_info' => $userInfo,
                    'feed_list' => $feedList,
                    'feed_error' => $payload['feed_error'] ?? '',
                    'analysis' => $analysis,
                    'cookies_config' => $cookiesConfig,
                ]),
            ]);

            CookiesService::markResult((int) ($data['_cookies_id'] ?? 0), true);

            return ['state' => true, 'msg' => '查询成功', 'id' => $logId, 'analysis' => $analysis, 'cookies_config' => $cookiesConfig];
        } catch (Throwable $exception) {
            CookiesService::markResult((int) ($data['_cookies_id'] ?? 0), false, $exception->getMessage());
            return self::failure($platform, $channel, $input, $exception->getMessage(), $started, $ip, $userAgent, $data);
        }
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private static function queryDouyin(string $input, array $data): array
    {
        $options = self::sdkOptions($data);
        $channel = (string) ($data['channel'] ?? 'auto');
        $channel = in_array($channel, ['h5', 'web', 'live'], true) ? $channel : 'live';

        $userInfo = match ($channel) {
            'h5' => Dy::H5($options)->v1()->user->info($input)->toArray(),
            'web' => Dy::Web($options)->v1()->user->info($input)->toArray(),
            default => Dy::Live($options)->v1()->user->profile($input)->toArray(),
        };

        $feedList = [];
        $feedError = '';
        try {
            $feedList = Dy::Web($options)->v1()->aweme->post($input, ['count' => self::sampleCount($data, 18)])->toArray();
        } catch (Throwable $exception) {
            $feedError = $exception->getMessage();
        }

        return ['channel' => $channel, 'user_info' => $userInfo, 'feed_list' => $feedList, 'feed_error' => $feedError];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private static function queryKuaishou(string $input, array $data): array
    {
        $options = self::sdkOptions($data);
        if (!empty($data['did'])) {
            $options['did'] = (string) $data['did'];
        }
        $client = Ks::Mini($options)->v1();
        $userInfo = $client->user->info($input)->toArray();

        $feedList = [];
        $feedError = '';
        try {
            $feedList = $client->feed->list($input, ['count' => self::sampleCount($data, 12)])->toArray();
        } catch (Throwable $exception) {
            $feedError = $exception->getMessage();
        }

        return ['channel' => 'mini', 'user_info' => $userInfo, 'feed_list' => $feedList, 'feed_error' => $feedError];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private static function sdkOptions(array $data): array
    {
        $options = [];
        foreach (['cookies', 'user_agent', 'timeout'] as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                $target = $key === 'user_agent' ? 'userAgent' : $key;
                $options[$target] = $key === 'timeout' ? (int) $data[$key] : (string) $data[$key];
            }
        }

        $extra = CookiesService::paramsToArray($data['params'] ?? '');
        foreach (['headers', 'params', 'removeParams', 'body', 'bodyType'] as $key) {
            if (array_key_exists($key, $extra)) {
                $options[$key] = $extra[$key];
            }
            if (array_key_exists($key, $data)) {
                $options[$key] = $data[$key];
            }
        }

        return $options;
    }

    /**
     * @return array<string,mixed>
     */
    private static function accountFields(array $userInfo): array
    {
        return [
            'account_id' => (string) ($userInfo['user_id'] ?? ''),
            'display_id' => (string) ($userInfo['display_id'] ?? ''),
            'nickname' => (string) ($userInfo['nickname'] ?? ''),
            'avatar_url' => (string) ($userInfo['avatar_url'] ?? ''),
            'signature' => (string) ($userInfo['signature'] ?? ''),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function baseLog(string $platform, string $channel, string $input, float $started, string $ip, string $userAgent, array $data = []): array
    {
        return [
            'platform' => $platform,
            'channel' => $channel,
            'cookies_id' => (int) ($data['_cookies_id'] ?? 0),
            'cookies_name' => (string) ($data['_cookies_name'] ?? ''),
            'user_uid' => trim((string) ($data['user_uid'] ?? '')),
            'input' => $input,
            'exec_time' => max(0, (int) round((microtime(true) - $started) * 1000)),
            'ip' => $ip,
            'user_agent' => $userAgent,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function failure(string $platform, string $channel, string $input, string $reason, float $started, string $ip, string $userAgent, array $data = []): array
    {
        $logId = self::saveLog([
            ...self::baseLog($platform, $channel, $input, $started, $ip, $userAgent, $data),
            'status' => WeightQueryLog::STATUS_FAIL,
            'fail_reason' => $reason,
            'weight_score' => 0,
            'weight_grade' => 'D',
            'analysis_summary' => '',
            'raw_result' => self::json(['error' => $reason]),
        ]);

        return ['state' => false, 'msg' => $reason, 'id' => $logId];
    }

    /**
     * 返回本次查询实际使用的 Cookie 配置，方便前端展示和原始结果追踪。
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private static function cookiesConfig(array $data): array
    {
        return [
            'id' => (int) ($data['_cookies_id'] ?? 0),
            'name' => (string) ($data['_cookies_name'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    private static function saveLog(array $data): int
    {
        $log = WeightQueryLog::mk();
        $log->save($data);
        return (int) $log->id;
    }

    /**
     * 用户信息接口偶尔会返回空数据，作品列表里的 author 可作为基础账号资料兜底。
     *
     * @param array<string,mixed> $userInfo
     * @param array<int,array<string,mixed>> $feedList
     * @return array<string,mixed>
     */
    private static function completeUserInfo(array $userInfo, array $feedList, string $platform): array
    {
        $author = self::firstFeedAuthor($feedList);
        if ($author === []) {
            return $userInfo;
        }

        $total = is_array($userInfo['total'] ?? null) ? $userInfo['total'] : [];
        $feedTotals = self::feedTotals($feedList);

        return [
            'platform' => self::firstString($userInfo['platform'] ?? '', $platform),
            'type' => self::firstString($userInfo['type'] ?? '', 'user'),
            'user_id' => self::firstString($userInfo['user_id'] ?? '', $author['user_id'] ?? ''),
            'sec_user_id' => self::firstString($userInfo['sec_user_id'] ?? '', $author['sec_user_id'] ?? ''),
            'display_id' => self::firstString($userInfo['display_id'] ?? '', $author['display_id'] ?? ''),
            'nickname' => self::firstString($userInfo['nickname'] ?? '', $author['nickname'] ?? ''),
            'signature' => (string) ($userInfo['signature'] ?? ''),
            'avatar_url' => self::firstString($userInfo['avatar_url'] ?? '', $author['avatar_url'] ?? ''),
            'gender' => (int) ($userInfo['gender'] ?? 0) === 1 ? 1 : 0,
            'city' => (string) ($userInfo['city'] ?? ''),
            'total' => [
                'fan_count' => self::firstPositiveInt($total['fan_count'] ?? null, $total['follower_count'] ?? null),
                'follow_count' => self::firstPositiveInt($total['follow_count'] ?? null, $total['following_count'] ?? null),
                'work_count' => self::firstPositiveInt($total['work_count'] ?? null, $total['feed_count'] ?? null, count($feedList)),
                'like_count' => self::firstPositiveInt($total['like_count'] ?? null, $total['liked_count'] ?? null, $feedTotals['like_count']),
                'collect_count' => self::firstPositiveInt($total['collect_count'] ?? null, $total['collection_count'] ?? null, $feedTotals['collect_count']),
            ],
            'verified' => !empty($userInfo['verified']),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $feedList
     * @return array<string,mixed>
     */
    private static function firstFeedAuthor(array $feedList): array
    {
        foreach ($feedList as $feed) {
            $author = is_array($feed['author'] ?? null) ? $feed['author'] : [];
            foreach (['user_id', 'sec_user_id', 'display_id', 'nickname', 'avatar_url'] as $key) {
                if (trim((string) ($author[$key] ?? '')) !== '') {
                    return $author;
                }
            }
        }

        return [];
    }

    /**
     * @param array<int,array<string,mixed>> $feedList
     * @return array{like_count:int,collect_count:int}
     */
    private static function feedTotals(array $feedList): array
    {
        $likes = 0;
        $collects = 0;
        foreach ($feedList as $feed) {
            $total = is_array($feed['total'] ?? null) ? $feed['total'] : [];
            $likes += max(0, (int) ($total['like_count'] ?? 0));
            $collects += max(0, (int) ($total['collect_count'] ?? 0));
        }

        return ['like_count' => $likes, 'collect_count' => $collects];
    }

    private static function firstString(mixed ...$values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private static function firstPositiveInt(mixed ...$values): int
    {
        foreach ($values as $value) {
            $value = max(0, (int) $value);
            if ($value > 0) {
                return $value;
            }
        }

        return 0;
    }

    /**
     * 将查询原始结果统一整理成前端可解析的 JSON 字符串。
     *
     * 新记录会直接保存标准 JSON；旧记录如果曾经被截断或写入了普通文本，
     * 这里会兜底包成 JSON 对象，避免详情页解析失败。
     */
    public static function formatRawResult(string $json): string
    {
        $json = trim($json);
        if ($json === '') {
            return '{}';
        }

        $decoded = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return self::jsonPretty($decoded);
        }

        return self::jsonPretty([
            'notice' => '原始结果不是标准JSON，已按文本转换',
            'raw_text' => $json,
        ]);
    }

    private static function platform(string $platform): string
    {
        $platform = trim($platform);
        return isset(WeightQueryLog::getPlatforms()[$platform]) ? $platform : 'other';
    }

    /**
     * @param array<string,mixed> $data
     */
    private static function sampleCount(array $data, int $default): int
    {
        $count = (int) ($data['sample_count'] ?? $data['feed_count'] ?? $default);
        return max(0, min(50, $count));
    }

    /**
     * @param array<string,mixed> $data
     */
    private static function json(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($json)) {
            return $json;
        }

        return (string) json_encode([
            'error' => '原始结果JSON编码失败',
            'json_error' => json_last_error_msg(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function jsonPretty(mixed $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        return is_string($json) ? $json : '{}';
    }
}

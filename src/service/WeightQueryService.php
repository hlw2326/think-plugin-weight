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
        $channel = trim((string) ($data['channel'] ?? 'auto')) ?: 'auto';
        $ip = trim((string) ($data['ip'] ?? ''));
        $userAgent = trim((string) ($data['user_agent'] ?? ''));

        if ($input === '') {
            return self::failure($platform, $channel, $input, '请输入账号链接或分享文本', $started, $ip, $userAgent);
        }

        try {
            $payload = match ($platform) {
                'dy' => self::queryDouyin($input, $data),
                'ks' => self::queryKuaishou($input, $data),
                default => throw new \RuntimeException('当前平台暂未支持自动查询'),
            };

            $userInfo = is_array($payload['user_info'] ?? null) ? $payload['user_info'] : [];
            $feedList = is_array($payload['feed_list'] ?? null) ? $payload['feed_list'] : [];
            $analysis = WeightScoreService::analyze($userInfo, $feedList);

            $logId = self::saveLog([
                ...self::baseLog($platform, (string) ($payload['channel'] ?? $channel), $input, $started, $ip, $userAgent),
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
                ]),
            ]);

            return ['state' => true, 'msg' => '查询成功', 'id' => $logId, 'analysis' => $analysis];
        } catch (Throwable $exception) {
            return self::failure($platform, $channel, $input, $exception->getMessage(), $started, $ip, $userAgent);
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
    private static function baseLog(string $platform, string $channel, string $input, float $started, string $ip, string $userAgent): array
    {
        return [
            'platform' => $platform,
            'channel' => $channel,
            'input' => $input,
            'exec_time' => max(0, (int) round((microtime(true) - $started) * 1000)),
            'ip' => $ip,
            'user_agent' => $userAgent,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function failure(string $platform, string $channel, string $input, string $reason, float $started, string $ip, string $userAgent): array
    {
        $logId = self::saveLog([
            ...self::baseLog($platform, $channel, $input, $started, $ip, $userAgent),
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
     * @param array<string,mixed> $data
     */
    private static function saveLog(array $data): int
    {
        $log = WeightQueryLog::mk();
        $log->save($data);
        return (int) $log->id;
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
        $json = is_string($json) ? $json : '{}';
        if (function_exists('mb_substr')) {
            return mb_substr($json, 0, 60000);
        }
        return substr($json, 0, 60000);
    }
}

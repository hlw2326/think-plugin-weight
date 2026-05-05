<?php

declare(strict_types=1);

namespace plugin\weight\service;

use InvalidArgumentException;
use plugin\weight\model\WeightCookies;
use Throwable;

/**
 * 平台请求 Cookie 配置服务。
 *
 * 作用：
 * - 保存和读取平台 Cookie 配置池中的 Cookie、UA、DID 和扩展参数。
 * - 支持抖音按 h5/web/live 分渠道配置，其他平台按 web/app/mini/default 扩展。
 * - 查询时用后台默认配置补齐表单未填写的参数。
 * - 数据库表不存在或未配置时，兼容旧 sysconf 配置作为兜底。
 */
class CookiesService
{
    /**
     * @return array<string,array{label:string,class:string}>
     */
    public static function platforms(): array
    {
        return [
            'dy' => ['label' => '抖音', 'class' => 'layui-bg-black'],
            'ks' => ['label' => '快手', 'class' => 'layui-bg-orange'],
            'xhs' => ['label' => '小红书', 'class' => 'layui-bg-red'],
            'bili' => ['label' => 'B站', 'class' => 'layui-bg-cyan'],
            'wb' => ['label' => '微博', 'class' => 'layui-bg-red'],
            'sph' => ['label' => '视频号', 'class' => 'layui-bg-green'],
            'tk' => ['label' => 'TikTok', 'class' => 'layui-bg-blue'],
            'other' => ['label' => '其他', 'class' => 'layui-bg-gray'],
        ];
    }

    /**
     * @return array<string,array{label:string,class:string}>
     */
    public static function channels(): array
    {
        return [
            'default' => ['label' => '默认', 'class' => 'layui-bg-gray'],
            'web' => ['label' => '网页', 'class' => 'layui-bg-blue'],
            'h5' => ['label' => 'H5', 'class' => 'layui-bg-cyan'],
            'live' => ['label' => '直播', 'class' => 'layui-bg-green'],
            'app' => ['label' => 'App', 'class' => 'layui-bg-orange'],
            'mini' => ['label' => '小程序', 'class' => 'layui-bg-orange'],
        ];
    }

    /**
     * 读取后台保存的 Cookie 配置。
     *
     * @return array<string,mixed>
     */
    public static function config(string $platform, string $channel = ''): array
    {
        $platform = self::platform($platform);
        $channel = self::queryChannel($channel);
        $dbConfig = self::readConfigFromDatabase($platform, $channel);

        if ($dbConfig !== []) {
            return self::configFromArray($platform, $dbConfig);
        }

        return self::legacyConfig($platform, $channel);
    }

    /**
     * 按 ID 读取一条启用中的 Cookie 配置。
     *
     * 传入平台时会同时校验平台，避免查询抖音时误用快手 Cookie。
     *
     * @return array<string,mixed>
     */
    public static function configById(int $id, string $platform = ''): array
    {
        if ($id <= 0) {
            return [];
        }

        try {
            $query = WeightCookies::mk()->where('id', $id)->where('status', 1);
            $platform = trim($platform);
            if ($platform !== '' && array_key_exists($platform, self::platforms())) {
                $query->where('platform', $platform);
            }

            $row = $query->findOrEmpty();
            if (!self::modelExists($row)) {
                return [];
            }

            $data = self::modelToArray($row);
            return self::configFromArray((string) ($data['platform'] ?? $platform), $data);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * 归一化 Cookie 配置。
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public static function configFromArray(string $platform, array $data): array
    {
        $platform = self::platform($platform);
        $channel = self::channel($platform, (string) ($data['channel'] ?? self::defaultChannel($platform)));
        $params = self::normalizeParamsText($data['params'] ?? '');

        $config = [
            'id' => (int) ($data['id'] ?? 0),
            'name' => trim((string) ($data['name'] ?? self::defaultName($platform, $channel))),
            'platform' => $platform,
            'label' => self::platforms()[$platform]['label'],
            'channel' => $channel,
            'channel_label' => self::channels()[$channel]['label'],
            'cookies' => trim((string) ($data['cookies'] ?? '')),
            'user_agent' => trim((string) ($data['user_agent'] ?? '')),
            'did' => trim((string) ($data['did'] ?? '')),
            'params' => $params,
            'params_array' => self::paramsToArray($params),
            'timeout' => self::intRange($data['timeout'] ?? 10000, 1000, 60000, 10000),
            'sample_count' => self::intRange($data['sample_count'] ?? 12, 0, 50, 12),
            'is_default' => (int) !empty($data['is_default']),
            'sort' => self::intRange($data['sort'] ?? 0, 0, 999999999, 0),
            'status' => self::status($data['status'] ?? 1),
            'expired_at' => trim((string) ($data['expired_at'] ?? '')),
            'last_error' => trim((string) ($data['last_error'] ?? '')),
            'remark' => trim((string) ($data['remark'] ?? '')),
        ];

        return $config;
    }

    /**
     * 保存某个平台的默认请求配置。
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public static function saveDefault(string $platform, array $data): array
    {
        $config = self::configFromArray($platform, [
            ...$data,
            'is_default' => 1,
            'status' => 1,
        ]);
        self::assertValidParams((string) $config['params']);

        $row = WeightCookies::mk()
            ->where('platform', $config['platform'])
            ->where('channel', $config['channel'])
            ->order('is_default desc,id desc')
            ->findOrEmpty();

        $id = self::modelExists($row) ? (int) $row->id : 0;
        self::clearDefault((string) $config['platform'], $id);

        $payload = self::databaseFields($config);
        $payload['is_default'] = 1;
        $payload['status'] = 1;

        if ($id > 0) {
            $row->save($payload);
            $config['id'] = $id;
        } else {
            $model = WeightCookies::mk();
            $model->save($payload);
            $config['id'] = (int) $model->id;
        }

        return $config;
    }

    /**
     * 查询时合并表单参数和后台默认配置。
     *
     * 表单里填了就用表单值；表单为空时，用后台配置兜底。
     *
     * @param array<string,mixed> $data
     * @param array<string,mixed>|null $defaults
     * @return array<string,mixed>
     */
    public static function mergeQueryData(string $platform, array $data, ?array $defaults = null): array
    {
        $platform = self::platform($platform);
        $requestedChannel = trim((string) ($data['channel'] ?? ''));
        if ($defaults === null) {
            $selected = self::configById((int) ($data['cookies_id'] ?? 0), $platform);
            $defaults = $selected !== [] ? $selected : self::config($platform, $requestedChannel);
        } else {
            $defaults = self::configFromArray($platform, $defaults);
        }

        foreach (['cookies', 'user_agent', 'timeout', 'sample_count', 'channel', 'did'] as $key) {
            if (!array_key_exists($key, $defaults)) {
                continue;
            }
            $value = array_key_exists($key, $data) ? trim((string) $data[$key]) : '';
            $shouldUseDefault = $value === '' || ($key === 'channel' && $value === 'auto');
            if ($shouldUseDefault) {
                $data[$key] = $defaults[$key];
            }
        }

        if (isset($data['timeout'])) {
            $data['timeout'] = self::intRange($data['timeout'], 1000, 60000, (int) $defaults['timeout']);
        }
        if (isset($data['sample_count'])) {
            $data['sample_count'] = self::intRange($data['sample_count'], 0, 50, (int) $defaults['sample_count']);
        }

        if ((int) ($defaults['id'] ?? 0) > 0) {
            $data['_cookies_id'] = (int) $defaults['id'];
            $data['_cookies_name'] = (string) ($defaults['name'] ?? '');
        }

        $data = self::mergeExtraParams($data, is_array($defaults['params_array'] ?? null) ? $defaults['params_array'] : []);

        return $data;
    }

    /**
     * 标记 Cookie 配置的使用结果。
     */
    public static function markResult(int $id, bool $success, string $error = ''): void
    {
        if ($id <= 0) {
            return;
        }

        try {
            $row = WeightCookies::mk()->where('id', $id)->findOrEmpty();
            if (!self::modelExists($row)) {
                return;
            }

            $field = $success ? 'success_count' : 'fail_count';
            $row->save([
                $field => (int) ($row->{$field} ?? 0) + 1,
                'last_used_at' => date('Y-m-d H:i:s'),
                'last_check_at' => date('Y-m-d H:i:s'),
                'last_error' => $success ? '' : self::limitText($error, 1000),
            ]);
        } catch (Throwable) {
            return;
        }
    }

    /**
     * 将扩展参数 JSON 转成数组。
     *
     * @return array<string,mixed>
     */
    public static function paramsToArray(mixed $params): array
    {
        if (is_array($params)) {
            return $params;
        }

        $params = trim((string) $params);
        if ($params === '') {
            return [];
        }

        $decoded = json_decode($params, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 校验扩展参数是否为 JSON 对象。
     */
    public static function assertValidParams(string $params): void
    {
        $params = trim($params);
        if ($params === '') {
            return;
        }

        $decoded = json_decode($params, true);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new InvalidArgumentException('扩展参数必须是 JSON 对象');
        }
    }

    private static function platform(string $platform): string
    {
        return array_key_exists($platform, self::platforms()) ? $platform : 'dy';
    }

    private static function channel(string $platform, string $channel): string
    {
        $channel = trim($channel);
        if ($channel === '' || $channel === 'auto') {
            return self::defaultChannel($platform);
        }

        return array_key_exists($channel, self::channels()) ? $channel : self::defaultChannel($platform);
    }

    private static function queryChannel(string $channel): string
    {
        $channel = trim($channel);
        if ($channel === '' || $channel === 'auto') {
            return '';
        }

        return array_key_exists($channel, self::channels()) ? $channel : '';
    }

    private static function defaultChannel(string $platform): string
    {
        return match ($platform) {
            'dy' => 'live',
            'ks' => 'mini',
            'xhs', 'bili', 'wb', 'sph', 'tk' => 'web',
            default => 'default',
        };
    }

    private static function defaultName(string $platform, string $channel): string
    {
        $platformLabel = self::platforms()[$platform]['label'] ?? $platform;
        $channelLabel = self::channels()[$channel]['label'] ?? $channel;
        return "{$platformLabel}{$channelLabel}配置";
    }

    private static function conf(string $key, string $default = ''): string
    {
        if (!function_exists('sysconf')) {
            return $default;
        }
        $value = sysconf($key);
        return $value === '' || $value === null ? $default : (string) $value;
    }

    private static function intRange(mixed $value, int $min, int $max, int $default): int
    {
        $value = is_numeric($value) ? (int) $value : $default;
        return max($min, min($max, $value));
    }

    private static function status(mixed $value): int
    {
        return (int) $value === 0 ? 0 : 1;
    }

    /**
     * @return array<string,mixed>
     */
    private static function legacyConfig(string $platform, string $channel = ''): array
    {
        $data = [
            'platform' => $platform,
            'channel' => $channel !== '' ? $channel : self::defaultChannel($platform),
            'cookies' => self::conf("weight.{$platform}_cookies"),
            'user_agent' => self::conf("weight.{$platform}_user_agent"),
            'timeout' => self::conf("weight.{$platform}_timeout", '10000'),
            'sample_count' => self::conf("weight.{$platform}_sample_count", '12'),
            'is_default' => 1,
            'status' => 1,
        ];

        if ($platform === 'dy' && $channel === '') {
            $data['channel'] = self::conf('weight.dy_channel', 'live');
        }
        if ($platform === 'ks') {
            $data['did'] = self::conf('weight.ks_did');
        }

        return self::configFromArray($platform, $data);
    }

    /**
     * @return array<string,mixed>
     */
    private static function readConfigFromDatabase(string $platform, string $channel = ''): array
    {
        try {
            if ($channel !== '') {
                $row = WeightCookies::mk()
                    ->where('platform', $platform)
                    ->where('channel', $channel)
                    ->where('status', 1)
                    ->order('is_default desc,sort desc,id desc')
                    ->findOrEmpty();
                if (self::modelExists($row)) {
                    return self::modelToArray($row);
                }
            }

            $row = WeightCookies::mk()
                ->where('platform', $platform)
                ->where('status', 1)
                ->order('is_default desc,sort desc,id desc')
                ->findOrEmpty();

            return self::modelExists($row) ? self::modelToArray($row) : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    private static function databaseFields(array $config): array
    {
        return [
            'name' => (string) $config['name'],
            'platform' => (string) $config['platform'],
            'channel' => (string) $config['channel'],
            'cookies' => (string) $config['cookies'],
            'user_agent' => (string) $config['user_agent'],
            'did' => (string) $config['did'],
            'params' => (string) $config['params'],
            'timeout' => (int) $config['timeout'],
            'sample_count' => (int) $config['sample_count'],
            'is_default' => (int) $config['is_default'],
            'sort' => (int) $config['sort'],
            'status' => (int) $config['status'],
            'expired_at' => (string) $config['expired_at'] !== '' ? (string) $config['expired_at'] : null,
            'remark' => (string) $config['remark'],
        ];
    }

    private static function clearDefault(string $platform, int $exceptId = 0): void
    {
        $query = WeightCookies::mk()->where('platform', $platform);
        if ($exceptId > 0) {
            $query->where('id', '<>', $exceptId);
        }
        $query->update(['is_default' => 0]);
    }

    private static function normalizeParamsText(mixed $params): string
    {
        if (is_array($params)) {
            $json = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return is_string($json) ? $json : '';
        }

        return trim((string) $params);
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
     */
    private static function mergeExtraParams(array $data, array $extra): array
    {
        foreach ($extra as $key => $value) {
            if (in_array($key, ['headers', 'params'], true) && is_array($value)) {
                $current = is_array($data[$key] ?? null) ? $data[$key] : [];
                $data[$key] = array_replace_recursive($value, $current);
                continue;
            }

            $current = $data[$key] ?? null;
            if ($current === null || $current === '' || $current === []) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * @return array<string,mixed>
     */
    private static function modelToArray(mixed $model): array
    {
        if (is_object($model) && method_exists($model, 'toArray')) {
            $array = $model->toArray();
            return is_array($array) ? $array : [];
        }

        return is_array($model) ? $model : [];
    }

    private static function modelExists(mixed $model): bool
    {
        return is_object($model) && method_exists($model, 'isExists') && $model->isExists();
    }

    private static function limitText(string $value, int $limit): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $limit);
        }
        return substr($value, 0, $limit);
    }
}

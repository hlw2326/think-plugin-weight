<?php

declare(strict_types=1);

namespace plugin\weight\model;

use think\admin\Model;

/**
 * 平台请求配置池
 *
 * 作用：
 * - 保存抖音、快手、小红书等平台的 Cookie、UA、DID 和扩展参数
 * - 支持同一平台按不同渠道配置，例如抖音 h5/web/live
 * - 查询账号权重时，服务层会从这里取可用配置补齐请求参数
 *
 * @property int $id
 * @property string $name
 * @property string $platform
 * @property string $channel
 * @property string $cookies
 * @property string $user_agent
 * @property string $did
 * @property string $params
 * @property int $timeout
 * @property int $sample_count
 * @property int $is_default
 * @property int $sort
 * @property int $status
 * @property int $success_count
 * @property int $fail_count
 * @property string $last_used_at
 * @property string $last_check_at
 * @property string $expired_at
 * @property string $last_error
 * @property string $remark
 * @property string $create_at
 * @property string $update_at
 * @class WeightCookies
 * @package plugin\weight\model
 */
class WeightCookies extends Model
{
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    /**
     * 平台枚举
     *
     * @return array<string,array{label:string,class:string}>
     */
    public static function getPlatforms(): array
    {
        return WeightQueryLog::getPlatforms();
    }

    /**
     * 渠道枚举
     *
     * @return array<string,array{label:string,class:string}>
     */
    public static function getChannels(): array
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
     * 状态枚举
     *
     * @return array<int,array{label:string,class:string}>
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DISABLED => ['label' => '禁用', 'class' => 'layui-bg-gray'],
            self::STATUS_ENABLED => ['label' => '启用', 'class' => 'layui-bg-green'],
        ];
    }
}

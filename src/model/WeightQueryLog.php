<?php

declare(strict_types=1);

namespace plugin\weight\model;

use think\admin\Model;

/**
 * 账号权重查询记录。
 *
 * @property int $id
 * @property string $platform
 * @property string $channel
 * @property int $cookies_id
 * @property string $cookies_name
 * @property string $user_uid
 * @property string $input
 * @property string $account_id
 * @property string $display_id
 * @property string $nickname
 * @property int $fan_count
 * @property int $follow_count
 * @property int $work_count
 * @property int $like_count
 * @property int $collect_count
 * @property int $sample_feed_count
 * @property int $avg_like_count
 * @property int $avg_comment_count
 * @property int $avg_share_count
 * @property int $avg_collect_count
 * @property int $avg_play_count
 * @property int $weight_score
 * @property string $weight_grade
 * @property int $status
 * @property string $create_at
 * @class WeightQueryLog
 * @package plugin\weight\model
 */
class WeightQueryLog extends Model
{
    public const STATUS_FAIL = 0;
    public const STATUS_SUCCESS = 1;

    public static function getPlatforms(): array
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

    public static function getChannels(): array
    {
        return [
            'auto' => ['label' => '自动', 'class' => 'layui-bg-gray'],
            'h5' => ['label' => 'H5', 'class' => 'layui-bg-cyan'],
            'web' => ['label' => '网页', 'class' => 'layui-bg-blue'],
            'live' => ['label' => '直播', 'class' => 'layui-bg-green'],
            'mini' => ['label' => '小程序', 'class' => 'layui-bg-orange'],
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_FAIL => ['label' => '失败', 'class' => 'layui-bg-red'],
            self::STATUS_SUCCESS => ['label' => '成功', 'class' => 'layui-bg-green'],
        ];
    }

    public static function getGrades(): array
    {
        return [
            'S' => ['label' => 'S', 'class' => 'layui-bg-red'],
            'A' => ['label' => 'A', 'class' => 'layui-bg-orange'],
            'B' => ['label' => 'B', 'class' => 'layui-bg-blue'],
            'C' => ['label' => 'C', 'class' => 'layui-bg-cyan'],
            'D' => ['label' => 'D', 'class' => 'layui-bg-gray'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace plugin\weight\model;

use think\admin\Model;

/**
 * 账号标签库
 *
 * 作用：
 * - 保存账号标签的展示名称、图标和扩展值
 * - value 字段按文本保存标签配置，可由业务层按 JSON 或普通文本解析
 *
 * @property int $id
 * @property string $icon
 * @property string $title
 * @property string $value
 * @property int $status
 * @property int $sort
 * @property string $create_at
 * @class WeightTags
 * @package plugin\weight\model
 */
class WeightTags extends Model
{
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

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

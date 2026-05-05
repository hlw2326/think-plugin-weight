<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 用户ID字段。
 *
 * 来源：用户信息 user_id。
 * 作用：记录平台侧账号主 ID，用于后续排查、去重和关联查询。
 */
class UserId extends MetadataField
{
    public function key(): string
    {
        return 'user_id';
    }

    public function label(): string
    {
        return '用户ID';
    }

    public function value(): string
    {
        return $this->stringValue();
    }

    public function tips(): array
    {
        $userId = $this->value();
        if ($userId === '') return ['用户ID为空，账号去重和关联查询会受影响'];
        if (mb_strlen($userId) < 3) return ['用户ID偏短，建议检查 SDK 返回字段'];
        if (preg_match('/^0+$/', $userId) === 1) return ['用户ID全为 0，建议检查 SDK 返回字段'];
        return ['用户ID已获取，便于账号去重和关联查询'];
    }
}

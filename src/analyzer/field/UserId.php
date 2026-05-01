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
}

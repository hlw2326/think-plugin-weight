<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 头像字段。
 *
 * 来源：用户信息 avatar_url。
 * 作用：检测账号是否有头像地址，头像完整度会影响账号基础可信度判断。
 */
class AvatarUrl extends MetadataField
{
    public function key(): string
    {
        return 'avatar_url';
    }

    public function label(): string
    {
        return '头像';
    }

    public function value(): string
    {
        return $this->stringValue();
    }

    public function messages(): array
    {
        return [$this->value() === '' ? '头像地址为空' : '头像地址已获取'];
    }
}

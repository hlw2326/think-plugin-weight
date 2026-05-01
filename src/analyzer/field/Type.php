<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 数据类型字段。
 *
 * 来源：用户信息 type。
 * 作用：确认当前 SDK 返回的是 user 类型，避免把作品数据误当账号数据分析。
 */
class Type extends MetadataField
{
    public function key(): string
    {
        return 'type';
    }

    public function label(): string
    {
        return '类型';
    }

    public function value(): string
    {
        return $this->stringValue();
    }

    public function messages(): array
    {
        if ($this->value() === 'user') {
            return ['类型字段已获取'];
        }
        return ['类型不是 user，请检查 SDK 返回结构'];
    }
}

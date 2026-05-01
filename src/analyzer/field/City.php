<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 城市字段。
 *
 * 来源：用户信息 city。
 * 作用：记录账号所在地信息，用于后续判断账号地域画像。
 */
class City extends MetadataField
{
    public function key(): string
    {
        return 'city';
    }

    public function label(): string
    {
        return '城市';
    }

    public function value(): string
    {
        return $this->stringValue();
    }
}

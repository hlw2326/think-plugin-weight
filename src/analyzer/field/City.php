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

    public function tips(): array
    {
        $city = $this->value();
        if ($city === '') return ['城市为空，地域画像维度不足'];
        if (in_array($city, ['未知', '保密', '其他'], true)) return ['城市信息不明确，地域画像参考价值偏弱'];
        if (preg_match('/[\p{N}@#￥$%^&*_=+<>]/u', $city) === 1) return ['城市字段包含异常字符，建议检查 SDK 返回字段'];
        return ['城市信息已获取，有助于判断账号地域画像'];
    }
}

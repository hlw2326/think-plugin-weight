<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 昵称字段。
 *
 * 来源：用户信息 nickname。
 * 作用：检查账号名称是否完整，昵称为空或过短会影响账号识别度。
 */
class Nickname extends AbstractField
{
    public function key(): string
    {
        return 'nickname';
    }

    public function label(): string
    {
        return '昵称';
    }

    public function value(): string
    {
        return $this->stringValue();
    }

    public function messages(): array
    {
        if ($this->value() === '') return ['昵称为空，账号识别度较弱'];
        if (mb_strlen($this->value()) < 2) return ['昵称过短，建议使用更明确的账号名称'];
        return ['昵称完整，有利于账号识别'];
    }
}

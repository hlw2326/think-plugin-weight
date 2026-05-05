<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 认证状态字段。
 *
 * 来源：用户信息 verified。
 * 作用：判断账号是否认证，认证账号可信度更高，会获得额外权重加分。
 */
class Verified extends AbstractField
{
    public function key(): string
    {
        return 'verified';
    }

    public function label(): string
    {
        return '认证状态';
    }

    public function value(): bool
    {
        return (bool) $this->rawValue;
    }

    public function tips(): array
    {
        return [$this->value() ? '账号已认证，可信度更高' : '账号未认证，可信度加分不足'];
    }
}

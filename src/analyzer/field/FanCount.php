<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 粉丝数字段。
 *
 * 来源：用户信息 total.fan_count。
 * 作用：衡量账号基础规模，是账号权重中占比最高的基础指标。
 */
class FanCount extends AbstractField
{
    public function key(): string
    {
        return 'fan_count';
    }

    public function label(): string
    {
        return '粉丝数';
    }

    public function value(): int
    {
        return $this->intValue();
    }

    public function messages(): array
    {
        if ($this->value() < 1000) return ['粉丝规模较小，账号权重基础偏弱'];
        if ($this->value() < 10000) return ['粉丝规模处于起步阶段'];
        if ($this->value() < 100000) return ['粉丝规模较稳定，具备基础影响力'];
        return ['粉丝规模较高，账号基础权重较强'];
    }
}

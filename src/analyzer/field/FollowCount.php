<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 关注数字段。
 *
 * 来源：用户信息 total.follow_count。
 * 作用：辅助判断账号关系是否过杂，关注数过高会降低账号画像清晰度。
 */
class FollowCount extends AbstractField
{
    public function key(): string
    {
        return 'follow_count';
    }

    public function label(): string
    {
        return '关注数';
    }

    public function value(): int
    {
        return $this->intValue();
    }

    public function tips(): array
    {
        if ($this->value() < 1) return ['关注数为 0，账号关系链较少'];
        if ($this->value() > 5000) return ['关注数偏高，可能影响账号画像纯度'];
        if ($this->value() > 1000) return ['关注数略高，建议保持账号关系清晰'];
        return ['关注数处于正常范围'];
    }
}

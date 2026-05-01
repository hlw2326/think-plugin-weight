<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 获赞数字段。
 *
 * 来源：用户信息 total.like_count。
 * 作用：衡量账号历史内容累计反馈，反映内容长期表现。
 */
class LikeCount extends AbstractField
{
    public function key(): string
    {
        return 'like_count';
    }

    public function label(): string
    {
        return '获赞数';
    }

    public function value(): int
    {
        return $this->intValue();
    }

    public function messages(): array
    {
        if ($this->value() < 1000) return ['获赞数偏低，内容互动基础较弱'];
        if ($this->value() < 50000) return ['获赞数处于成长阶段'];
        return ['获赞数较高，内容历史表现较好'];
    }
}

<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 收藏数字段。
 *
 * 来源：用户信息 total.collect_count。
 * 作用：衡量账号历史内容被收藏的沉淀能力，收藏越高通常代表内容长期价值越强。
 */
class CollectCount extends AbstractField
{
    public function key(): string
    {
        return 'collect_count';
    }

    public function label(): string
    {
        return '收藏数';
    }

    public function value(): int
    {
        return $this->intValue();
    }

    public function tips(): array
    {
        if ($this->value() < 1) return ['收藏数为空或为 0，内容长期沉淀不足'];
        if ($this->value() < 100) return ['收藏数偏低，内容长期沉淀不足'];
        if ($this->value() < 10000) return ['收藏数处于成长阶段'];
        return ['收藏数较高，内容沉淀表现较好'];
    }
}

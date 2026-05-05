<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 作品数字段。
 *
 * 来源：用户信息 total.work_count。
 * 作用：衡量账号内容沉淀程度，作品过少时账号权重基础会偏弱。
 */
class WorkCount extends AbstractField
{
    public function key(): string
    {
        return 'work_count';
    }

    public function label(): string
    {
        return '作品数';
    }

    public function value(): int
    {
        return $this->intValue();
    }

    public function tips(): array
    {
        if ($this->value() < 1) return ['作品数为空或为 0，账号内容沉淀不足'];
        if ($this->value() < 10) return ['作品数量较少，账号内容沉淀不足'];
        if ($this->value() < 50) return ['作品数量正常，仍有提升空间'];
        return ['作品数量较充足，内容沉淀较好'];
    }
}

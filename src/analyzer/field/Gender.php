<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 性别字段。
 *
 * 来源：用户信息 gender。
 * 作用：保留 SDK 返回的性别值，用于后续账号画像分析。
 */
class Gender extends MetadataField
{
    public function key(): string
    {
        return 'gender';
    }

    public function label(): string
    {
        return '性别';
    }

    public function value(): int
    {
        return max(0, (int) $this->rawValue);
    }

    protected function isFilled(): bool
    {
        return in_array($this->value(), [0, 1], true);
    }
}

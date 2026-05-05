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

    public function tips(): array
    {
        $gender = $this->value();
        if ($gender === 0) return ['性别未知，账号画像维度不足'];
        if (in_array($gender, [1, 2], true)) return ['性别字段已获取，账号画像更完整'];
        return ['性别值不在常见范围，建议检查 SDK 返回字段'];
    }

    protected function isFilled(): bool
    {
        return in_array($this->value(), [0, 1], true);
    }
}

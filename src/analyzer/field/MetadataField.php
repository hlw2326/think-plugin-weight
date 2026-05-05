<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 账号基础资料字段基类。
 *
 * 作用：
 * - 用于 platform、user_id、avatar_url、city 等基础字段。
 * - 这些字段主要负责展示和完整性检测，不直接参与权重分累加。
 * - 字段为空时通过中文提示说明，方便发现 SDK 返回缺字段的问题。
 */
abstract class MetadataField extends AbstractField
{
    public function tips(): array
    {
        return [$this->isFilled() ? $this->label() . '已获取' : $this->label() . '为空'];
    }

    protected function isFilled(): bool
    {
        $value = $this->value();
        if (is_string($value)) {
            return trim($value) !== '';
        }
        return $value !== null;
    }
}

<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 签名字段。
 *
 * 来源：用户信息 signature。
 * 作用：检查账号简介是否能表达定位和服务内容，过短或过长都会扣分。
 */
class Signature extends AbstractField
{
    public function key(): string
    {
        return 'signature';
    }

    public function label(): string
    {
        return '签名';
    }

    public function value(): string
    {
        return $this->stringValue();
    }

    public function messages(): array
    {
        $length = mb_strlen($this->value());
        if ($length < 1) return ['签名为空，建议补充账号定位和服务内容'];
        if ($length < 10) return ['签名偏短，账号定位表达不够充分'];
        if ($length > 160) return ['签名偏长，建议压缩核心卖点'];
        return ['签名完整，账号定位表达清晰'];
    }
}

<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 加密用户ID字段。
 *
 * 来源：用户信息 sec_user_id。
 * 作用：记录平台侧加密账号 ID，常用于抖音等平台的后续接口请求。
 */
class SecUserId extends MetadataField
{
    public function key(): string
    {
        return 'sec_user_id';
    }

    public function label(): string
    {
        return '加密用户ID';
    }

    public function value(): string
    {
        return $this->stringValue();
    }

    public function tips(): array
    {
        $secUserId = $this->value();
        if ($secUserId === '') return ['加密用户ID为空，后续接口请求可能受限'];
        if (mb_strlen($secUserId) < 8) return ['加密用户ID偏短，建议检查 SDK 返回字段'];
        if (preg_match('/\s/u', $secUserId) === 1) return ['加密用户ID包含空格，建议检查 SDK 返回字段'];
        return ['加密用户ID已获取，便于后续接口请求'];
    }
}

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
}

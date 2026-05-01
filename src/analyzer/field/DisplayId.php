<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 展示账号ID字段。
 *
 * 来源：用户信息 display_id。
 * 作用：记录用户对外展示的账号 ID，方便后台人工识别账号。
 */
class DisplayId extends MetadataField
{
    public function key(): string
    {
        return 'display_id';
    }

    public function label(): string
    {
        return '展示账号ID';
    }

    public function value(): string
    {
        return $this->stringValue();
    }
}

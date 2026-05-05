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

    public function tips(): array
    {
        $displayId = $this->value();
        if ($displayId === '') return ['展示账号ID为空，后台人工识别困难'];
        if (mb_strlen($displayId) < 3) return ['展示账号ID偏短，后台识别度偏弱'];
        if (preg_match('/\s/u', $displayId) === 1) return ['展示账号ID包含空格，建议检查账号展示字段'];
        if (preg_match('/^\p{N}+$/u', $displayId) === 1) return ['展示账号ID全是数字，人工识别度偏弱'];
        return ['展示账号ID已获取，便于后台识别账号'];
    }
}

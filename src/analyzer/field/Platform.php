<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 平台字段。
 *
 * 来源：用户信息 platform。
 * 作用：记录账号来自哪个平台，目前 SDK 标准值为 dy、ks。
 */
class Platform extends MetadataField
{
    public function key(): string
    {
        return 'platform';
    }

    public function label(): string
    {
        return '平台';
    }

    public function value(): string
    {
        return $this->stringValue();
    }

    public function tips(): array
    {
        $platform = $this->value();
        if ($platform === '') {
            return ['平台为空，无法判断账号来源'];
        }
        if (!in_array($platform, ['dy', 'ks'], true)) {
            return ['平台不在 SDK 标准范围内'];
        }
        return [$platform === 'dy' ? '平台为抖音，账号来源已确认' : '平台为快手，账号来源已确认'];
    }
}

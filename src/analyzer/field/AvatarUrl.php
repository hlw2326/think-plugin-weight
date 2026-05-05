<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 头像字段。
 *
 * 来源：用户信息 avatar_url。
 * 作用：检测账号是否有头像地址，头像完整度会影响账号基础可信度判断。
 */
class AvatarUrl extends MetadataField
{
    public function key(): string
    {
        return 'avatar_url';
    }

    public function label(): string
    {
        return '头像';
    }

    public function value(): string
    {
        return $this->stringValue();
    }

    public function tips(): array
    {
        $url = $this->value();
        if ($url === '') return ['头像地址为空，账号基础资料不完整'];
        if (!filter_var($url, FILTER_VALIDATE_URL)) return ['头像地址格式无效，建议检查 SDK 返回字段'];
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) return ['头像地址协议异常，建议使用 http 或 https 链接'];
        return ['头像地址已获取，账号基础资料较完整'];
    }
}

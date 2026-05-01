<?php

declare(strict_types=1);

namespace plugin\weight;

use think\admin\Plugin;

/**
 * 账号权重插件。
 *
 * @class Service
 * @package plugin\weight
 */
class Service extends Plugin
{
    /**
     * 插件显示名称。
     *
     * @var string
     */
    protected $appName = '账号权重';

    /**
     * Composer 安装包名。
     *
     * @var string
     */
    protected $package = 'hlw2326/think-plugin-weight';

    /**
     * 注册模块菜单。
     */
    public static function menu(): array
    {
        $code = app(static::class)->appCode;

        return [
            [
                'name' => 'AI模型配置',
                'icon' => 'layui-icon layui-icon-set',
                'node' => "{$code}/config.index/index",
            ],
            [
                'name' => '查询记录',
                'icon' => 'layui-icon layui-icon-search',
                'node' => "{$code}/query.log/index",
            ],
            [
                'name' => '数据概览',
                'icon' => 'layui-icon layui-icon-chart-screen',
                'node' => "{$code}/main/index",
            ],
        ];
    }
}

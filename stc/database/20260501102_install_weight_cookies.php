<?php

declare(strict_types=1);

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

/**
 * 创建表：weight_cookies（平台请求 Cookie 配置池）。
 *
 * 作用：
 * - 记录各个平台、各个渠道请求账号数据时使用的 Cookie、UA、DID。
 * - 用 params 保存平台特殊参数，例如 headers、query params、msToken、a_bogus 等。
 * - 支持抖音按 h5/web/live 分开配置，其他平台可按 web/app/mini/default 扩展。
 */
class InstallWeightCookies extends Migrator
{
    public function getName(): string
    {
        return 'WeightCookies';
    }

    public function change(): void
    {
        $table = $this->table('weight_cookies', [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '平台请求配置池',
        ]);

        PhinxExtend::upgrade($table, [
            ['name', 'string', ['limit' => 80, 'default' => '', 'null' => true, 'comment' => '配置名称']],
            ['platform', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '平台标识（dy/ks/xhs/bili/wb/sph/tk/other）']],
            ['channel', 'string', ['limit' => 30, 'default' => 'default', 'null' => true, 'comment' => '渠道标识（default/web/h5/live/app/mini）']],
            ['cookies', 'text', ['default' => null, 'null' => true, 'comment' => '平台 Cookie']],
            ['user_agent', 'string', ['limit' => 1000, 'default' => '', 'null' => true, 'comment' => 'User-Agent']],
            ['did', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '设备 DID 或平台设备标识']],
            ['params', 'text', ['default' => null, 'null' => true, 'comment' => '扩展参数JSON']],
            ['timeout', 'integer', ['limit' => 11, 'default' => 10000, 'null' => true, 'comment' => '请求超时时间(毫秒)']],
            ['sample_count', 'integer', ['limit' => 11, 'default' => 12, 'null' => true, 'comment' => '默认采样作品数']],
            ['is_default', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '是否平台默认配置']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态(0禁用,1启用)']],
            ['success_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '成功次数']],
            ['fail_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '失败次数']],
            ['last_used_at', 'datetime', ['default' => null, 'null' => true, 'comment' => '最后使用时间']],
            ['last_check_at', 'datetime', ['default' => null, 'null' => true, 'comment' => '最后检测时间']],
            ['expired_at', 'datetime', ['default' => null, 'null' => true, 'comment' => '预计失效时间']],
            ['last_error', 'string', ['limit' => 1000, 'default' => '', 'null' => true, 'comment' => '最近错误']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '备注说明']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
            ['update_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '更新时间']],
        ], [
            'platform', 'channel', 'status', 'is_default', 'sort', 'last_used_at',
        ]);
    }
}

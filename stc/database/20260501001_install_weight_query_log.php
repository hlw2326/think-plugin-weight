<?php

declare(strict_types=1);

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

/**
 * 创建表：weight_query_log（账号权重查询记录）。
 */
class InstallWeightQueryLog extends Migrator
{
    public function getName(): string
    {
        return 'WeightQueryLog';
    }

    public function change(): void
    {
        $table = $this->table('weight_query_log', [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '账号权重查询记录',
        ]);

        PhinxExtend::upgrade($table, [
            ['platform', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '平台标识（dy/ks/xhs/bili/wb/sph/tk/other）']],
            ['channel', 'string', ['limit' => 20, 'default' => 'auto', 'null' => true, 'comment' => '查询渠道']],
            ['input', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '输入链接或分享文本']],
            ['account_id', 'string', ['limit' => 120, 'default' => '', 'null' => true, 'comment' => '平台账号ID']],
            ['display_id', 'string', ['limit' => 120, 'default' => '', 'null' => true, 'comment' => '展示账号ID']],
            ['nickname', 'string', ['limit' => 160, 'default' => '', 'null' => true, 'comment' => '账号昵称']],
            ['avatar_url', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '头像地址']],
            ['signature', 'string', ['limit' => 1000, 'default' => '', 'null' => true, 'comment' => '账号简介']],
            ['fan_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '粉丝数']],
            ['follow_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '关注数']],
            ['work_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '作品数']],
            ['like_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '获赞数']],
            ['collect_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '收藏数']],
            ['sample_feed_count', 'integer', ['limit' => 11, 'default' => 0, 'null' => true, 'comment' => '采样作品数']],
            ['avg_like_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '平均点赞数']],
            ['avg_comment_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '平均评论数']],
            ['avg_share_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '平均分享数']],
            ['avg_collect_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '平均收藏数']],
            ['avg_play_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '平均播放数']],
            ['interaction_rate', 'decimal', ['precision' => 10, 'scale' => 4, 'default' => 0, 'null' => true, 'comment' => '互动率(%)']],
            ['weight_score', 'integer', ['limit' => 11, 'default' => 0, 'null' => true, 'comment' => '权重分']],
            ['weight_grade', 'string', ['limit' => 2, 'default' => 'D', 'null' => true, 'comment' => '权重等级']],
            ['analysis_summary', 'string', ['limit' => 1000, 'default' => '', 'null' => true, 'comment' => '分析摘要']],
            ['status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '查询状态']],
            ['fail_reason', 'string', ['limit' => 1000, 'default' => '', 'null' => true, 'comment' => '失败原因']],
            ['exec_time', 'integer', ['limit' => 11, 'default' => 0, 'null' => true, 'comment' => '执行耗时(毫秒)']],
            ['ip', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '请求IP']],
            ['user_agent', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => 'User-Agent']],
            ['raw_result', 'text', ['default' => null, 'null' => true, 'comment' => '原始结果JSON']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
        ], [
            'platform', 'channel', 'status', 'weight_grade', 'create_at', 'account_id', 'display_id',
        ]);
    }
}

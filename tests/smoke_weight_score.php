<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use plugin\weight\service\WeightScoreService;

$analysis = WeightScoreService::analyze([
    'platform' => 'dy',
    'nickname' => 'demo',
    'total' => [
        'fan_count' => 120000,
        'follow_count' => 120,
        'work_count' => 80,
        'like_count' => 380000,
        'collect_count' => 90000,
    ],
    'verified' => true,
], [
    ['total' => ['like_count' => 1200, 'comment_count' => 90, 'share_count' => 35, 'collect_count' => 40, 'play_count' => 20000]],
    ['total' => ['like_count' => 800, 'comment_count' => 50, 'share_count' => 20, 'collect_count' => 30, 'play_count' => 10000]],
]);

if ($analysis['score'] < 70) {
    throw new RuntimeException('期望权重分不低于 70');
}

if (!in_array($analysis['grade'], ['S', 'A'], true)) {
    throw new RuntimeException('期望得到高等级');
}

if ($analysis['sample_feed_count'] !== 2) {
    throw new RuntimeException('期望采样作品数为 2');
}

if ($analysis['avg_like_count'] !== 1000) {
    throw new RuntimeException('期望平均点赞数为 1000');
}

if (($analysis['fan_count'] ?? 0) !== 120000 || ($analysis['work_count'] ?? 0) !== 80 || ($analysis['like_count'] ?? 0) !== 380000 || ($analysis['collect_count'] ?? 0) !== 90000) {
    throw new RuntimeException('期望评分服务读取 SDK 新字段名');
}

if (empty($analysis['fields']['fan_count'])) {
    throw new RuntimeException('期望评分服务输出字段对象明细');
}

if (empty($analysis['fields']['work']) || !empty($analysis['fields']['avg_like_count'])) {
    throw new RuntimeException('期望作品列表由 Work 字段对象统一分析');
}

if (($analysis['fields']['work']['value']['sample_feed_count'] ?? 0) !== 2 || ($analysis['fields']['work']['value']['avg_like_count'] ?? 0) !== 1000) {
    throw new RuntimeException('期望作品采样统计放在 Work 字段对象中');
}

if (($analysis['avg_collect_count'] ?? 0) !== 35 || ($analysis['avg_play_count'] ?? 0) !== 15000) {
    throw new RuntimeException('期望评分服务输出收藏和播放均值');
}

if (empty($analysis['fields']['date']['value']['days']) || count($analysis['fields']['date']['value']['days']) !== 7) {
    throw new RuntimeException('期望评分服务输出未来 7 天发布时间预测');
}

if (($analysis['fields']['pool']['value']['pool_key'] ?? '') !== 'premium') {
    throw new RuntimeException('期望评分服务输出流量池分析');
}

if (($analysis['fields']['score']['value']['score'] ?? 0) !== $analysis['score']) {
    throw new RuntimeException('期望评分服务输出分数解释');
}

if (($analysis['fields']['weight']['value']['grade'] ?? '') !== $analysis['grade']) {
    throw new RuntimeException('期望评分服务输出账号权重结论');
}

foreach ($analysis['fields'] as $field) {
    if (array_key_exists('score', $field) || array_key_exists('weight', $field) || array_key_exists('level', $field)) {
        throw new RuntimeException('期望字段明细不再输出 score/weight/level');
    }
}

if (!str_contains($analysis['summary'], '权重分')) {
    throw new RuntimeException('期望分析摘要使用中文');
}

echo "weight score ok\n";

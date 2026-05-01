<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use plugin\weight\analyzer\Analyzer;
use plugin\weight\analyzer\field\AvatarUrl;
use plugin\weight\analyzer\field\City;
use plugin\weight\analyzer\field\CollectCount;
use plugin\weight\analyzer\field\Date as PublishDate;
use plugin\weight\analyzer\field\DisplayId;
use plugin\weight\analyzer\field\FanCount;
use plugin\weight\analyzer\field\Gender;
use plugin\weight\analyzer\field\Nickname;
use plugin\weight\analyzer\field\Pool;
use plugin\weight\analyzer\field\Platform;
use plugin\weight\analyzer\field\Score;
use plugin\weight\analyzer\field\SecUserId;
use plugin\weight\analyzer\field\Signature;
use plugin\weight\analyzer\field\Type;
use plugin\weight\analyzer\field\UserId;
use plugin\weight\analyzer\field\Weight;
use plugin\weight\analyzer\field\Work;
use plugin\weight\analyzer\field\WorkItem;

$platform = new Platform('dy');
if ($platform->value() !== 'dy') {
    throw new RuntimeException('平台字段对象评分异常');
}

$type = new Type('user');
if ($type->value() !== 'user') {
    throw new RuntimeException('类型字段对象评分异常');
}

$userId = new UserId('12345');
if ($userId->value() !== '12345') {
    throw new RuntimeException('用户ID字段对象评分异常');
}

$secUserId = new SecUserId('sec_12345');
if ($secUserId->value() !== 'sec_12345') {
    throw new RuntimeException('SecUserId字段对象评分异常');
}

$displayId = new DisplayId('demo_account');
if ($displayId->value() !== 'demo_account') {
    throw new RuntimeException('展示账号ID字段对象评分异常');
}

$avatarUrl = new AvatarUrl('https://example.com/avatar.jpg');
if ($avatarUrl->value() !== 'https://example.com/avatar.jpg') {
    throw new RuntimeException('头像字段对象评分异常');
}

$gender = new Gender(1);
if ($gender->value() !== 1 || $gender->label() !== '性别') {
    throw new RuntimeException('性别字段对象评分异常');
}

$city = new City('杭州');
if ($city->value() !== '杭州') {
    throw new RuntimeException('城市字段对象评分异常');
}

$nickname = new Nickname('账号权重研究所');
if ($nickname->label() !== '昵称' || $nickname->value() !== '账号权重研究所') {
    throw new RuntimeException('昵称字段对象评分异常');
}

$signature = new Signature('');
if ($signature->value() !== '' || empty($signature->messages())) {
    throw new RuntimeException('空签名应为风险项');
}

$fanCount = new FanCount(120000);
if ($fanCount->value() !== 120000) {
    throw new RuntimeException('粉丝数字段对象评分异常');
}

$collectCount = new CollectCount(90000);
if ($collectCount->value() !== 90000) {
    throw new RuntimeException('收藏数字段对象评分异常');
}

foreach ([$platform, $type, $userId, $secUserId, $displayId, $avatarUrl, $gender, $city, $nickname, $signature, $fanCount, $collectCount] as $field) {
    if (method_exists($field, 'score') || method_exists($field, 'weight') || method_exists($field, 'level')) {
        throw new RuntimeException('字段对象不应再暴露 score/weight/level 方法');
    }
}

$feedList = [
    [
        'platform' => 'dy',
        'type' => 'feed',
        'item_id' => 'item_1',
        'desc' => '第一条作品',
        'create_time' => 1710000000,
        'duration' => 15,
        'cover_url' => 'https://example.com/cover1.jpg',
        'video_url' => 'https://example.com/video1.mp4',
        'share_url' => 'https://example.com/share1',
        'width' => 1080,
        'height' => 1920,
        'is_top' => true,
        'total' => ['like_count' => 1200, 'comment_count' => 90, 'share_count' => 35, 'collect_count' => 40, 'play_count' => 20000],
        'author' => ['user_id' => '12345', 'sec_user_id' => 'sec_12345', 'display_id' => 'demo_account', 'nickname' => '账号权重研究所', 'avatar_url' => 'https://example.com/avatar.jpg'],
        'tags' => [['tag_id' => 'tag_1', 'tag_name' => '账号分析', 'level' => 1]],
    ],
    [
        'platform' => 'dy',
        'type' => 'feed',
        'item_id' => 'item_2',
        'desc' => '第二条作品',
        'create_time' => 1710000600,
        'duration' => 20,
        'cover_url' => 'https://example.com/cover2.jpg',
        'video_url' => 'https://example.com/video2.mp4',
        'share_url' => 'https://example.com/share2',
        'width' => 1080,
        'height' => 1920,
        'is_top' => false,
        'total' => ['like_count' => 800, 'comment_count' => 50, 'share_count' => 20, 'collect_count' => 30, 'play_count' => 10000],
        'author' => ['user_id' => '12345', 'sec_user_id' => 'sec_12345', 'display_id' => 'demo_account', 'nickname' => '账号权重研究所', 'avatar_url' => 'https://example.com/avatar.jpg'],
        'tags' => [['tag_id' => 'tag_2', 'tag_name' => '内容运营', 'level' => 1]],
    ],
];
$workItem = new WorkItem($feedList[0]);
$workItemValue = $workItem->toArray();
if (($workItemValue['item_id'] ?? '') !== 'item_1' || ($workItemValue['author']['display_id'] ?? '') !== 'demo_account') {
    throw new RuntimeException('单条作品字段对象应保留 SDK 作品字段');
}

$work = new Work($feedList, 120000);
$workValue = $work->value();
if (($workValue['avg_like_count'] ?? 0) !== 1000 || ($workValue['avg_collect_count'] ?? 0) !== 35) {
    throw new RuntimeException('作品字段对象应统一计算作品平均互动');
}
if (($workValue['items'][0]['item_id'] ?? '') !== 'item_1' || ($workValue['items'][0]['tags'][0]['tag_name'] ?? '') !== '账号分析') {
    throw new RuntimeException('作品字段对象应输出每条作品的完整字段');
}

$pool = new Pool(120000, $workValue);
if (($pool->value()['pool_key'] ?? '') !== 'premium' || ($pool->value()['label'] ?? '') !== '高权重流量池') {
    throw new RuntimeException('流量池字段对象分析异常');
}

$scoreField = new Score(86, 'S');
if (($scoreField->value()['label'] ?? '') !== '高权重账号') {
    throw new RuntimeException('分数字段对象分析异常');
}

$weight = new Weight(86, 'S', [
    'fan_count' => ['label' => '粉丝数', 'level' => 'good'],
    'signature' => ['label' => '签名', 'level' => 'risk'],
], $pool->value());
if (($weight->value()['grade'] ?? '') !== 'S' || empty($weight->value()['strengths']) || empty($weight->value()['weaknesses'])) {
    throw new RuntimeException('账号权重字段对象分析异常');
}

$date = new PublishDate($feedList, new DateTimeImmutable('2026-05-01 00:00:00', new DateTimeZone('Asia/Shanghai')));
$dateValue = $date->value();
if (($dateValue['days'][0]['date'] ?? '') !== '2026-05-01' || ($dateValue['days'][6]['date'] ?? '') !== '2026-05-07') {
    throw new RuntimeException('发布时间预测应生成从起始日开始的未来 7 天');
}
if (($dateValue['days'][2]['date'] ?? '') !== '2026-05-03' || !preg_match('/^\d{2}:00-\d{2}:00$/', (string) ($dateValue['days'][2]['time_range'] ?? ''))) {
    throw new RuntimeException('发布时间预测应给 3 号生成稳定时间范围');
}
$dateAgain = new PublishDate($feedList, new DateTimeImmutable('2026-05-01 00:00:00', new DateTimeZone('Asia/Shanghai')));
if (($dateAgain->value()['days'][2]['time_range'] ?? '') !== ($dateValue['days'][2]['time_range'] ?? '')) {
    throw new RuntimeException('同一日期的发布时间预测必须稳定');
}
$dateOnThirdDay = new PublishDate($feedList, new DateTimeImmutable('2026-05-03 00:00:00', new DateTimeZone('Asia/Shanghai')));
if (($dateOnThirdDay->value()['days'][0]['date'] ?? '') !== '2026-05-03' || ($dateOnThirdDay->value()['days'][0]['time_range'] ?? '') !== ($dateValue['days'][2]['time_range'] ?? '')) {
    throw new RuntimeException('过了 3 天后，3 号仍应对应原来生成的时间范围');
}

$analysis = Analyzer::analyze([
    'platform' => 'dy',
    'type' => 'user',
    'user_id' => '12345',
    'sec_user_id' => 'sec_12345',
    'display_id' => 'demo_account',
    'nickname' => '账号权重研究所',
    'signature' => '专注账号诊断、内容数据分析、权重优化。',
    'avatar_url' => 'https://example.com/avatar.jpg',
    'gender' => 1,
    'city' => '杭州',
    'verified' => true,
    'total' => [
        'fan_count' => 120000,
        'follow_count' => 120,
        'work_count' => 80,
        'like_count' => 380000,
        'collect_count' => 90000,
    ],
], $feedList);

if ($analysis['fan_count'] !== 120000 || $analysis['work_count'] !== 80 || $analysis['like_count'] !== 380000 || $analysis['collect_count'] !== 90000) {
    throw new RuntimeException('总分析器未读取 SDK 新字段名');
}

if ($analysis['score'] < 70 || !in_array($analysis['grade'], ['S', 'A'], true)) {
    throw new RuntimeException('总分析器评分异常');
}

if (($analysis['fields']['nickname']['label'] ?? '') !== '昵称') {
    throw new RuntimeException('字段明细缺少昵称');
}

$requiredFields = ['platform', 'type', 'user_id', 'sec_user_id', 'display_id', 'avatar_url', 'gender', 'city'];
foreach ($requiredFields as $field) {
    if (!isset($analysis['fields'][$field])) {
        throw new RuntimeException("字段明细缺少 {$field}");
    }
}

if (($analysis['fields']['collect_count']['label'] ?? '') !== '收藏数') {
    throw new RuntimeException('字段明细缺少收藏数');
}

if (($analysis['fields']['work']['label'] ?? '') !== '作品表现') {
    throw new RuntimeException('作品列表应由 Work 字段对象统一分析');
}

if (($analysis['fields']['work']['value']['avg_like_count'] ?? 0) !== 1000 || ($analysis['fields']['work']['value']['avg_play_count'] ?? 0) !== 15000) {
    throw new RuntimeException('作品列表平均值计算异常');
}

if (($analysis['avg_like_count'] ?? 0) !== 1000 || ($analysis['avg_collect_count'] ?? 0) !== 35 || ($analysis['avg_play_count'] ?? 0) !== 15000) {
    throw new RuntimeException('总分析器应输出作品采样统计');
}

if (($analysis['fields']['date']['label'] ?? '') !== '发布时间预测') {
    throw new RuntimeException('字段明细缺少发布时间预测');
}

if (($analysis['fields']['pool']['value']['pool_key'] ?? '') !== 'premium') {
    throw new RuntimeException('字段明细缺少流量池分析');
}

if (($analysis['fields']['score']['value']['score'] ?? 0) !== $analysis['score']) {
    throw new RuntimeException('字段明细缺少分数解释');
}

if (($analysis['fields']['weight']['value']['grade'] ?? '') !== $analysis['grade']) {
    throw new RuntimeException('字段明细缺少账号权重结论');
}

foreach ($analysis['fields'] as $field) {
    if (array_key_exists('score', $field) || array_key_exists('weight', $field) || array_key_exists('level', $field)) {
        throw new RuntimeException('字段明细不应再输出 score/weight/level');
    }
}

if (isset($analysis['fields']['avg_like_count'], $analysis['fields']['avg_comment_count'], $analysis['fields']['avg_share_count'], $analysis['fields']['avg_collect_count'], $analysis['fields']['avg_play_count'])) {
    throw new RuntimeException('作品平均数据不应拆成多个 Avg 字段对象');
}

if (empty($analysis['suggestions']) || !is_array($analysis['suggestions'])) {
    throw new RuntimeException('总分析器应输出优化建议');
}

echo "field analyzer ok\n";

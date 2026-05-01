<?php

declare(strict_types=1);

namespace plugin\weight\analyzer;

use plugin\weight\analyzer\field\AbstractField;
use plugin\weight\analyzer\field\AvatarUrl;
use plugin\weight\analyzer\field\City;
use plugin\weight\analyzer\field\CollectCount;
use plugin\weight\analyzer\field\Date;
use plugin\weight\analyzer\field\DisplayId;
use plugin\weight\analyzer\field\FanCount;
use plugin\weight\analyzer\field\FollowCount;
use plugin\weight\analyzer\field\Gender;
use plugin\weight\analyzer\field\LikeCount;
use plugin\weight\analyzer\field\Nickname;
use plugin\weight\analyzer\field\Pool;
use plugin\weight\analyzer\field\Platform;
use plugin\weight\analyzer\field\Score;
use plugin\weight\analyzer\field\SecUserId;
use plugin\weight\analyzer\field\Signature;
use plugin\weight\analyzer\field\Type;
use plugin\weight\analyzer\field\UserId;
use plugin\weight\analyzer\field\Verified;
use plugin\weight\analyzer\field\Weight;
use plugin\weight\analyzer\field\Work;
use plugin\weight\analyzer\field\WorkCount;

/**
 * 账号权重总分析器。
 *
 * 作用：
 * - 接收 SDK 返回的账号信息和作品列表。
 * - 把粉丝、作品、获赞、互动等数据拆成多个字段对象展示和检测。
 * - 集中计算总分、等级、摘要、建议，并返回给查询服务写入 weight_query_log。
 */
class Analyzer
{
    /**
     * 分析账号权重。
     *
     * $userInfo 对应 SDK 用户信息，主要读取 nickname、signature、verified、total。
     * $feedList 对应 SDK 作品列表，由 Work 字段对象统一分析作品互动数据。
     *
     * @param array<string,mixed> $userInfo
     * @param array<int,array<string,mixed>> $feedList
     * @return array<string,mixed>
     */
    public static function analyze(array $userInfo, array $feedList = []): array
    {
        $total = is_array($userInfo['total'] ?? null) ? $userInfo['total'] : [];
        $fanCount = self::metric($total, 'fan_count', 'follower_count');
        $followCount = self::metric($total, 'follow_count', 'following_count');
        $workCount = self::metric($total, 'work_count', 'feed_count');
        $likeCount = self::metric($total, 'like_count', 'liked_count');
        $collectCount = self::metric($total, 'collect_count', 'collection_count');
        $work = new Work($feedList, $fanCount);
        $workMetrics = $work->value();

        // 每个字段单独负责自己的取值和中文说明，方便后台查看原始分析依据。
        $scoreFields = [
            new Platform($userInfo['platform'] ?? ''),
            new Type($userInfo['type'] ?? ''),
            new UserId($userInfo['user_id'] ?? ''),
            new SecUserId($userInfo['sec_user_id'] ?? ''),
            new DisplayId($userInfo['display_id'] ?? ''),
            new Nickname($userInfo['nickname'] ?? ''),
            new Signature($userInfo['signature'] ?? ''),
            new AvatarUrl($userInfo['avatar_url'] ?? ''),
            new Gender($userInfo['gender'] ?? 0),
            new City($userInfo['city'] ?? ''),
            new FanCount($fanCount),
            new FollowCount($followCount),
            new WorkCount($workCount),
            new LikeCount($likeCount),
            new CollectCount($collectCount),
            new Verified(!empty($userInfo['verified'])),
            $work,
            new Date($feedList),
        ];

        $baseFieldDetails = self::fieldDetails($scoreFields);
        $score = self::calculateScore(
            $userInfo,
            $fanCount,
            $followCount,
            $workCount,
            $likeCount,
            $collectCount,
            $workMetrics
        );
        $grade = self::grade($score);
        $pool = new Pool($fanCount, $workMetrics);
        $fields = [
            ...$scoreFields,
            $pool,
            new Score($score, $grade),
            new Weight($score, $grade, $baseFieldDetails, $pool->value()),
        ];
        $fieldDetails = self::fieldDetails($fields);

        return [
            'score' => $score,
            'grade' => $grade,
            'summary' => self::summary($score, $grade, $fanCount, $workCount, (int) $workMetrics['sample_feed_count']),
            'fields' => $fieldDetails,
            'suggestions' => self::suggestions($fields),
            'risk_messages' => self::messagesByLevel($fields, 'risk'),
            'platform' => (string) ($userInfo['platform'] ?? ''),
            'type' => (string) ($userInfo['type'] ?? ''),
            'user_id' => (string) ($userInfo['user_id'] ?? ''),
            'sec_user_id' => (string) ($userInfo['sec_user_id'] ?? ''),
            'display_id' => (string) ($userInfo['display_id'] ?? ''),
            'nickname' => (string) ($userInfo['nickname'] ?? ''),
            'signature' => (string) ($userInfo['signature'] ?? ''),
            'avatar_url' => (string) ($userInfo['avatar_url'] ?? ''),
            'gender' => max(0, (int) ($userInfo['gender'] ?? 0)),
            'city' => (string) ($userInfo['city'] ?? ''),
            'fan_count' => $fanCount,
            'follow_count' => $followCount,
            'work_count' => $workCount,
            'like_count' => $likeCount,
            'collect_count' => $collectCount,
            'sample_feed_count' => (int) $workMetrics['sample_feed_count'],
            'avg_like_count' => (int) $workMetrics['avg_like_count'],
            'avg_comment_count' => (int) $workMetrics['avg_comment_count'],
            'avg_share_count' => (int) $workMetrics['avg_share_count'],
            'avg_collect_count' => (int) $workMetrics['avg_collect_count'],
            'avg_play_count' => (int) $workMetrics['avg_play_count'],
            'interaction_rate' => (float) $workMetrics['interaction_rate'],
            'verified' => !empty($userInfo['verified']) ? 1 : 0,
            'follower_count' => $fanCount,
            'following_count' => $followCount,
            'feed_count' => $workCount,
            'liked_count' => $likeCount,
        ];
    }

    /**
     * 读取账号统计值。
     *
     * SDK 当前字段使用 fan_count / follow_count / work_count / like_count / collect_count，
     * fallback 用于兼容旧版本命名，避免历史调用直接失效。
     *
     * @param array<string,mixed> $total
     */
    private static function metric(array $total, string $primary, string $fallback): int
    {
        return max(0, (int) ($total[$primary] ?? $total[$fallback] ?? 0));
    }

    /**
     * 把字段对象转成以字段 key 为索引的数组。
     *
     * 返回结构会进入 raw_result.analysis.fields，方便后台详情查看每个字段的取值和说明。
     *
     * @param array<int,AbstractField> $fields
     * @return array<string,array<string,mixed>>
     */
    private static function fieldDetails(array $fields): array
    {
        $details = [];
        foreach ($fields as $field) {
            $details[$field->key()] = $field->toArray();
        }
        return $details;
    }

    /**
     * 收集需要优化的建议。
     *
     * 只把带风险含义的中文说明放进建议列表，表现正常的字段不额外打扰管理员。
     *
     * @param array<int,AbstractField> $fields
     * @return array<int,string>
     */
    private static function suggestions(array $fields): array
    {
        $messages = [];
        foreach ($fields as $field) {
            foreach ($field->messages() as $message) {
                if (self::isWeakMessage($message)) {
                    $messages[] = $message;
                }
            }
        }

        return $messages ?: ['账号基础表现较稳定，建议持续更新优质内容并保持互动。'];
    }

    /**
     * 收集风险提示。
     *
     * 字段对象已经不再输出 level，这里保留 risk_messages 兼容旧调用方。
     *
     * @param array<int,AbstractField> $fields
     * @return array<int,string>
     */
    private static function messagesByLevel(array $fields, string $level): array
    {
        if ($level !== 'risk') {
            return [];
        }

        $messages = [];
        foreach ($fields as $field) {
            foreach ($field->messages() as $message) {
                if (self::isWeakMessage($message)) {
                    $messages[] = $message;
                }
            }
        }
        return $messages;
    }

    /**
     * 集中计算账号总分。
     *
     * 字段对象只负责取值和说明；评分规则集中在这里，避免 fields 明细暴露 score、weight、level。
     *
     * @param array<string,mixed> $userInfo
     * @param array<string,mixed> $workMetrics
     */
    private static function calculateScore(
        array $userInfo,
        int $fanCount,
        int $followCount,
        int $workCount,
        int $likeCount,
        int $collectCount,
        array $workMetrics
    ): int {
        $score = self::nicknameScore((string) ($userInfo['nickname'] ?? ''))
            + self::signatureScore((string) ($userInfo['signature'] ?? ''))
            + self::logScore($fanCount, 35, 7.0)
            + self::followScore($followCount)
            + self::workCountScore($workCount)
            + self::logScore($likeCount, 20, 3.6)
            + self::logScore($collectCount, 8, 2.6)
            + (!empty($userInfo['verified']) ? 10 : 0)
            + self::workScore($workMetrics);

        return min(100, $score);
    }

    private static function nicknameScore(string $nickname): int
    {
        $length = mb_strlen(trim($nickname));
        if ($length < 1) return 0;
        if ($length < 2) return 1;
        return 3;
    }

    private static function signatureScore(string $signature): int
    {
        $length = mb_strlen(trim($signature));
        if ($length < 1) return 0;
        if ($length < 10) return 2;
        if ($length > 160) return 3;
        return 4;
    }

    private static function followScore(int $followCount): int
    {
        if ($followCount <= 0) return 1;
        if ($followCount <= 1000) return 3;
        if ($followCount <= 5000) return 2;
        return 1;
    }

    private static function workCountScore(int $workCount): int
    {
        return (int) round(min(15.0, $workCount / 100 * 15));
    }

    /**
     * @param array<string,mixed> $workMetrics
     */
    private static function workScore(array $workMetrics): int
    {
        $score = self::logScore((int) $workMetrics['avg_like_count'], 8, 2.4)
            + self::logScore((int) $workMetrics['avg_comment_count'], 4, 1.7)
            + self::logScore((int) $workMetrics['avg_share_count'], 4, 1.8)
            + self::logScore((int) $workMetrics['avg_collect_count'], 3, 1.4)
            + self::logScore((int) $workMetrics['avg_play_count'], 5, 1.2)
            + self::interactionScore((float) $workMetrics['interaction_rate']);

        return min(30, $score);
    }

    private static function logScore(int $value, int $maxScore, float $factor): int
    {
        return (int) round(min((float) $maxScore, log10(max(0, $value) + 1) * $factor));
    }

    private static function interactionScore(float $rate): int
    {
        if ($rate >= 3.0) return 6;
        if ($rate >= 1.0) return 4;
        if ($rate > 0.2) return 2;
        return 0;
    }

    private static function isWeakMessage(string $message): bool
    {
        foreach (self::weakKeywords() as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<int,string>
     */
    private static function weakKeywords(): array
    {
        return ['为空', '偏低', '偏弱', '不足', '过少', '未认证', '过短', '过长', '略高', '过高', '无法判断', '不在 SDK 标准范围', '不是 user'];
    }

    private static function grade(int $score): string
    {
        if ($score >= 85) return 'S';
        if ($score >= 70) return 'A';
        if ($score >= 55) return 'B';
        if ($score >= 40) return 'C';
        return 'D';
    }

    private static function summary(int $score, string $grade, int $fanCount, int $workCount, int $sampleCount): string
    {
        return "权重分 {$score}，等级 {$grade}。粉丝数 {$fanCount}，作品数 {$workCount}，采样作品 {$sampleCount} 条。";
    }
}

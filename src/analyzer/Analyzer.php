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
use plugin\weight\analyzer\field\Tags;
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
            new Tags($feedList),
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
        $page = self::pagePayload(
            $userInfo,
            $fanCount,
            $followCount,
            $workCount,
            $likeCount,
            $collectCount,
            $workMetrics,
            $score,
            $grade,
            $fieldDetails
        );

        return [
            'score' => $score,
            'grade' => $grade,
            'summary' => self::summary($score, $grade, $fanCount, $workCount, (int) $workMetrics['sample_feed_count']),
            'fields' => $fieldDetails,
            'suggestions' => self::suggestions($fields),
            'risk_messages' => self::messagesByLevel($fields, 'risk'),
            'page' => $page,
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
     * 组装小程序 dy/weight 页面可直接使用的数据。
     *
     * page 下保持单词 key，避免前端同时维护后台字段名和页面变量名。
     *
     * @param array<string,mixed> $userInfo
     * @param array<string,mixed> $workMetrics
     * @param array<string,array<string,mixed>> $fields
     * @return array<string,mixed>
     */
    private static function pagePayload(
        array $userInfo,
        int $fanCount,
        int $followCount,
        int $workCount,
        int $likeCount,
        int $collectCount,
        array $workMetrics,
        int $score,
        string $grade,
        array $fields
    ): array {
        $pool = is_array($fields['pool']['value'] ?? null) ? $fields['pool']['value'] : [];
        $tags = is_array($fields['account_tags']['value'] ?? null) ? $fields['account_tags']['value'] : [];

        return [
            'user' => self::pageUser($userInfo, $fanCount, $followCount, $workCount, $likeCount, $tags),
            'analysis' => self::pageAnalysis($score, $grade, $workCount, $workMetrics, $tags),
            'advice' => self::pageAdvice($fields),
            'traffic' => self::pageTraffic($pool, $workMetrics),
            'valuation' => self::pageValuation($userInfo, $fanCount, $workCount, $likeCount, $collectCount, $workMetrics, $score),
        ];
    }

    /**
     * @param array<string,mixed> $userInfo
     * @param array<string,mixed> $tags
     * @return array<string,mixed>
     */
    private static function pageUser(
        array $userInfo,
        int $fanCount,
        int $followCount,
        int $workCount,
        int $likeCount,
        array $tags
    ): array {
        $name = trim((string) ($userInfo['nickname'] ?? ''));
        $userId = self::firstFilledString(
            $userInfo['display_id'] ?? '',
            $userInfo['user_id'] ?? '',
            $userInfo['sec_user_id'] ?? ''
        );

        return [
            'avatar' => self::avatarUrl($userInfo, $name ?: $userId),
            'name' => $name !== '' ? $name : '未命名账号',
            'userId' => $userId,
            'isVerified' => !empty($userInfo['verified']),
            'tags' => self::pageTags($tags),
            'stats' => [
                ['value' => self::formatCount($followCount), 'label' => '关注'],
                ['value' => self::formatCount($fanCount), 'label' => '粉丝'],
                ['value' => self::formatCount($likeCount), 'label' => '点赞'],
                ['value' => self::formatCount($workCount), 'label' => '作品'],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $tags
     * @return array<int,array<string,string>>
     */
    private static function pageTags(array $tags): array
    {
        $topTags = is_array($tags['top_tags'] ?? null) ? array_slice($tags['top_tags'], 0, 2) : [];
        $classes = [
            'bg-rose-50 text-rose-600 border-rose-100',
            'bg-indigo-50 text-indigo-600 border-indigo-100',
        ];

        $items = [];
        foreach ($topTags as $index => $tag) {
            $tagName = trim((string) (is_array($tag) ? ($tag['tag_name'] ?? '') : ''));
            if ($tagName === '') {
                continue;
            }

            $items[] = [
                'text' => $tagName,
                'icon' => self::tagIcon($tagName),
                'class' => $classes[$index] ?? $classes[0],
            ];
        }

        return $items;
    }

    /**
     * @param array<string,mixed> $workMetrics
     * @param array<string,mixed> $tags
     * @return array<string,mixed>
     */
    private static function pageAnalysis(int $score, string $grade, int $workCount, array $workMetrics, array $tags): array
    {
        $activity = self::activityScore($workCount, (int) $workMetrics['sample_feed_count']);
        $verticality = self::verticalityScore($tags);
        $content = self::contentScore($workMetrics);
        $interaction = self::interactionMetricScore((float) $workMetrics['interaction_rate']);

        return [
            'score' => $score,
            'grade' => $grade,
            'currentLevel' => self::scoreLevel($score),
            'isUpgrading' => false,
            'status' => self::statusText($score),
            'metrics' => [
                self::pageMetric('活跃度', $activity, 'bg-blue-500'),
                self::pageMetric('垂直度', $verticality, 'bg-amber-500'),
                self::pageMetric('内容力', $content, 'bg-green-500'),
                self::pageMetric('互动率', $interaction, 'bg-red-400'),
            ],
        ];
    }

    /**
     * @param array<string,array<string,mixed>> $fields
     * @return array<int,array<string,mixed>>
     */
    private static function pageAdvice(array $fields): array
    {
        $items = [];
        foreach ($fields as $field) {
            foreach (is_array($field['tips'] ?? null) ? $field['tips'] : [] as $tip) {
                $tip = trim((string) $tip);
                if ($tip === '' || !self::isWeakMessage($tip)) {
                    continue;
                }

                $items[] = self::adviceItem($field, $tip);
            }
        }

        if ($items !== []) {
            return array_slice($items, 0, 8);
        }

        return [[
            'id' => 'stable',
            'title' => '账号状态稳定',
            'tag' => '表现正常',
            'desc' => '账号基础信息和近期作品表现较稳定，建议保持更新节奏并持续优化内容互动。',
            'icon' => 'i-fa6-solid-circle-check',
            'iconBg' => 'bg-green-100 text-green-600',
            'tagClass' => 'text-green-600 bg-green-50 px-1.5 py-0.5 rounded border border-green-100',
        ]];
    }

    /**
     * @param array<string,mixed> $pool
     * @param array<string,mixed> $workMetrics
     * @return array<string,mixed>
     */
    private static function pageTraffic(array $pool, array $workMetrics): array
    {
        $poolKey = (string) ($pool['pool_key'] ?? 'cold_start');
        $level = self::poolLevel($poolKey);
        $nextRequirement = self::nextRequirement($workMetrics, $level);

        return [
            'level' => $level,
            'maxLevel' => 8,
            'playRange' => self::playRange($poolKey),
            'nextRequirement' => $nextRequirement,
            'progress' => max(0, min(100, (int) ($pool['pool_score'] ?? 0))),
            'advice' => self::trafficAdvice($poolKey, $nextRequirement),
        ];
    }

    /**
     * @param array<string,mixed> $userInfo
     * @param array<string,mixed> $workMetrics
     * @return array<string,mixed>
     */
    private static function pageValuation(
        array $userInfo,
        int $fanCount,
        int $workCount,
        int $likeCount,
        int $collectCount,
        array $workMetrics,
        int $score
    ): array {
        $estimate = (int) round(
            $fanCount * 1.15
            + (int) $workMetrics['avg_play_count'] * 2.2
            + (int) $workMetrics['avg_like_count'] * 32
            + (int) $workMetrics['avg_collect_count'] * 45
            + $workCount * 60
            + $score * 720
            + min(50000, $likeCount * 0.04)
            + min(30000, $collectCount * 0.06)
        );

        $seed = crc32(self::firstFilledString(
            $userInfo['display_id'] ?? '',
            $userInfo['user_id'] ?? '',
            $userInfo['nickname'] ?? '',
            (string) $fanCount
        ));
        $queryCount = 800 + ($seed % 3200);

        return [
            'value' => number_format(max(0, $estimate)),
            'queryCount' => number_format($queryCount),
        ];
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
        $tips = [];
        foreach ($fields as $field) {
            foreach ($field->tips() as $tip) {
                if (self::isWeakMessage($tip)) {
                    $tips[] = $tip;
                }
            }
        }

        return $tips ?: ['账号基础表现较稳定，建议持续更新优质内容并保持互动。'];
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

        $tips = [];
        foreach ($fields as $field) {
            foreach ($field->tips() as $tip) {
                if (self::isWeakMessage($tip)) {
                    $tips[] = $tip;
                }
            }
        }
        return $tips;
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

    private static function firstFilledString(mixed ...$values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param array<string,mixed> $userInfo
     */
    private static function avatarUrl(array $userInfo, string $seed): string
    {
        $avatarUrl = trim((string) ($userInfo['avatar_url'] ?? ''));
        if ($avatarUrl !== '') {
            return $avatarUrl;
        }

        $seed = $seed !== '' ? $seed : 'weight';
        return 'https://api.dicebear.com/9.x/avataaars/svg?seed=' . rawurlencode($seed);
    }

    private static function formatCount(int $value): string
    {
        $value = max(0, $value);
        if ($value >= 100000000) {
            return rtrim(rtrim(number_format($value / 100000000, 1), '0'), '.') . '亿';
        }
        if ($value >= 10000) {
            return rtrim(rtrim(number_format($value / 10000, 1), '0'), '.') . 'w';
        }

        return (string) $value;
    }

    private static function tagIcon(string $tagName): string
    {
        foreach ([
            '美食' => 'i-fa6-solid-bowl-food',
            '萌宠' => 'i-fa6-solid-paw',
            '宠物' => 'i-fa6-solid-paw',
            '音乐' => 'i-fa6-solid-music',
            '舞蹈' => 'i-fa6-solid-person-running',
            '旅游' => 'i-fa6-solid-location-dot',
            '旅行' => 'i-fa6-solid-location-dot',
            '汽车' => 'i-fa6-solid-car',
            '教育' => 'i-fa6-solid-graduation-cap',
            '财经' => 'i-fa6-solid-chart-line',
            '科技' => 'i-fa6-solid-microchip',
            '游戏' => 'i-fa6-solid-gamepad',
            '影视' => 'i-fa6-solid-film',
            '母婴' => 'i-fa6-solid-baby',
            '健身' => 'i-fa6-solid-dumbbell',
            '穿搭' => 'i-fa6-solid-shirt',
            '剧情' => 'i-fa6-solid-masks-theater',
            '搞笑' => 'i-fa6-solid-face-laugh-squint',
        ] as $keyword => $icon) {
            if (str_contains($tagName, $keyword)) {
                return $icon;
            }
        }

        return 'i-fa6-solid-hashtag';
    }

    private static function activityScore(int $workCount, int $sampleFeedCount): int
    {
        return max(0, min(100, (int) round(
            min(60.0, $workCount / 120 * 60)
            + min(40.0, $sampleFeedCount / 18 * 40)
        )));
    }

    /**
     * @param array<string,mixed> $tags
     */
    private static function verticalityScore(array $tags): int
    {
        $coverage = (float) ($tags['coverage_rate'] ?? 0);
        $concentration = (float) ($tags['concentration_rate'] ?? 0);

        return max(0, min(100, (int) round($coverage * 0.45 + $concentration * 0.55)));
    }

    /**
     * @param array<string,mixed> $workMetrics
     */
    private static function contentScore(array $workMetrics): int
    {
        $score = self::logScore((int) $workMetrics['avg_play_count'], 40, 8.0)
            + self::logScore((int) $workMetrics['avg_like_count'], 30, 7.5)
            + self::logScore((int) $workMetrics['avg_collect_count'], 15, 5.5)
            + self::logScore((int) $workMetrics['avg_share_count'], 15, 5.5);

        return max(0, min(100, $score));
    }

    private static function interactionMetricScore(float $rate): int
    {
        if ($rate >= 3.0) return 95;
        if ($rate >= 1.0) return 78;
        if ($rate > 0.2) return 52;
        if ($rate > 0.0) return 28;
        return 0;
    }

    /**
     * @return array<string,string>
     */
    private static function pageMetric(string $label, int $score, string $color): array
    {
        $score = max(0, min(100, $score));
        if ($score < 40) {
            $color = 'bg-red-400';
        } elseif ($score < 70) {
            $color = 'bg-amber-500';
        }

        return [
            'label' => $label,
            'value' => self::metricLabel($score),
            'color' => $color,
            'width' => $score . '%',
        ];
    }

    private static function metricLabel(int $score): string
    {
        if ($score >= 85) return '极高';
        if ($score >= 70) return '良好';
        if ($score >= 40) return '中等';
        return '偏低';
    }

    private static function statusText(int $score): string
    {
        if ($score >= 85) return '账号状态极佳，各项核心指标运行平稳';
        if ($score >= 70) return '账号状态良好，具备继续放大的基础';
        if ($score >= 55) return '账号处于成长阶段，建议重点优化内容互动';
        if ($score >= 40) return '账号基础较弱，需要补齐资料和作品表现';
        return '账号存在明显短板，建议先完成基础养号和内容定位';
    }

    private static function scoreLevel(int $score): int
    {
        return max(0, min(9, (int) floor($score / 10)));
    }

    /**
     * @param array<string,mixed> $field
     * @return array<string,mixed>
     */
    private static function adviceItem(array $field, string $tip): array
    {
        $severity = self::adviceSeverity($tip);

        return [
            'id' => (string) ($field['key'] ?? md5($tip)),
            'title' => trim((string) ($field['label'] ?? '账号指标')) . '待优化',
            'tag' => $severity['tag'],
            'desc' => $tip,
            'icon' => self::adviceIcon((string) ($field['key'] ?? ''), $tip),
            'iconBg' => $severity['iconBg'],
            'tagClass' => $severity['tagClass'],
        ];
    }

    /**
     * @return array{tag:string,iconBg:string,tagClass:string}
     */
    private static function adviceSeverity(string $tip): array
    {
        foreach (['违规', '手机号', '隐私', '封号', '无法判断'] as $keyword) {
            if (str_contains($tip, $keyword)) {
                return [
                    'tag' => '高风险',
                    'iconBg' => 'bg-red-100 text-red-600',
                    'tagClass' => 'text-red-600 bg-red-50 px-1.5 py-0.5 rounded border border-red-100',
                ];
            }
        }

        foreach (['为空', '偏低', '偏弱', '不足', '过少', '未认证'] as $keyword) {
            if (str_contains($tip, $keyword)) {
                return [
                    'tag' => '需优化',
                    'iconBg' => 'bg-amber-100 text-amber-600',
                    'tagClass' => 'text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100',
                ];
            }
        }

        return [
            'tag' => '建议',
            'iconBg' => 'bg-blue-100 text-blue-600',
            'tagClass' => 'text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100',
        ];
    }

    private static function adviceIcon(string $fieldKey, string $tip): string
    {
        if (str_contains($tip, '违规') || str_contains($tip, '手机号')) {
            return 'i-fa6-solid-triangle-exclamation';
        }

        return match ($fieldKey) {
            'nickname' => 'i-fa6-solid-id-card',
            'signature' => 'i-fa6-solid-pen-to-square',
            'fan_count', 'follow_count' => 'i-fa6-solid-users',
            'work', 'work_count' => 'i-fa6-solid-chart-line',
            'verified' => 'i-fa6-solid-circle-check',
            'account_tags' => 'i-fa6-solid-tags',
            'pool' => 'i-fa6-solid-water',
            default => 'i-fa6-solid-circle-info',
        };
    }

    private static function poolLevel(string $poolKey): int
    {
        return match ($poolKey) {
            'premium' => 8,
            'stable' => 6,
            'growth' => 4,
            'basic' => 2,
            default => 1,
        };
    }

    /**
     * @param array<string,mixed> $workMetrics
     */
    private static function nextRequirement(array $workMetrics, int $level): string
    {
        if ((int) $workMetrics['avg_play_count'] < 1000) return '平均播放 > 1,000';
        if ((float) $workMetrics['interaction_rate'] <= 0.2) return '互动率 > 0.2%';
        if ((int) $workMetrics['avg_comment_count'] < 10) return '平均评论 > 10';
        if ((int) $workMetrics['avg_collect_count'] < 5) return '平均收藏 > 5';
        if ($level >= 8) return '保持稳定更新';
        return '完播与互动继续提升';
    }

    private static function playRange(string $poolKey): string
    {
        return match ($poolKey) {
            'premium' => '20,000+',
            'stable' => '5,000 ~ 20,000',
            'growth' => '2,000 ~ 5,000',
            'basic' => '500 ~ 2,000',
            default => '0 ~ 500',
        };
    }

    private static function trafficAdvice(string $poolKey, string $nextRequirement): string
    {
        if ($poolKey === 'premium') {
            return '账号已接近高权重流量池，建议保持更新节奏，继续放大高互动作品选题。';
        }

        return "当前账号仍有晋级空间，建议围绕「{$nextRequirement}」优化近期作品，提升进入更高流量池的概率。";
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

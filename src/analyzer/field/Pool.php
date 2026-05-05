<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 流量池分析字段。
 *
 * 来源：粉丝数、作品平均浏览、点赞、评论、收藏数据。
 * 作用：判断账号当前更接近哪个推荐流量池，帮助理解内容分发能力。
 */
class Pool extends AbstractField
{
    private int $fanCount;

    /** @var array<string,mixed> */
    private array $workMetrics;

    /** @var array<string,mixed>|null */
    private ?array $analysis = null;

    /**
     * @param array<string,mixed> $workMetrics
     */
    public function __construct(int $fanCount, array $workMetrics)
    {
        parent::__construct(['fan_count' => $fanCount, 'work' => $workMetrics]);
        $this->fanCount = max(0, $fanCount);
        $this->workMetrics = $workMetrics;
    }

    public function key(): string
    {
        return 'pool';
    }

    public function label(): string
    {
        return '流量池';
    }

    /**
     * @return array<string,mixed>
     */
    public function value(): array
    {
        if ($this->analysis !== null) {
            return $this->analysis;
        }

        $parts = [
            'fan' => $this->fanScore(),
            'play' => $this->playScore(),
            'like' => $this->likeScore(),
            'comment' => $this->commentScore(),
            'collect' => $this->collectScore(),
        ];
        $poolScore = min(100, array_sum($parts));
        [$poolKey, $label] = $this->poolLabel($poolScore);

        return $this->analysis = [
            'pool_key' => $poolKey,
            'label' => $label,
            'pool_score' => $poolScore,
            'fan_count' => $this->fanCount,
            'avg_play_count' => $this->metric('avg_play_count'),
            'avg_like_count' => $this->metric('avg_like_count'),
            'avg_comment_count' => $this->metric('avg_comment_count'),
            'avg_collect_count' => $this->metric('avg_collect_count'),
            'parts' => $parts,
        ];
    }

    public function tips(): array
    {
        $value = $this->value();
        return ["账号当前接近{$value['label']}，流量池分 {$value['pool_score']}。"];
    }

    private function fanScore(): int
    {
        if ($this->fanCount >= 100000) return 25;
        if ($this->fanCount >= 10000) return 18;
        if ($this->fanCount >= 1000) return 10;
        if ($this->fanCount > 0) return 4;
        return 0;
    }

    private function playScore(): int
    {
        $value = $this->metric('avg_play_count');
        if ($value >= 50000) return 30;
        if ($value >= 10000) return 22;
        if ($value >= 1000) return 12;
        if ($value > 0) return 5;
        return 0;
    }

    private function likeScore(): int
    {
        $value = $this->metric('avg_like_count');
        if ($value >= 1000) return 20;
        if ($value >= 300) return 14;
        if ($value >= 100) return 8;
        if ($value > 0) return 3;
        return 0;
    }

    private function commentScore(): int
    {
        $value = $this->metric('avg_comment_count');
        if ($value >= 100) return 12;
        if ($value >= 50) return 8;
        if ($value >= 10) return 4;
        if ($value > 0) return 1;
        return 0;
    }

    private function collectScore(): int
    {
        $value = $this->metric('avg_collect_count');
        if ($value >= 100) return 13;
        if ($value >= 30) return 8;
        if ($value >= 5) return 4;
        if ($value > 0) return 1;
        return 0;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function poolLabel(int $score): array
    {
        if ($score >= 80) return ['premium', '高权重流量池'];
        if ($score >= 65) return ['stable', '稳定流量池'];
        if ($score >= 45) return ['growth', '成长流量池'];
        if ($score >= 25) return ['basic', '基础推荐池'];
        return ['cold_start', '冷启动流量池'];
    }

    private function metric(string $key): int
    {
        return max(0, (int) ($this->workMetrics[$key] ?? 0));
    }
}

<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 作品表现字段。
 *
 * 来源：作品列表中每条作品的 total.like_count、comment_count、share_count、
 * collect_count、play_count。
 * 作用：统一分析作品采样，不再把平均点赞、评论、分享、收藏、播放拆成多个字段类。
 */
class Work extends AbstractField
{
    private int $fanCount;

    /** @var array<string,mixed>|null */
    private ?array $metrics = null;

    /**
     * @param array<int,array<string,mixed>> $feedList
     */
    public function __construct(array $feedList, int $fanCount = 0)
    {
        parent::__construct($feedList);
        $this->fanCount = max(0, $fanCount);
    }

    public function key(): string
    {
        return 'work';
    }

    public function label(): string
    {
        return '作品表现';
    }

    /**
     * @return array<string,mixed>
     */
    public function value(): array
    {
        if ($this->metrics !== null) {
            return $this->metrics;
        }

        $count = 0;
        $likes = 0;
        $comments = 0;
        $shares = 0;
        $collects = 0;
        $plays = 0;

        $items = $this->workItems();
        foreach ($items as $item) {
            $total = $item->total();
            $likes += max(0, (int) ($total['like_count'] ?? 0));
            $comments += max(0, (int) ($total['comment_count'] ?? 0));
            $shares += max(0, (int) ($total['share_count'] ?? 0));
            $collects += max(0, (int) ($total['collect_count'] ?? 0));
            $plays += max(0, (int) ($total['play_count'] ?? 0));
            $count++;
        }

        if ($count < 1) {
            return $this->metrics = [
                'sample_feed_count' => 0,
                'avg_like_count' => 0,
                'avg_comment_count' => 0,
                'avg_share_count' => 0,
                'avg_collect_count' => 0,
                'avg_play_count' => 0,
                'interaction_rate' => 0.0,
                'items' => [],
            ];
        }

        $metrics = [
            'sample_feed_count' => $count,
            'avg_like_count' => (int) round($likes / $count),
            'avg_comment_count' => (int) round($comments / $count),
            'avg_share_count' => (int) round($shares / $count),
            'avg_collect_count' => (int) round($collects / $count),
            'avg_play_count' => (int) round($plays / $count),
            'interaction_rate' => 0.0,
            'items' => array_map(static fn (WorkItem $item): array => $item->toArray(), $items),
        ];
        $metrics['interaction_rate'] = $this->interactionRate($metrics);

        return $this->metrics = $metrics;
    }

    public function messages(): array
    {
        $value = $this->value();
        if ((int) $value['sample_feed_count'] < 1) {
            return ['作品列表为空，无法判断近期内容表现'];
        }

        $messages = [];
        if ((int) $value['avg_like_count'] < 100) $messages[] = '作品平均点赞偏低';
        if ((int) $value['avg_comment_count'] < 10) $messages[] = '评论互动偏弱';
        if ((int) $value['avg_share_count'] < 5) $messages[] = '分享传播偏弱';
        if ((int) $value['avg_collect_count'] < 5) $messages[] = '收藏沉淀偏弱';
        if ((int) $value['avg_play_count'] < 1000) $messages[] = '播放曝光偏弱';
        if ((float) $value['interaction_rate'] <= 0.2) $messages[] = '互动率偏低，内容触达后反馈不足';

        return $messages ?: ['作品互动表现较稳定'];
    }

    /**
     * @return array<int,WorkItem>
     */
    private function workItems(): array
    {
        $items = [];
        foreach (is_array($this->rawValue) ? $this->rawValue : [] as $feed) {
            if (is_array($feed)) {
                $items[] = new WorkItem($feed);
            }
        }
        return $items;
    }

    /**
     * @param array<string,int|float> $metrics
     */
    private function interactionRate(array $metrics): float
    {
        if ($this->fanCount < 1) {
            return 0.0;
        }

        $interaction = (int) $metrics['avg_like_count']
            + (int) $metrics['avg_comment_count']
            + (int) $metrics['avg_share_count']
            + (int) $metrics['avg_collect_count'];

        return round($interaction / $this->fanCount * 100, 4);
    }
}

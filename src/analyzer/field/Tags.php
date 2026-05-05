<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 账号标签字段。
 *
 * 来源：作品列表中每条作品的 tags 字段。
 * 作用：根据作品标签聚合账号主标签和内容方向，辅助判断账号垂直度。
 */
class Tags extends AbstractField
{
    /** @var array<string,mixed>|null */
    private ?array $analysis = null;

    public function key(): string
    {
        return 'account_tags';
    }

    public function label(): string
    {
        return '账号标签';
    }

    /**
     * @return array<string,mixed>
     */
    public function value(): array
    {
        if ($this->analysis !== null) {
            return $this->analysis;
        }

        $feeds = $this->feedList();
        $tagStats = [];
        $taggedFeedCount = 0;
        $totalTagMentions = 0;
        $firstSeen = 0;

        foreach ($feeds as $feed) {
            $feedTags = $this->feedTagNames($feed);
            if ($feedTags === []) {
                continue;
            }

            $taggedFeedCount++;
            foreach ($feedTags as $tagName) {
                $tagStats[$tagName] ??= [
                    'tag_name' => $tagName,
                    'count' => 0,
                    'first_seen' => $firstSeen++,
                ];
                $tagStats[$tagName]['count']++;
                $totalTagMentions++;
            }
        }

        $topTags = array_values($tagStats);
        usort($topTags, static function (array $left, array $right): int {
            return ((int) $right['count'] <=> (int) $left['count'])
                ?: ((int) $left['first_seen'] <=> (int) $right['first_seen']);
        });

        $primaryTag = (string) ($topTags[0]['tag_name'] ?? '');
        $primaryCount = (int) ($topTags[0]['count'] ?? 0);
        $totalFeedCount = count($feeds);
        $coverageRate = $totalFeedCount > 0 ? round($taggedFeedCount / $totalFeedCount * 100, 2) : 0.0;
        $concentrationRate = $taggedFeedCount > 0 ? round($primaryCount / $taggedFeedCount * 100, 2) : 0.0;

        return $this->analysis = [
            'primary_tag' => $primaryTag,
            'tag_count' => count($topTags),
            'total_tag_mentions' => $totalTagMentions,
            'tagged_feed_count' => $taggedFeedCount,
            'total_feed_count' => $totalFeedCount,
            'coverage_rate' => $coverageRate,
            'concentration_rate' => $concentrationRate,
            'top_tags' => array_map(
                static fn (array $tag): array => [
                    'tag_name' => (string) $tag['tag_name'],
                    'count' => (int) $tag['count'],
                    'coverage_rate' => $taggedFeedCount > 0 ? round((int) $tag['count'] / $taggedFeedCount * 100, 2) : 0.0,
                ],
                array_slice($topTags, 0, 10)
            ),
        ];
    }

    public function tips(): array
    {
        $value = $this->value();
        if ((int) $value['total_feed_count'] < 1) return ['作品列表为空，无法分析账号标签'];
        if ((int) $value['tagged_feed_count'] < 1) return ['作品标签为空，无法判断账号内容标签'];
        if ((float) $value['coverage_rate'] < 50.0) return ['作品标签覆盖不足，账号标签判断不够稳定'];
        if ((int) $value['tag_count'] > 5 && (float) $value['concentration_rate'] < 40.0) return ['作品标签较分散，账号内容垂直度偏弱'];
        if ((float) $value['concentration_rate'] >= 60.0) return ["账号主标签为{$value['primary_tag']}，内容定位较集中"];
        return ["账号标签以{$value['primary_tag']}为主，内容方向可识别"];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function feedList(): array
    {
        return array_values(array_filter(
            is_array($this->rawValue) ? $this->rawValue : [],
            static fn (mixed $feed): bool => is_array($feed)
        ));
    }

    /**
     * @param array<string,mixed> $feed
     * @return array<int,string>
     */
    private function feedTagNames(array $feed): array
    {
        $tags = is_array($feed['tags'] ?? null) ? $feed['tags'] : [];
        $names = [];

        foreach ($tags as $tag) {
            if (!is_array($tag)) {
                continue;
            }

            $tagName = trim((string) ($tag['tag_name'] ?? ''));
            if ($tagName !== '') {
                $names[$tagName] = $tagName;
            }
        }

        return array_values($names);
    }
}

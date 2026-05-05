<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

use DateTimeImmutable;
use DateTimeZone;

/**
 * 发布时间预测字段。
 *
 * 来源：作品列表中的 create_time 和 total 互动数据。
 * 作用：根据账号历史作品表现，生成未来 7 天稳定的发布黄金时间。
 */
class Date extends AbstractField
{
    private DateTimeImmutable $baseDate;

    private DateTimeZone $timezone;

    /** @var array<string,mixed>|null */
    private ?array $prediction = null;

    /**
     * 常见内容发布高峰时段，用于历史作品不足时兜底。
     *
     * @var array<int,int>
     */
    private array $fallbackHours = [8, 12, 18, 19, 20, 21];

    /**
     * @param array<int,array<string,mixed>> $feedList
     */
    public function __construct(array $feedList, ?DateTimeImmutable $baseDate = null, string $timezone = 'Asia/Shanghai')
    {
        parent::__construct($feedList);
        $this->timezone = new DateTimeZone($timezone);
        $this->baseDate = ($baseDate ?? new DateTimeImmutable('now', $this->timezone))
            ->setTimezone($this->timezone)
            ->setTime(0, 0);
    }

    public function key(): string
    {
        return 'date';
    }

    public function label(): string
    {
        return '发布时间预测';
    }

    /**
     * @return array<string,mixed>
     */
    public function value(): array
    {
        if ($this->prediction !== null) {
            return $this->prediction;
        }

        $history = $this->historyScores();
        $days = [];
        for ($offset = 0; $offset < 7; $offset++) {
            $target = $this->baseDate->modify("+{$offset} days");
            $days[] = $this->predictionForDate($target, $history);
        }

        return $this->prediction = [
            'timezone' => $this->timezone->getName(),
            'base_date' => $this->baseDate->format('Y-m-d'),
            'source' => $this->hasHistory() ? 'history' : 'fallback',
            'days' => $days,
        ];
    }

    public function tips(): array
    {
        return [$this->hasHistory() ? '已根据历史作品表现生成未来一周发布时间' : '历史作品不足，已使用平台常见高峰时段生成发布时间'];
    }

    /**
     * @return array{weekday:array<int,array<int,float>>,global:array<int,float>}
     */
    private function historyScores(): array
    {
        $weekdayScores = [];
        $globalScores = [];

        foreach ($this->feedList() as $feed) {
            $timestamp = max(0, (int) ($feed['create_time'] ?? 0));
            if ($timestamp < 1) {
                continue;
            }

            $time = (new DateTimeImmutable("@{$timestamp}"))->setTimezone($this->timezone);
            $weekday = (int) $time->format('N');
            $hour = (int) $time->format('G');
            $score = $this->engagementScore(is_array($feed['total'] ?? null) ? $feed['total'] : []);

            $weekdayScores[$weekday][$hour] = ($weekdayScores[$weekday][$hour] ?? 0.0) + $score;
            $globalScores[$hour] = ($globalScores[$hour] ?? 0.0) + $score;
        }

        return ['weekday' => $weekdayScores, 'global' => $globalScores];
    }

    /**
     * @param array{weekday:array<int,array<int,float>>,global:array<int,float>} $history
     * @return array<string,mixed>
     */
    private function predictionForDate(DateTimeImmutable $date, array $history): array
    {
        $dateString = $date->format('Y-m-d');
        $weekday = (int) $date->format('N');
        $ranked = $this->rankedHours($history['weekday'][$weekday] ?? []);
        $source = 'weekday_history';

        if ($ranked === []) {
            $ranked = $this->rankedHours($history['global']);
            $source = $ranked === [] ? 'fallback' : 'global_history';
        }
        if ($ranked === []) {
            $ranked = array_map(static fn (int $hour): array => ['hour' => $hour, 'score' => 0.0], $this->fallbackHours);
        }

        $selected = $ranked[$this->stableIndex($dateString, count($ranked))];
        $hour = (int) $selected['hour'];
        $endHour = ($hour + 1) % 24;

        return [
            'date' => $dateString,
            'weekday' => $weekday,
            'time_range' => sprintf('%02d:00-%02d:00', $hour, $endHour),
            'start_time' => sprintf('%02d:00', $hour),
            'end_time' => sprintf('%02d:00', $endHour),
            'score' => round((float) $selected['score'], 2),
            'source' => $source,
        ];
    }

    /**
     * @param array<int,float> $scores
     * @return array<int,array{hour:int,score:float}>
     */
    private function rankedHours(array $scores): array
    {
        $ranked = [];
        foreach ($scores as $hour => $score) {
            $ranked[] = ['hour' => (int) $hour, 'score' => (float) $score];
        }

        usort($ranked, static function (array $left, array $right): int {
            $scoreCompare = $right['score'] <=> $left['score'];
            return $scoreCompare !== 0 ? $scoreCompare : $left['hour'] <=> $right['hour'];
        });

        return $ranked;
    }

    /**
     * @param array<string,mixed> $total
     */
    private function engagementScore(array $total): float
    {
        return max(1.0,
            (float) ($total['like_count'] ?? 0)
            + (float) ($total['comment_count'] ?? 0) * 3
            + (float) ($total['share_count'] ?? 0) * 4
            + (float) ($total['collect_count'] ?? 0) * 4
            + (float) ($total['play_count'] ?? 0) / 100
        );
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

    private function hasHistory(): bool
    {
        foreach ($this->feedList() as $feed) {
            if ((int) ($feed['create_time'] ?? 0) > 0) {
                return true;
            }
        }
        return false;
    }

    private function stableIndex(string $date, int $count): int
    {
        if ($count <= 1) {
            return 0;
        }
        return (int) (crc32($date) % $count);
    }
}

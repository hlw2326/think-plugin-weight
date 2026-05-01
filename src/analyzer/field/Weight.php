<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 账号权重结论字段。
 *
 * 来源：总分、等级、字段明细和流量池分析。
 * 作用：汇总账号整体权重、优势项、短板项和流量池结论。
 */
class Weight extends AbstractField
{
    private int $score;

    private string $grade;

    /** @var array<string,array<string,mixed>> */
    private array $fields;

    /** @var array<string,mixed> */
    private array $pool;

    /**
     * @param array<string,array<string,mixed>> $fields
     * @param array<string,mixed> $pool
     */
    public function __construct(int $score, string $grade, array $fields, array $pool = [])
    {
        parent::__construct(['score' => $score, 'grade' => $grade, 'fields' => $fields, 'pool' => $pool]);
        $this->score = max(0, min(100, $score));
        $this->grade = $grade;
        $this->fields = $fields;
        $this->pool = $pool;
    }

    public function key(): string
    {
        return 'weight';
    }

    public function label(): string
    {
        return '账号权重';
    }

    /**
     * @return array<string,mixed>
     */
    public function value(): array
    {
        return [
            'score' => $this->score,
            'grade' => $this->grade,
            'label' => $this->weightLabel(),
            'summary' => $this->summary(),
            'pool' => $this->pool,
            'strengths' => $this->fieldsByHealth(false),
            'weaknesses' => $this->fieldsByHealth(true),
        ];
    }

    public function messages(): array
    {
        return [$this->summary()];
    }

    private function weightLabel(): string
    {
        return match ($this->grade) {
            'S' => '高权重',
            'A' => '优质权重',
            'B' => '成长权重',
            'C' => '基础权重',
            default => '低权重',
        };
    }

    private function summary(): string
    {
        $poolLabel = (string) ($this->pool['label'] ?? '未知流量池');
        return "账号当前为{$this->weightLabel()}，总分 {$this->score}，等级 {$this->grade}，接近{$poolLabel}。";
    }

    /**
     * @return array<int,string>
     */
    private function fieldsByHealth(bool $weak): array
    {
        $labels = [];
        foreach ($this->fields as $field) {
            $isWeak = $this->hasWeakMessage($field) || in_array((string) ($field['level'] ?? ''), ['weak', 'risk'], true);
            if ($isWeak === $weak) {
                $labels[] = (string) ($field['label'] ?? $field['key'] ?? '');
            }
        }
        return array_values(array_filter($labels));
    }

    /**
     * @param array<string,mixed> $field
     */
    private function hasWeakMessage(array $field): bool
    {
        $keywords = ['为空', '偏低', '偏弱', '不足', '过少', '未认证', '过短', '过长', '略高', '过高', '无法判断', '不在 SDK 标准范围', '不是 user'];
        foreach (is_array($field['messages'] ?? null) ? $field['messages'] : [] as $message) {
            foreach ($keywords as $keyword) {
                if (str_contains((string) $message, $keyword)) {
                    return true;
                }
            }
        }
        return false;
    }
}

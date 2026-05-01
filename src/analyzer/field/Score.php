<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 分数解释字段。
 *
 * 来源：总分析器计算出的 score 和 grade。
 * 作用：把数字分数转换成中文解释，方便后台直接展示。
 */
class Score extends AbstractField
{
    private int $score;

    private string $grade;

    public function __construct(int $score, string $grade)
    {
        parent::__construct(['score' => $score, 'grade' => $grade]);
        $this->score = max(0, min(100, $score));
        $this->grade = $grade;
    }

    public function key(): string
    {
        return 'score';
    }

    public function label(): string
    {
        return '分数解释';
    }

    /**
     * @return array<string,mixed>
     */
    public function value(): array
    {
        return [
            'score' => $this->score,
            'grade' => $this->grade,
            'label' => $this->scoreLabel(),
            'description' => $this->description(),
        ];
    }

    public function messages(): array
    {
        return [$this->description()];
    }

    private function scoreLabel(): string
    {
        if ($this->score >= 85) return '高权重账号';
        if ($this->score >= 70) return '优质账号';
        if ($this->score >= 55) return '成长账号';
        if ($this->score >= 40) return '基础账号';
        return '低权重账号';
    }

    private function description(): string
    {
        return "账号权重分 {$this->score}，等级 {$this->grade}，属于{$this->scoreLabel()}。";
    }
}

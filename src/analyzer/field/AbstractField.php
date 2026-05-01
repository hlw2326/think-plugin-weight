<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

use JsonSerializable;

/**
 * 账号权重字段基类。
 *
 * 作用：
 * - 统一字段对象的输出格式和原始值转换方法。
 * - 每个具体字段类只需要实现 key、label、value 和可选 messages。
 * - Analyzer 会把所有字段对象汇总成 fields 明细。
 */
abstract class AbstractField implements JsonSerializable
{
    public function __construct(protected mixed $rawValue)
    {
    }

    /**
     * 字段唯一标识，通常与 SDK 字段或数据库字段一致。
     */
    abstract public function key(): string;

    /**
     * 后台展示用的中文字段名。
     */
    abstract public function label(): string;

    /**
     * 返回清洗后的字段值，子类可按需要转成整数、浮点数或字符串。
     */
    public function value(): mixed
    {
        return $this->rawValue;
    }

    /**
     * 当前字段的中文检测说明。
     *
     * 总分析器会根据 messages 里的风险描述收集优化建议。
     *
     * @return array<int,string>
     */
    public function messages(): array
    {
        return [$this->label() . '检测完成'];
    }

    /**
     * 标准化输出给总分析器、raw_result 和后台详情使用。
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'value' => $this->value(),
            'messages' => $this->messages(),
        ];
    }

    /**
     * 让字段对象可以直接 json_encode。
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    protected function intValue(): int
    {
        return max(0, (int) $this->rawValue);
    }

    protected function stringValue(): string
    {
        return trim((string) $this->rawValue);
    }
}

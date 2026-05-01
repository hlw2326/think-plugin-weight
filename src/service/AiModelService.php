<?php

declare(strict_types=1);

namespace plugin\weight\service;

use InvalidArgumentException;

/**
 * AI 大模型配置与调用服务。
 *
 * 作用：
 * - 维护国产大模型供应商预设。
 * - 读取并归一化后台保存的模型配置。
 * - 使用 openai-php/client 调用 OpenAI 兼容接口。
 */
class AiModelService
{
    /**
     * 国产模型供应商预设。
     *
     * base_url 为空的供应商说明官方入口可能按租户或控制台动态生成，
     * 后台仍可选择该供应商并手动填写实际 OpenAI 兼容地址。
     *
     * @return array<string,array{label:string,base_url:string,model:string,note:string}>
     */
    public static function providers(): array
    {
        return [
            'qwen' => [
                'label' => '通义千问',
                'base_url' => 'https://dashscope.aliyuncs.com/compatible-mode/v1',
                'model' => 'qwen-plus',
                'note' => '阿里云百炼 / DashScope OpenAI 兼容接口',
            ],
            'doubao' => [
                'label' => '豆包',
                'base_url' => 'https://ark.cn-beijing.volces.com/api/v3',
                'model' => 'doubao-seed-1-6-250615',
                'note' => '火山方舟 OpenAI 兼容接口，model 可填写模型名或接入点 ID',
            ],
            'deepseek' => [
                'label' => 'DeepSeek',
                'base_url' => 'https://api.deepseek.com/v1',
                'model' => 'deepseek-chat',
                'note' => 'DeepSeek OpenAI 兼容接口',
            ],
            'kimi' => [
                'label' => 'Kimi',
                'base_url' => 'https://api.moonshot.ai/v1',
                'model' => 'moonshot-v1-8k',
                'note' => '月之暗面 Moonshot / Kimi OpenAI 兼容接口',
            ],
            'hunyuan' => [
                'label' => '腾讯混元',
                'base_url' => 'https://api.hunyuan.cloud.tencent.com/v1',
                'model' => 'hunyuan-turbos-latest',
                'note' => '腾讯混元 OpenAI 兼容接口',
            ],
            'qianfan' => [
                'label' => '百度千帆/文心',
                'base_url' => 'https://qianfan.baidubce.com/v2',
                'model' => 'ernie-4.5-turbo-128k',
                'note' => '百度智能云千帆 OpenAI 兼容接口',
            ],
            'zhipu' => [
                'label' => '智谱 GLM',
                'base_url' => 'https://open.bigmodel.cn/api/paas/v4',
                'model' => 'glm-4-plus',
                'note' => '智谱大模型开放平台 OpenAI 兼容接口',
            ],
            'minimax' => [
                'label' => 'MiniMax',
                'base_url' => 'https://api.minimax.io/v1',
                'model' => 'MiniMax-Text-01',
                'note' => 'MiniMax OpenAI 兼容接口',
            ],
            'stepfun' => [
                'label' => '阶跃星辰',
                'base_url' => 'https://api.stepfun.com/v1',
                'model' => 'step-2-mini',
                'note' => '阶跃星辰 StepFun OpenAI 兼容接口',
            ],
            'xunfei' => [
                'label' => '讯飞星火',
                'base_url' => 'https://spark-api-open.xf-yun.com/v1',
                'model' => 'generalv3.5',
                'note' => '讯飞星火 OpenAI 兼容接口',
            ],
            'sensenova' => [
                'label' => '商汤日日新',
                'base_url' => 'https://api.sensenova.cn/compatible-mode/v1',
                'model' => 'SenseChat-5',
                'note' => '商汤日日新 OpenAI 兼容接口',
            ],
            'baichuan' => [
                'label' => '百川智能',
                'base_url' => 'https://api.baichuan-ai.com/v1',
                'model' => 'Baichuan4-Turbo',
                'note' => '百川智能 OpenAI 兼容接口',
            ],
            'yi' => [
                'label' => '零一万物',
                'base_url' => 'https://api.lingyiwanwu.com/v1',
                'model' => 'yi-lightning',
                'note' => '零一万物 OpenAI 兼容接口',
            ],
            'mimo' => [
                'label' => '小米 MiMo',
                'base_url' => '',
                'model' => '',
                'note' => '小米 MiMo，按开放平台提供的 OpenAI 兼容地址和模型名填写',
            ],
            'pangu' => [
                'label' => '华为盘古',
                'base_url' => '',
                'model' => '',
                'note' => '华为盘古，按实际接入网关填写 OpenAI 兼容地址和模型名',
            ],
            'tiangong' => [
                'label' => '天工大模型',
                'base_url' => '',
                'model' => '',
                'note' => '昆仑万维天工，按开放平台提供的兼容接口填写',
            ],
            'brain360' => [
                'label' => '360 智脑',
                'base_url' => '',
                'model' => '',
                'note' => '360 智脑，按开放平台提供的兼容接口填写',
            ],
            'siliconflow' => [
                'label' => '硅基流动',
                'base_url' => 'https://api.siliconflow.cn/v1',
                'model' => 'deepseek-ai/DeepSeek-V3',
                'note' => '国产模型推理平台，可接入多种国产模型',
            ],
            'custom' => [
                'label' => '自定义兼容接口',
                'base_url' => '',
                'model' => '',
                'note' => '手动填写任意 OpenAI 兼容 base_url 和模型名',
            ],
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function providerOptions(): array
    {
        $options = [];
        foreach (self::providers() as $code => $provider) {
            $options[$code] = $provider['label'];
        }
        return $options;
    }

    /**
     * @return array{label:string,base_url:string,model:string,note:string}
     */
    public static function provider(string $code): array
    {
        $providers = self::providers();
        return $providers[$code] ?? $providers['qwen'];
    }

    /**
     * 读取后台保存的模型配置。
     *
     * @return array<string,mixed>
     */
    public static function config(): array
    {
        return self::configFromArray([
            'enabled' => self::conf('weight.ai_enabled', '0'),
            'provider' => self::conf('weight.ai_provider', 'qwen'),
            'api_key' => self::conf('weight.ai_api_key', ''),
            'base_url' => self::conf('weight.ai_base_url', ''),
            'model' => self::conf('weight.ai_model', ''),
            'temperature' => self::conf('weight.ai_temperature', '0.3'),
            'max_tokens' => self::conf('weight.ai_max_tokens', '1200'),
            'system_prompt' => self::conf('weight.ai_system_prompt', self::defaultSystemPrompt()),
        ]);
    }

    /**
     * 归一化配置数组，方便控制器、测试和后续业务复用。
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public static function configFromArray(array $data): array
    {
        $providers = self::providers();
        $providerCode = strtolower(trim((string) ($data['provider'] ?? 'qwen')));
        if (!isset($providers[$providerCode])) {
            $providerCode = 'qwen';
        }

        $provider = $providers[$providerCode];
        $baseUrl = trim((string) ($data['base_url'] ?? ''));
        $model = trim((string) ($data['model'] ?? ''));

        return [
            'enabled' => self::boolValue($data['enabled'] ?? false),
            'provider' => $providerCode,
            'label' => $provider['label'],
            'api_key' => trim((string) ($data['api_key'] ?? '')),
            'api_key_mask' => self::maskApiKey((string) ($data['api_key'] ?? '')),
            'base_url' => self::normalizeBaseUrl($baseUrl !== '' ? $baseUrl : $provider['base_url']),
            'model' => $model !== '' ? $model : $provider['model'],
            'temperature' => self::temperature($data['temperature'] ?? 0.3),
            'max_tokens' => self::maxTokens($data['max_tokens'] ?? 1200),
            'system_prompt' => trim((string) ($data['system_prompt'] ?? self::defaultSystemPrompt())),
            'note' => $provider['note'],
        ];
    }

    /**
     * 调用当前配置的大模型。
     *
     * @param array<int,array{role:string,content:string}> $messages
     */
    public static function chat(string $content, array $messages = [], ?array $config = null): string
    {
        $config = $config === null ? self::config() : self::configFromArray($config);
        self::assertUsableConfig($config);

        if ($messages === []) {
            $messages = [
                ['role' => 'system', 'content' => (string) $config['system_prompt']],
                ['role' => 'user', 'content' => $content],
            ];
        }

        $response = \OpenAI::factory()
            ->withApiKey((string) $config['api_key'])
            ->withBaseUri((string) $config['base_url'])
            ->make()
            ->chat()
            ->create([
                'model' => (string) $config['model'],
                'messages' => $messages,
                'temperature' => (float) $config['temperature'],
                'max_tokens' => (int) $config['max_tokens'],
            ]);

        return trim((string) ($response->choices[0]->message->content ?? ''));
    }

    public static function maskApiKey(string $apiKey): string
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            return '';
        }
        if (mb_strlen($apiKey) <= 8) {
            return str_repeat('*', mb_strlen($apiKey));
        }
        return mb_substr($apiKey, 0, 4) . '****' . mb_substr($apiKey, -4);
    }

    public static function defaultSystemPrompt(): string
    {
        return '你是账号权重分析助手，请基于账号信息和作品数据输出中文分析建议，内容要具体、克制、可执行。';
    }

    /**
     * @param array<string,mixed> $config
     */
    private static function assertUsableConfig(array $config): void
    {
        if (empty($config['enabled'])) {
            throw new InvalidArgumentException('AI 模型分析未启用');
        }
        if ((string) $config['api_key'] === '') {
            throw new InvalidArgumentException('请先配置 AI 模型 API Key');
        }
        if ((string) $config['base_url'] === '') {
            throw new InvalidArgumentException('请先配置 AI 模型接口地址');
        }
        if ((string) $config['model'] === '') {
            throw new InvalidArgumentException('请先配置 AI 模型名称');
        }
    }

    private static function conf(string $key, string $default = ''): string
    {
        if (!function_exists('sysconf')) {
            return $default;
        }
        $value = sysconf($key);
        return $value === '' || $value === null ? $default : (string) $value;
    }

    private static function boolValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }

    private static function normalizeBaseUrl(string $baseUrl): string
    {
        return rtrim(trim($baseUrl), '/');
    }

    private static function temperature(mixed $value): float
    {
        return max(0.0, min(2.0, round((float) $value, 2)));
    }

    private static function maxTokens(mixed $value): int
    {
        return max(1, min(128000, (int) $value));
    }
}

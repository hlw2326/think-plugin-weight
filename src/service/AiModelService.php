<?php

declare(strict_types=1);

namespace plugin\weight\service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;
use Throwable;

/**
 * AI 大模型配置与调用服务
 *
 * 作用：
 * - 维护国产大模型供应商预设
 * - 读取并归一化后台保存的模型配置
 * - 使用 OpenAI 兼容 HTTP 接口调用模型
 */
class AiModelService
{
    /**
     * 国产模型供应商预设
     *
     * base_url 为空的供应商说明官方入口可能按租户或控制台动态生成，
     * 后台仍可选择该供应商并手动填写实际 OpenAI 兼容地址
     *
     * @return array<string,array{label:string,base_url:string,model:string,note:string,models:array<int,string>}>
     */
    public static function providers(): array
    {
        return [
            'qwen' => [
                'label' => '通义千问',
                'base_url' => 'https://dashscope.aliyuncs.com/compatible-mode/v1',
                'model' => 'qwen-plus',
                'note' => '阿里云百炼 / DashScope OpenAI 兼容接口',
                'models' => ['qwen-plus', 'qwen-max', 'qwen-turbo', 'qwen-long'],
            ],
            'doubao' => [
                'label' => '豆包',
                'base_url' => 'https://ark.cn-beijing.volces.com/api/v3',
                'model' => 'doubao-seed-1-6-250615',
                'note' => '火山方舟 OpenAI 兼容接口，model 可填写模型名或接入点 ID',
                'models' => ['doubao-seed-1-6-250615', 'doubao-seed-1-6-thinking-250715', 'doubao-1-5-pro-32k'],
            ],
            'deepseek' => [
                'label' => 'DeepSeek',
                'base_url' => 'https://api.deepseek.com',
                'model' => 'deepseek-v4-flash',
                'note' => 'DeepSeek OpenAI 兼容接口，调用接口需要 DeepSeek API Key',
                'models' => ['deepseek-v4-flash', 'deepseek-v4-pro', 'deepseek-chat', 'deepseek-reasoner'],
            ],
            'kimi' => [
                'label' => 'Kimi',
                'base_url' => 'https://api.moonshot.ai/v1',
                'model' => 'moonshot-v1-8k',
                'note' => '月之暗面 Moonshot / Kimi OpenAI 兼容接口',
                'models' => ['kimi-k2.5', 'kimi-k2-thinking-turbo', 'moonshot-v1-8k', 'moonshot-v1-32k', 'moonshot-v1-128k'],
            ],
            'hunyuan' => [
                'label' => '腾讯混元',
                'base_url' => 'https://api.hunyuan.cloud.tencent.com/v1',
                'model' => 'hunyuan-turbos-latest',
                'note' => '腾讯混元 OpenAI 兼容接口',
                'models' => ['hunyuan-turbos-latest', 'hunyuan-large', 'hunyuan-standard'],
            ],
            'qianfan' => [
                'label' => '百度千帆/文心',
                'base_url' => 'https://qianfan.baidubce.com/v2',
                'model' => 'ernie-4.5-turbo-128k',
                'note' => '百度智能云千帆 OpenAI 兼容接口',
                'models' => ['ernie-4.5-turbo-128k', 'ernie-4.5-8k-preview', 'ernie-4.0-turbo-8k'],
            ],
            'zhipu' => [
                'label' => '智谱 GLM',
                'base_url' => 'https://open.bigmodel.cn/api/paas/v4',
                'model' => 'glm-4-plus',
                'note' => '智谱大模型开放平台 OpenAI 兼容接口',
                'models' => ['glm-4-plus', 'glm-4-air', 'glm-4-flash', 'glm-z1-air'],
            ],
            'minimax' => [
                'label' => 'MiniMax',
                'base_url' => 'https://api.minimax.io/v1',
                'model' => 'MiniMax-Text-01',
                'note' => 'MiniMax OpenAI 兼容接口',
                'models' => ['MiniMax-Text-01', 'MiniMax-M1'],
            ],
            'stepfun' => [
                'label' => '阶跃星辰',
                'base_url' => 'https://api.stepfun.com/v1',
                'model' => 'step-2-mini',
                'note' => '阶跃星辰 StepFun OpenAI 兼容接口',
                'models' => ['step-2-mini', 'step-1-8k', 'step-1-32k'],
            ],
            'xunfei' => [
                'label' => '讯飞星火',
                'base_url' => 'https://spark-api-open.xf-yun.com/v1',
                'model' => 'generalv3.5',
                'note' => '讯飞星火 OpenAI 兼容接口',
                'models' => ['generalv3.5', 'generalv3', '4.0Ultra'],
            ],
            'sensenova' => [
                'label' => '商汤日日新',
                'base_url' => 'https://api.sensenova.cn/compatible-mode/v1',
                'model' => 'SenseChat-5',
                'note' => '商汤日日新 OpenAI 兼容接口',
                'models' => ['SenseChat-5', 'SenseChat-Turbo'],
            ],
            'baichuan' => [
                'label' => '百川智能',
                'base_url' => 'https://api.baichuan-ai.com/v1',
                'model' => 'Baichuan4-Turbo',
                'note' => '百川智能 OpenAI 兼容接口',
                'models' => ['Baichuan4-Turbo', 'Baichuan4-Air'],
            ],
            'yi' => [
                'label' => '零一万物',
                'base_url' => 'https://api.lingyiwanwu.com/v1',
                'model' => 'yi-lightning',
                'note' => '零一万物 OpenAI 兼容接口',
                'models' => ['yi-lightning', 'yi-large', 'yi-medium'],
            ],
            'mimo' => [
                'label' => '小米 MiMo',
                'base_url' => '',
                'model' => '',
                'note' => '小米 MiMo，按开放平台提供的 OpenAI 兼容地址和模型名填写',
                'models' => [],
            ],
            'pangu' => [
                'label' => '华为盘古',
                'base_url' => '',
                'model' => '',
                'note' => '华为盘古，按实际接入网关填写 OpenAI 兼容地址和模型名',
                'models' => [],
            ],
            'tiangong' => [
                'label' => '天工大模型',
                'base_url' => '',
                'model' => '',
                'note' => '昆仑万维天工，按开放平台提供的兼容接口填写',
                'models' => [],
            ],
            'brain360' => [
                'label' => '360 智脑',
                'base_url' => '',
                'model' => '',
                'note' => '360 智脑，按开放平台提供的兼容接口填写',
                'models' => [],
            ],
            'siliconflow' => [
                'label' => '硅基流动',
                'base_url' => 'https://api.siliconflow.cn/v1',
                'model' => 'deepseek-ai/DeepSeek-V3',
                'note' => '国产模型推理平台，可接入多种国产模型',
                'models' => ['deepseek-ai/DeepSeek-V3', 'deepseek-ai/DeepSeek-R1', 'Qwen/Qwen2.5-72B-Instruct'],
            ],
            'openrouter' => [
                'label' => 'OpenRouter',
                'base_url' => 'https://openrouter.ai/api/v1',
                'model' => 'openai/gpt-5.4-mini',
                'note' => 'OpenRouter 聚合模型接口，模型列表可无密钥公开获取，调用模型仍需 API Key',
                'models' => ['openai/gpt-5.4-mini', 'openai/gpt-5.5', 'openai/gpt-5.4', 'deepseek/deepseek-chat'],
            ],
            'custom' => [
                'label' => '自定义兼容接口',
                'base_url' => '',
                'model' => '',
                'note' => '手动填写任意 OpenAI 兼容 base_url 和模型名',
                'models' => [],
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
     * @return array{label:string,base_url:string,model:string,note:string,models:array<int,string>}
     */
    public static function provider(string $code): array
    {
        $providers = self::providers();
        return $providers[$code] ?? $providers['qwen'];
    }

    /**
     * 返回供应商内置推荐模型
     *
     * @return array<int,string>
     */
    public static function modelOptions(string $provider): array
    {
        $providerConfig = self::provider($provider);
        return self::uniqueStrings([
            (string) ($providerConfig['model'] ?? ''),
            ...($providerConfig['models'] ?? []),
        ]);
    }

    public static function modelsEndpoint(string $baseUrl): string
    {
        return self::normalizeBaseUrl($baseUrl) . '/models';
    }

    /**
     * 从 OpenAI 兼容模型列表响应中提取模型名称
     *
     * @param array<string,mixed> $payload
     * @return array<int,string>
     */
    public static function extractModelIds(array $payload): array
    {
        $models = [];
        foreach (['data', 'models'] as $key) {
            $items = is_array($payload[$key] ?? null) ? $payload[$key] : [];
            foreach ($items as $item) {
                if (is_string($item)) {
                    $models[] = $item;
                    continue;
                }
                if (!is_array($item)) {
                    continue;
                }
                $models[] = (string) ($item['id'] ?? $item['name'] ?? '');
            }
        }

        return self::uniqueStrings($models);
    }

    /**
     * 获取可选模型列表
     *
     * 有 Base URL 时优先请求 OpenAI 兼容 /models；
     * API Key 为空时按公开模型列表接口尝试无鉴权请求，失败后返回内置推荐模型
     *
     * @param array<string,mixed> $data
     * @return array{online:bool,models:array<int,string>,message:string,endpoint:string}
     */
    public static function listModels(array $data, ?Client $client = null): array
    {
        $config = self::configFromArray($data);
        $fallback = self::fallbackModels($config);
        $endpoint = (string) $config['base_url'] !== '' ? self::modelsEndpoint((string) $config['base_url']) : '';

        if ($endpoint === '') {
            return [
                'online' => false,
                'models' => $fallback,
                'message' => '未配置 Base URL，已显示内置推荐模型',
                'endpoint' => $endpoint,
            ];
        }

        try {
            $headers = ['Accept' => 'application/json'];
            if ((string) $config['api_key'] !== '') {
                $headers['Authorization'] = 'Bearer ' . (string) $config['api_key'];
            }

            $client ??= new Client(['timeout' => 12, 'http_errors' => true]);
            $response = $client->get($endpoint, ['headers' => $headers]);
            $payload = json_decode((string) $response->getBody(), true);
            if (!is_array($payload)) {
                throw new InvalidArgumentException('模型列表接口返回不是 JSON 对象');
            }

            $models = self::uniqueStrings([
                ...self::extractModelIds($payload),
                ...$fallback,
            ]);
            if ($models === []) {
                throw new InvalidArgumentException('模型列表为空');
            }

            return [
                'online' => true,
                'models' => $models,
                'message' => '已获取模型列表',
                'endpoint' => $endpoint,
            ];
        } catch (Throwable $exception) {
            $prefix = (string) $config['api_key'] === '' ? '公开模型列表不可用' : '获取模型列表失败';
            return [
                'online' => false,
                'models' => $fallback,
                'message' => $prefix . '，已显示内置推荐模型：' . $exception->getMessage(),
                'endpoint' => $endpoint,
            ];
        }
    }

    /**
     * 读取后台保存的模型配置
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
     * 归一化配置数组，方便控制器、测试和后续业务复用
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
     * 调用当前配置的大模型
     *
     * @param array<int,array{role:string,content:string}> $messages
     */
    public static function chat(string $content, array $messages = [], ?array $config = null, ?Client $client = null): string
    {
        $config = $config === null ? self::config() : self::configFromArray($config);
        self::assertUsableConfig($config);

        if ($messages === []) {
            $messages = [
                ['role' => 'system', 'content' => (string) $config['system_prompt']],
                ['role' => 'user', 'content' => $content],
            ];
        }

        return self::extractChatReply(self::requestChatCompletion($config, $messages, $client));
    }

    /**
     * 使用当前配置发起一次轻量请求，验证模型、密钥和地址是否可用
     *
     * @param array<string,mixed> $data
     * @return array{reply:string}
     */
    public static function testConnection(array $data, ?Client $client = null): array
    {
        $config = self::configFromArray($data);
        self::assertConnectableConfig($config);

        $payload = self::requestChatCompletion($config, [
            ['role' => 'user', 'content' => '请只回复：连接正常'],
        ], $client, [
            'temperature' => 0,
            'max_tokens' => 16,
        ]);

        return ['reply' => self::extractChatReply($payload)];
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
        self::assertConnectableConfig($config);
    }

    /**
     * @param array<string,mixed> $config
     */
    private static function assertConnectableConfig(array $config): void
    {
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

    /**
     * @param array<string,mixed> $config
     * @param array<int,array{role:string,content:string}> $messages
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private static function requestChatCompletion(array $config, array $messages, ?Client $client = null, array $overrides = []): array
    {
        $client ??= new Client(['timeout' => 60, 'http_errors' => true]);
        try {
            $response = $client->post(self::chatCompletionEndpoint((string) $config['base_url']), [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . (string) $config['api_key'],
                ],
                'json' => [
                    'model' => (string) $config['model'],
                    'messages' => $messages,
                    'temperature' => (float) $config['temperature'],
                    'max_tokens' => (int) $config['max_tokens'],
                ] + $overrides,
            ]);
        } catch (RequestException $exception) {
            throw new InvalidArgumentException(self::requestExceptionMessage($exception), 0, $exception);
        }

        $payload = json_decode((string) $response->getBody(), true);
        if (!is_array($payload)) {
            throw new InvalidArgumentException('模型接口返回不是 JSON 对象');
        }
        if (is_array($payload['error'] ?? null)) {
            $message = trim((string) ($payload['error']['message'] ?? '模型接口返回错误'));
            throw new InvalidArgumentException($message !== '' ? $message : '模型接口返回错误');
        }

        return $payload;
    }

    private static function requestExceptionMessage(RequestException $exception): string
    {
        $response = $exception->getResponse();
        if ($response !== null) {
            $body = trim((string) $response->getBody());
            if ($body !== '') {
                $payload = json_decode($body, true);
                if (is_array($payload)) {
                    $message = $payload['error']['message'] ?? $payload['message'] ?? $payload['msg'] ?? '';
                    if (trim((string) $message) !== '') {
                        return trim((string) $message);
                    }
                }
                return mb_substr($body, 0, 500);
            }
            return '模型接口请求失败，HTTP 状态码：' . $response->getStatusCode();
        }

        $message = trim($exception->getMessage());
        return $message !== '' ? $message : '模型接口请求失败：' . $exception::class;
    }

    public static function chatCompletionEndpoint(string $baseUrl): string
    {
        return self::normalizeBaseUrl($baseUrl) . '/chat/completions';
    }

    /**
     * @param array<string,mixed> $payload
     */
    private static function extractChatReply(array $payload): string
    {
        $message = $payload['choices'][0]['message'] ?? [];
        if (!is_array($message)) {
            return '';
        }

        $content = $message['content'] ?? '';
        if (is_string($content)) {
            return trim($content);
        }
        if (is_array($content)) {
            return trim(implode('', array_map(static function (mixed $item): string {
                if (is_array($item)) {
                    return (string) ($item['text'] ?? '');
                }
                return is_scalar($item) ? (string) $item : '';
            }, $content)));
        }

        return '';
    }

    /**
     * @param array<string,mixed> $config
     * @return array<int,string>
     */
    private static function fallbackModels(array $config): array
    {
        return self::uniqueStrings([
            (string) ($config['model'] ?? ''),
            ...self::modelOptions((string) ($config['provider'] ?? 'qwen')),
        ]);
    }

    /**
     * @param array<int,string> $values
     * @return array<int,string>
     */
    private static function uniqueStrings(array $values): array
    {
        $result = [];
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '' && !in_array($value, $result, true)) {
                $result[] = $value;
            }
        }

        return $result;
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

<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use plugin\weight\service\AiModelService;

if (!class_exists(\OpenAI\Factory::class)) {
    throw new RuntimeException('期望已安装 openai-php/client');
}

$providers = AiModelService::providers();
$required = [
    'qwen',
    'doubao',
    'deepseek',
    'kimi',
    'hunyuan',
    'qianfan',
    'zhipu',
    'minimax',
    'stepfun',
    'xunfei',
    'sensenova',
    'baichuan',
    'yi',
    'mimo',
    'pangu',
    'tiangong',
    'brain360',
    'siliconflow',
    'openrouter',
    'custom',
];

foreach ($required as $code) {
    if (empty($providers[$code]['label'])) {
        throw new RuntimeException("缺少国产模型供应商预设：{$code}");
    }
    if (!array_key_exists('models', $providers[$code]) || !is_array($providers[$code]['models'])) {
        throw new RuntimeException("供应商预设需要包含推荐模型列表：{$code}");
    }
}

if (($providers['qwen']['base_url'] ?? '') !== 'https://dashscope.aliyuncs.com/compatible-mode/v1') {
    throw new RuntimeException('通义千问 base_url 预设不正确');
}

if (($providers['doubao']['base_url'] ?? '') !== 'https://ark.cn-beijing.volces.com/api/v3') {
    throw new RuntimeException('豆包 base_url 预设不正确');
}

if (($providers['deepseek']['base_url'] ?? '') !== 'https://api.deepseek.com') {
    throw new RuntimeException('DeepSeek base_url 预设不正确');
}

if (($providers['openrouter']['base_url'] ?? '') !== 'https://openrouter.ai/api/v1') {
    throw new RuntimeException('OpenRouter base_url 预设不正确');
}

if (!in_array('deepseek-v4-flash', AiModelService::modelOptions('deepseek'), true)) {
    throw new RuntimeException('DeepSeek 推荐模型应包含官方当前默认模型');
}

if (!in_array('deepseek-v4-pro', AiModelService::modelOptions('deepseek'), true)) {
    throw new RuntimeException('DeepSeek 推荐模型应包含官方推理模型');
}

if (AiModelService::modelsEndpoint('https://api.deepseek.com/') !== 'https://api.deepseek.com/models') {
    throw new RuntimeException('模型列表接口地址拼接不正确');
}

$modelIds = AiModelService::extractModelIds([
    'data' => [
        ['id' => 'deepseek-chat'],
        ['id' => 'deepseek-reasoner'],
        ['name' => 'ignored-name'],
    ],
]);
if ($modelIds !== ['deepseek-chat', 'deepseek-reasoner', 'ignored-name']) {
    throw new RuntimeException('模型列表解析应兼容 id/name 字段');
}

$fallbackModels = AiModelService::listModels([
    'provider' => 'deepseek',
    'api_key' => '',
    'base_url' => '',
    'model' => '',
]);
if (!empty($fallbackModels['online']) || !in_array('deepseek-chat', $fallbackModels['models'], true)) {
    throw new RuntimeException('没有 API Key 时应返回内置推荐模型兜底');
}

$history = [];
$mock = new MockHandler([
    new Response(200, ['Content-Type' => 'application/json'], json_encode([
        'data' => [
            ['id' => 'openrouter/test-model'],
        ],
    ], JSON_THROW_ON_ERROR)),
]);
$handler = HandlerStack::create($mock);
$handler->push(Middleware::history($history));
$publicModels = AiModelService::listModels([
    'provider' => 'custom',
    'api_key' => '',
    'base_url' => 'https://openrouter.ai/api/v1',
    'model' => '',
], new Client(['handler' => $handler]));
if (empty($publicModels['online']) || !in_array('openrouter/test-model', $publicModels['models'], true)) {
    throw new RuntimeException('无 API Key 时应尝试公开模型列表接口');
}
if (($history[0]['request']->getHeaderLine('Authorization') ?? '') !== '') {
    throw new RuntimeException('无 API Key 拉取公开模型列表时不应发送 Authorization');
}

$chatHistory = [];
$chatMock = new MockHandler([
    new Response(200, ['Content-Type' => 'application/json'], json_encode([
        'id' => 'chatcmpl-test',
        'object' => 'chat.completion',
        'created' => time(),
        'model' => 'deepseek-v4-flash',
        'choices' => [
            [
                'index' => 0,
                'message' => [
                    'role' => 'assistant',
                    'content' => '连接正常',
                ],
                'finish_reason' => 'stop',
            ],
        ],
        'usage' => [
            'prompt_tokens' => 8,
            'completion_tokens' => 2,
            'total_tokens' => 10,
            'completion_tokens_details' => [
                'reasoning_tokens' => 0,
            ],
        ],
    ], JSON_THROW_ON_ERROR)),
]);
$chatHandler = HandlerStack::create($chatMock);
$chatHandler->push(Middleware::history($chatHistory));
$testResult = AiModelService::testConnection([
    'provider' => 'deepseek',
    'api_key' => 'sk-test',
    'base_url' => 'https://api.deepseek.com/v1',
    'model' => 'deepseek-v4-flash',
], new Client(['handler' => $chatHandler]));
if (($testResult['reply'] ?? '') !== '连接正常') {
    throw new RuntimeException('测试连接应兼容缺少 accepted_prediction_tokens 的模型响应');
}
if (($chatHistory[0]['request']->getHeaderLine('Authorization') ?? '') !== 'Bearer sk-test') {
    throw new RuntimeException('测试连接应发送 API Key 鉴权头');
}

$missingKeyMessage = '';
try {
    AiModelService::testConnection([
        'provider' => 'deepseek',
        'api_key' => '',
        'base_url' => 'https://api.deepseek.com',
        'model' => 'deepseek-v4-flash',
    ]);
} catch (Throwable $exception) {
    $missingKeyMessage = $exception->getMessage();
}
if ($missingKeyMessage !== '请先配置 AI 模型 API Key') {
    throw new RuntimeException('测试连接缺少 API Key 时应返回明确提示');
}

$errorMock = new MockHandler([
    new Response(401, ['Content-Type' => 'application/json'], json_encode([
        'error' => [
            'message' => 'invalid api key',
        ],
    ], JSON_THROW_ON_ERROR)),
]);
$errorMessage = '';
try {
    AiModelService::testConnection([
        'provider' => 'deepseek',
        'api_key' => 'sk-test',
        'base_url' => 'https://api.deepseek.com/v1',
        'model' => 'deepseek-v4-flash',
    ], new Client(['handler' => HandlerStack::create($errorMock)]));
} catch (Throwable $exception) {
    $errorMessage = $exception->getMessage();
}
if ($errorMessage !== 'invalid api key') {
    throw new RuntimeException('测试连接应优先返回模型接口 error.message');
}

$plainTextErrorMock = new MockHandler([
    new Response(401, ['Content-Type' => 'text/plain'], 'Authentication Fails (governor)'),
]);
$plainTextErrorMessage = '';
try {
    AiModelService::testConnection([
        'provider' => 'deepseek',
        'api_key' => 'sk-test',
        'base_url' => 'https://api.deepseek.com',
        'model' => 'deepseek-v4-flash',
    ], new Client(['handler' => HandlerStack::create($plainTextErrorMock)]));
} catch (Throwable $exception) {
    $plainTextErrorMessage = $exception->getMessage();
}
if ($plainTextErrorMessage !== 'Authentication Fails (governor)') {
    throw new RuntimeException('测试连接应透出模型接口纯文本错误');
}

$config = AiModelService::configFromArray([
    'enabled' => '1',
    'provider' => 'qwen',
    'api_key' => 'sk-test',
    'base_url' => '',
    'model' => '',
    'temperature' => '0.3',
    'max_tokens' => '1024',
    'system_prompt' => '你是账号权重分析助手',
]);

if ($config['provider'] !== 'qwen' || $config['label'] !== '通义千问') {
    throw new RuntimeException('期望配置能识别通义千问供应商');
}

if ($config['base_url'] !== $providers['qwen']['base_url'] || $config['model'] !== $providers['qwen']['model']) {
    throw new RuntimeException('期望空 base_url/model 使用供应商预设');
}

if ($config['temperature'] !== 0.3 || $config['max_tokens'] !== 1024) {
    throw new RuntimeException('期望正确归一化模型参数');
}

$custom = AiModelService::configFromArray([
    'enabled' => '1',
    'provider' => 'custom',
    'api_key' => 'sk-custom',
    'base_url' => 'https://example.com/v1',
    'model' => 'custom-model',
]);

if ($custom['base_url'] !== 'https://example.com/v1' || $custom['model'] !== 'custom-model') {
    throw new RuntimeException('期望自定义供应商保留自定义 base_url/model');
}

if (AiModelService::maskApiKey('sk-1234567890') !== 'sk-1****7890') {
    throw new RuntimeException('期望 API Key 脱敏显示');
}

$controller = (string) file_get_contents(__DIR__ . '/../src/controller/config/Index.php');
foreach (['public function models(): void', 'public function test(): void', 'AiModelService::listModels', 'AiModelService::testConnection', 'currentConfigInput', 'formatExceptionMessage', '连接失败：'] as $keyword) {
    if (!str_contains($controller, $keyword)) {
        throw new RuntimeException("AI 配置控制器缺少模型获取或连接测试能力：{$keyword}");
    }
}
foreach (['use think\\exception\\HttpResponseException;', 'catch (HttpResponseException $exception)', 'throw $exception;'] as $keyword) {
    if (!str_contains($controller, $keyword)) {
        throw new RuntimeException("AI 配置控制器不应把 ThinkAdmin 成功响应误判为连接失败：{$keyword}");
    }
}

$view = (string) file_get_contents(__DIR__ . '/../src/view/config/index/index.html');
foreach (['FetchAiModels', 'TestAiModelConnection', 'AiModelSelect', 'lay-filter="modelSelect"', 'url("models")', 'url("test")', 'refreshModelSelect', 'currentAiConfig', 'input-right-icon', 'layui-icon-refresh', 'ai-model-control', 'ai-model-input', 'ai-model-help', 'ai-model-select-wrap'] as $keyword) {
    if (!str_contains($view, $keyword)) {
        throw new RuntimeException("AI 配置页缺少智能模型选择交互：{$keyword}");
    }
}
foreach (['ai-model-row', 'layui-col-xs12 layui-col-md8', 'layui-col-xs12 layui-col-md4'] as $keyword) {
    if (!str_contains($view, $keyword)) {
        throw new RuntimeException("模型名称与推荐模型应在桌面端同一行显示：{$keyword}");
    }
}
foreach (['style="width:38%"', 'style="width:26%"', 'width: 520px', 'layui-input-inline ai-model-input', 'ai-model-toolbar', 'layui-col-xs7', 'layui-col-xs5'] as $keyword) {
    if (str_contains($view, $keyword)) {
        throw new RuntimeException("AI 配置页不应使用挤在一行的固定宽度模型布局：{$keyword}");
    }
}

echo "ai model config ok\n";

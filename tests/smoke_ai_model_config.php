<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

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
    'custom',
];

foreach ($required as $code) {
    if (empty($providers[$code]['label'])) {
        throw new RuntimeException("缺少国产模型供应商预设：{$code}");
    }
}

if (($providers['qwen']['base_url'] ?? '') !== 'https://dashscope.aliyuncs.com/compatible-mode/v1') {
    throw new RuntimeException('通义千问 base_url 预设不正确');
}

if (($providers['doubao']['base_url'] ?? '') !== 'https://ark.cn-beijing.volces.com/api/v3') {
    throw new RuntimeException('豆包 base_url 预设不正确');
}

if (($providers['deepseek']['base_url'] ?? '') !== 'https://api.deepseek.com/v1') {
    throw new RuntimeException('DeepSeek base_url 预设不正确');
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

echo "ai model config ok\n";

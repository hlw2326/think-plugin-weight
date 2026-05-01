<?php

declare(strict_types=1);

namespace plugin\weight\controller\config;

use plugin\weight\service\AiModelService;
use think\admin\Controller;

/**
 * 插件配置。
 *
 * 作用：
 * - 管理账号权重插件的 AI 大模型配置。
 * - 配置写入 sysconf，和 think-plugin-qz 的系统参数页面保持一致。
 */
class Index extends Controller
{
    /**
     * AI 模型配置。
     *
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $provider = (string) ($data['provider'] ?? 'qwen');
            if (!isset(AiModelService::providers()[$provider])) {
                $provider = 'qwen';
            }

            sysconf('weight.ai_enabled', (int) ($data['enabled'] ?? 0));
            sysconf('weight.ai_provider', $provider);
            sysconf('weight.ai_base_url', trim((string) ($data['base_url'] ?? '')));
            sysconf('weight.ai_model', trim((string) ($data['model'] ?? '')));
            sysconf('weight.ai_temperature', (string) ($data['temperature'] ?? '0.3'));
            sysconf('weight.ai_max_tokens', (string) ($data['max_tokens'] ?? '1200'));
            sysconf('weight.ai_system_prompt', trim((string) ($data['system_prompt'] ?? '')));

            $apiKey = trim((string) ($data['api_key'] ?? ''));
            if ($apiKey !== '') {
                sysconf('weight.ai_api_key', $apiKey);
            }

            $this->success('AI模型配置已保存');
        }

        $this->title = 'AI模型配置';
        $this->providers = AiModelService::providers();
        $this->ai = AiModelService::config();
        $this->fetch();
    }
}

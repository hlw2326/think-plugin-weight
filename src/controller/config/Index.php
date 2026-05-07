<?php

declare(strict_types=1);

namespace plugin\weight\controller\config;

use plugin\weight\service\AiModelService;
use think\admin\Controller;
use think\exception\HttpResponseException;
use Throwable;

/**
 * 插件配置
 *
 * 作用：
 * - 管理账号权重插件的 AI 大模型配置
 * - 平台 Cookie、UA、DID 等请求配置已迁移到 cookies.index 模块统一管理
 */
class Index extends Controller
{
    /**
     * AI 模型配置
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
        $this->current = 'index';
        $this->providers = AiModelService::providers();
        $this->ai = AiModelService::config();
        $this->fetch();
    }

    /**
     * 获取当前供应商可用模型列表
     *
     * @auth true
     */
    public function models(): void
    {
        $result = AiModelService::listModels($this->currentConfigInput());
        $this->success((string) $result['message'], $result);
    }

    /**
     * 测试当前模型配置是否可连接
     *
     * @auth true
     */
    public function test(): void
    {
        try {
            $result = AiModelService::testConnection($this->currentConfigInput());
            $reply = trim((string) ($result['reply'] ?? ''));
            $this->success($reply !== '' ? "连接成功：{$reply}" : '连接成功', $result);
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->error(self::formatExceptionMessage($exception));
        }
    }

    public static function formatExceptionMessage(Throwable $exception): string
    {
        $message = trim($exception->getMessage());
        if ($message !== '') {
            return $message;
        }

        $previous = $exception->getPrevious();
        if ($previous !== null) {
            $message = trim($previous->getMessage());
            if ($message !== '') {
                return $message;
            }
        }

        return '连接失败：' . $exception::class;
    }

    /**
     * 读取当前表单配置，API Key 留空时使用已保存的密钥
     *
     * @return array<string,mixed>
     */
    private function currentConfigInput(): array
    {
        $data = $this->request->post();
        if (trim((string) ($data['api_key'] ?? '')) === '') {
            $data['api_key'] = (string) (AiModelService::config()['api_key'] ?? '');
        }

        return $data;
    }
}

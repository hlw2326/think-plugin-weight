# AI 模型配置集成进度

## 2026-05-01

- 已确认 `think-plugin-qz` 配置页使用 `sysconf()` 保存。
- 已确定 `think-plugin-weight` 需要新增配置控制器、视图、菜单和 AI 服务类。
- 已调研主流国产模型供应商的 OpenAI 兼容地址。
- 已执行 `composer require openai-php/client guzzlehttp/guzzle`，依赖安装成功。
- Composer 提示当前 PHP 环境缺少 zip/unzip/7z，已自动回退 source 安装，不影响本次依赖安装结果。
- 已新增 `tests/smoke_ai_model_config.php`，当前按预期失败：`AiModelService` 尚未实现。
- 已新增 `src/service/AiModelService.php`，内置国产模型供应商预设并封装 OpenAI 兼容调用。
- 已新增 `src/controller/config/Index.php` 和 `src/view/config/index/index.html`，后台配置用 `sysconf('weight.ai_*')` 保存。
- 已在 `src/Service.php` 菜单中增加 `AI模型配置` 入口。
- `php tests\smoke_ai_model_config.php` 已通过。
- 全量 smoke 和 PHP 语法检查已通过。
- `composer validate --no-check-publish` 通过，仅提示 Packagist 发布时建议移除 `version` 字段。
- 包版本查询命令首次写法不对，已准备改为分别查询。
- 已分别确认 `openai-php/client v0.10.1` 和 `guzzlehttp/guzzle 7.10.0` 已安装。

# AI 模型配置集成计划

## 目标

在 `think-plugin-weight` 中安装 OpenAI 兼容 PHP 客户端，并增加后台可配置的国产大模型供应商预设，配置风格参考 `think-plugin-qz` 的 `config.index` 页面和 `sysconf()` 保存方式。

## 阶段

- [x] 阶段 1：确认 `think-plugin-qz` 设置页写法和国产模型兼容地址
- [x] 阶段 2：安装 Composer 依赖
- [x] 阶段 3：先写 smoke 测试覆盖供应商预设、配置读取和服务入参
- [x] 阶段 4：实现配置控制器、视图、菜单和 AI 服务类
- [x] 阶段 5：运行 smoke、语法检查和 Composer 校验

## 设计决策

- 使用 `openai-php/client` 调用 OpenAI 兼容接口。
- 配置保存到 `sysconf('weight.ai_*')`，不新增数据库表。
- 内置国产供应商预设，同时保留 `custom` 自定义。
- `api_key` 保存时如果提交空值则保留旧值，避免误清空密钥。

## 错误记录

| 时间 | 问题 | 处理 |
| --- | --- | --- |
| 2026-05-01 | Composer 无法使用 dist 包，提示缺少 zip/unzip/7z | Composer 自动回退 source 安装并成功完成 |
| 2026-05-01 | `composer show openai-php/client guzzlehttp/guzzle` 把第二个包名当成版本约束 | 改为分别执行 `composer show openai-php/client` 与 `composer show guzzlehttp/guzzle` |

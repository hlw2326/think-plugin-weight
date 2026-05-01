# AI 模型配置调研记录

## 项目设置方式

- `think-plugin-qz` 使用 `src/controller/config/Index.php` 渲染配置页，POST 后用 `sysconf()` 保存。
- `think-plugin-qz` 的菜单入口在 `src/Service.php::menu()` 中添加 `config.index/index` 节点。
- `think-plugin-weight` 当前只有查询记录和数据概览，需要新增配置菜单。

## 国产模型供应商预设

- 通义千问：阿里云百炼 OpenAI 兼容地址 `https://dashscope.aliyuncs.com/compatible-mode/v1`。
- 豆包：火山方舟 OpenAI 兼容地址 `https://ark.cn-beijing.volces.com/api/v3`。
- DeepSeek：OpenAI 兼容地址 `https://api.deepseek.com/v1`。
- Kimi：Moonshot/Kimi OpenAI 兼容地址 `https://api.moonshot.ai/v1`。
- 腾讯混元：OpenAI 兼容地址 `https://api.hunyuan.cloud.tencent.com/v1`。
- 百度千帆/文心：OpenAI 兼容地址 `https://qianfan.baidubce.com/v2`。
- 智谱 GLM：OpenAI 兼容地址 `https://open.bigmodel.cn/api/paas/v4/`。
- MiniMax：OpenAI 兼容地址 `https://api.minimax.io/v1`。
- 阶跃星辰：OpenAI 兼容地址 `https://api.stepfun.com/v1`。
- 讯飞星火：OpenAI 兼容地址 `https://spark-api-open.xf-yun.com/v1`。
- 商汤日日新：OpenAI 兼容地址 `https://api.sensenova.cn/compatible-mode/v1/`。
- 百川智能：常见 OpenAI 兼容地址 `https://api.baichuan-ai.com/v1`，官方文档页可访问但公开页面内容较少。
- 零一万物：常见 OpenAI 兼容地址 `https://api.lingyiwanwu.com/v1`，未找到稳定官方公开文档页面，作为预设便于配置。
- 小米 MiMo：作为国产模型预设保留，实际 `base_url/model` 以开放平台为准。
- 硅基流动：国产模型推理平台，OpenAI 兼容地址 `https://api.siliconflow.cn/v1`，可调用多种国产开源/闭源模型。


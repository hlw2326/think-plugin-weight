# 账号权重查询记录设计

## 目标

构建 ThinkAdmin 插件模块，用于查询平台账号数据、计算账号权重，并把每次请求保存到 `weight_query_log`。

## 范围

- 新增 `weight_query_log` 数据库迁移。
- 新增 `WeightQueryLog` 模型，统一维护平台、渠道、状态、等级枚举。
- 新增查询服务，调用 `hlw2326/platform-data-sdk` 已支持的平台接口。
- 新增评分服务，把账号信息和作品采样数据转成权重分与等级。
- 新增后台列表、搜索、详情、清理、发起查询页面。
- 在 `Service::menu()` 中注册查询记录菜单。

## 平台标识

- `dy`：抖音，当前可自动查询。
- `ks`：快手，当前可自动查询。
- `xhs`：小红书，先保留枚举，SDK 未支持时记录失败。
- `bili`：B站，先保留枚举，SDK 未支持时记录失败。
- `wb`：微博，先保留枚举，SDK 未支持时记录失败。
- `sph`：视频号，先保留枚举，SDK 未支持时记录失败。
- `tk`：TikTok，先保留枚举，SDK 未支持时记录失败。
- `other`：其他平台。

## 数据表

表名为 `weight_query_log`，主要字段包括：

- 请求字段：`platform`、`channel`、`input`、`ip`、`user_agent`。
- 账号字段：`account_id`、`display_id`、`nickname`、`avatar_url`、`signature`。
- 指标字段：`fan_count`、`follow_count`、`work_count`、`like_count`、`collect_count`、`sample_feed_count`、`avg_like_count`、`avg_comment_count`、`avg_share_count`、`avg_collect_count`、`avg_play_count`、`interaction_rate`。
- 分析字段：`weight_score`、`weight_grade`、`analysis_summary`。
- 结果字段：`status`、`fail_reason`、`exec_time`、`raw_result`、`create_at`。

## 查询流程

1. 管理员打开后台发起查询弹窗。
2. 选择平台并输入账号主页链接或分享文本。
3. `WeightQueryService` 根据平台短码选择 SDK 适配逻辑。
4. 支持的平台返回账号信息和作品采样列表。
5. `WeightScoreService` 调用字段对象分析器，根据粉丝、获赞、作品数、采样互动计算权重。
6. 查询成功或失败都会写入 `weight_query_log`。
7. 管理员可在列表和详情弹窗查看结果。

## 评分规则

- 粉丝规模最高 35 分。
- 获赞规模最高 20 分。
- 作品数量最高 15 分。
- 采样作品平均互动最高 20 分。
- 认证账号加 10 分。

等级：

- `S`：85 分及以上。
- `A`：70 分及以上。
- `B`：55 分及以上。
- `C`：40 分及以上。
- `D`：40 分以下。

## 异常处理

- SDK 暂未支持的平台写入失败记录。
- SDK 抛出的异常写入 `fail_reason`。
- 原始结果使用 JSON 保存，中文不转义。
- Cookie、DID、User-Agent 只用于本次查询，不保存敏感 Cookie。

## 验证

- 运行评分 smoke 测试。
- 运行平台短码 smoke 测试。
- 运行插件 PHP 语法检查。
- 运行 Composer 校验。

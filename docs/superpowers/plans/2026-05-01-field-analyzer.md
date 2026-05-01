# 字段对象权重分析实现计划

## 任务 1：测试先行

- 新增 `tests/smoke_field_analyzer.php`。
- 测试 `Platform`、`Type`、`UserId`、`Nickname`、`Signature`、`FanCount` 等单字段对象。
- 测试 `WorkItem` 能保留 SDK FeedItemType 的作品字段。
- 测试 `Analyzer` 能汇总字段、建议、风险和数据库字段。
- 更新 `tests/smoke_weight_score.php` 使用 `fan_count / follow_count / work_count / like_count`。

## 任务 2：字段对象

- 新增 `src/analyzer/field/AbstractField.php`。
- 新增平台、类型、用户ID、昵称、签名、头像、性别、城市、粉丝、关注、作品、获赞、收藏、认证等账号字段对象。
- 新增 `WorkItem.php`，对应 SDK FeedItemType 的单条作品字段。
- 新增 `Work.php`，统一分析作品平均点赞、评论、分享、收藏、播放、互动率和作品明细。

## 任务 3：总分析器

- 新增 `src/analyzer/Analyzer.php`。
- 从 `user_info` 和 `feed_list` 组装字段对象。
- 集中计算总分、等级、摘要、建议、风险。

## 任务 4：接入现有服务

- 修改 `WeightScoreService` 调用总分析器。
- 修改 `WeightQueryService` 写入新字段名。
- 修改迁移、列表页、详情页字段名。

## 任务 5：验证

- 运行 `php tests/smoke_field_analyzer.php`。
- 运行 `php tests/smoke_weight_score.php`。
- 运行 `php tests/smoke_platform_codes.php`。
- 运行插件 PHP 语法检查。
- 运行 Composer 校验。

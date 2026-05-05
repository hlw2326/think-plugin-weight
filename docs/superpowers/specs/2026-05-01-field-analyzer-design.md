# 字段对象权重分析设计

## 目标

把账号权重分析拆成多个可独立理解、独立测试的字段对象。每个字段对象负责读取一个字段、归一化值、输出检测说明，总分析器负责集中计算总分、组装结果和汇总建议。

## SDK 字段

账号信息字段：

- `platform`：平台标识。
- `type`：数据类型。
- `user_id`：平台用户 ID。
- `sec_user_id`：平台加密用户 ID。
- `display_id`：展示账号 ID。
- `nickname`：昵称。
- `signature`：签名。
- `avatar_url`：头像地址。
- `gender`：性别。
- `city`：城市。
- `verified`：认证状态。
- `total.fan_count`：粉丝数。
- `total.follow_count`：关注数。
- `total.work_count`：作品数。
- `total.like_count`：获赞数。
- `total.collect_count`：收藏数。

作品列表字段：

- `platform`：作品所属平台。
- `type`：数据类型。
- `item_id`：作品 ID。
- `desc`：作品描述。
- `create_time`：发布时间。
- `duration`：作品时长。
- `cover_url`：封面地址。
- `video_url`：视频地址。
- `share_url`：分享地址。
- `width`：作品宽度。
- `height`：作品高度。
- `is_top`：是否置顶。
- `total.like_count`：作品点赞数。
- `total.comment_count`：作品评论数。
- `total.share_count`：作品分享数。
- `total.collect_count`：作品收藏数。
- `total.play_count`：作品播放数。
- `author.user_id`、`author.sec_user_id`、`author.display_id`、`author.nickname`、`author.avatar_url`：作者信息。
- `tags`：作品标签列表。

## 文件结构

- `src/analyzer/Analyzer.php`：总分析器。
- `src/analyzer/field/AbstractField.php`：字段对象基类，统一输出字段值和中文说明。
- `src/analyzer/field/MetadataField.php`：账号基础资料字段基类，不直接参与总分。
- `src/analyzer/field/Platform.php`：平台字段。
- `src/analyzer/field/Type.php`：数据类型字段。
- `src/analyzer/field/UserId.php`：用户 ID 字段。
- `src/analyzer/field/SecUserId.php`：加密用户 ID 字段。
- `src/analyzer/field/DisplayId.php`：展示账号 ID 字段。
- `src/analyzer/field/Nickname.php`：昵称检测。
- `src/analyzer/field/Signature.php`：签名检测。
- `src/analyzer/field/AvatarUrl.php`：头像字段。
- `src/analyzer/field/Gender.php`：性别字段。
- `src/analyzer/field/City.php`：城市字段。
- `src/analyzer/field/FanCount.php`：粉丝数检测。
- `src/analyzer/field/FollowCount.php`：关注数检测。
- `src/analyzer/field/WorkCount.php`：作品数检测。
- `src/analyzer/field/LikeCount.php`：获赞数检测。
- `src/analyzer/field/CollectCount.php`：收藏数检测。
- `src/analyzer/field/Verified.php`：认证加分。
- `src/analyzer/field/Work.php`：统一分析作品列表的点赞、评论、分享、收藏、播放和互动率。
- `src/analyzer/field/WorkItem.php`：单条作品数据对象，对应 SDK FeedItemType 全字段。
- `src/analyzer/field/Date.php`：根据作品发布时间和互动表现，生成未来 7 天稳定发布黄金时间。
- `src/analyzer/field/Pool.php`：根据粉丝数、作品浏览、点赞、评论、收藏分析流量池。
- `src/analyzer/field/Score.php`：把总分和等级转换成中文分数解释。
- `src/analyzer/field/Weight.php`：汇总账号权重结论、优势项、短板项和流量池结果。

## 字段对象接口

每个字段对象提供：

- `key()`：字段标识。
- `label()`：中文名称。
- `value()`：归一化后的字段值。
- `tips()`：中文检测说明。
- `toArray()`：输出给详情页和原始分析 JSON。

字段对象不再暴露 `score()`、`weight()`、`level()` 方法，`fields` 明细也不再输出 `score`、`weight`、`level` 三个键；总分只由总分析器集中计算。

## 汇总结果

总分析器输出：

- `score`：总分。
- `grade`：`S/A/B/C/D`。
- `summary`：中文摘要。
- `fields`：每个字段对象的明细。
- `suggestions`：优化建议。
- `risk_messages`：风险提示。
- 账号基础字段：`platform`、`type`、`user_id`、`sec_user_id`、`display_id`、`nickname`、`signature`、`avatar_url`、`gender`、`city`、`verified`。
- `fields.date.value.days`：未来 7 天发布黄金时间，每天固定到日期，不随机变化。
- `fields.pool.value`：流量池分析，输出 `cold_start`、`basic`、`growth`、`stable`、`premium` 之一。
- `fields.score.value`：分数解释，包含总分、等级、中文标签。
- `fields.weight.value`：账号权重结论，包含优势项、短板项和流量池摘要。
- 数据库需要的统计字段：`fan_count`、`follow_count`、`work_count`、`like_count`、`collect_count`、`sample_feed_count`、`avg_like_count`、`avg_comment_count`、`avg_share_count`、`avg_collect_count`、`avg_play_count`、`interaction_rate`。

## 数据库同步

`weight_query_log` 字段改为 SDK 当前命名：

- `fan_count`
- `follow_count`
- `work_count`
- `like_count`
- `collect_count`

保留作品采样相关平均值，并新增统一作品字段对象：

- `avg_collect_count`
- `avg_play_count`
- `interaction_rate`

## 验证

- 新增 `tests/smoke_field_analyzer.php`，测试字段对象和总分析器。
- 更新 `tests/smoke_weight_score.php`，确认评分服务读取新字段。
- 运行 PHP 语法检查和 Composer 校验。

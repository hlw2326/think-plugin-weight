# 账号权重查询记录实现计划

**目标：** 构建后台账号权重查询模块，支持抖音、快手自动查询，并把结果写入 `weight_query_log`。

**架构：** 模型维护枚举，查询服务负责 SDK 调用与落库，评分服务负责本地权重计算，控制器和视图负责后台交互。

**技术栈：** PHP 8.2、ThinkAdmin 插件、ThinkPHP 模型与迁移、`hlw2326/platform-data-sdk`。

## 任务 1：评分服务

- 新增 `tests/smoke_weight_score.php`。
- 新增 `src/service/WeightScoreService.php`。
- 先运行测试确认缺少服务时失败。
- 实现 `WeightScoreService::analyze()`。
- 再运行测试确认输出 `weight score ok`。

## 任务 2：平台短码

- 新增 `tests/smoke_platform_codes.php`。
- 平台短码固定为 `dy`、`ks`、`xhs`、`bili`、`wb`、`sph`、`tk`、`other`。
- 控制器校验、表单默认值、查询服务分支都使用短码。

## 任务 3：持久化

- 新增 `src/model/WeightQueryLog.php`。
- 新增 `stc/database/20260501001_install_weight_query_log.php`。
- 表名固定为 `weight_query_log`。

## 任务 4：查询服务

- 新增 `src/service/WeightQueryService.php`。
- `dy` 调用抖音 SDK 查询账号与作品采样。
- `ks` 调用快手 SDK 查询账号与作品采样。
- 其他平台写入“暂未支持自动查询”的失败记录。

## 任务 5：后台页面

- 新增 `src/controller/query/Log.php`。
- 新增 `src/view/query/log/index.html`。
- 新增 `src/view/query/log/index_search.html`。
- 新增 `src/view/query/log/form.html`。
- 新增 `src/view/query/log/detail.html`。
- 修改 `src/Service.php` 注册“查询记录”菜单。

## 任务 6：验证

- 运行 `php tests/smoke_weight_score.php`。
- 运行 `php tests/smoke_platform_codes.php`。
- 运行插件 PHP 语法检查。
- 运行 `composer validate --strict --no-check-publish --no-check-version`。

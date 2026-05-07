# think-plugin-weight

> `hlw2326/think-plugin-weight` · ThinkAdmin v6 插件 · PHP >= 8.2

账号权重查询与记录插件。当前支持通过 `hlw2326/platform-data-sdk` 查询抖音、快手账号数据，按昵称、签名、粉丝数、收藏数、作品表现、流量池、发布时间预测等字段对象输出检测说明，并集中计算账号权重后写入 `weight_query_log`。

## 目录结构

```text
think-plugin-weight/
├── composer.json
├── README.md
├── stc/database/
│   └── 20260501001_install_weight_query_log.php
└── src/
    ├── Service.php
    ├── helper.php
    ├── controller/
    │   ├── Main.php
    │   └── query/Log.php
    ├── analyzer/
    │   ├── Analyzer.php
    │   └── field/
    ├── model/
    │   └── WeightQueryLog.php
    ├── service/
    │   ├── WeightQueryService.php
    │   └── ScoreService.php
    ├── lang/
    │   └── en-us.php
    └── view/
        └── main/
            └── index.html
```

## 命名说明

- 安装包：`hlw2326/think-plugin-weight`
- 命名空间：`plugin\weight`
- 后台入口：`/plugin-weight/query.log/index`
- 数据表：`weight_query_log`
- 平台标识：`dy`、`ks`、`xhs`、`bili`、`wb`、`sph`、`tk`、`other`
- 主要指标：`fan_count`、`follow_count`、`work_count`、`like_count`、`collect_count`、`avg_like_count`、`avg_comment_count`、`avg_share_count`、`avg_collect_count`、`avg_play_count`、`interaction_rate`

<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use plugin\weight\service\CookiesService;
use plugin\weight\service\WeightQueryService;

$form = (string) file_get_contents(__DIR__ . '/../src/view/query/log/form.html');
foreach (['name="cookies_id"', 'Cookie配置', '请选择 Cookie 配置'] as $keyword) {
    if (!str_contains($form, $keyword)) {
        throw new RuntimeException("查询弹窗缺少 Cookie 配置选择能力：{$keyword}");
    }
}
foreach (['name="user_uid"', '用户UID', '可选，未传则为空'] as $keyword) {
    if (!str_contains($form, $keyword)) {
        throw new RuntimeException("查询弹窗缺少可选用户 UID：{$keyword}");
    }
}
foreach (['name="platform"', 'name="channel"', 'CookiesMap', 'lay-filter="platform"', 'lay-filter="cookie-config"'] as $keyword) {
    if (str_contains($form, $keyword)) {
        throw new RuntimeException("查询弹窗不应再提供平台或渠道选择：{$keyword}");
    }
}
foreach (['name="cookies"', '仅本次查询使用，不会保存到查询记录'] as $keyword) {
    if (str_contains($form, $keyword)) {
        throw new RuntimeException("查询弹窗不应再提供手动 Cookie 输入：{$keyword}");
    }
}
foreach (['name="user_agent"', '留空使用当前浏览器 UA'] as $keyword) {
    if (str_contains($form, $keyword)) {
        throw new RuntimeException("查询弹窗不应再提供手动 User-Agent 输入：{$keyword}");
    }
}
foreach (['name="did"', '快手可选，留空从 Cookie 读取'] as $keyword) {
    if (str_contains($form, $keyword)) {
        throw new RuntimeException("查询弹窗不应再提供手动 DID 输入：{$keyword}");
    }
}

$controller = (string) file_get_contents(__DIR__ . '/../src/controller/query/Log.php');
foreach (['WeightCookies', 'CookiesService', 'cookieConfigs', 'cookies_id.require', 'user_uid.default', 'configById', 'Cookie 配置不存在或已禁用', 'querySuccessMessage'] as $keyword) {
    if (!str_contains($controller, $keyword)) {
        throw new RuntimeException("查询控制器缺少 Cookie 配置或结果返回逻辑：{$keyword}");
    }
}
if (!str_contains($controller, "like('user_uid,input,nickname,account_id,display_id,ip')")) {
    throw new RuntimeException('查询控制器关键词搜索应包含用户 UID');
}

$cookiesService = (string) file_get_contents(__DIR__ . '/../src/service/CookiesService.php');
foreach (['function configById(', 'cookies_id', '_cookies_name'] as $keyword) {
    if (!str_contains($cookiesService, $keyword)) {
        throw new RuntimeException("Cookie 配置服务缺少指定 Cookie 配置合并逻辑：{$keyword}");
    }
}

$queryService = (string) file_get_contents(__DIR__ . '/../src/service/WeightQueryService.php');
foreach (['cookies_id', 'cookies_name', "'user_uid' => trim((string) (\$data['user_uid'] ?? ''))", 'config' => '配置'] as $keyword) {
    if (!str_contains($queryService, $keyword)) {
        throw new RuntimeException("查询服务缺少 Cookie 配置日志或返回结果：{$keyword}");
    }
}

$queryLogMigration = __DIR__ . '/../stc/database/20260501101_install_weight_query_log.php';
if (!is_file($queryLogMigration)) {
    throw new RuntimeException('查询日志缺少安装迁移');
}

$queryLogMigrationContent = (string) file_get_contents($queryLogMigration);
foreach (['cookies_id', 'cookies_name', 'raw_result', 'longtext', 'user_uid', '用户UID', 'weight_query_log'] as $keyword) {
    if (!str_contains($queryLogMigrationContent, $keyword)) {
        throw new RuntimeException("查询日志安装迁移缺少字段：{$keyword}");
    }
}

foreach ([
    '../stc/database/20260501103_update_weight_query_log_cookies_config.php',
    '../stc/database/20260501104_update_weight_query_log_raw_result.php',
    '../stc/database/20260501105_update_weight_query_log_user_uid.php',
] as $migration) {
    if (is_file(__DIR__ . '/' . $migration)) {
        throw new RuntimeException("开发阶段不应保留增量迁移：{$migration}");
    }
}

$model = (string) file_get_contents(__DIR__ . '/../src/model/WeightQueryLog.php');
foreach (['cookies_id', 'cookies_name', '@property string $user_uid'] as $keyword) {
    if (!str_contains($model, $keyword)) {
        throw new RuntimeException("查询记录模型缺少 Cookie 配置字段说明：{$keyword}");
    }
}

$indexView = (string) file_get_contents(__DIR__ . '/../src/view/query/log/index.html');
$detailView = (string) file_get_contents(__DIR__ . '/../src/view/query/log/detail.html');
foreach (['CookieTpl', 'cookies_name', "field: 'user_uid'", 'UserUidTpl', 'keep_days#30', '清理30天前', 'keep_days#0', '清空日志', '删除选中', 'data-rule="id#{id}"', "type: 'checkbox'"] as $keyword) {
    if (!str_contains($indexView, $keyword)) {
        throw new RuntimeException("查询记录列表缺少 Cookie 配置或用户 UID 展示：{$keyword}");
    }
}
if (!str_contains($detailView, 'Cookie配置') || !str_contains($detailView, 'cookies_name') || !str_contains($detailView, '用户UID') || !str_contains($detailView, 'user_uid')) {
    throw new RuntimeException('查询详情缺少 Cookie 配置或用户 UID 展示');
}
if (!str_contains($detailView, 'layui-table') || !str_contains($detailView, '<table')) {
    throw new RuntimeException('查询详情应使用表格展示，避免弹窗详情无样式');
}
if (str_contains($detailView, 'layui-form layui-card')) {
    throw new RuntimeException('查询详情不应再使用表单卡片作为主体布局');
}
foreach (['RawJsonData', 'RawJsonView', 'renderRawJson', 'jsonviewer', 'jquery.json-viewer', 'jsonViewer', 'collapsed: false', 'rootCollapsable: true', 'collapseFirstLayer'] as $keyword) {
    if (!str_contains($detailView, $keyword)) {
        throw new RuntimeException("查询详情原始结果缺少 JSON 折叠插件展示：{$keyword}");
    }
}
if (str_contains($detailView, 'collapsed: true')) {
    throw new RuntimeException('查询详情原始结果不应默认全部折叠，应默认展开第一层');
}
foreach (['width: 140px', 'max-height: 520px', 'overflow-wrap: anywhere', 'word-break: break-word', 'json-document', 'json-placeholder'] as $keyword) {
    if (!str_contains($detailView, $keyword)) {
        throw new RuntimeException("查询详情 JSON 展示样式需要支持表头加宽、折叠滚动和自动换行：{$keyword}");
    }
}
if (str_contains($detailView, '<textarea readonly class="layui-textarea raw-result"')) {
    throw new RuntimeException('查询详情原始结果不应再使用普通 textarea 展示 JSON');
}
foreach (['highlightJson', 'json-line'] as $keyword) {
    if (str_contains($detailView, $keyword)) {
        throw new RuntimeException("查询详情原始结果不应再使用手写 JSON 高亮：{$keyword}");
    }
}

foreach (['$keepDays === 0', '清空全部查询记录', '清空完成', "where('create_at', '<', \$cutoff)"] as $keyword) {
    if (!str_contains($controller, $keyword)) {
        throw new RuntimeException("查询记录清理逻辑需要同时支持清理30天前和清空全部：{$keyword}");
    }
}
foreach (['public function remove(): void', '删除勾选的查询记录', 'WeightQueryLog::mDelete()'] as $keyword) {
    if (!str_contains($controller, $keyword)) {
        throw new RuntimeException("查询记录缺少勾选删除能力：{$keyword}");
    }
}

$composerConfig = json_decode((string) file_get_contents(__DIR__ . '/../composer.json'), true);
$pluginCopy = $composerConfig['extra']['plugin']['copy'] ?? [];
if (($pluginCopy['stc/assets/json-viewer'] ?? '') !== 'public/static/plugs/qz-json-viewer') {
    throw new RuntimeException('composer.json 缺少 JSON 折叠插件静态资源复制配置');
}
foreach (['jquery.json-viewer.js', 'jquery.json-viewer.css'] as $asset) {
    if (!is_file(__DIR__ . '/../stc/assets/json-viewer/' . $asset)) {
        throw new RuntimeException("JSON 折叠插件静态资源不存在：{$asset}");
    }
}

$defaults = CookiesService::configFromArray('dy', [
    'id' => 23,
    'name' => '抖音H5可用Cookie',
    'cookies' => 'dy_cookie=1;',
    'user_agent' => 'config ua',
    'timeout' => '15000',
    'sample_count' => '18',
    'channel' => 'h5',
    'params' => '{"headers":{"Referer":"https://www.douyin.com/"}}',
]);

$merged = CookiesService::mergeQueryData('dy', [
    'cookies_id' => 23,
    'cookies' => '',
    'user_agent' => 'manual ua',
    'timeout' => '',
    'sample_count' => '',
    'channel' => 'auto',
], $defaults);

if (($merged['_cookies_id'] ?? 0) !== 23 || ($merged['_cookies_name'] ?? '') !== '抖音H5可用Cookie') {
    throw new RuntimeException('查询参数应记录实际使用的 Cookie 配置 ID 和名称');
}

if ($merged['cookies'] !== 'dy_cookie=1;' || $merged['user_agent'] !== 'manual ua') {
    throw new RuntimeException('查询参数应使用 Cookie 配置兜底，同时保留手动填写的 UA');
}

if ($merged['channel'] !== 'h5' || $merged['timeout'] !== 15000 || $merged['sample_count'] !== 18) {
    throw new RuntimeException('查询参数应从选中的 Cookie 配置补齐渠道、超时和采样数');
}

$baseLog = (new \ReflectionClass(WeightQueryService::class))->getMethod('baseLog');
$logWithUserUid = $baseLog->invoke(null, 'dy', 'h5', 'https://example.com/user', microtime(true), '127.0.0.1', 'ua', [
    'user_uid' => ' user_1001 ',
]);
if (($logWithUserUid['user_uid'] ?? '') !== 'user_1001') {
    throw new RuntimeException('查询记录应保存传入的用户 UID，并清理首尾空白');
}

$logWithoutUserUid = $baseLog->invoke(null, 'dy', 'h5', 'https://example.com/user', microtime(true), '127.0.0.1', 'ua', []);
if (($logWithoutUserUid['user_uid'] ?? null) !== '') {
    throw new RuntimeException('查询记录未传用户 UID 时应保存为空字符串');
}

$method = (new \ReflectionClass(WeightQueryService::class))->getMethod('json');
$rawJson = $method->invoke(null, ['body' => str_repeat('a', 70000)]);
$decoded = json_decode($rawJson, true);
if (json_last_error() !== JSON_ERROR_NONE || ($decoded['body'] ?? '') !== str_repeat('a', 70000)) {
    throw new RuntimeException('查询原始结果不能被截断，必须保存为完整 JSON');
}

$fallbackJson = WeightQueryService::formatRawResult('old broken raw text');
$fallbackDecoded = json_decode($fallbackJson, true);
if (json_last_error() !== JSON_ERROR_NONE || ($fallbackDecoded['raw_text'] ?? '') !== 'old broken raw text') {
    throw new RuntimeException('查询详情需要把旧的非标准原始结果兜底转换为 JSON');
}

$completeUserInfo = (new \ReflectionClass(WeightQueryService::class))->getMethod('completeUserInfo');
$completedUserInfo = $completeUserInfo->invoke(null, [], [
    [
        'author' => [
            'user_id' => '3175831997789580',
            'sec_user_id' => 'MS4wLjABAAAARYwKy2cg',
            'display_id' => '',
            'nickname' => '小黑弟弟～',
            'avatar_url' => 'https://example.com/avatar.jpg',
        ],
        'total' => ['like_count' => 120, 'collect_count' => 30],
    ],
    [
        'author' => ['nickname' => '小黑弟弟～'],
        'total' => ['like_count' => 80, 'collect_count' => 10],
    ],
], 'dy');
if (($completedUserInfo['nickname'] ?? '') !== '小黑弟弟～' || ($completedUserInfo['sec_user_id'] ?? '') === '') {
    throw new RuntimeException('用户信息接口为空时，应从作品作者信息兜底补全账号资料');
}
if (($completedUserInfo['total']['work_count'] ?? 0) !== 2 || ($completedUserInfo['total']['like_count'] ?? 0) !== 200 || ($completedUserInfo['total']['collect_count'] ?? 0) !== 40) {
    throw new RuntimeException('用户信息兜底时，应用采样作品补齐作品数、获赞数和收藏数');
}

echo "query cookie config ok\n";

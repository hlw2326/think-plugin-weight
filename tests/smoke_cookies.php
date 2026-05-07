<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use plugin\weight\service\CookiesService;

$controller = (string) file_get_contents(__DIR__ . '/../src/controller/config/Index.php');
foreach (['index'] as $method) {
    if (!str_contains($controller, "function {$method}(")) {
        throw new RuntimeException("配置控制器缺少 {$method} 方法");
    }
}
foreach (['douyin', 'kuaishou'] as $method) {
    if (str_contains($controller, "function {$method}(")) {
        throw new RuntimeException("{$method} 配置应迁移到 cookies/index，不应留在 config/index");
    }
}

foreach ([
    '../stc/database/20260501102_install_weight_cookies.php',
    '../src/model/WeightCookies.php',
    '../src/controller/cookies/Index.php',
    '../src/view/cookies/index/index.html',
    '../src/view/cookies/index/index_search.html',
    '../src/view/cookies/index/form.html',
] as $file) {
    if (!is_file(__DIR__ . '/' . $file)) {
        throw new RuntimeException("Cookie配置池缺少文件 {$file}");
    }
}

$cookiesController = (string) file_get_contents(__DIR__ . '/../src/controller/cookies/Index.php');
foreach (['index', 'add', 'edit', 'state', 'remove', 'default'] as $method) {
    if (!str_contains($cookiesController, "function {$method}(")) {
        throw new RuntimeException("Cookie配置池控制器缺少 {$method} 方法");
    }
}

if (is_file(__DIR__ . '/../src/controller/config/Platform.php')) {
    throw new RuntimeException('Cookie配置池控制器应放在 controller/cookies/Index.php');
}

if (!str_contains($cookiesController, 'namespace plugin\\weight\\controller\\cookies') || !str_contains($cookiesController, 'class Index')) {
    throw new RuntimeException('Cookie配置池控制器命名空间和类名应为 cookies/Index');
}

$cookiesIndexView = (string) file_get_contents(__DIR__ . '/../src/view/cookies/index/index.html');
if (str_contains($cookiesIndexView, "config/index/tabs")) {
    throw new RuntimeException('Cookie配置池页面不应包含 config/index/tabs');
}

$cookiesFormView = (string) file_get_contents(__DIR__ . '/../src/view/cookies/index/form.html');
if (str_contains($cookiesFormView, 'lang("必须是 JSON 对象')) {
    throw new RuntimeException('扩展参数 placeholder 不应在 lang() 中嵌套 JSON 引号');
}
if (!str_contains($cookiesFormView, 'name="platform"') || !str_contains($cookiesFormView, 'name="channel"')) {
    throw new RuntimeException('Cookie配置池表单应包含平台和渠道字段');
}
foreach (['name="sort"', 'name="status"'] as $keyword) {
    if (str_contains($cookiesFormView, $keyword)) {
        throw new RuntimeException('Cookie配置池表单不应填写状态和排序');
    }
}

$migration = (string) file_get_contents(__DIR__ . '/../stc/database/20260501102_install_weight_cookies.php');
foreach (['weight_cookies', 'cookies', 'user_agent', 'did', 'params', 'platform', 'channel'] as $keyword) {
    if (!str_contains($migration, $keyword)) {
        throw new RuntimeException("Cookie配置池迁移缺少字段 {$keyword}");
    }
}

if (!is_file(__DIR__ . '/../src/view/config/index/index.html')) {
    throw new RuntimeException('配置页缺少 index.html 模板');
}
foreach (['douyin.html', 'kuaishou.html'] as $view) {
    if (is_file(__DIR__ . "/../src/view/config/index/{$view}")) {
        throw new RuntimeException("{$view} 已迁移到 Cookie配置池，不应留在 config/index");
    }
}

$service = (string) file_get_contents(__DIR__ . '/../src/Service.php');
if (!str_contains($service, '凭证管理') || !str_contains($service, 'cookies.index/index')) {
    throw new RuntimeException('凭证管理应作为独立菜单入口注册');
}

if (is_file(__DIR__ . '/../src/view/config/index/tabs.html')) {
    $tabs = (string) file_get_contents(__DIR__ . '/../src/view/config/index/tabs.html');
    foreach (['config.index/douyin', 'config.index/kuaishou', '抖音配置', '快手配置'] as $keyword) {
        if (str_contains($tabs, $keyword)) {
            throw new RuntimeException('配置页 tabs 不应再包含旧抖音/快手配置入口');
        }
    }
}

$douyin = CookiesService::configFromArray('dy', [
    'cookies' => "  dy_cookie=1;  \n",
    'user_agent' => '  Mozilla/5.0 dy  ',
    'timeout' => '15000',
    'sample_count' => '18',
    'channel' => 'web',
    'params' => "{\n  \"headers\": {\"Referer\": \"https://www.douyin.com/\"}\n}",
]);

if ($douyin['cookies'] !== 'dy_cookie=1;' || $douyin['user_agent'] !== 'Mozilla/5.0 dy') {
    throw new RuntimeException('抖音配置应清洗 cookies 和 user_agent');
}

if ($douyin['timeout'] !== 15000 || $douyin['sample_count'] !== 18 || $douyin['channel'] !== 'web') {
    throw new RuntimeException('抖音配置应归一化超时、采样数和默认渠道');
}

if (!is_array($douyin['params_array']) || ($douyin['params_array']['headers']['Referer'] ?? '') !== 'https://www.douyin.com/') {
    throw new RuntimeException('扩展参数 JSON 应能转成数组');
}

$mergedDouyin = CookiesService::mergeQueryData('dy', [
    'cookies' => '',
    'user_agent' => 'manual ua',
    'timeout' => '',
    'sample_count' => '',
    'channel' => 'auto',
], $douyin);

if ($mergedDouyin['cookies'] !== 'dy_cookie=1;' || $mergedDouyin['user_agent'] !== 'manual ua') {
    throw new RuntimeException('查询参数应优先使用表单值，并用配置 cookies 兜底');
}

if ($mergedDouyin['timeout'] !== 15000 || $mergedDouyin['sample_count'] !== 18 || $mergedDouyin['channel'] !== 'web') {
    throw new RuntimeException('查询参数应从抖音配置补齐默认值');
}

if (($mergedDouyin['headers']['Referer'] ?? '') !== 'https://www.douyin.com/') {
    throw new RuntimeException('查询参数应合并扩展参数中的 headers');
}

$kuaishou = CookiesService::configFromArray('ks', [
    'cookies' => 'ks_cookie=1;',
    'did' => 'web_123',
    'user_agent' => 'Mozilla/5.0 ks',
    'timeout' => '12000',
    'sample_count' => '9',
]);

if ($kuaishou['cookies'] !== 'ks_cookie=1;' || $kuaishou['did'] !== 'web_123') {
    throw new RuntimeException('快手配置应保留 cookies 和 did');
}

$mergedKuaishou = CookiesService::mergeQueryData('ks', [
    'did' => '',
    'cookies' => '',
], $kuaishou);

if ($mergedKuaishou['did'] !== 'web_123' || $mergedKuaishou['cookies'] !== 'ks_cookie=1;') {
    throw new RuntimeException('查询参数应从快手配置补齐 did 和 cookies');
}

$platforms = array_keys(CookiesService::platforms());
foreach (['dy', 'ks', 'xhs', 'bili', 'wb', 'sph', 'tk', 'other'] as $platform) {
    if (!in_array($platform, $platforms, true)) {
        throw new RuntimeException("Cookie 配置服务缺少平台 {$platform}");
    }
}

echo "cookies ok\n";

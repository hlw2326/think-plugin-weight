<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);

$controllerPath = $basePath . '/src/controller/tags/Index.php';
$indexPath = $basePath . '/src/view/tags/index/index.html';
$searchPath = $basePath . '/src/view/tags/index/index_search.html';
$formPath = $basePath . '/src/view/tags/index/form.html';
$iconPickerPath = $basePath . '/src/view/base/common/iconify_picker.html';
$servicePath = $basePath . '/src/Service.php';

foreach ([$controllerPath, $indexPath, $searchPath, $formPath, $iconPickerPath, $servicePath] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "missing file: {$path}\n");
        exit(1);
    }
}

$controller = file_get_contents($controllerPath);
$index = file_get_contents($indexPath);
$search = file_get_contents($searchPath);
$form = file_get_contents($formPath);
$iconPicker = file_get_contents($iconPickerPath);
$service = file_get_contents($servicePath);

$checks = [
    [$controller, 'namespace plugin\\weight\\controller\\tags;', 'controller namespace'],
    [$controller, 'class Index extends Controller', 'controller class'],
    [$controller, 'use plugin\\weight\\model\\WeightTags;', 'weight tags model import'],
    [$controller, "WeightTags::mQuery()->layTable", 'table query'],
    [$controller, "\$query->like('title,value')", 'title and value search'],
    [$controller, "\$query->equal('status')", 'status search'],
    [$controller, "WeightTags::mForm('form')", 'modal form'],
    [$controller, 'function state(): void', 'state method'],
    [$controller, 'function remove(): void', 'remove method'],
    [$controller, 'normalizeKeywords', 'keyword normalization'],
    [$controller, 'fa6-solid|fa6-brands|ri', 'icon prefix validation'],
    [$index, 'WeightTagsTable', 'table id'],
    [$index, "tags/index/index_search", 'search include'],
    [$index, 'StatusSwitch', 'status switch'],
    [$index, 'IconTpl', 'icon template'],
    [$index, '/static/plugs/iconify-picker/picker.js', 'iconify picker script'],
    [$index, 'qzIconifyEnsureAll', 'iconify collection preload'],
    [$index, 'qzParseIconClass', 'icon class parser'],
    [$index, '<iconify-icon icon="{{_parsed}}"', 'rendered iconify icon'],
    [$search, 'name="title"', 'title search field'],
    [$search, 'name="status"', 'status search field'],
    [$form, 'name="title"', 'title form field'],
    [$form, 'base/common/iconify_picker', 'qz style icon picker include'],
    [$form, 'name="value"', 'value form field'],
    [$iconPicker, 'iconify-picker-root', 'icon picker root'],
    [$iconPicker, 'name="icon"', 'icon picker field'],
    [$iconPicker, 'iconify-picker-preview', 'icon picker preview'],
    [$iconPicker, '/static/plugs/iconify-picker/picker.js', 'icon picker script'],
    [$iconPicker, 'i-fa6-solid-tag', 'icon placeholder'],
    [$controller, "array_key_exists('sort', \$data)", 'preserve sort when field is absent'],
    [$controller, "array_key_exists('status', \$data)", 'preserve status when field is absent'],
    [$service, '标签管理', 'menu title'],
    [$service, 'tags.index/index', 'menu route'],
];

foreach ($checks as [$haystack, $needle, $label]) {
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, "missing {$label}: {$needle}\n");
        exit(1);
    }
}

foreach (['name="sort"', 'name="status"', '<select name="status"'] as $needle) {
    if (str_contains($form, $needle)) {
        fwrite(STDERR, "form should not contain {$needle}\n");
        exit(1);
    }
}

echo "tags controller ok\n";

<?php

declare(strict_types=1);

namespace think\admin {
    if (!class_exists(Model::class, false)) {
        class Model
        {
        }
    }
}

namespace {
    require __DIR__ . '/../vendor/autoload.php';

    use plugin\weight\model\WeightTags;
    use think\admin\Model;

    $modelFile = __DIR__ . '/../src/model/WeightTags.php';
    if (!is_file($modelFile)) {
        throw new RuntimeException('账号标签模型文件不存在');
    }

    if (!is_subclass_of(WeightTags::class, Model::class)) {
        throw new RuntimeException('账号标签模型应继承 think\\admin\\Model');
    }

    $content = (string) file_get_contents($modelFile);
    foreach ([
        '@property int $id',
        '@property string $icon',
        '@property string $title',
        '@property string $value',
        '@property int $status',
        '@property int $sort',
        '@property string $create_at',
    ] as $keyword) {
        if (!str_contains($content, $keyword)) {
            throw new RuntimeException("账号标签模型缺少字段说明：{$keyword}");
        }
    }

    $statuses = WeightTags::getStatuses();
    foreach ([WeightTags::STATUS_DISABLED, WeightTags::STATUS_ENABLED] as $status) {
        if (!isset($statuses[$status]['label'], $statuses[$status]['class'])) {
            throw new RuntimeException("账号标签模型缺少状态枚举：{$status}");
        }
    }

    echo "tags model ok\n";
}

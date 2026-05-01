<?php

declare(strict_types=1);

namespace think\admin {
    class Model
    {
    }
}

namespace {
    require __DIR__ . '/../src/model/WeightQueryLog.php';

    use plugin\weight\model\WeightQueryLog;

    $expected = ['dy', 'ks', 'xhs', 'bili', 'wb', 'sph', 'tk', 'other'];
    $actual = array_keys(WeightQueryLog::getPlatforms());

    if ($actual !== $expected) {
        throw new RuntimeException('平台标识应为短码：' . json_encode($actual, JSON_UNESCAPED_UNICODE));
    }

    echo "platform codes ok\n";
}

<?php

declare(strict_types=1);

namespace plugin\weight\service;

use plugin\weight\analyzer\Analyzer;

/**
 * 本地账号权重评分工具
 */
class ScoreService
{
    /**
     * @param array<string,mixed> $userInfo
     * @param array<int,array<string,mixed>> $feedList
     * @return array<string,mixed>
     */
    public static function analyze(array $userInfo, array $feedList = []): array
    {
        return Analyzer::analyze($userInfo, $feedList);
    }
}

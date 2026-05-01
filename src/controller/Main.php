<?php

declare(strict_types=1);

namespace plugin\weight\controller;

use think\admin\Controller;

/**
 * 默认模块入口。
 *
 * @class Main
 * @package plugin\weight\controller
 */
class Main extends Controller
{
    /**
     * 数据概览。
     *
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        $this->title = '账号权重';
        $this->fetch();
    }
}

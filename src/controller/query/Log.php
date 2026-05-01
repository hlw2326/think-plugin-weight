<?php

declare(strict_types=1);

namespace plugin\weight\controller\query;

use plugin\weight\model\WeightQueryLog;
use plugin\weight\service\WeightQueryService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 账号权重查询记录。
 *
 * @class Log
 * @package plugin\weight\controller\query
 */
class Log extends Controller
{
    /**
     * 查询记录列表。
     *
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        WeightQueryLog::mQuery()->layTable(function () {
            $this->title = '查询记录';
            $this->platforms = WeightQueryLog::getPlatforms();
            $this->channels = WeightQueryLog::getChannels();
            $this->statuses = WeightQueryLog::getStatuses();
            $this->grades = WeightQueryLog::getGrades();
        }, function (QueryHelper $query) {
            $query->like('input,nickname,account_id,display_id,ip');
            $query->equal('platform,channel,status,weight_grade');
            $query->dateBetween('create_at');
        });
    }

    /**
     * 发起新的账号查询。
     *
     * @auth true
     */
    public function query(): void
    {
        $this->_applyFormToken();

        if ($this->request->isGet()) {
            $this->platforms = WeightQueryLog::getPlatforms();
            $this->channels = WeightQueryLog::getChannels();
            $this->fetch('query/log/form');
            return;
        }

        $data = $this->_vali([
            'platform.require' => '请选择查询平台！',
            'platform.in:dy,ks,xhs,bili,wb,sph,tk,other' => '查询平台不支持！',
            'channel.default' => 'auto',
            'input.require' => '请输入账号链接或分享文本！',
            'cookies.default' => '',
            'did.default' => '',
            'user_agent.default' => '',
            'timeout.default' => 10000,
            'sample_count.default' => 12,
        ]);

        $data['ip'] = $this->request->ip();
        if ($data['user_agent'] === '') {
            $data['user_agent'] = (string) $this->request->server('HTTP_USER_AGENT', '');
        }

        $result = WeightQueryService::query($data);
        if (!empty($result['state'])) {
            $this->success('查询成功！');
        }

        $this->error($result['msg'] ?? '查询失败！');
    }

    /**
     * 查看查询详情。
     *
     * @auth true
     */
    public function detail(): void
    {
        $id = intval(input('id', 0));
        $record = WeightQueryLog::mk()->where(['id' => $id])->findOrEmpty();
        if (!$record->isExists()) {
            $this->error('查询记录不存在！');
        }

        $this->vo = $record->toArray();
        $this->platforms = WeightQueryLog::getPlatforms();
        $this->channels = WeightQueryLog::getChannels();
        $this->statuses = WeightQueryLog::getStatuses();
        $this->grades = WeightQueryLog::getGrades();
        $this->raw_result = $this->formatJson((string) ($this->vo['raw_result'] ?? ''));
        $this->fetch('query/log/detail');
    }

    /**
     * 清理旧查询记录。
     *
     * @auth true
     */
    public function clear(): void
    {
        $keepDays = max(1, intval(input('keep_days', 30)));
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$keepDays} days"));
        $count = WeightQueryLog::mk()->where('create_at', '<', $cutoff)->delete();
        sysoplog('账号权重查询', "清理 {$keepDays} 天前的查询记录，删除 {$count} 条记录");
        $this->success("清理完成，共删除 {$count} 条记录！");
    }

    private function formatJson(string $json): string
    {
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $json;
        }
        return (string) json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}

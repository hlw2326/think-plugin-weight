<?php

declare(strict_types=1);

namespace plugin\weight\controller\query;

use plugin\weight\model\WeightCookies;
use plugin\weight\model\WeightQueryLog;
use plugin\weight\service\CookiesService;
use plugin\weight\service\WeightQueryService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use Throwable;

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
            $query->like('user_uid,input,nickname,account_id,display_id,ip');
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
            $cookieConfigs = $this->cookieConfigs();
            $this->cookieConfigs = $cookieConfigs;
            $this->fetch('query/log/form');
            return;
        }

        $data = $this->_vali([
            'cookies_id.require' => '请选择 Cookie 配置！',
            'input.require' => '请输入账号链接或分享文本！',
            'user_uid.default' => '',
            'cookies.default' => '',
            'did.default' => '',
            'user_agent.default' => '',
            'timeout.default' => 10000,
            'sample_count.default' => 12,
        ]);

        $cookiesConfig = CookiesService::configById((int) ($data['cookies_id'] ?? 0));
        if ($cookiesConfig === []) {
            $this->error('Cookie 配置不存在或已禁用！');
        }

        $data['platform'] = (string) $cookiesConfig['platform'];
        $data['channel'] = (string) $cookiesConfig['channel'];
        $data['ip'] = $this->request->ip();

        $result = WeightQueryService::query($data);
        if (!empty($result['state'])) {
            $this->success($this->querySuccessMessage($result), [
                'id' => (int) ($result['id'] ?? 0),
                'detail_url' => url('detail', ['id' => (int) ($result['id'] ?? 0)]),
                'analysis' => $result['analysis'] ?? [],
                'cookies_config' => $result['cookies_config'] ?? [],
            ]);
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
        $this->raw_result = WeightQueryService::formatRawResult((string) ($this->vo['raw_result'] ?? ''));
        $this->fetch('query/log/detail');
    }

    /**
     * 清理旧查询记录。
     *
     * @auth true
     */
    public function clear(): void
    {
        $keepDays = max(0, intval(input('keep_days', 30)));
        if ($keepDays === 0) {
            $count = WeightQueryLog::mk()->where('id', '>', 0)->delete();
            sysoplog('账号权重查询', "清空全部查询记录，删除 {$count} 条记录");
            $this->success("清空完成，共删除 {$count} 条记录！");
        }

        $cutoff = date('Y-m-d H:i:s', strtotime("-{$keepDays} days"));
        $count = WeightQueryLog::mk()->where('create_at', '<', $cutoff)->delete();
        sysoplog('账号权重查询', "清理 {$keepDays} 天前的查询记录，删除 {$count} 条记录");
        $this->success("清理完成，共删除 {$count} 条记录！");
    }

    /**
     * 删除勾选的查询记录。
     *
     * @auth true
     */
    public function remove(): void
    {
        WeightQueryLog::mDelete();
    }

    /**
     * 查询弹窗可选的 Cookie 配置。
     *
     * 只下发展示所需字段，不把 Cookie、UA、DID 直接输出到页面。
     *
     * @return array<int,array<string,mixed>>
     */
    private function cookieConfigs(): array
    {
        try {
            $rows = WeightCookies::mk()
                ->where('status', WeightCookies::STATUS_ENABLED)
                ->order('platform asc,is_default desc,sort desc,id desc')
                ->select()
                ->toArray();
        } catch (Throwable) {
            return [];
        }

        $platforms = WeightCookies::getPlatforms();
        $channels = WeightCookies::getChannels();
        $configs = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $platform = (string) ($row['platform'] ?? '');
            $channel = (string) ($row['channel'] ?? 'default');
            if ($id <= 0 || !isset($platforms[$platform])) {
                continue;
            }
            if (!isset($channels[$channel])) {
                $channel = 'default';
            }

            $name = trim((string) ($row['name'] ?? ''));
            $configs[] = [
                'id' => $id,
                'name' => $name !== '' ? $name : '未命名配置',
                'platform' => $platform,
                'platform_label' => $platforms[$platform]['label'],
                'channel' => $channel,
                'channel_label' => $channels[$channel]['label'],
                'is_default' => (int) ($row['is_default'] ?? 0),
            ];
        }

        return $configs;
    }

    /**
     * 查询完成后给弹窗返回可读结果。
     *
     * @param array<string,mixed> $result
     */
    private function querySuccessMessage(array $result): string
    {
        $analysis = is_array($result['analysis'] ?? null) ? $result['analysis'] : [];
        $score = (int) ($analysis['score'] ?? 0);
        $grade = (string) ($analysis['grade'] ?? 'D');
        $message = "查询成功！分数 {$score}，等级 {$grade}";

        $fields = is_array($analysis['fields'] ?? null) ? $analysis['fields'] : [];
        $pool = is_array($fields['pool']['value'] ?? null) ? $fields['pool']['value'] : [];
        $poolName = trim((string) ($pool['name'] ?? $pool['label'] ?? ''));
        if ($poolName !== '') {
            $message .= "，流量池 {$poolName}";
        }

        $config = is_array($result['cookies_config'] ?? null) ? $result['cookies_config'] : [];
        $configName = trim((string) ($config['name'] ?? ''));
        if ($configName !== '') {
            $message .= "，Cookie {$configName}";
        }

        return $message;
    }
}

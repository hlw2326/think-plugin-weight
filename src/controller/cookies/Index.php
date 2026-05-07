<?php

declare(strict_types=1);

namespace plugin\weight\controller\cookies;

use plugin\weight\model\WeightCookies;
use plugin\weight\service\CookiesService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use Throwable;

/**
 * 平台 Cookie 配置池
 *
 * 作用：
 * - 管理抖音、快手、小红书等平台请求账号信息时使用的 Cookie、UA、DID
 * - 抖音可按 h5、web、live 分开保存配置
 * - params 用于保存平台扩展参数 JSON，例如 headers、params、msToken、a_bogus
 */
class Index extends Controller
{
    /**
     * 凭证列表
     *
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        WeightCookies::mQuery()->layTable(function () {
            $this->title = '凭证列表';
            $this->current = 'cookies';
            $this->platforms = CookiesService::platforms();
            $this->channels = CookiesService::channels();
            $this->statuses = WeightCookies::getStatuses();
        }, function (QueryHelper $query) {
            $query->like('name,remark');
            $query->equal('platform,channel,status,is_default');
            $query->dateBetween('create_at');
        });
    }

    /**
     * 添加凭证
     *
     * @auth true
     */
    public function add(): void
    {
        WeightCookies::mForm('form');
    }

    /**
     * 添加凭证
     *
     * @auth true
     */
    public function edit(): void
    {
        WeightCookies::mForm('form');
    }

    /**
     * 表单凭证
     *
     * @param array<string,mixed> $data
     */
    protected function _form_filter(array &$data): void
    {
        $this->platforms = CookiesService::platforms();
        $this->channels = CookiesService::channels();

        if (!$this->request->isPost()) {
            return;
        }

        $platform = (string) ($data['platform'] ?? 'dy');
        $config = CookiesService::configFromArray($platform, $data);
        try {
            CookiesService::assertValidParams((string) $config['params']);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
        }

        $data = [
            ...$data,
            'name' => (string) $config['name'],
            'platform' => (string) $config['platform'],
            'channel' => (string) $config['channel'],
            'cookies' => (string) $config['cookies'],
            'user_agent' => (string) $config['user_agent'],
            'did' => (string) $config['did'],
            'params' => (string) $config['params'],
            'timeout' => (int) $config['timeout'],
            'sample_count' => (int) $config['sample_count'],
            'is_default' => (int) $config['is_default'],
            'sort' => (int) $config['sort'],
            'status' => (int) $config['status'],
            'expired_at' => (string) $config['expired_at'] !== '' ? (string) $config['expired_at'] : null,
            'remark' => (string) $config['remark'],
        ];

        if ((int) $data['is_default'] === 1) {
            $query = WeightCookies::mk()->where('platform', $data['platform']);
            if (!empty($data['id'])) {
                $query->where('id', '<>', (int) $data['id']);
            }
            $query->update(['is_default' => 0]);
        }
    }

    /**
     * 修改状态
     *
     * @auth true
     */
    public function state(): void
    {
        WeightCookies::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 设置平台配置
     *
     * @auth true
     */
    public function default(): void
    {
        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            $this->error('请选择要设为默认的配置');
        }

        $row = WeightCookies::mk()->where('id', $id)->findOrEmpty();
        if (!$row->isExists()) {
            $this->error('配置不存在或已删除');
        }

        WeightCookies::mk()->where('platform', $row->platform)->where('id', '<>', $id)->update(['is_default' => 0]);
        $row->save(['is_default' => 1, 'status' => 1]);
        $this->success('默认配置已更新');
    }

    /**
     * 删除凭证
     *
     * @auth true
     */
    public function remove(): void
    {
        WeightCookies::mDelete();
    }
}

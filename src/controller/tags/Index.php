<?php

declare(strict_types=1);

namespace plugin\weight\controller\tags;

use plugin\weight\model\WeightTags;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 账号标签库
 *
 * 作用：
 * - 管理账号标签的名称、图标、关键词和启用状态
 * - 标签关键词会被账号分析逻辑用于从作品 tag 字段判断账号方向
 */
class Index extends Controller
{
    /**
     * 标签列表
     *
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        WeightTags::mQuery()->layTable(function () {
            $this->title = '标签列表';
            $this->current = 'tags';
            $this->statuses = WeightTags::getStatuses();
        }, function (QueryHelper $query) {
            $query->like('title,value');
            $query->equal('status');
            $query->dateBetween('create_at');
        });
    }

    /**
     * 添加标签
     *
     * @auth true
     */
    public function add(): void
    {
        WeightTags::mForm('form');
    }

    /**
     * 编辑标签
     *
     * @auth true
     */
    public function edit(): void
    {
        WeightTags::mForm('form');
    }

    /**
     * 表单数据处理
     *
     * @param array<string,mixed> $data
     */
    protected function _form_filter(array &$data): void
    {
        $this->statuses = WeightTags::getStatuses();

        if (!$this->request->isPost()) {
            return;
        }

        $data['title'] = trim((string) ($data['title'] ?? ''));
        $data['icon'] = trim((string) ($data['icon'] ?? ''));
        $data['value'] = self::normalizeKeywords((string) ($data['value'] ?? ''));
        if (array_key_exists('sort', $data)) {
            $data['sort'] = max(0, (int) $data['sort']);
        }
        if (array_key_exists('status', $data)) {
            $data['status'] = (int) $data['status'] === WeightTags::STATUS_DISABLED
                ? WeightTags::STATUS_DISABLED
                : WeightTags::STATUS_ENABLED;
        }

        if ($data['title'] === '') {
            $this->error('请输入标签名称！');
        }
        if ($data['icon'] === '') {
            $this->error('请输入图标类名！');
        }
        if (!preg_match('/^i-(fa6-solid|fa6-brands|ri)-[a-z0-9-]+$/', $data['icon'])) {
            $this->error('图标类名格式异常，请使用 i-fa6-solid-tag 这类 Iconify 类名！');
        }
        if ($data['value'] === '') {
            $this->error('请输入标签关键词！');
        }
    }

    /**
     * 修改状态
     *
     * @auth true
     */
    public function state(): void
    {
        WeightTags::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除标签
     *
     * @auth true
     */
    public function remove(): void
    {
        WeightTags::mDelete();
    }

    private static function normalizeKeywords(string $value): string
    {
        $items = preg_split('/[,，\r\n]+/u', $value) ?: [];
        $items = array_map(static fn (string $item): string => trim($item), $items);
        $items = array_values(array_unique(array_filter($items, static fn (string $item): bool => $item !== '')));

        return implode(',', $items);
    }
}

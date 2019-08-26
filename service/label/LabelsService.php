<?php
/**
 * 标签service
 * @author yjh
 * @date 2018-07-05
 */
namespace service\label;

use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsLabels;
use app\models\PsRoomLabel;
use app\models\PsRoomUser;
use app\models\PsRoomUserLabel;
use common\core\F;
use service\BaseService;
use service\rbac\OperateService;
use Yii;
use yii\db\Query;

Class LabelsService extends BaseService
{
    // 标签 新增 编辑
    private function _save($param, $scenario)
    {
        if (!empty($param['id'])) {
            $label = PsLabels::getOne($param);
            if (!$label) {
                return $this->failed('数据不存在！');
            }

            if ($label['is_sys'] == 2) {
                return $this->failed('系统内置标签不能编辑');
            }
        }

        $model = new PsLabels(['scenario' => $scenario]);

        if (!$model->load($param, '') || !$model->validate()) {
            return $this->failed($this->getError($model));
        }

        if (!$model->saveData($scenario, $param)) {
            return $this->failed($this->getError($model));
        }

        return $this->success();
    }

    // 标签 新增
    public function add($param)
    {
        return $this->_save($param, 'add');
    }

    // 标签 编辑
    public function edit($param)
    {
        return $this->_save($param, 'edit');
    }

    // 标签 列表
    public function list($param)
    {
        $list = PsLabels::getList($param);

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['label_attribute_name'] = PsLabels::attribute($v['label_attribute']);
                $list[$k]['label_type_name'] = PsLabels::type($v['label_type']);
            }
        }

        $result['list']   = $list;
        $result['totals'] = PsLabels::getTotals($param);

        return $result;
    }

    // 标签 删除
    public function delete($param)
    {
        $model = PsLabels::getOne($param);

        if (!$model) {
            return $this->failed('数据不存在');
        }

        if ($model['is_sys'] == 2) {
            return $this->failed('系统内置标签不能删除');
        }

        if ($model['community_id'] != $param['community_id']) {
            return $this->failed('没有权限删除此数据');
        }

        // return $this->failed('删除的前置条件为标签没有使用');

        if (PsLabels::updateAll(['is_delete' => 2], ['id' => $param['id']])) {
            return $this->success();
        }

        return $this->failed();
    }

    // 标签 详情
    public function show($param)
    {
        $model = PsLabels::getOne($param);

        if (!$model) {
            return $this->failed('数据不存在');
        }

        return $this->success($model);
    }

    // 标签 下拉列表
    public function differenceList($param)
    {
        $param['system_type'] = UserService::currentUser('system_type');

        $result['list'] = PsLabels::getDropDown($param);

        return $result;
    }

    // 标签 属性
    public function labelAttribute($param)
    {
        $result = PsLabels::attribute();

        $list = [];
        foreach ($result as $k => $v) {
            $arr['id'] = $k;
            $arr['name'] = $v;

            $list[] = $arr;
        }

        return ['list' => $list];
    }

    // 标签 分类
    public function labelType($param)
    {
        $result = PsLabels::type();

        $list = [];
        foreach ($result as $k => $v) {
            $arr['id'] = $k;
            $arr['name'] = $v;

            $list[] = $arr;
        }

        return ['list' => $list];
    }
}
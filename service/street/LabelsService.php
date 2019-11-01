<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/10/31
 * Time: 17:56
 */

namespace service\street;
use app\models\ParkingCars;
use app\models\PsCommunityRoominfo;
use app\models\PsMember;
use app\models\PsRoomUser;
use app\models\StLabels;
use app\models\StLabelsRela;
use Yii;

class LabelsService extends BaseService
{
// 标签 新增 编辑
    private function _save($param, $scenario)
    {
        if (!empty($param['id'])) {
            $label = StLabels::getOne($param);
            if (!$label) {
                return $this->failed('数据不存在！');
            }

            if ($label['is_sys'] == 2) {
                return $this->failed('系统内置标签不能编辑');
            }

            if ($label['organization_id'] != $param['organization_id']) {
                return $this->failed('无权限编辑');
            }
        }

        $model = new StLabels(['scenario' => $scenario]);

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
        $list = StLabels::getList($param);

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['label_attribute_name'] = StLabels::attribute($v['label_attribute']);
                $list[$k]['label_type_name'] = StLabels::type($v['label_type']);
            }
        }

        $result['list']   = $list;
        $result['totals'] = StLabels::getTotals($param);

        return $result;
    }

    // 标签 删除
    public function delete($param)
    {
        if (!$param['id']) {
            return $this->failed('id不能为空');
        }
        $model = StLabels::getOne($param);

        if (!$model) {
            return $this->failed('数据不存在');
        }

        if ($model['is_sys'] == 2) {
            return $this->failed('系统内置标签不能删除');
        }

        if ($model['organization_id'] != $param['organization_id']) {
            return $this->failed('没有权限删除此数据');
        }

        // return $this->failed('删除的前置条件为标签没有使用');

        if (StLabels::updateAll(['is_delete' => 2], ['id' => $param['id']])) {
            StLabelsRela::deleteAll(['labels_id' => $param['id']]);
            return $this->success();
        }

        return $this->failed();
    }

    // 标签 详情
    public function show($param)
    {
        $model = StLabels::getOne($param);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        if ($model['is_sys'] == 1 && $model['organization_id'] != $param['organization_id']) {
            return $this->failed('无权查看数据');
        }
        return $this->success($model);
    }

    // 标签 下拉列表
    public function differenceList($param)
    {
        $result['list'] = StLabels::getDropDown($param);

        return $result;
    }

    // 标签 属性
    public function labelAttribute($param)
    {
        $result = StLabels::attribute();

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
        $result = StLabels::type(0, $param['type']);

        $list = [];
        foreach ($result as $k => $v) {
            $arr['id'] = $k;
            $arr['name'] = $v;
            $arr['children'] = StLabels::find()->select('id, name')
                ->where(['organization_id' => $param['organization_id']])
                ->orWhere(['is_sys' => 2])
                ->andWhere(['label_type' => $k])
                ->andFilterWhere(['label_attribute' => $param['type']])
                ->andFilterWhere(['is_delete' => 1])->asArray()->all();

            $list[] = $arr;
        }

        return ['list' => $list];
    }

    // 添加 关联数据
    public function addRelation($data_id, $labels_id, $data_type,$organization_type,$organization_id)
    {
        if (!$data_id) {
            return $this->failed('关联记录id不能为空');
        }
        if (!$data_type) {
            return $this->failed('标签属性不能为空');
        }
        if (!$labels_id) {
            return $this->failed('标签id不能为空');
        }
        if (!in_array($data_type, [1,2,3])) {
            return $this->failed('标签属性值不合法');
        }
        $type = 1;
        switch ($data_type) {
            case '1': // 1 房屋
                $m = PsCommunityRoominfo::findOne($data_id);
                break;
            case '3': // 3 车辆
                $m = ParkingCars::findOne($data_id);
                break;
            default: // 2 住户
                $m = PsMember::findOne($data_id);
                break;
        }
        if (!$m) {
            return $this->failed('数据不存在');
        }

        $trans = \Yii::$app->getDb()->beginTransaction();
        try {
            if (is_array($labels_id)) { // 批量添加标签关联关系
                StLabelsRela::deleteAll(['data_type' => $data_type, 'data_id' => $data_id]);
                foreach ($labels_id as $v) {
                    $labelModel = StLabels::findOne($v);
                    if (!$labelModel) {
                        return $this->failed('标签不存在');
                    }
                    //标签关系已存在
                    $relaModel = StLabelsRela::find()
                        ->where(['labels_id' => $v, 'data_type' => $data_type, 'data_id' => $data_id, 'organization_id' => $organization_id])
                        ->asArray()
                        ->one();
                    if ($relaModel) {
                        return $this->failed('标签关联关系已存在');
                    }
                    $insert[] = ['organization_type' => $organization_type, 'organization_id' => $organization_id,'labels_id' => $v, 'data_id' => $data_id, 'data_type' => $data_type, 'created_at' => time(), 'type' => $type];
                }
            } else { // 单个添加标签关联关系
                $labelModel = StLabels::findOne($labels_id);
                if (!$labelModel) {
                    return $this->failed('标签不存在');
                }
                //标签关系已存在
                $relaModel = StLabelsRela::find()
                    ->where(['labels_id' => $labels_id, 'data_type' => $data_type, 'data_id' => $data_id, 'organization_id' => $organization_id])
                    ->asArray()
                    ->one();
                if ($relaModel) {
                    return $this->failed('标签关联关系已存在');
                }

                $insert[] = ['organization_type' => $organization_type, 'organization_id' => $organization_id,'labels_id' => $labels_id, 'data_id' => $data_id, 'data_type' => $data_type, 'created_at' => time(), 'type' => $type];
            }
            Yii::$app->db->createCommand()
                ->batchInsert('st_labels_rela', ['organization_type','organization_id','labels_id', 'data_id', 'data_type', 'created_at', 'type'], $insert)->execute();
            $trans->commit();
            return $this->success();
        } catch (\Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }

    }

    // 删除 关联数据
    public function deleteRelation($param)
    {
        if (!$param['data_id']) {
            return $this->failed('关联记录id不能为空');
        }
        if (!$param['data_type']) {
            return $this->failed('标签属性不能为空');
        }
        if (!$param['labels_id']) {
            return $this->failed('标签id不能为空');
        }
        if (!in_array($param['data_type'], [1,2,3])) {
            return $this->failed('标签属性值不合法');
        }
        $rela = StLabelsRela::find()
            ->where(['data_type' => $param['data_type'], 'data_id' => $param['data_id'], 'labels_id' => $param['labels_id']])
            ->andWhere(['organization_id' => $param['organization_id']])
            ->asArray()
            ->one();

        if (empty($rela)) {
            return $this->failed('标签关系不存在');
        }

        $model = StLabelsRela::deleteAll(['data_type' => $param['data_type'], 'data_id' => $param['data_id'],
            'labels_id' => $param['labels_id'], 'organization_id' => $param['organization_id']]);
        if (!empty($model)) {
            return $this->success();
        }
        return $this->failed();
    }

    //根据房屋id获取这个房屋下的所有标签
    public function getLabelByRoomId($room_id)
    {
        $list = StLabelsRela::find()->alias('lr')
            ->leftJoin(['l'=>StLabels::tableName()],'l.id = lr.labels_id')
            ->select(['l.name'])
            ->where(['lr.data_id'=>$room_id,'lr.data_type'=>1,'l.is_delete'=>1])->asArray()->column();
        return $list ? $list : [];
    }

    //根据房屋id获取这个房屋下的所有标签id和名称
    public function getLabelInfoByRoomId($room_id)
    {
        $list = StLabelsRela::find()->alias('lr')
            ->leftJoin(['l'=>StLabels::tableName()],'l.id = lr.labels_id')
            ->select(['l.id','l.name','l.label_type'])
            ->where(['lr.data_id'=>$room_id,'lr.data_type'=>1,'l.is_delete'=>1])->asArray()->all();
        return $list ? $list : [];
    }

    //根据车辆id获取这个车辆下的所有标签id和名称
    public function getLabelInfoByCarId($carId)
    {
        $list = StLabelsRela::find()->alias('lr')
            ->leftJoin(['l'=>StLabels::tableName()],'l.id = lr.labels_id')
            ->select(['l.id','l.name', 'l.label_type'])
            ->where(['lr.data_id'=>$carId,'lr.data_type'=>3,'l.is_delete'=>1])->asArray()->all();
        return $list ? $list : [];
    }
}
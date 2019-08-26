<?php // 标签service
namespace service\label;

use Yii;

use common\core\F;

use service\BaseService;

use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsLabels;
use app\models\PsLabelsRela;
use app\models\PsRoomLabel;
use app\models\PsRoomUser;
use app\models\PsRoomUserLabel;

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

    // 添加表关联数据
    public function addRelation($data_id, $labels_id, $data_type)
    {
        if (!empty($labels_id) && !empty($data_id) && !empty($data_type)) {
            $trans = Yii::$app->getDb()->beginTransaction();
            try {
                if (is_array($labels_id)) {
                    foreach ($labels_id as $v) {
                        $insert[] = ['labels_id' => $v, 'data_id' => $data_id, 'data_type' => $data_type, 'created_at' => time()];
                    }
                } else {
                    $insert[] = ['labels_id' => $labels_id, 'data_id' => $data_id, 'data_type' => $data_type, 'created_at' => time()];
                }

                Yii::$app->db->createCommand()
                    ->batchInsert('ps_labels_rela', ['labels_id', 'data_id', 'data_type', 'created_at'], $insert)->execute();

                $trans->commit();
            } catch (\Exception $e) {
                $trans->rollBack();
                return $this->failed($e->getMessage());
            }

            return true;
        }
        return false;
    }


    //根据房屋id获取这个房屋下的所有标签
    public function getLabelByRoomId($room_id)
    {

        $list = PsLabelsRela::find()->alias('lr')
            ->leftJoin(['l'=>PsLabels::tableName()],'l.id = lr.labels_id')
            ->select(['l.name'])
            ->where(['lr.data_id'=>$room_id,'lr.data_type'=>1,'l.is_delete'=>1])->asArray()->column();
        return $list ? $list : [];
    }
}
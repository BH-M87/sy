<?php
/**
 * 报事报修类目相关服务
 * User: fengwenchao
 * Date: 2019/8/13
 * Time: 11:07
 */

namespace service\issue;


use app\models\PsRepair;
use app\models\PsRepairType;
use common\core\PsCommon;
use service\BaseService;
use service\rbac\OperateService;

class RepairTypeService extends BaseService
{
    /*报修类型管理--类目级别*/
    public static $Repair_Type_Level = [
        '1' => '一级类目',
        '2' => '二级类目',
        '3' => '三级类目',
    ];

    //获取报修类目列表
    public function getRepairTypeList($params)
    {
        $status = PsCommon::get($params, 'status');
        $model = PsRepairType::find()
            ->filterWhere([
                'community_id' => PsCommon::get($params, 'community_id'),
            ]);
        if ($status) {
            $model->andFilterWhere(['status' => $status]);
        }
        $res = $model->orderBy('level,created_at desc')->asArray()->all();
        if ($res) {
            $result = self::getRepairTypesById(array_unique(array_column($res, 'parent_id')));
            $count = count($res);
            foreach ($res as $key => $value) {
                foreach ($result as $k => $v) {
                    if ($value['parent_id'] == $v['id']) {
                        $res[$key]['parent_name'] = $v;
                    }
                }
                if ($value['parent_id'] == '0') {
                    $res[$key]['parent_name'] = [];
                }
                $res[$key]['level_name'] = ['id' => $value['level'], 'name' => self::$Repair_Type_Level[$value['level']]];
                $res[$key]['is_relate_room'] = ($value['is_relate_room'] == '1') ? "1" : "2";
                $res[$key]['cid'] = $count;
                $count--;
            }
        }
        return $res;
    }

    //新增报修类目
    public function add($params, $userInfo = [])
    {
        $params['created_at'] = time();
        $params['status'] = 1;
        if ($params['parent_id']) {
            $type_parent = PsRepairType::findOne($params['parent_id']);
            if (!$type_parent) {
                return "父级类目不存在！";
            }
            $params['is_relate_room'] = $type_parent['is_relate_room'];//是否关联房屋只跟父类有关系
        } else {
            if (empty($params['is_relate_room'])) {
                return "是否关联房屋信息必填！";
            }
        }
        $checkResult = $this->getRepairTypeByName($params['name'], $params['community_id']);
        if ($checkResult) {
            return "该类目已经存在！";
        }
        $mod = new PsRepairType();
        $mod->setAttributes($params);
        if (!$mod->save()) {
            return "新增失败！";
        }
        $operate = [
            "community_id" =>$params['community_id'],
            "operate_menu" => "报修类目",
            "operate_type" => "新增类目",
            "operate_content" => '类别名称'.$params["name"]
        ];
        OperateService::addComm($userInfo, $operate);
        return $mod->id;
    }

    //编辑报修类目状态
    public function changeStatus($params, $userInfo = [])
    {
        $id = PsCommon::get($params, 'id', 0);
        $status = PsCommon::get($params, 'status', 1);
        $mod = PsRepairType::findOne($id);
        if (!$mod) {
            return "类目不存在！";
        }
        $mod->status = $status;
        if (!$mod->save()) {
            return "更新失败！";
        }

        $status_msg = $status == 1 ? '显示' : '隐藏';
        $operate = [
            "community_id" =>$params['community_id'],
            "operate_menu" => "报修类目",
            "operate_type" => $status_msg,
            "operate_content" => '类别名称'.$mod->name
        ];
        OperateService::addComm($userInfo, $operate);
        return $mod->id;
    }

    public function getRepairTypeByName($name, $communityId)
    {
        return PsRepairType::find()
            ->where(['community_id' => $communityId, 'name' => $name])
            ->asArray()
            ->one();
    }

    //递归处理类目
    private function dealRepairType($list, $level = 1, $parentId = '')
    {
        $newList = [];
        foreach ($list as $key => $value) {
            $v = [];
            if ($value['level'] == $level) {
                if ($parentId) {
                    if ($value['parent_id'] == $parentId) {
                        $children = self::dealRepairType($list, $level + 1, $value['id']);
                        $v = ["value" => $value['id'], "label" => $value['name'], "relate" => $value['is_relate_room']];
                        if ($children) {
                            $v['children'] = $children;
                        }
                        $newList[] = $v;
                    }
                } else {
                    $children = self::dealRepairType($list, $level + 1, $value['id']);
                    $v = ["value" => $value['id'], "label" => $value['name'], "relate" => $value['is_relate_room']];
                    if ($children) {
                        $v['children'] = $children;
                    }
                    $newList[] = $v;
                }
            }
        }
        return $newList;
    }

    //获取报修类目树
    public function getRepairTypeTree($params)
    {
        $model = $this->getRepairTypeList($params);
        return self::dealRepairType($model);
    }

    private function getRepairTypesById($idList)
    {
        $res = PsRepairType::find()->select(['id', 'name'])->where(['in', 'id', $idList])->asArray()->all();
        return $res;
    }

    public function getRepairTypeById($id)
    {
        return PsRepairType::find()
            ->where(['id' => $id])
            ->asArray()
            ->one();
    }

    //报修类型是否关联房屋
    public function repairTypeRelateRoom($id)
    {
        $relateRoom = PsRepairType::find()
            ->select('is_relate_room')
            ->where(['id' => $id])
            ->asArray()
            ->scalar();
        return $relateRoom == 1 ? true : false;
    }
}
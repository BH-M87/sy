<?php
/**
 * 报事报修类目相关服务
 * User: fengwenchao
 * Date: 2019/8/13
 * Time: 11:07
 */

namespace service\issue;
use app\models\PsRepairType;
use app\models\RepairType;
use common\core\PsCommon;
use common\MyException;
use service\BaseService;
use service\property_basic\JavaService;
use service\rbac\OperateService;
use Yii;

class RepairTypeService extends BaseService
{

    //获取报修类目列表
    public function getRepairTypeList($params)
    {
        // 获得所有小区
        $javaResult = JavaService::service()->communityNameList(['token'=>$params['token']]);
        $communityIds = !empty($javaResult['list'])?array_column($javaResult['list'],'key'):[];
        $javaResult = !empty($javaResult['list'])?array_column($javaResult['list'],'name','key'):[];
        $communityId = !empty($params['community_id'])?$params['community_id']:$communityIds;

        $is_relate_room = PsCommon::get($params, 'is_relate_room');
        $name = PsCommon::get($params, 'name');
        $query = PsRepairType::find()->filterWhere(['community_id' => $communityId]);
        if ($is_relate_room) {
            $query->andFilterWhere(['is_relate_room' => $is_relate_room]);
        }
        if ($name) {
            $query->andFilterWhere(['like','name',$name]);
        }

        $re['totals'] = $query->count();
        $query->orderBy('level,created_at desc');
        $offset = ($params['page'] - 1) * $params['rows'];
        $query->offset($offset)->limit($params['rows']);
        $list = $query->asArray()->all();
        if ($list) {
            $result = self::getRepairTypesById(array_unique(array_column($list, 'parent_id')));
            $count = count($list);
            foreach ($list as $key => $value) {
                foreach ($result as $k => $v) {
                    if ($value['parent_id'] == $v['id']) {
                        $list[$key]['parent_name'] = $v;
                    }
                }
                if ($value['parent_id'] == '0') {
                    $list[$key]['parent_name'] = [];
                }
                $list[$key]['community_name'] = !empty($value['community_id'])?$javaResult[$value['community_id']]:'';
                $list[$key]['is_relate_room'] = ($value['is_relate_room'] == '1') ? "1" : "2";
                $list[$key]['cid'] = $count;
                $count--;
            }
        }
        $re['list'] = $list;
        return $re;
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
        $mod->community_id = $params['community_id'];
        $mod->name = $params['name'];
        $mod->level = $params['level'];
        $mod->status = $params['status'];
        $mod->created_at = $params['created_at'];
        $mod->icon_url = $params['icon_url'];
        $mod->is_relate_room = $params['is_relate_room'];
//        $mod->setAttributes($params);
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

    //编辑报修类目
    public function edit($params, $userInfo = [])
    {
        $params['created_at'] = time();
        if ($params['parent_id']) {
            $type_parent = PsRepairType::findOne($params['parent_id']);
            if (!$type_parent) {
                throw new MyException('父类id不存在');
            }
            $params['is_relate_room'] = $type_parent['is_relate_room'];//是否关联房屋只跟父类有关系
        } else {
            if (empty($params['is_relate_room'])) {
                throw new MyException('请选择是否关联房屋');
            }
            //将这个类型下面的所有子类型的关联状态改成跟这个类型一致，批量更新
            $type_associated = PsRepairType::find()->where(['parent_id' => $params['id']])->asArray()->all();
            if ($type_associated) {
                //批量更新多条数据
                \Yii::$app->db->createCommand()->update(PsRepairType::tableName(), ['is_relate_room' => $params['is_relate_room']], "parent_id=:parent_id",
                    [":parent_id" => $params["id"]]
                )->execute();
            }
        }
        $mod = PsRepairType::findOne(PsCommon::get($params, 'id', 0));
        $mod->setAttributes($params);
        if ($mod->save()) {
            return true;
        } else {
            throw new MyException('编辑失败');
        }

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

    // 获取报修类目树
    public function getRepairTypeTree($params)
    {
        $params['status'] = 1;
        $model = $this->getRepairTypeList($params);
        return self::dealRepairType($model['list']);
    }

    //小程序报修类目树，结构与后台不一样
    public function getSmallAppRepairTypeTree($params)
    {
        $model = new RepairType();
        $type_info = $model::find()
            ->select('id, name, parent_id')
            ->where(['status' => 1, 'community_id' => $params['community_id']])
            ->asArray()
            ->all();
        $list = $this->_makeTree($type_info, 'id', 'parent_id', 'subList');
        return $list;
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

    /**
     * @api 获取类型分类树
     * @param $list
     * @param string $pk 当前 id
     * @param string $pid 父级 id
     * @param string $child 定义下级key
     * @param int $root 下级开始坐标
     * @return array
     */
    private function _makeTree($list, $pk = 'id', $pid = 'parent_id', $child = 'child', $root = 0)
    {
        $tree = [];
        $packData = [];
        foreach ($list as $data) {
            $packData[$data[$pk]] = $data;
        }
        foreach ($packData as $key => $val) {
            if ($val[$pid] == $root) {
                $tree[] = &$packData[$key];
            } else {
                $packData[$val[$pid]][$child][] = &$packData[$key];
            }
        }
        return $tree;
    }

    /**
     * 报事报修类型管理--获取类目--列表
     * User zq to dingding1.0
     * @param $params
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getRepairTypeLevelList($params)
    {
        // 获得所有小区
        $javaResult = JavaService::service()->communityNameList(['token'=>$params['token']]);
        $communityIds = !empty($javaResult['list'])?array_column($javaResult['list'],'key'):[];
        $communityId = !empty($params['community_id'])?$params['community_id']:$communityIds;

        $level = PsCommon::get($params, 'level');
        $id = PsCommon::get($params, 'id');
        switch ($level) {
            case "1":
                $levels = '1';
                break;
            case "2":
                $levels = '1';
                break;
            case "3":
                $levels = '2';
                break;
            default:
                $levels = '1';
        }
        $mod = PsRepairType::find()->filterWhere(['community_id' => $communityId,'level' => $levels,]);
        //剔除出入的id，防止修改类目的时候选到自己当前这个类目
        if ($id) {
            $mod->andFilterWhere(['not in', 'id', [$id]]);
        }
        $res = $mod->asArray()->all();
        return $res;
    }
}
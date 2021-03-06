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
    // 同步报修类目
    public function addType($community_id)
    {
        $arr = [
            ['name' => '居家报修', 'icon_url' => 'https://community-static.zje.com/community-1577427310864-hbk43s8238g00.jpg'],
            ['name' => '小区报修', 'icon_url' => 'https://community-static.zje.com/community-1577427328603-90yci88tikg00.jpg'],
            ['name' => '小区卫生', 'icon_url' => 'https://community-static.zje.com/community-1577427746705-jgwnga7z0u800.jpg'],
            ['name' => '小区绿化', 'icon_url' => 'https://community-static.zje.com/community-1577427761239-53nw6ge9vg400.jpg'],
            ['name' => '小区安全', 'icon_url' => 'https://community-static.zje.com/community-1577427775439-7fbeyep0vys00.jpg']
        ];

        foreach ($arr as $k => $v) {
            $m = PsRepairType::find()->where(['name' => $v['name']])->andWhere(['community_id' => $community_id])->one();
            if (empty($m)) {
                /*$mod = new PsRepairType();
                $mod->community_id = $community_id;
                $mod->name = $v['name'];
                $mod->level = 1;
                $mod->status = 1;
                $mod->type = 2;
                $mod->created_at = time();
                $mod->icon_url = $v['icon_url'];
                $mod->is_relate_room = 2;
                $mod->save();*/
                $commParam[] = ['community_id' => $community_id, 'name' => $v['name'], 'level' => 1, 'status' => 1, 'type' => 2, 'created_at' => time(), 'icon_url' => $v['icon_url'], 'is_relate_room' => 2];
            }
        }

        Yii::$app->db->createCommand()->batchInsert('ps_repair_type', ['community_id', 'name', 'level', 'status', 'type', 'created_at', 'icon_url', 'is_relate_room'], $commParam)->execute();
    }

    //获取报修类目列表
    public function getRepairTypeList($params)
    {
        // 获得所有小区
        $javaResult = JavaService::service()->communityNameList(['token'=>$params['token']]);
        $communityIds = !empty($javaResult['list'])?array_column($javaResult['list'],'key'):[];
        $javaResult = !empty($javaResult['list'])?array_column($javaResult['list'],'name','key'):[];
        $communityId = !empty($params['community_id'])?$params['community_id']:$communityIds;

        if (!empty($communityIds)) { // 同步报修类目
            foreach ($communityIds as $c_id) {
                //self::addType($c_id);
            }
        }

        $is_relate_room = PsCommon::get($params, 'is_relate_room');
        $name = PsCommon::get($params, 'name');
        $status = PsCommon::get($params, 'status');

        $query = PsRepairType::find()->filterWhere(['community_id' => $communityId]);
        if ($is_relate_room) {
            $query->andFilterWhere(['is_relate_room' => $is_relate_room]);
        }

        if ($name) {
            $query->andFilterWhere(['like','name',$name]);
        }

        if ($status) {
            $query->andFilterWhere(['=', 'status', $status]);
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
        $mod = PsRepairType::findOne(PsCommon::get($params, 'id', 0));

        if ($mod->type == 2) {
            throw new MyException('默认区域不可编辑');
        }

        $mod->community_id = $params['community_id'];
        $mod->name = $params['name'];
        $mod->icon_url = $params['icon_url'];
        $mod->is_relate_room = $params['is_relate_room'];
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

        if ($mod->type == 2) {
            throw new MyException('默认区域不可隐藏');
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

    // 小程序报修类目树，结构与后台不一样
    public function getSmallAppRepairTypeTree($params)
    {
        if (!empty($params['community_id'])) { // 同步报修类目
            self::addType($params['community_id']);
        }
        
        $model = new RepairType();
        
        $list = $model::find()
            ->select('id, name,is_relate_room,icon_url, parent_id')
            ->where(['status' => 1, 'community_id' => $params['community_id']])
            ->asArray()
            ->all();

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
<?php
namespace service\property_basic;

use common\core\F;
use common\core\PsCommon;
use common\MyException;

use service\BaseService;
use service\rbac\OperateService;

use app\models\PsCommunityBuilding;
use app\models\PsCommunityRoominfo;
use app\models\PsSteWard;
use app\models\PsSteWardEvaluate;
use app\models\PsSteWardRelat;

class StewardService extends BaseService
{
    // 参数验证
    public function _checkBackendList($params)
    {
        if (empty($params['community_id'])) {
            throw new MyException('小区ID不能为空');
        }

        if (!empty($params['building_id'])) {
            if (is_array($params['building_id'])) {
                foreach ($params['building_id'] as $v) {
                    if (!is_numeric($v)) {
                        throw new MyException('楼幢ID错误');
                    }
                }
            } else {
                throw new MyException('楼幢ID必须是数组格式');
            }
        }
    }

    // 管家详情
    public function steWardInfo($params)
    {
        $data = PsCommon::validParamArr(new PsSteWard(), $params, 'detail');
        if (!$data['status']) {
            throw new MyException($data['errorMsg']);
        }

        $this->checkSteward($params);
        // 获取管家信息
        $steward = PsSteWard::find()->select('id,name,mobile,evaluate,praise,sex')->where(['community_id' => $params['community_id'],'is_del'=>'1','id'=>$params['id']])->asArray()->one();
        $steward['negative'] = $steward['evaluate']-$steward['praise']; // 差评数量
        $steward_r[0] = $steward; // 方便遍历
        
        $this->getGroupBuildingInfo($steward_r, []);
        
        return $steward_r[0];
    }

    // 评论列表
    public function commentList($p)
    {
        $this->checkSteward($p);
        if (empty($p['steward_type']) || $p['steward_type'] == 3) {
            $p['steward_type'] = null;
        }

        $page = !empty($p['page']) ? $p['page'] : 1;
        $rows = !empty($p['rows']) ? $p['rows'] : 10;

        $stewardEvaluate = PsSteWardEvaluate::find()->alias('s')->select('s.*,r.group,r.building,r.unit,r.room,r.address')
            ->innerJoin(['r' => PsCommunityRoominfo::tableName()], 's.room_id = r.id')
            ->where(['steward_id' => $p['id']])
            ->andFilterWhere(['=', 's.community_id', $p['community_id']])
            ->andFilterWhere(['=', 's.steward_type', $p['steward_type']]);

        $totals = $stewardEvaluate->count();
        $list = $stewardEvaluate->orderBy('create_at desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();;
        foreach ($list as &$v){
            $v['create_at'] = date('Y-m-d H:i', $v['create_at']);
            $v['user_mobile'] = PsMember::userinfo($v['user_id'])['mobile'];
        }

        return ['list' => $list, 'totals' => $totals];
    }

    // 获取后台专属管家列表
    public function getBackendStewardList($params, $pageSize, $page)
    {
        $this->_checkBackendList($params);
        $stewatd = PsSteWard::find()->alias('s')->select('s.name,s.mobile,s.id,s.evaluate,s.praise,s.sex')->distinct()
            ->filterWhere(['or', ['like', 'name', $params['name'] ?? null], ['like', 'mobile', $params['name'] ?? null]])
            ->leftJoin(['r' => PsSteWardRelat::tableName()], 's.id = r.steward_id')
            ->filterWhere(['data_id' => $params['building_id'] ?? []])->andWhere(['s.community_id' => $params['community_id']])->andWhere(['s.is_del' => 1]);
        $count = $stewatd->count();
        if ($count > 0) {
            $list = $stewatd->orderBy('id desc')->offset(($page - 1) * $pageSize)->limit($pageSize)->asArray()->all();
            $this->getGroupBuildingInfo($list, $params['building_id'] ?? []);
        }

        return ['list' => $list ?? [], 'totals' => $count];
    }

    // 获取楼幢信息
    public function getGroupBuildingInfo(&$data, $building_id = null)
    {
        foreach ($data as $k => &$v) {
            $building = PsSteWardRelat::find()->alias('s')->select('b.name,b.id,b.group_name,b.group_id')
                ->innerJoin(['b' => PsCommunityBuilding::tableName()], 'b.id = s.data_id')
                ->where(['s.steward_id' => $v['id'], 's.data_type' => 1])->filterWhere(['data_id' => $building_id])->asArray()->all();
            $v['building_info'] = $building;
            $v['sex_desc'] = PsSteWard::$sex_info[$v['sex']];
            $v['praise_rate'] = $this->getPraiseRate($v['evaluate'], $v['praise']);
            $v['mobile'] = F::processMobile($v['mobile']);
        }
    }

    // 管家删除
    public function deleteBackendSteward($params, $userinfo = '')
    {
        $data = PsCommon::validParamArr(new PsSteWard(), $params, 'delete');
        if (!$data['status']) {
            throw new MyException($data['errorMsg']);
        }
        $info = $this->checkSteward($params);
        $info->is_del = 2;
        $info->save();
        $operate = [
            "community_id" =>$info['community_id'],
            "operate_menu" => "管家管理",
            "operate_type" => '删除管家',
            "operate_content" => '名称'.$info["name"]
        ];
        OperateService::addComm($userinfo, $operate);
    }

    // 新增专属管家
    public function addBackendSteward($params, $userinfo)
    {
        $steward_relat = new PsSteWardRelat();
        $steward = $this->checkStewardBaseInfo($params);
        $trans = \Yii::$app->getDb()->beginTransaction();
        $info = null;
        
        try {
            $steward->save();
            foreach ($params['building_id'] as $v) {
                $info[] = [$steward->id, 1, $v];
            }
            $steward_relat->yiiBatchInsert(['steward_id', 'data_type', 'data_id'], $info);
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "管家管理",
                "operate_type" => '新增管家',
                "operate_content" => '名称'.$params["name"]
            ];
            OperateService::addComm($userinfo, $operate);
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
    }

    // 管家修改
    public function editBackendSteward($params, $userinfo = '')
    {
        $steward_relat = new PsSteWardRelat();
        $this->checkStewardBaseInfo($params,false);
        $steward = $this->checkSteward($params);
        $trans = \Yii::$app->getDb()->beginTransaction();
        $info = null;
        
        try {
            $steward->name = $params['name'];
            $steward->mobile = $params['mobile'];
            $steward->sex = $params['sex'];
            $steward->save();
            foreach ($params['building_id'] as $v) {
                $info[] = [$steward->id, 1, $v];
            }
            PsSteWardRelat::deleteAll(['data_type'=>1,'steward_id' => $steward->id]);
            $steward_relat->yiiBatchInsert(['steward_id', 'data_type', 'data_id'], $info);
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "管家管理",
                "operate_type" => '修改管家',
                "operate_content" => '名称'.$params["name"]
            ];
            OperateService::addComm($userinfo, $operate);
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
    }

    public function getOptionBuildingInfo($params)
    {
        if (empty($params['community_id'])) {
            throw new MyException('小区ID不能为空');
        }
        $data = PsCommunityBuilding::find()->where(['community_id' => $params['community_id']])->asArray()->all();
        $building_info = [];
        $group_info = [];
        foreach ($data as $k => $v) {
            if (!in_array($v['group_id'],$group_info)) {
                $group_info[] = $v['group_id'];
                $building_info[] = [
                    'title' => $v['group_name'],
                    // 乘以负数 避免和children里的value重复 重复的话前端选择会有问题说 相同value的都会一起勾选说
                    'value' => $v['group_id']*'-1', 
                    'children' => [
                        ['title' => $v['group_name'].$v['name'],
                        'value' => $v['id']]
                    ]
                ];
            } else {
                foreach ($building_info as $kk => $vv) {
                    if ($vv['value'] == $v['group_id']*'-1') {
                        $building_info[$kk]['children'][] = ['title' => $v['group_name'].$v['name'], 'value' => $v['id']];
                    }
                }
            }
        }
        return $building_info;
    }

    // 基础信息验证
    public function checkStewardBaseInfo($params, $type = true)
    {
        if ($type ) {
            $flag = true;
            $scenario = 'add';
        } else {
            $flag = false;
            $scenario = 'edit';
        }
        $steward = new PsSteWard();
        $data = PsCommon::validParamArr($steward, $params, $scenario);
        if (!$data['status']) {
            throw new MyException($data['errorMsg']);
        }
        $this->checkMobile($params, $flag);
        $this->checkBuilding($params);
        return $steward;
    }

    // 检查用户是否存在
    public function checkSteward($params)
    {
        $steward = PsSteWard::find()->where(['community_id' => $params['community_id'],'id' => $params['id'],'is_del' => 1])->one();
        if (empty($steward)) {
            throw new MyException('用户不存在');
        }
        return $steward;
    }

    // 管家手机号验证
    public function checkMobile($params,$type = true)
    {
        $steward = PsSteWard::find()->where(['community_id' => $params['community_id'],'mobile' => $params['mobile'],'is_del' =>1])->one();
        if ($type) {
            if (!empty($steward)) {
                throw new MyException('手机号已存在');
            }
        } else {
            if (!empty($steward)) {
                if ($steward->id != $params['id']) {
                    throw new MyException('手机号已存在');
                }
            }
        }
    }

    // 楼幢信息检查
    public function checkBuilding($params)
    {
        if (empty($params['building_id']) || !is_array($params['building_id'])) {
            throw new MyException('楼幢ID格式错误');
        }
        foreach ($params['building_id'] as $v) {
            $building = PsCommunityBuilding::find()->where(['community_id' => $params['community_id'],'id' => $v])->limit(1)->one();
            if (empty($building)) {
                throw new MyException('楼幢非法ID');
            } else {
                $steward = PsSteWard::find()->alias('s')->select('s.id')
                    ->leftJoin(['r' => PsSteWardRelat::tableName()], 's.id = r.steward_id')->where(['s.is_del' => 1,'r.data_type' => 1,'r.data_id' => $v])->limit(1)->one();
                if (!empty($steward)) {
                    if (isset($params['id'])) { //新增场景
                        if ($steward->id != $params['id']) {
                            throw new MyException($building->group_name.$building->name.'已存在管家');
                        }
                    } else { //编辑场景
                        throw new MyException($building->group_name.$building->name.'已存在管家');
                    }
                }
            }
        }
    }

    // 计算好评率
    public function getPraiseRate($total, $praise)
    {
        if ($total != 0) {
            $number = (int)($praise/$total*100);
            return $number.'%';
        } else {
            return '0%';
        }
    }
}
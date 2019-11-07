<?php
/**
 * 小区区域管理，如 盛世嘉园小区下分盛景苑，盛大苑等
 * User: fengwenchao
 * Date: 2019/8/12
 * Time: 13:54
 */
namespace service\basic_data;

use app\models\PsCommunityBuilding;
use app\models\PsCommunityGroups;
use app\models\PsCommunityModel;
use common\core\PsCommon;
use service\BaseService;
use service\rbac\OperateService;

class CommunityGroupService extends BaseService {

    private function searchDeal($data)
    {
        $community_id = $data['community_id'];
        $model = PsCommunityGroups::find()->alias('cg')
            ->leftJoin(['c' => PsCommunityModel::tableName()], 'c.id = cg.community_id')
            ->where(['cg.community_id' => $community_id]);
        if (!empty($data['group_name'])) {
            $model = $model->andFilterWhere(['like', 'cg.name', $data['group_name']]);
        }
        return $model;

    }

    public function getGroupList($data,$order = 'desc')
    {
        $list = PsCommunityGroups::find()
            ->select(['id as group_id', 'name as group_name'])
            ->where(['community_id' => $data['community_id']])
            ->orderBy('id '.$order)
            ->asArray()->all();
        return $list;
    }

    //区域列表
    public function getList($data, $page, $pageSize)
    {
        $offset = ($page - 1) * $pageSize;
        $list = $this->searchDeal($data)
            ->select(['c.name as community_name', 'cg.id as group_id', 'cg.name as group_name'])
            ->offset($offset)->limit($pageSize)
            ->orderBy('cg.id desc')
            ->asArray()->all();
        return $list;
    }

    public function getListCount($data)
    {
        return $this->searchDeal($data)->count();
    }


    public function checkGroups($group, $type, $community_id = '')
    {
        //判断id是否存在
        if ($type == 1) {
            return PsCommunityGroups::findOne($group);
        }
        //判断名称是否重复，新增用
        if ($type == 2) {
            $group = preg_match("/^[0-9\#]*$/", $group) ? $group.'期' :  $group;
            return PsCommunityGroups::find()->where(['community_id' => $community_id, 'name' => $group])->asArray()->one();
        }
        //判断编码是否重复
        if ($type == 3 && !empty($group)) {
            return PsCommunityGroups::find()->where(['community_id' => $community_id, 'code' => $group])->asArray()->one();
        }
        return [];//默认返回空
    }

    //区域新增
    public function add($data,$userInfo)
    {
        $group = $this->checkGroups($data['group_name'], 2, $data['community_id']);
        if (!empty($group)) {
            return PsCommon::responseFailed('区域名称已存在');
        }

        $data['group_code'] = !empty($data['group_code']) ? $this->getFillZeroCode($data['group_code']): '';
        $group = $this->checkGroups($data['group_code'], 3, $data['community_id']);
        if (!empty($group)) {
            return PsCommon::responseFailed("区域编码已存在");
        }
        $res = $this->saveGroup($data);
        if ($res['code']) {
            /*$content = "区域名称:" . $data['group_name'];
            $content .= "区域code:" . $data['group_code'];
            $operate = [
                "community_id" =>$data['community_id'],
                "operate_menu" => "区域信息",
                "operate_type" => "新增区域",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);*/
            return PsCommon::responseSuccess($res['data']);
        } else {
            return PsCommon::responseFailed($res['msg']);
        }
    }

    //新增苑期区
    public function saveGroup($data)
    {
        $group_name = preg_match("/^[0-9\#]*$/", $data['group_name']) ? $data['group_name'].'期' :  $data['group_name']; // 房屋所在的组团名称
        $model = new PsCommunityGroups();
        $model->community_id = $data['community_id'];
        $model->name = $group_name;
        $model->code = $data['group_code'];
        $model->groups_code = PsCommon::getIncrStr('HOUSE_GROUP',YII_ENV.'lyl:house-group');
        if ($model->save()) {
            return $this->success($model->id);
        } else {
            return $this->failed("新增失败");
        }
    }

    //默认新增住宅
    public function saveGroupDefault($community_id)
    {
        $name = '住宅';
        $group = PsCommunityGroups::find()->where(['community_id' => $community_id, 'name' => $name])->asArray()->one();
        if ($group) {
            $group_id = $group['id'];
            return $this->success($group_id);
        } else {
            $groupData['community_id'] = $community_id;
            $groupData['group_name'] = $name;
            $groupData['group_code'] = 0;
            return $this->saveGroup($groupData);
        }
    }

    // 编辑苑期区
    public function edit($data,$userInfo)
    {
        $group = $this->checkGroups($data['group_id'], 1);
        if (empty($group)) {
            return PsCommon::responseFailed("苑期区ID不存在");
        }
        $model = $group;
        $data['group_code'] = !empty($data['group_code']) ? $this->getFillZeroCode($data['group_code']) : '';
        $group = $this->checkGroups($data['group_code'], 3, $data['community_id']);
        if (!empty($group) && $group['id'] != $data['group_id']) {
            return PsCommon::responseFailed("苑期区编码已存在");
        }
        $model->community_id = $data['community_id'];
        $model->code = $data['group_code'];
        if ($model->save()) {
            /*$content = "区域code:" . $data['group_code'];
            $operate = [
                "community_id" =>$data['community_id'],
                "operate_menu" => "区域信息",
                "operate_type" => "编辑区域",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);*/

            return PsCommon::responseSuccess($model->id);
        } else {
            return PsCommon::responseFailed("编辑失败");
        }
    }

    public function detail($data)
    {
        $group = $this->checkGroups($data['group_id'], 1);
        if (empty($group)) {
            return PsCommon::responseFailed("苑期区ID不存在");
        }
        $detail['group_code'] = $group['code'] ? $group['code'] : '';
        $detail['group_id'] = $group['id'];
        $detail['group_name'] = $group['name'];
        return PsCommon::responseSuccess($detail);
    }

    // 苑期区删除
    public function delete($data,$userInfo)
    {
        $group = $this->checkGroups($data['group_id'], 1);
        $group_name = $group['name'];
        if (empty($group)) {
            return PsCommon::responseFailed("苑期区ID不存在");
        }

        $build = PsCommunityBuilding::find()->where(['community_id' => $data['community_id'], 'group_id' => $data['group_id']])->asArray()->one();
        if ($build) {
            return PsCommon::responseFailed("区域下无挂靠楼栋才可删除");
        }
        if ($group->delete()) {
            return PsCommon::responseSuccess("删除成功");
        } else {
            return PsCommon::responseFailed("删除失败");
        }
    }

    /**
     * 苑期区编码以0填充
     * @param $var
     * @param int $num
     * @return string
     */
    public function getFillZeroCode($var, $num = 2)
    {
        return str_pad($var,$num,"0",STR_PAD_LEFT);
    }

}
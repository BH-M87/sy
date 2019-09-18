<?php
namespace service\property_basic;

use service\BaseService;
use service\rbac\OperateService;

use app\models\PsCommunityConvention;
use app\models\PsCommunityModel;

class CommunityConventionService extends BaseService 
{
    // 新增公约
    public function addConvention($params)
    {
        $model = new PsCommunityConvention(['scenario' => 'add']);
        $result = PsCommunityModel::find()->select(['id', 'name'])->where(['=','id',$params['community_id']])->asArray()->one();
        if(!empty($result)){
            $params['title'] = !empty($params['title'])?$params['title']:$result['name']."社区公约";
        }
        $temp = '<p>遵守行车秩序，礼让行人，禁止鸣笛；</p><p>按规定方向停车、不跨线、压线、不占用他人车位；</p><p>外出遛狗时，需佩戴牵引绳，并及时清理宠物的粪便保持公共场所的环境整洁，生活垃圾分类处理；</p><p>不往窗外抛洒物品、垃圾，不在窗台、阳台边缘放置易坠落物品；</p><p>在清晨和夜晚，主动将室内音量降低，不扰邻；亲友到访主动登记，出入社区谨防尾随，发现可疑情况及时告知物管人员；</p><p>组建社区群组，及时传达咨询，并积极开展社区活动；</p><p>不破坏绿化及公物，严禁群租，恪守社区功德；</p><p>关爱呵护孩子自尊，在公共场合避免责骂；</p><p>孝敬服务，关爱老人，主动为老人提供帮助；邻里之间发生矛盾，以和为贵，各自退让，及时化解。</p>';
        $params['content'] = !empty($params['content'])?$params['content']:$temp;
        if ($model->load($params, '') && $model->validate() && $model->saveData()) {
            return $this->success();
        }
        return $this->failed($this->getError($model));
    }

    // 修改公约
    public function updateConvention($params, $userinfo = [])
    {
        $model = new PsCommunityConvention(['scenario' => 'update']);
        if ($model->load($params, '') && $model->validate() && $model->edit($params)) {
            if (!empty($userinfo)){
                $content = "编辑内容:".$params['content'];
                $operate = [
                    "community_id" =>$params['community_id'],
                    "operate_menu" => "社区运营",
                    "operate_type" => "邻里公约编辑",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
            }
            return $this->success();
        }
        return $this->failed($this->getError($model));
    }

    // 公约详情接口
    public function conventionDetail($params)
    {
        $model = new PsCommunityConvention(['scenario' => 'detail']);
        if ($model->load($params, '') && $model->validate()) {
            $result = $model->detail($params);
            $result['update_at'] = !empty($result['update_at'])?date('Y.m.d',$result['update_at']):'';
            return ['data'=>$result];
        }
        return $this->failed($this->getError($model));
    }
}
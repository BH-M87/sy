<?php
/**
 * 社区指南
 * @author shenyang
 * @date 2017/11/13
 */

namespace service\property_basic;

use common\core\F;
use common\core\PsCommon;

use app\models\PsGuide;

use service\rbac\OperateService;
use service\BaseService;

Class GuideService extends BaseService
{
    const STATUS_SHOW = 1;
    const STATUS_HIDE = 2;

    const DOMAIN_NAME = "https://static.elive99.com";
    //快递、药店、水果超市、装修五金、水站、搬家公司、宽带、疏通、热力、燃气、修车、修鞋、家电维修、家居维修、手机维修、物品回收、开锁、家政、健身
    public $types = [
        1 => ['id' => 1, 'name' => '快递','img'=>self::DOMAIN_NAME.'/2019032213402347152.png'],
        2 => ['id' => 2, 'name' => '药店','img'=>self::DOMAIN_NAME.'/2019032213411478891.png'],
        3 => ['id' => 3, 'name' => '水果超市','img'=>self::DOMAIN_NAME.'/2019032213413711645.png'],
        4 => ['id' => 4, 'name' => '装修五金','img'=>self::DOMAIN_NAME.'/2019032213415641543.png'],
        5 => ['id' => 5, 'name' => '水站','img'=>self::DOMAIN_NAME.'/2019032213421652917.png'],
        6 => ['id' => 6, 'name' => '搬家公司','img'=>self::DOMAIN_NAME.'/2019032213424037666.png'],
        7 => ['id' => 7, 'name' => '宽带','img'=>self::DOMAIN_NAME.'/2019032213430347723.png'],
        8 => ['id' => 8, 'name' => '疏通','img'=>self::DOMAIN_NAME.'/2019032213432395821.png'],
        9 => ['id' => 9, 'name' => '热力','img'=>self::DOMAIN_NAME.'/2019032213435237123.png'],
        10 => ['id' => 10, 'name' => '燃气','img'=>self::DOMAIN_NAME.'/2019032213441221266.png'],
        11 => ['id' => 11, 'name' => '修车','img'=>self::DOMAIN_NAME.'/2019032213443241135.png'],
        12 => ['id' => 12, 'name' => '修鞋','img'=>self::DOMAIN_NAME.'/2019032213445165513.png'],
        13 => ['id' => 13, 'name' => '家电维修','img'=>self::DOMAIN_NAME.'/201903221345113268.png'],
        14 => ['id' => 14, 'name' => '家居维修','img'=>self::DOMAIN_NAME.'/2019032213453312365.png'],
        15 => ['id' => 15, 'name' => '手机维修','img'=>self::DOMAIN_NAME.'/2019032213455084472.png'],
        16 => ['id' => 16, 'name' => '物品回收','img'=>self::DOMAIN_NAME.'/2019032213460825227.png'],
        17 => ['id' => 17, 'name' => '开锁','img'=>self::DOMAIN_NAME.'/2019032213463140260.png'],
        18 => ['id' => 18, 'name' => '家政','img'=>self::DOMAIN_NAME.'/2019032213474588639.png'],
        19 => ['id' => 19, 'name' => '健身','img'=>self::DOMAIN_NAME.'/2019032213480419042.png'],
    ];

    public $status = [
        1 => ['id' => 1, 'name' => '显示'],
        2 => ['id' => 2, 'name' => '隐藏'],
    ];

    /**
     * 后台列表
     * @param $communityId
     * @param $page
     * @param $pageSize
     */
    public function getList($communityId, $page, $pageSize)
    {
        $data = PsGuide::find()
            ->where(['community_id' => $communityId])
            ->orderBy('id desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            if (!strstr($v['img_url'], 'http')) {
                $v['img_url'] = F::ossImagePath($v['img_url']);
            }
            $v['type'] = PsCommon::get($this->types, $v['type'], []);
            $v['status'] = PsCommon::get($this->status, $v['status'], []);
            $v['hours_start'] = $v['hours_start']>=10?$v['hours_start'].":00":"0".$v['hours_start'].":00";
            $v['hours_end'] = $v['hours_end']>=10?$v['hours_end'].":00":"0".$v['hours_end'].":00";

            $result[] = $v;
        }

        return $result;
    }

    /**
     * 获取所有显示的指南
     * @param $communityId
     */
    public function getAllOnline($communityId)
    {
        $data = PsGuide::find()
            ->where(['community_id' => $communityId, 'status' => self::STATUS_SHOW])
            ->orderBy('update_at desc')
            ->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $v['type'] = PsCommon::get($this->types, $v['type'], []);
            $v['status'] = PsCommon::get($this->status, $v['status'], []);
            $result[] = $v;
        }
        return $result;
    }

    /**
     * 数量
     * @param $communityId
     */
    public function getListCount($communityId)
    {
        return PsGuide::find()->where(['community_id' => $communityId])->count();
    }

    /**
     * 新建
     * @param $params
     */
    public function create($params,$userinfo=[])
    {
        $model = new PsGuide(['scenario' => 'add']);
        if ($model->load($params, '') && $model->validate() && $model->save()) {
            if (!empty($userinfo)){
                $content = "社区名称:".$params['title']."地址:".$params['address'];
                $operate = [
                    "community_id" =>$params['community_id'],
                    "operate_menu" => "社区运营",
                    "operate_type" => "社区指南新增",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
            }
            return $this->success();
        }
        return $this->failed($this->getError($model));
    }

    /**
     * 详情
     * @param $id
     */
    public function detail($id, $communityId = null)
    {
        $data = PsGuide::find()
            ->filterWhere(['id' => $id, 'community_id' => $communityId])
            ->asArray()->one();
        if (!$data) {
            return $this->failed('数据不存在');
        }
        
        if (!strstr($data['img_url'], 'http')) {
            $data['img_url'] = F::ossImagePath($data['img_url']);
        }
        $data['type'] = PsCommon::get($this->types, $data['type'], []);
        $data['status'] = PsCommon::get($this->status, $data['status'], []);

        return $this->success($data);
    }

    /**
     * 编辑
     * @param $id
     * @param $params
     */
    public function edit($id, $params,$userinfo=[])
    {
        $model = PsGuide::findOne($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        
        if (strstr($params['img_url'], 'http')) {
            unset($params['img_url']);
        }

        //数据验证
        $checkModel = new PsGuide(['scenario' => 'update']);
        if($checkModel->load($params,'') && $checkModel->validate()){
            $model->update_at = time();
            $model->load($params, '');
            if ($model->validate() && $model->save()) {
                if (!empty($userinfo)){
                    $content = "社区名称:".$params['title']."地址:".$params['address'];
                    $operate = [
                        "community_id" =>$params['community_id'],
                        "operate_menu" => "社区运营",
                        "operate_type" => "社区指南编辑",
                        "operate_content" => $content,
                    ];
                    OperateService::addComm($userinfo, $operate);
                }
                return $this->success();
            }
            return $this->failed($this->getError($model));
        }else{
            return $this->failed($this->getError($checkModel));
        }

    }

    /**
     * 删除
     * @param $id
     */
    public function remove($id, $communityId,$userinfo=[])
    {
        if (!$id) {
            return $this->failed('ID不能为空');
        }
        $model = PsGuide::findOne(['id' => $id, 'community_id' => $communityId]);
        if (!$model) {
            return $this->failed('数据不存在');
        }
//        if ($model->status == self::STATUS_SHOW) {
//            return $this->failed('显示状态无法被删除');
//        }
        if (!$model->delete()) {

            return $this->failed('数据不存在');
        }
        if (!empty($userinfo)){
            $content = "社区名称:".$model->title."地址:".$model->address;
            $operate = [
                "community_id" =>$communityId,
                "operate_menu" => "社区运营",
                "operate_type" => "社区指南删除",
                "operate_content" => $content,
            ];
            OperateService::addComm($userinfo, $operate);
        }
        return $this->success();
    }

    /**
     * 显示、隐藏
     * @param $id
     * @param $status
     */
    public function openDown($id, $communityId, $status)
    {
        $model = PsGuide::findOne(['id' => $id, 'community_id' => $communityId]);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        if ($model->status == $status) {
            return $this->failed('无法重复'.($status == self::STATUS_SHOW ? '显示' : '隐藏'));
        }
        $model->status = intval($status);
        $model->update_at = time();//显示时间

        if ($model->validate() && $model->save()) {
            return $this->success();
        }
        return $this->failed($this->getError($model));
    }
}

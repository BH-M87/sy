<?php
/**
 * 小程序消息
 * @author shenyang
 * @date 2017-06-12
 */

namespace service\message;

use common\core\PsCommon;
use app\models\PsLifeBroadcast;
use app\models\PsCommunityModel;
use app\models\PsLifeBroadcastRecord;
use service\BaseService;
use yii\db\Expression;
use Exception;
use Yii;

Class BroadcastService extends BaseService
{

    public $common = [
        'push_type' => [
            3 => ['id' => 3, 'name' => '小程序'],
        ],
        'type' => [
            1 => ['id' => 1, 'name' => '文本'],
            2 => ['id' => 2, 'name' => '图片'],
            3 => ['id' => 3, 'name' => '图文'],
        ],
        'status' => [
            1 => ['id' => '1', 'name' => '已发送'],
            2 => ['id' => '2', 'name' => '未发送'],
            3 => ['id' => '3', 'name' => '发送失败'],
        ]
    ];

    /**
     * 创建广播消息
     * @param $params
     * @return array
     */
    public function create($params)
    {

        $transaction = Yii::$app->getDb()->beginTransaction();
        try {
            $model = new PsLifeBroadcast();
            $model->created_at = time();
            $model->load($params, '');
            if ($model->validate()) {  # 验证数据
                $type = PsCommon::get($params, 'type');
                $push_type = PsCommon::get($params, 'push_type');
                $vali_type = ['1' => 'content', '2' => 'image', '3' => 'material'];                         //生活号的事物
                $vali_type_small = ['1' => 'content_small', '2' => 'image_small', '3' => 'imaText_small'];  //小程序的事务
                if ($push_type == 1) {
                    $model->setScenario($vali_type[$type]);
                } else {
                    $model->setScenario($vali_type_small[$type]);
                }
                if (($push_type == 1) && (empty($params['life_service_ids']) || !is_array($params['life_service_ids']))) {//推送为全部跟生活号时才验证
                    return $this->failed('请选择要发送的生活号');
                }
                if (($push_type == 2) && (empty($params['community_ids']) || !is_array($params['community_ids']))) {//推送为小程序时才验证
                    return $this->failed('请选择要推送的小区');
                }
                if (!$model->save()) {
                    $errors = array_values($model->getErrors());
                    throw new Exception($errors[0][0]);
                }

                //添加生活号关系
                if (!empty($params['life_service_ids']) && $push_type == 1) {
                    foreach ($params['life_service_ids'] as $id) {
                        $record = new PsLifeBroadcastRecord();
                        $record->broadcast_id = $model->id;
                        $record->life_service_id = $id;
                        $record->create_at = time();
                        $record->status = 2;
                        if (!$record->save()) {
                            $errors = array_values($record->getErrors());
                            throw new Exception($errors[0][0]);
                        }
                    }
                }
                //添加小区关系
                if (!empty($params['community_ids']) && $push_type == 2) {
                    foreach ($params['community_ids'] as $id) {
                        $record = new PsLifeBroadcastRecord();
                        $record->broadcast_id = $model->id;
                        $record->community_id = $id;
                        $record->create_at = time();
                        $record->send_at = time();
                        $record->status = 1;
                        if (!$record->save()) {
                            $errors = array_values($record->getErrors());
                            throw new Exception($errors[0][0]);
                        }
                    }
                }
                $transaction->commit();
                return $this->success();
            }
            return $this->failed($model->getErrors());
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /**
     * 未发送列表
     * @param int $limit
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getUnSends($limit = 10)
    {
        return PsLifeBroadcastRecord::find()->with('broadcast')->where(['status' => 2, 'is_lock' => 0])
            ->andWhere(['!=','life_service_id',''])
            ->orderBy('id asc')->limit($limit)->indexBy('id')->all();
    }

    /**
     * 锁行
     * @param $ids
     * @return int
     */
    public function lock($ids)
    {
        return PsLifeBroadcastRecord::updateAll(['is_lock' => time()], ['id' => $ids]);
    }

    /**
     * 解锁
     * @param $ids
     * @return int
     */
    public function unlock($ids)
    {
        return PsLifeBroadcastRecord::updateAll(['is_lock' => 0], ['id' => $ids]);
    }

    /**
     * 已发送列表
     * TODO 优化
     */
    public function getSends($page, $pageSize)
    {
        $pageSize = 10;
        //消息内容
        $broadsList = PsLifeBroadcast::find()
            ->orderBy('id desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->asArray()->all();
        if (empty($broadsList)) {
            return [];
        }
        $infoList = [];
        foreach ($broadsList as $broads){
            $info['id'] = $broads['id'];
            $info['push_type'] = $broads['push_type'];
            $info['push_type_desc'] = $broads['push_type']==1?"生活号":"小程序";
            $info['type'] = $this->common['type'][$broads['type']];
            $info['title'] = $broads['title'];
            $info['content'] = $broads['content'];
            $info['image'] = $broads['image'];
            $info['material_cover_image'] = $info['material_title'] = '';
            if (!empty($broads['material_id'])) {
                //素材
                $materials = !empty($broads['material_id']) ? MaterialService::service()->getBasicInfoOne($broads['material_id']) : [];
                $info['material_cover_image'] = $materials['cover_image'];
                $info['material_title'] = $materials['title'];
            }
            //验证推送范围是生活号还是小程序
            if ($broads['push_type'] == 1) {//说明是生活号
                $exception = new Expression("GROUP_CONCAT(life_service_id SEPARATOR ',') AS life_service_ids, send_at, status");
                $data = PsLifeBroadcastRecord::find()
                    ->select($exception)
                    ->where(['broadcast_id'=>$broads['id']])
                    ->groupBy('status')
                    ->orderBy('id desc')
                    ->asArray()->all();
                if(!empty($data)){
                    $life_services_list = [];
                    foreach ($data as $d){
                        $life_ids = explode(',', $d['life_service_ids']);
                        //生活号
                        $lifes = !empty($life_ids) ? LifeNoService::service()->getLifeNoName($life_ids) : [];

                        $life_services['life_services'] = $lifes;
                        $life_services['status'] = $this->common['status'][$d['status']]['name'];
                        $life_services['send_at'] = !empty($d['send_at'])?date('Y-m-d H:i',$d['send_at']):'';
                        $life_services_list[] = $life_services;
                    }
                    $info['life_services_list'] = $life_services_list;
                }
            }else{
                $community_list = PsLifeBroadcastRecord::find()->select(['community_id'])->where(['broadcast_id'=>$broads['id']])->asArray()->column();
                //小区
                $communitys = !empty($community_list) ? PsCommunityModel::getCommunityName($community_list) : [];
                $info['community_list'] = $communitys;
            }
            $info['created_at'] = !empty($broads['created_at'])?date('Y-m-d H:i',$broads['created_at']):'';
            $infoList[] = $info;
        }
        return $infoList;
    }


    /**
     * 已发送总数(分组)
     */
    public function getSendsCount()
    {
        return PsLifeBroadcast::find()->asArray()->count();
    }

    //消息详情
    public function getMsgInfo($params)
    {
        $id = PsCommon::get($params, 'id');
        if (empty($id)) {
            return $this->failed('消息id不能为空');
        }
        //消息内容
        $broads = PsLifeBroadcast::find()->where(['id' => $id])->asArray()->one();
        if (empty($broads)) {
            return $this->failed('消息不存在');
        }

        $info['id'] = $broads['id'];
        $info['push_type'] = $broads['push_type'];
        $info['push_type_desc'] = $broads['push_type']==1?"生活号":"小程序";
        $info['type'] = $broads['type'];
        $info['type_desc'] = $this->common['type'][$broads['type']]['name'];
        $info['title'] = $broads['title'];
        $info['content'] = $broads['content'];
        $info['image'] = $broads['image'];
        $info['material_cover_image'] = $info['material_title'] = '';
        if (!empty($broads['material_id'])) {
            //素材
            $materials = !empty($broads['material_id']) ? MaterialService::service()->getBasicInfoOne($broads['material_id']) : [];
            $info['material_cover_image'] = $materials['cover_image'];
            $info['material_title'] = $materials['title'];
        }
        //验证推送范围是生活号还是小程序
        if ($broads['push_type'] == 1) {//说明是生活号
            $exception = new Expression("GROUP_CONCAT(life_service_id SEPARATOR ',') AS life_service_ids, send_at, status");
            $data = PsLifeBroadcastRecord::find()
                ->select($exception)
                ->where(['broadcast_id'=>$broads['id']])
                ->groupBy('status')
                ->orderBy('id desc')
                ->asArray()->all();
            if(!empty($data)){
                $life_services_list = [];
                foreach ($data as $d){
                    $life_ids = explode(',', $d['life_service_ids']);
                    //生活号
                    $lifes = !empty($life_ids) ? LifeNoService::service()->getLifeNoName($life_ids) : [];

                    $life_services['life_services'] = $lifes;
                    $life_services['status'] = $this->common['status'][$d['status']]['name'];
                    $life_services['send_at'] = !empty($d['send_at'])?date('Y-m-d H:i',$d['send_at']):'';
                    $life_services_list[] = $life_services;
                }
                $info['life_services_list'] = $life_services_list;
            }
        }else{
            $community_list = PsLifeBroadcastRecord::find()->select(['community_id'])->where(['broadcast_id'=>$broads['id']])->asArray()->column();
            //小区
            $communitys = !empty($community_list) ? PsCommunityModel::getCommunityName($community_list) : [];
            $info['community_list'] = $communitys;
        }
        $info['created_at'] = !empty($broads['created_at'])?date('Y-m-d H:i',$broads['created_at']):'';
        return $this->success($info);
    }
}
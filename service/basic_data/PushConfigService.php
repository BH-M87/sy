<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/5/17
 * Time: 16:29
 */

namespace service\basic_data;

use app\models\ParkingPushConfig;
use yii\base\Exception;

class PushConfigService  extends BaseService
{

    private function checkUnique($supplier_id,$community_id){
        $model = ParkingPushConfig::find()
            ->select(['id', 'aes_key', 'call_back_tag', 'request_url' , 'is_connect'])
            ->where(['supplier_id'=>$supplier_id,'community_id'=>$community_id])->one();
        if($model){
            return $model;
        }else{
            return false;
        }
    }

    public function register($data){
        $push = self::checkUnique($data['supplier_id'],$data['community_id']);
        if($push){
            return $this->failed("该供应商已经注册");
        }

        //增加事务注册回调
        $tran = \Yii::$app->db->beginTransaction();
        try {
            $model = new ParkingPushConfig();
            $model->setAttributes($data);
            if (!$model->save()) {
                throw new Exception("注册失败");
            }
            $params['authCode'] = $data['auth_code'];
            $params['methodName'] = 'checkUrl';
            $req['methodName'] = "checkUrl";
            PushService::service()->init($params)->request($req);

            $tran->commit();
            return $this->success();
        } catch (Exception $e) {
            $tran->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    public function update($data){
        $model = self::checkUnique($data['supplier_id'],$data['community_id']);
        if(!$model){
            return $this->failed("还未注册");
        }

        $tran = \Yii::$app->db->beginTransaction();
        try {
            $model->setAttributes($data);
            if (!$model->save()) {
                throw new Exception("更新失败");
            }
            $params['authCode'] = $data['auth_code'];
            $params['methodName'] = 'checkUrl';
            $req['methodName'] = "checkUrl";
            PushService::service()->init($params)->request($req);
            $tran->commit();
            return $this->success();
        }  catch (Exception $e) {
            $tran->rollBack();
            return $this->failed("更新失败，".$e->getMessage());
        }
    }

    public function get($data){
        $model = self::checkUnique($data['supplier_id'],$data['community_id']);
        if(!$model){
            return $this->failed("还未注册");
        }
        return $this->success($model->toArray());
    }

    public function delete($data){
        $model = self::checkUnique($data['supplier_id'],$data['community_id']);
        if(!$model){
            return $this->failed("还未注册");
        }
        if($model->delete()){
            return $this->success();
        }else{
            return $this->failed("删除失败");
        }
    }

}
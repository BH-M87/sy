<?php
namespace service\park;

use app\models\PsParkShared;
use service\BaseService;
use Yii;
use yii\db\Exception;

class SharedService extends BaseService{


    //兑换记录新增（小程序端）
    public function addOfC($params){
        print_r($params);die;
        $trans = Yii::$app->db->beginTransaction();
        try{
            $recordsParams['product_id'] = !empty($params['product_id'])?$params['product_id']:'';
            $recordsParams['product_num'] = !empty($params['product_num'])?$params['product_num']:'';
            $recordsParams['community_id'] = !empty($params['community_id'])?$params['community_id']:'';
            $recordsParams['room_id'] = !empty($params['room_id'])?$params['room_id']:'';
            $recordsParams['user_id'] = !empty($params['user_id'])?$params['user_id']:'';
            $recordsParams['volunteer_id'] = !empty($params['volunteer_id'])?$params['volunteer_id']:'';
            $recordsParams['cust_name'] = !empty($params['cust_name'])?$params['cust_name']:'';
            $recordsParams['cust_mobile'] = !empty($params['cust_mobile'])?$params['cust_mobile']:'';
            $recordsParams['address'] = !empty($params['address'])?$params['address']:'';
            $model = new PsParkShared(['scenario'=>'add']);
            if($model->load($recordsParams,'')&&$model->validate()){
                if(!$model->save()){
                    return $this->failed('新增失败！');
                }

                $trans->commit();
                return $this->success(['id'=>$model->attributes['id'],'verification_qr_code'=>!empty($qrUrl)?$qrUrl:'']);
            }else{
                $msg = array_values($model->errors)[0][0];
                return $this->failed($msg);
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }
}
<?php
namespace service\park;

use app\models\PsParkShared;
use service\BaseService;
use Yii;
use yii\db\Exception;

class SharedService extends BaseService{


    //兑换记录新增（小程序端）
    public function addOfC($params){
        $trans = Yii::$app->db->beginTransaction();
        try{
            $model = new PsParkShared(['scenario'=>'add']);
            $params['start_date'] = !empty($params['start_date'])?strtotime($params['start_date']):0;
            $params['end_date'] = !empty($params['end_date'])?strtotime($params['end_date']." 23:59:59"):0;
            if($model->load($params,'')&&$model->validate()){
                print("asdf");die;
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
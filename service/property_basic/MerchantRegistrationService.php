<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/6/4
 * Time: 15:00
 * Desc: 商户报名
 */
namespace service\property_basic;

use app\models\PsMerchantRegistration;
use service\BaseService;

class MerchantRegistrationService extends BaseService {

    public function add($params){
        $model = new PsMerchantRegistration();
        $model->setScenario('add');
        if($model->load($params,'') && $model->validate()){
            if(!$model->save()){
                return $this->failed("新增失败");
            }
            return $this->success(['id'=>$model->attributes['id']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }
}
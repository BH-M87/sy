<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/5/22
 * Time: 9:31
 * Desc:兑换记录service
 */
namespace service\property_basic;

use app\models\PsDeliveryRecords;
use common\core\PsCommon;
use service\BaseService;

class DeliveryRecordsService extends BaseService{

    //兑换记录新增
    public function add($params){
        $model = new PsDeliveryRecords(['scenario'=>'add']);
        if($model->load($params,'')&&$model->validate()){
            if(!$model->save()){
                return $this->failed('新增失败！');
            }
            return $this->success(['id'=>$model->attributes['id']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //兑换记录详情
    public function detail($params){
        $model = new PsDeliveryRecords(['scenario'=>'detail']);
        if($model->load($params,'')&&$model->validate()){
            $result = $model->detail($params);
            return $this->success($result);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //兑换列表
    public function getList($params){
        $model = new PsDeliveryRecords();
        $result = $model->getList($params);
        if(!empty($result['data'])){
            foreach($result['data'] as $key=>$value){
                $result['data'][$key]['create_at_msg'] = !empty($value['create_at'])?date('Y-m-d H:i',$value['create_at']):'';
                $result['data'][$key]['cust_name'] = !empty($value['cust_name'])?PsCommon::hideName($value['cust_name']):'';
                $result['data'][$key]['cust_mobile'] = !empty($value['cust_mobile'])?PsCommon::hideMobile($value['cust_mobile']):'';
            }
        }
        return $this->success($result);
    }
}
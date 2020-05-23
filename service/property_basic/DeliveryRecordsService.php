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
use common\core\Curl;
use common\core\PsCommon;
use service\BaseService;

class DeliveryRecordsService extends BaseService{

    //兑换记录新增（小程序端）
    public function addOfC($params){

        self::doReduce();die;
        //验证用户信息
        print_r($params);die;

        $model = new PsDeliveryRecords(['scenario'=>'add']);
        if($model->load($params,'')&&$model->validate()){
            if(!$model->save()){
                return $this->failed('新增失败！');
            }
            //调用街道志愿者接口 减积分
            return $this->success(['id'=>$model->attributes['id']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //扣除积分
    public function doReduce(){
        $url = "https://dev-api.elive99.com/volunteer-ckl/?r=/internal/volunteer/use-score";
        $data['sysUserId'] = 45;
        $data['score'] = 50;
        $data['content'] = "测试";

        $curl = Curl::getInstance();
        $result = $curl::post($url,$data);
        print_r($result);die;
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
                $result['data'][$key]['status_msg'] = !empty($value['status'])?$model::STATUS[$value['status']]:'';
                $result['data'][$key]['delivery_type_msg'] = !empty($value['delivery_type'])?$model::DELIVERY_TYPE[$value['delivery_type']]:'';
            }
        }
        return $this->success($result);
    }

    //兑换列表小程序端
    public function getListOfC($params){
        $model = new PsDeliveryRecords(['scenario'=>'app_list']);
        if($model->load($params,'')&&$model->validate()){
            $result = $model->getListOfC($params);
            if(!empty($result['data'])){
                foreach($result['data'] as $key=>$value){
                    $result['data'][$key]['create_at_msg'] = !empty($value['create_at'])?date('Y/m/d',$value['create_at']):'';
                }
            }
            return $this->success($result);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //兑换记录发货
    public function edit($params){
        if(empty($params['delivery_type'])){
            return $this->failed("配送方式必填！");
        }
        $model = new PsDeliveryRecords();
        $scenario = $params['delivery_type'] == 1?'send_edit':'self_edit';
        $model->setScenario($scenario);
        $updateParams['id'] = !empty($params['id'])?$params['id']:'';
        $updateParams['delivery_type'] = !empty($params['delivery_type'])?$params['delivery_type']:'';
        $updateParams['courier_company'] = !empty($params['courier_company'])?$params['courier_company']:'';
        $updateParams['order_num'] = !empty($params['order_num'])?$params['order_num']:'';
        $updateParams['records_code'] = !empty($params['records_code'])?$params['records_code']:'';
        $updateParams['operator_id'] = !empty($params['create_id'])?$params['create_id']:'';
        $updateParams['operator_name'] = !empty($params['create_name'])?$params['create_name']:'';
        if($model->load($updateParams,'')&&$model->validate()){
            if(!$model->edit($updateParams)){
                return $this->failed("操作失败");
            }
            return $this->success(['id'=>$model->attributes['id']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }
}
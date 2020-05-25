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
use Yii;
use yii\db\Exception;

class DeliveryRecordsService extends BaseService{

    //兑换记录新增（小程序端）
    public function addOfC($params){

        $streetParams['sysUserId'] = 465465;
        $streetParams['score'] = 5;
        $streetParams['content'] = "兑换";
        $streetResult = self::doReduce($streetParams);
        print_r($streetResult);die;


        if(empty($params['user_id'])){
            return $this->failed("用户id不能为空");
        }
        if(empty($params['community_id'])){
            return $this->failed("小区id不能为空");
        }
        if(empty($params['product_id'])){
            return $this->failed("商品id不能为空");
        }
        if(empty($params['product_num'])){
            return $this->failed("商品数量不能为空");
        }

        $javaParams['communityId'] = $params['community_id'];
        $javaParams['residentId'] = $params['user_id'];
        $javaParams['token'] = $params['token'];
        $javaService = new JavaOfCService();
        $result = $javaService->getResidentFullAddress($javaParams);
        if(!isset($result['fullName'])||empty($result['fullName'])){
            return $this->failed("住户信息不存在");
        }
        $trans = Yii::$app->db->beginTransaction();
        try{
            $recordsParams['product_id'] = !empty($params['product_id'])?$params['product_id']:'';
            $recordsParams['product_num'] = !empty($params['product_num'])?$params['product_num']:'';
            $recordsParams['community_id'] = !empty($params['community_id'])?$params['community_id']:'';
            $recordsParams['user_id'] = !empty($params['user_id'])?$params['user_id']:'';
            $recordsParams['cust_name'] = !empty($result['memberName'])?$result['memberName']:'';
            $recordsParams['cust_mobile'] = !empty($result['memberMobile'])?$result['memberMobile']:'';
            $recordsParams['address'] = !empty($result['fullName'])?$result['fullName']:'';
            $model = new PsDeliveryRecords(['scenario'=>'add']);
            if($model->load($recordsParams,'')&&$model->validate()){
                if(!$model->save()){
                    return $this->failed('新增失败！');
                }
                //调用街道志愿者接口 减积分
                if($model->attributes['integral']>0){
                    $streetParams['sysUserId'] = $model->attributes['user_id'];
                    $streetParams['score'] = $model->attributes['integral'];
                    $streetParams['content'] = $model->attributes['product_name']."兑换";
                    $streetResult = self::doReduce($streetParams);
                    if($streetResult['code']!=0){
                        throw new Exception($streetResult['message']);
                    }
                }
                $trans->commit();
                return $this->success(['id'=>$model->attributes['id']]);
            }else{
                $msg = array_values($model->errors)[0][0];
                return $this->failed($msg);
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }


    //兑换记录新增（小程序端）
    public function add($params){

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
    public function doReduce($data){
        $url = "https://dev-api.elive99.com/volunteer-ckl/?r=/internal/volunteer/use-score";
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
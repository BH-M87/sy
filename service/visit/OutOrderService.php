<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/8/20
 * Time: 9:04
 */
namespace service\visit;

use app\models\PsOutOrder;
use service\BaseService;

class OutOrderService extends BaseService{

    //新建出门单
    public function addOfC($params){
        $recordsParams['community_id'] = !empty($params['community_id'])?$params['community_id']:'';
        $recordsParams['groupId'] = !empty($params['groupId'])?$params['groupId']:'';
        $recordsParams['buildingId'] = !empty($params['buildingId'])?$params['buildingId']:'';
        $recordsParams['unitId'] = !empty($params['unitId'])?$params['unitId']:'';
        $recordsParams['roomId'] = !empty($params['roomId'])?$params['roomId']:'';
        $recordsParams['application_name'] = !empty($params['application_name'])?$params['application_name']:'';
        $recordsParams['application_mobile'] = !empty($params['application_mobile'])?$params['application_mobile']:'';
        $recordsParams['member_type'] = !empty($params['member_type'])?$params['member_type']:'';
        $recordsParams['room_address'] = !empty($params['room_address'])?$params['room_address']:'';
        $recordsParams['application_id'] = !empty($params['application_id'])?$params['application_id']:''; //会员member
        $recordsParams['application_at'] = !empty($params['application_at'])?strtotime($params['application_at']." 23:59:59"):'';
        $recordsParams['content'] = !empty($params['content'])?$params['content']:'';
        $recordsParams['car_number'] = !empty($params['car_number'])?$params['car_number']:'';
        $recordsParams['content_img'] = !empty($params['content_img'])?$params['content_img']:'';
        $recordsParams['ali_form_id'] = !empty($params['ali_form_id'])?$params['ali_form_id']:'';
        $recordsParams['ali_user_id'] = !empty($params['ali_user_id'])?$params['ali_user_id']:'';
        $model = new PsOutOrder(['scenario'=>'add']);
        if($model->load($recordsParams,'')&&$model->validate()) {
            if (!$model->save()) {
                return $this->failed('新增失败！');
            }
            return $this->success(['id'=>$model->attributes['id']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //出门单二维码
    public function orderQrCode($params){
        $model = new PsOutOrder(['scenario'=>'detail']);
        if($model->load($params,'')&&$model->validate()) {
            $detail = $model->getOrderQrCode($params);
            $detail['application_at_msg'] = !empty($detail['application_at'])?date('Y-m-d',$detail['application_at']):'';
            return $this->success($detail);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }
}
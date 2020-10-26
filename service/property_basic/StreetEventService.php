<?php
namespace service\property_basic;

use app\models\PsEvent;
use service\BaseService;
use service\rbac\OperateService;

class StreetEventService extends BaseService{

    //事件-新增
    public function add($params,$userInfo){

        $model = new PsEvent(['scenario'=>'add']);
        $params['event_time'] = !empty($params['event_time'])?strtotime($params['event_time']):'';
        $params['create_name'] = !empty($userInfo['truename'])?$userInfo['truename']:'';
        $params['create_id'] = !empty($userInfo['id'])?$userInfo['id']:'';
        if($model->load($params,'')&&$model->validate()){
            if(!$model->save()){
                return $this->failed('新增失败！');
            }

            //添加日志
            $content = "小区：".$model->xq_name;
            $operate = [
                "community_id" => $params['xq_id'],
                "operate_menu" => "事件上报",
                "operate_type" => "事件新增",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success(['id'=>$model->attributes['id']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }

    }

    //事件-列表
    public function getList($params){

    }
}
<?php
namespace service\property_basic;

use app\models\PsEvent;
use app\models\PsEventComment;
use app\models\PsEventProcess;
use service\BaseService;
use service\rbac\OperateService;
use Yii;
use yii\db\Exception;

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
        $model = new PsEvent();
        $result = $model->getList($params);
        if(!empty($result['list'])){
            foreach($result['list'] as $key=>$value){
                $result['list'][$key]['status_msg'] = !empty($value['status'])?$model->statusMsg[$value['status']]:'';
                $result['list'][$key]['is_close_msg'] = !empty($value['is_close'])?$model->closeMsg[$value['is_close']]:'';
                $result['list'][$key]['source_msg'] = !empty($value['source'])?$model->sourceMsg[$value['source']]:'';
                $result['list'][$key]['event_time_msg'] = !empty($value['event_time'])?date('Y/m/d H:i:s',$value['event_time']):'';
            }
        }
        return $this->success($result);
    }

    //事件评价-新增
    public function commentAdd($params,$userInfo){
        $model = new PsEventComment(['scenario'=>'add']);
        $params['create_name'] = !empty($userInfo['truename'])?$userInfo['truename']:'';
        $params['create_id'] = !empty($userInfo['id'])?$userInfo['id']:'';
        if($model->load($params,'')&&$model->validate()){
            if(!$model->save()){
                return $this->failed('新增失败！');
            }

            //添加日志
            $content = "评价人：".$model->create_name;
            $operate = [
                "community_id" => '',
                "operate_menu" => "事件上报",
                "operate_type" => "评价新增",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success(['id'=>$model->attributes['id']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 事件-结案 input is_close 1 是 否
     * 前置条件：
     *  status=3（状态：已办结） is_close=1 （未结案）
     *
     * 1.结案是
     *  is_close=2 添加事件处置过程表
     * 2.结案否
     *  is_close=0 status=4 添加事件处置过程表
     */
    public function eventClose($params,$userInfo){
        $trans = Yii::$app->db->beginTransaction();
        try{
            $model = new PsEvent(['scenario'=>'close']);
            if(empty($params['content'])){
                return $this->failed('描述必填！');
            }
            $editParams['id'] = !empty($params['id'])?$params['id']:'';
            $editParams['is_close'] = !empty($params['is_close'])?$params['is_close']:'';
            if($model->load($editParams,'')&&$model->validate()){
                $editParams['is_close'] = $model->attributes['is_close'];
                $editParams['status'] = $model->attributes['status'];
                
                if(!$model->edit($editParams)){
                    throw new Exception("结案失败");
                }

                $processModel = new PsEventProcess(['scenario'=>'add']);
                $processParams['event_id'] = $model->attributes['id'];
                $processParams['content'] = $params['content'];
                $processParams['create_id'] = !empty($userInfo['truename'])?$userInfo['truename']:'';
                $processParams['create_name'] = !empty($userInfo['id'])?$userInfo['id']:'';
                $processParams['status'] = $params['is_close'] == 1?4:3;
                if($processModel->load($processParams,'')&&$processModel->validate()){
                    if(!$processModel->saveData()){
                        throw new Exception("结案进度新增失败！");
                    }
                }else{
                    $msg = array_values($processModel->errors)[0][0];
                    throw new Exception($msg);
                }

                //添加日志
                $content = "结案人：".$userInfo['truename'];
                $operate = [
                    "community_id" => $model->attributes['is_close'],
                    "operate_menu" => "事件上报",
                    "operate_type" => "事件结案",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userInfo, $operate);
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

}
<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/10/20
 * Time: 9:55
 */
namespace service\property_basic;

use app\models\PsDecorationPatrol;
use app\models\PsDecorationProblem;
use app\models\PsDecorationRegistration;
use service\BaseService;
use service\rbac\OperateService;

Class DecorationService extends BaseService
{

    public $contentMsg = ['1'=>'工作','2'=>'水电','3'=>'泥工','4'=>'木工','5'=>'油漆工','6'=>'保洁'];

    /*
     * 装修登记-新增
     */
    public function add($params, $userInfo)
    {
        $model = new PsDecorationRegistration(['scenario' => 'add']);
        if ($model->load($params, '') && $model->validate()) {
            if (!$model->save()) {
                return $this->failed('新增失败！');
            }
            //添加日志
            $content = "小区：".$model->community_name.",房屋地址：" . $model->address;
            $operate = [
                "community_id" => $params['community_id'],
                "operate_menu" => "装修登记",
                "operate_type" => "登记新增",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success(['id' => $model->attributes['id']]);
        } else {
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 巡检记录-新增
     */
    public function patrolAdd($params, $userInfo){
        $model = new PsDecorationPatrol(['scenario' => 'add']);
        $params['patrol_name'] = $userInfo['truename'];
        $params['patrol_id'] = $userInfo['id'];
        if ($model->load($params, '') && $model->validate()) {
            if (!$model->save()) {
                return $this->failed('新增失败！');
            }
            //添加日志
            $content = "小区：".$model->community_name.",房屋地址：" . $model->address.",巡检人：".$model->patrol_name;
            $operate = [
                "community_id" => $params['community_id'],
                "operate_menu" => "装修登记",
                "operate_type" => "巡检记录新增",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success(['id' => $model->attributes['id']]);
        } else {
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 装修登记-列表
     */
    public function getList($params){
        $model = new PsDecorationRegistration();
        $result = $model->getList($params);
        if(!empty($result['list'])) {
            foreach ($result['list'] as $key => $value) {
                $patrol = $value['patrol'];
                unset($result['list'][$key]['patrol']);
                $result['list'][$key]['status_msg'] = !empty($value['status']) ? $model->statusMsg[$value['status']] : "";
                $result['list'][$key]['create_at_msg'] = !empty($value['create_at']) ? date('Y-m-d H:i:s', $value['create_at']) : "";
                $result['list'][$key]['patrol_time_msg'] = '';
                $result['list'][$key]['patrol_count'] = 0;
                if(!empty($patrol)){
                    $result['list'][$key]['patrol_time_msg'] = date('Y-m-d H:i:s',$patrol[0]['create_at']);
                    $result['list'][$key]['patrol_count'] = count($patrol);
                }
            }
        }
        return $this->success($result);
    }

    /*
     * 装修登记-详情
     */
    public function getDetail($params){
        $model = new PsDecorationRegistration(['scenario' => 'detail']);
        if ($model->load($params, '') && $model->validate()) {
            $detail = $model->detail($params);
            $detail['create_at_msg'] = !empty($detail['create_at'])?date('Y-m-d H:i:s',$detail['create_at']):"";
            $detail['img_arr'] = !empty($detail['img'])?explode(',',$detail['img']):'';
            $detail['status_msg'] = !empty($detail['status']) ? $model->statusMsg[$detail['status']] : "";
            $detail['patrol_time_msg'] = '';
            $detail['patrol_count'] = 0;
            if(!empty($detail['patrol'])){
                $detail['patrol_time_msg'] = date('Y-m-d H:i:s',$detail['patrol'][0]['create_at']);
                $detail['patrol_count'] = count($detail['patrol']);
                foreach($detail['patrol'] as $key=>$value){
                    $detail['patrol'][$key]['create_at_msg'] = date('Y-m-d H:i:s',$value['create_at']);
                    $detail['patrol'][$key]['content_msg'] = [];
                    if(!empty($value['content'])){
                        $content = explode(',',$value['content']);
                        foreach($content as $v){
                            array_push($detail['patrol'][$key]['content_msg'],$this->contentMsg[$v]);
                        }
                    }
                }
            }
            return $this->success($detail);
        } else {
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 装修登记-完成
     */
    public function complete($params,$userInfo){
        $model = new PsDecorationRegistration(['scenario' => 'complete']);
        if ($model->load($params, '') && $model->validate()) {
            $editParams['id'] = $params['id'];
            $editParams['status'] = 2;
            if (!$model->edit($editParams)) {
                return $this->failed('操作失败！');
            }
            $detail = $model->detail($params);
            //添加日志
            $content = "小区：".$detail['community_name'].",房屋地址：" . $detail['address'];
            $operate = [
                "community_id" => $params['community_id'],
                "operate_menu" => "装修登记",
                "operate_type" => "登记完成",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success(['id' => $model->attributes['id']]);
        } else {
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 装修登记-巡查列表
     */
    public function patrolList($params){
        $model = new PsDecorationPatrol(['scenario' => 'list']);
        if ($model->load($params, '') && $model->validate()) {
            $result = $model->getList($params);
            if(!empty($result['list'])){
                foreach($result['list'] as $key=>$value){
                    $problem = $value['problem'];
                    unset($result['list'][$key]['problem']);
                    $result['list'][$key]['content_msg'] = [];
                    $result['list'][$key]['create_at_msg'] = date('Y-m-d H:i:s',$value['create_at']);
                    if(!empty($value['content'])){
                        $content = explode(',',$value['content']);
                        foreach($content as $v){
                            array_push($result['list'][$key]['content_msg'],$this->contentMsg[$v]);
                        }
                    }
                    //新增问题按钮
                    $result['list'][$key]['is_question'] = 2;  //无
                    if($value['is_licensed']==1||$value['is_safe']==1||$value['is_violation']==1||$value['is_env']==1){
                        $result['list'][$key]['is_question'] = 1; //有
                        if(!empty($problem)){
                            $result['list'][$key]['is_question'] = 2;  //无
                        }
                    }
                }
            }
            return $this->success($result);
        } else {
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //违规问题-新增
    public function problemAdd($params,$userInfo){
        $model = new PsDecorationProblem(['scenario' => 'add']);
        $params['assign_name'] = $userInfo['truename'];
        $params['assign_id'] = $userInfo['id'];
        if ($model->load($params, '') && $model->validate()) {
            if (!$model->save()) {
                return $this->failed('新增失败！');
            }
            //添加日志
            $content = "小区：".$model->community_name.",房屋地址：" . $model->address.",被指派人：".$model->assigned_name;
            $operate = [
                "community_id" => $params['community_id'],
                "operate_menu" => "装修登记",
                "operate_type" => "违规新增",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success(['id' => $model->attributes['id']]);
        } else {
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //巡查记录-详情
    public function patrolDetail($params){
        $model = new PsDecorationPatrol(['scenario' => 'detail']);
        if ($model->load($params, '') && $model->validate()) {
            $detail = $model->detail($params);
            $problem = $detail['problem'];
            unset($detail['problem']);
            $detail['content_msg'] = [];
            if(!empty($detail['content'])){
                $content = explode(',',$detail['content']);
                foreach($content as $v){
                    array_push($detail['content_msg'],$this->contentMsg[$v]);
                }
            }
            //新增问题按钮
            $detail['is_question'] = 2;  //无
            if($detail['is_licensed']==1||$detail['is_safe']==1||$detail['is_violation']==1||$detail['is_env']==1){
                $detail['is_question'] = 1; //有
                if(!empty($problem)){
                    $detail['is_question'] = 2;  //无
                }
            }
            return $this->success($detail);
        } else {
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }
}
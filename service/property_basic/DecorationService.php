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
            $detail['img_arr'] = !empty($detail['img'])?explode(',',$detail['img']):[];
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

    //巡检违规-列表
    public function problemList($params){
        $model = new PsDecorationProblem();
        $result = $model->getList($params);
        if(!empty($result['list'])){
            foreach($result['list'] as $key=>$value){
                $result['list'][$key]['type_msg_desc'] = [];
                if(!empty($value['type_msg'])){
                    $type_msg = explode(',',$value['type_msg']);
                    foreach($type_msg as $v){
                        array_push($result['list'][$key]['type_msg_desc'],$model->typeMsg[$v]);
                    }
                }
                $result['list'][$key]['status_msg'] = $model->statusMsg[$value['status']];
                $result['list'][$key]['create_at_msg'] = !empty($value['create_at'])?date('Y-m-d H:i:s',$value['create_at']):'';
                $result['list'][$key]['deal_at_msg'] = !empty($value['deal_at'])?date('Y-m-d H:i:s',$value['deal_at']):'';
            }
        }
        return $this->success($result);
    }

    //巡检违规-详情
    public function problemDetail($params){
        $model = new PsDecorationProblem(['scenario' => 'detail']);
        if ($model->load($params, '') && $model->validate()) {
            $detail = $model->detail($params);
            $detail['type_msg_desc'] = [];
            if(!empty($detail['type_msg'])){
                $type_msg = explode(',',$detail['type_msg']);
                foreach($type_msg as $v){
                    array_push($detail['type_msg_desc'],$model->typeMsg[$v]);
                }
            }
            $detail['status_msg'] = $model->statusMsg[$detail['status']];
            $detail['create_at_msg'] = !empty($detail['create_at'])?date('Y-m-d H:i:s',$detail['create_at']):'';
            $detail['problem_img_arr'] = !empty($detail['problem_img'])?explode(',',$detail['problem_img']):[];
            $detail['deal_at_msg'] = !empty($detail['deal_at'])?date('Y-m-d H:i:s',$detail['deal_at']):'';
            $detail['deal_img_arr'] = !empty($detail['deal_img'])?explode(',',$detail['deal_img']):[];
            return $this->success($detail);
        } else {
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 装修违规处理
     */
    public function problemDeal($params,$userInfo){
        $model = new PsDecorationProblem(['scenario' => 'deal']);
        $editParams['id'] = !empty($params['id'])?$params['id']:'';
        $editParams['community_id'] = !empty($params['community_id'])?$params['community_id']:'';
        $editParams['deal_content'] = !empty($params['deal_content'])?$params['deal_content']:'';
        $editParams['deal_img'] = !empty($params['deal_img'])?$params['deal_img']:'';
        $editParams['deal_at'] = time();
        if ($model->load($editParams, '') && $model->validate()) {
            if (!$model->edit($editParams)) {
                return $this->failed('处理失败！');
            }
            $detail = $model->detail($params);
            //添加日志
            $content = "小区：".$detail['community_name'].",房屋地址：" . $detail['address'];
            $operate = [
                "community_id" => $params['community_id'],
                "operate_menu" => "装修登记",
                "operate_type" => "违规处理",
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
     * 装修押金列表
     */
    public function depositList($params){
        $model = new PsDecorationRegistration();
        $result = $model->depositList($params);
        if(!empty($result['list'])){
            foreach($result['list'] as $key=>$value){
                $result['list'][$key]['status_msg'] = !empty($value['status'])?$model->statusMsg[$value['status']]:'';
                $result['list'][$key]['is_receive_msg'] = !empty($value['is_receive'])?$model->moneyStatusMsg[$value['is_receive']]:'';
                $result['list'][$key]['is_refund_msg'] = !empty($value['is_refund'])?$model->moneyStatusMsg[$value['is_refund']]:'';
                $result['list'][$key]['receive_at_msg'] = !empty($value['receive_at'])?date('Y-m-d H:i:s',$value['receive_at']):'';
                $result['list'][$key]['refund_at_msg'] = !empty($value['refund_at'])?date('Y-m-d H:i:s',$value['refund_at']):'';
            }
        }
        return $this->success($result);
    }

    /*
     * 装修押金详情
     */
    public function depositDetail($params){
        $model = new PsDecorationRegistration(['scenario' => 'detail']);
        if ($model->load($params, '') && $model->validate()) {
            $detail = $model->depositDetail($params);
            $detail['status_msg'] = !empty($detail['status'])?$model->statusMsg[$detail['status']]:'';
            $detail['is_receive_msg'] = !empty($detail['is_receive'])?$model->moneyStatusMsg[$detail['is_receive']]:'';
            $detail['is_refund_msg'] = !empty($detail['is_refund'])?$model->moneyStatusMsg[$detail['is_refund']]:'';
            $detail['receive_at_msg'] = !empty($detail['receive_at'])?date('Y-m-d H:i:s',$detail['receive_at']):'';
            $detail['refund_at_msg'] = !empty($detail['refund_at'])?date('Y-m-d H:i:s',$detail['refund_at']):'';
            return $this->success($detail);
        } else {
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //装修押金-收款
    public function depositReceive($params,$userInfo){
        $model = new PsDecorationRegistration(['scenario' => 'receive']);
        $editParams['id'] = !empty($params['id'])?$params['id']:'';
        $editParams['community_id'] = !empty($params['community_id'])?$params['community_id']:'';
        $editParams['money'] = !empty($params['money'])?$params['money']:'';
        $editParams['is_receive'] = 2;
        $editParams['receive_at'] = time();
        if ($model->load($editParams, '') && $model->validate()) {
            if (!$model->edit($editParams)) {
                return $this->failed('收款失败！');
            }
            $detail = $model->roomDetail($params);
            //添加日志
            $content = "小区：".$detail['community_name'].",房屋地址：" . $detail['address'].",保证金：".$model->money;
            $operate = [
                "community_id" => $params['community_id'],
                "operate_menu" => "装修登记",
                "operate_type" => "保证金收款",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success(['id' => $model->attributes['id']]);
        } else {
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //装修押金-退款
    public function depositRefund($params,$userInfo){
        $model = new PsDecorationRegistration(['scenario' => 'refund']);
        $editParams['id'] = !empty($params['id'])?$params['id']:'';
        $editParams['community_id'] = !empty($params['community_id'])?$params['community_id']:'';
        $editParams['is_refund'] = 2;
        $editParams['refund_at'] = time();
        if ($model->load($editParams, '') && $model->validate()) {
            if (!$model->edit($editParams)) {
                return $this->failed('退款失败！');
            }
            $detail = $model->roomDetail($params);
            //添加日志
            $content = "小区：".$detail['community_name'].",房屋地址：" . $detail['address'];
            $operate = [
                "community_id" => $params['community_id'],
                "operate_menu" => "装修登记",
                "operate_type" => "保证金退款",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success(['id' => $model->attributes['id']]);
        } else {
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //装修统计
    public function statistics($params){
        $data = [];
        $temp_year = '';        //年
        $temp_month = '';       //月
        if(!empty($params['year'])&&!empty($params['month'])){
            $temp_year = $params['year'];
            $temp_month = $params['month'];
        }
        if(!empty($params['year'])){
            $temp_year = $params['year'];
        }
        $params['temp_year'] = $temp_year;
        $params['temp_month'] = $temp_month;
        $regResult = $this->regStatistics($params);     //装修登记统计
        $data = array_merge($data,$regResult);
        $patrolResult = $this->patrolStatistics($params);            //巡查次数统计
        $data = array_merge($data,$patrolResult);
        $problemResult = $this->problemStatistics($params);    //违规统计
        $data = array_merge($data,$problemResult);
        $moneyResult = $this->moneyStatistics($params);     //装修金统计
        $data = array_merge($data,$moneyResult);
        return $this->success($data);
    }

    //装修登记统计
    public function regStatistics($params){

        $model = PsDecorationRegistration::find()->select(['status','count(id) as count'])->where(1);

        if(!empty($params['communityList'])){
            $model->andWhere(['in','community_id',$params['communityList']]);
        }

        if(!empty($params['community_id'])){
            $model->andWhere(['=','community_id',$params['community_id']]);
        }
        if(!empty($params['temp_year'])){
            $model->andWhere(['=',"FROM_UNIXTIME(create_at, '%Y')",$params['temp_year']]);
        }
        if(!empty($params['temp_month'])){
            $model->andWhere(['=',"FROM_UNIXTIME(create_at, '%m')",$params['temp_month']]);
        }
        $result = $model->groupBy(['status'])->asArray()->all();
        $reg_count = 0;
        $processing_count = 0;
        $completed_count = 0;
        if(!empty($result)){
            foreach($result as $key=>$value){
                $reg_count+=$value['count'];
                switch($value['status']){
                    case 1:
                        $processing_count+=$value['count'];
                        break;
                    case 2:
                        $completed_count+=$value['count'];
                        break;
                }
            }
        }
        return [
            'reg_count'         => $reg_count,
            'processing_count'  => $processing_count,
            'completed_count'   => $completed_count,
        ];
    }

    //巡查次数统计
    public function patrolStatistics($params){
        $model = PsDecorationPatrol::find()->where(1);

        if(!empty($params['communityList'])){
            $model->andWhere(['in','community_id',$params['communityList']]);
        }

        if(!empty($params['community_id'])){
            $model->andWhere(['=','community_id',$params['community_id']]);
        }
        if(!empty($params['temp_year'])){
            $model->andWhere(['=',"FROM_UNIXTIME(create_at, '%Y')",$params['temp_year']]);
        }
        if(!empty($params['temp_month'])){
            $model->andWhere(['=',"FROM_UNIXTIME(create_at, '%m')",$params['temp_month']]);
        }
        $count = $model->count('id');

        return [
            'patrol_count'=>$count
        ];
    }

    //违规统计
    public function problemStatistics($params){
        $regModel = PsDecorationProblem::find()->select(['status','count(id) as count'])->where(1);

        if(!empty($params['communityList'])){
            $regModel->andWhere(['in','community_id',$params['communityList']]);
        }

        if(!empty($params['community_id'])){
            $regModel->andWhere(['=','community_id',$params['community_id']]);
        }
        if(!empty($params['temp_year'])){
            $regModel->andWhere(['=',"FROM_UNIXTIME(create_at, '%Y')",$params['temp_year']]);
        }
        if(!empty($params['temp_month'])){
            $regModel->andWhere(['=',"FROM_UNIXTIME(create_at, '%m')",$params['temp_month']]);
        }
        $result = $regModel->groupBy(['status'])->asArray()->all();
        $problem_count = 0;
        $process_count = 0;
        $processed_count = 0;
        if(!empty($result)){
            foreach($result as $key=>$value){
                $problem_count+=$value['count'];
                switch($value['status']){
                    case 1:
                        $process_count+=$value['count'];
                        break;
                    case 2:
                        $processed_count+=$value['count'];
                        break;
                }
            }
        }
        return [
            'problem_count'     => $problem_count,
            'process_count'     => $process_count,              //待处理
            'processed_count'   => $processed_count,            //已处理
        ];
    }

    //装修金统计
    public function moneyStatistics($params){
        $model = PsDecorationRegistration::find()->where(1);

        if(!empty($params['communityList'])){
            $model->andWhere(['in','community_id',$params['communityList']]);
        }

        if(!empty($params['community_id'])){
            $model->andWhere(['=','community_id',$params['community_id']]);
        }
        if(!empty($params['temp_year'])){
            $model->andWhere(['=',"FROM_UNIXTIME(create_at, '%Y')",$params['temp_year']]);
        }
        if(!empty($params['temp_month'])){
            $model->andWhere(['=',"FROM_UNIXTIME(create_at, '%m')",$params['temp_month']]);
        }
        $money = $model->sum('money');//总金额
        $refunded_money = $model->andWhere(['=','is_refund',2])->sum('money');//已退款
        $pending_money = $money - $refunded_money;//待退款
        return [
            'money'=>$money,
            'refunded_money'=>$refunded_money,    //已退款
            'pending_money'=>$pending_money,     //待退款
        ];
    }
}
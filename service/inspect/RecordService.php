<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/1/19
 * Time: 10:07
 * Desc: 巡检任务service
 */
namespace service\inspect;

use app\models\PsInspectRecord;
use common\core\PsCommon;
use service\property_basic\CommonService;

class RecordService extends BaseService {

    //任务状态
    public $status = [
        '1'=>['key'=>1,'name'=>'待巡检'],
        '2'=>['key'=>2,'name'=>'巡检中'],
        '3'=>['key'=>3,'name'=>'已完成'],
        '4'=>['key'=>4,'name'=>'已关闭'],
    ];

    //任务执行状态
    public $runStatus = [
        '1'=>['key'=>1,'name'=>'逾期'],
        '2'=>['key'=>2,'name'=>'旷巡'],
        '3'=>['key'=>3,'name'=>'正常'],
    ];

    //巡检结果
    public $resultStatus = [
        ['key'=>1,'name'=>'未完成'],
        ['key'=>2,'name'=>'异常'],
        ['key'=>3,'name'=>'正常'],
    ];

    //巡检列表
    public function recordList($params){
        //获得所有小区id
        $commonService = new CommonService();
        $javaParams['token'] = $params['token'];
        $communityInfo = $commonService->getCommunityInfo($javaParams);
        $params['communityIds'] = $communityInfo['communityIds'];
        $model = new PsInspectRecord();
        $result = $model->getList($params);
        $data = [];
        if(!empty($result['data'])){
            foreach($result['data'] as $key=>$value){
                $element['id'] = !empty($value['id'])?$value['id']:'';
                $element['community_id'] = !empty($value['community_id'])?$value['community_id']:'';
                $element['community_name'] = !empty($value['community_id'])?$communityInfo['communityResult'][$value['community_id']]:'';
                $element['task_name'] = !empty($value['task_name'])?$value['task_name']:'';
                $element['head_name'] = !empty($value['head_name'])?$value['head_name']:'';
                $element['task_time_msg'] = '';
                if(!empty($value['check_start_at'])&&!empty($value['check_end_at'])){
                    $element['task_time_msg'] = date('Y/m/d',$value['check_start_at'])." ".date('H:i',$value['check_start_at'])."-".date('H:i',$value['check_end_at']);
                }
                $element['status'] = !empty($value['status'])?$value['status']:'';
                $element['status_msg'] = !empty($value['status'])?$this->status[$value['status']]['name']:'';
                $element['run_status'] = !empty($value['run_status'])?$value['run_status']:'';
                $element['run_status_msg'] = !empty($value['run_status'])?$this->runStatus[$value['run_status']]['name']:'';
                $element['result_status'] = !empty($value['result_status'])?$value['result_status']:'';
                $element['result_status_msg'] = !empty($value['result_status'])?$this->resultStatus[$value['result_status']]['name']:'';
                $data[] = $element;
            }
        }
        return ['list'=>$data,'totals'=>$result['count']];
    }

    //任务状态下拉
    public function statusDrop(){
        return ['list' => array_values($this->status)];
    }

    //任务执行状态下拉
    public function runStatusDrop(){
        return ['list' => array_values($this->runStatus)];
    }

    //任务关闭
    public function closeRecord($params){
        $model = new PsInspectRecord(['scenario'=>'detail']);
        if($model->load($params,'')&&$model->validate()){
            $detail = $model->getDataOne($params);
            if(!in_array($detail['status'],[1,2])){
                return PsCommon::responseFailed("只能关闭待巡检及巡检中的任务");
            }
            $editParams['id'] = $params['id'];
            $editParams['status'] = 4;
            if(!$model->edit($editParams)){
                $resultMsg = array_values($model->errors)[0][0];
                return PsCommon::responseFailed($resultMsg);
            }
            return ['id'=>$params['id']];
        }else{
            $resultMsg = array_values($model->errors)[0][0];
            return PsCommon::responseFailed($resultMsg);
        }
    }
}
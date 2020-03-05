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
use app\models\PsInspectRecordPoint;
use common\core\F;
use common\core\PsCommon;
use service\common\ExcelService;
use service\property_basic\CommonService;
use yii\db\Exception;
use Yii;

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
        '1'=>['key'=>1,'name'=>'未完成'],
        '2'=>['key'=>2,'name'=>'异常'],
        '3'=>['key'=>3,'name'=>'正常'],
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

    //任务删除
    public function deleteRecord($params){

        $trans = Yii::$app->getDb()->beginTransaction();
        try {

            if(empty($params['ids'])){
                return PsCommon::responseFailed("任务ids必填");
            }
            if(!is_array($params['ids'])){
                return PsCommon::responseFailed("任务ids必须是一个数组");
            }
            //判断是否符合条件
            $result = PsInspectRecord::find()->select(['id','status'])->where(['in','id',$params['ids']])->asArray()->all();
            if(empty($result)){
                return PsCommon::responseFailed("任务不存在");
            }
            foreach($result as $key=>$value){
                if(!in_array($value['status'],[1,4])){
                    return PsCommon::responseFailed("只能删除已关闭、待巡检的任务");
                }
            }

            //批量删除任务
            PsInspectRecord::deleteAll(['in','id',$params['ids']]);
            //批量删除任务点
            PsInspectRecordPoint::deleteAll(['in','record_id',$params['ids']]);
            $trans->commit();
        }catch (Exception $e) {
            $trans->rollBack();
            return PsCommon::responseFailed($e->getMessage());
        }

    }

    //巡检任务-详情
    public function recordDetail($params){
        $model = new PsInspectRecord(['scenario'=>'detail']);
        if($model->load($params,'')&&$model->validate()){
            //获得所有小区id
            $commonService = new CommonService();
            $javaParams['token'] = $params['token'];
            $communityInfo = $commonService->getCommunityInfo($javaParams);
            $result = $model->getDetail($params);
            $element['id'] = !empty($result['id'])?$result['id']:'';
            $element['community_id'] = !empty($result['community_id'])?$result['community_id']:'';
            $element['community_name'] = $communityInfo['communityResult'][$result['community_id']];
            $element['task_name'] = !empty($result['task_name'])?$result['task_name']:'';
            $element['task_date_msg'] = !empty($result['task_at'])?date('Y/m/d',$result['task_at']):'';
            $element['task_time_msg'] = '';
            if(!empty($result['check_start_at'])&&!empty($result['check_end_at'])){
                $element['task_time_msg'] = date('H:i',$result['check_start_at'])."-".date('H:i',$result['check_end_at']);
            }
            $element['head_name'] = !empty($result['head_name'])?$result['head_name']:'';
            $element['line_name'] = !empty($result['line_name'])?$result['line_name']:'';

            $element['status'] = !empty($result['status'])?$result['status']:'';
            $element['status_msg'] = !empty($result['status'])?$this->status[$result['status']]['name']:'';
            $element['run_status'] = !empty($result['run_status'])?$result['run_status']:'';
            $element['run_status_msg'] = !empty($result['run_status'])?$this->runStatus[$result['run_status']]['name']:'';
            $element['result_status'] = !empty($result['result_status'])?$result['result_status']:'';
            $element['result_status_msg'] = !empty($result['result_status'])?$this->resultStatus[$result['result_status']]['name']:'';
            $element['point_count'] = !empty($result['point_count'])?$result['point_count']:0;
            $element['finish_count'] = !empty($result['finish_count'])?$result['finish_count']:0;
            $element['finish_time_msg'] = '';
            if($result['status']==3){
                $element['finish_time_msg'] = date('Y/m/d',$result['update_at']);
            }
            $element['point'] = [];
            if(!empty($result['point'])){
                $device_status = ['1'=>'正常','2'=>'异常'];
                foreach($result['point'] as $key=>$value){
                    $ele['point_name'] = !empty($value['point_name'])?$value['point_name']:'';
                    $ele['status'] = !empty($value['status'])?$value['status']:'';
                    $ele['point_location'] = !empty($value['point_location'])?$value['point_location']:'';
                    $ele['finish_at_msg'] = !empty($value['finish_at'])?date('Y/m/d H:i',$value['finish_at']):'';
                    $ele['device_status_msg'] = !empty($value['device_status'])?$device_status[$value['device_status']]:'';
                    $ele['record_note'] = !empty($value['record_note'])?$value['record_note']:'';
                    $ele['picture'] = !empty($value['picture'])?$value['picture']:'';           //打卡图片
                    $ele['imgs'] = !empty($value['imgs'])?explode(',',$value['imgs']):[];       //备注图片
                    $element['point'][] = $ele;
                }
            }
            return $element;
        }else{
            $resultMsg = array_values($model->errors)[0][0];
            return PsCommon::responseFailed($resultMsg);
        }
    }

    /*
     * 任务导出
     */
    public function recordExport($params){

        //获得所有小区id
        $commonService = new CommonService();
        $javaParams['token'] = $params['token'];
        $communityInfo = $commonService->getCommunityInfo($javaParams);
        $params['communityIds'] = $communityInfo['communityIds'];
        $model = new PsInspectRecord();
        //查询任务数量
        $totals = $model->getCount($params);
        $cycle = ceil($totals / 1000);
        $config["sheet_config"] = [
            'A' => ['title' => '小区名称', 'width' => 20, 'data_type' => 'str', 'field' => 'community_name'],
            'B' => ['title' => '任务名称', 'width' => 30, 'data_type' => 'str', 'field' => 'task_name'],
            'C' => ['title' => '任务时间', 'width' => 25, 'data_type' => 'str', 'field' => 'task_time_msg'],
            'D' => ['title' => '执行人', 'width' => 10, 'data_type' => 'str', 'field' => 'head_name'],
            'E' => ['title' => '任务状态', 'width' => 10, 'data_type' => 'str', 'field' => 'status_msg'],
            'F' => ['title' => '执行状态', 'width' => 10, 'data_type' => 'str', 'field' => 'run_status_msg'],
            'G' => ['title' => '巡检结果', 'width' => 10, 'data_type' => 'str', 'field' => 'result_status_msg'],
        ];
        $config["save"] = true;
        $savePath = Yii::$app->basePath . '/web/store/zip/record/';
        $config["save_path"] = $savePath;
        //房屋数量查过一千则导出压缩文件
        if ($cycle == 1) {//下载单个文件
            $config["file_name"] = "Task1.xlsx";
            $params['page'] = 1;
            $params['pageSize'] = 1000;
            $params['sort'] = SORT_DESC;
            $result = self::recordList($params);
            $file_name = ExcelService::service()->recordDown($result["list"], $config);
            $downUrl = F::downloadUrl('record/' .  $file_name, 'zip');
            return PsCommon::responseSuccess(['down_url' => $downUrl]);
        } else {//下载zip压缩包
            self::delDir($savePath);
            for ($i = 1; $i <= $cycle; $i++) {
                $config["file_name"] = "Task".$i.".xlsx";
                $params['page'] = $i;
                $params['pageSize'] = 1000;
                $params['sort'] = SORT_DESC;
                $result = self::recordList($params);
                ExcelService::service()->recordDown($result["list"], $config);
            }
            $path = $savePath . 'recordTask.zip';
            ExcelService::service()->addZip($savePath, $path);
            $downUrl = F::downloadUrl('record/'.'recordTask.zip', 'zip');
            return PsCommon::responseSuccess(['down_url' => $downUrl]);
        }
    }

    //清空文件夹函数和清空文件夹后删除空文件夹函数的处理
    public function delDir($path){
        //如果是目录则继续
        if(is_dir($path)){
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            foreach($p as $val){
                //排除目录中的.和..
                if($val !="." && $val !=".."){
                    //如果是目录则递归子目录，继续操作
                    if(is_dir($path.$val)){
                        //子目录中操作删除文件夹和文件
                        deldir($path.$val.'/');
                        //目录清空后删除空文件夹
                        @rmdir($path.$val.'/');
                    }else{
                        //如果是文件直接删除
                        unlink($path.$val);
                    }
                }
            }
        }
    }

    /*
     * 任务执行状态变化脚本
     */
    public function recordScript(){
        set_time_limit(20);
        $nowTime = time();
        $fields = ['id','point_count','finish_count'];
        $modelAll = PsInspectRecord::find()
                        ->select($fields)
                        ->where(['=','run_status',3])       //执行状态 正常
                        ->andWhere(['!=','status',4])       //状态 不等于 已关闭
                        ->andWhere(['<','check_end_at',$nowTime])   //执行时间已结束
                        ->asArray()->all();
        if(!empty($modelAll)){
            foreach($modelAll as $key=>$value){
                if($value['finish_count']==0){
                    //旷巡
                    $updateParams['run_status'] = 2;
                }else if($value['finish_count']>0&&$value['point_count']!=$value['finish_count']){
                    //逾期
                    $updateParams['run_status'] = 1;
                }else{
                    $updateParams['run_status'] = 3;
                }
                if(!empty($updateParams)){
                    PsInspectRecord::updateAll($updateParams,['id' => $value['id']]);
                }
            }
        }
    }
}
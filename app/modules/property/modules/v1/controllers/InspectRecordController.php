<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/1/19
 * Time: 10:02
 * Desc: 巡检任务
 */
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\inspect\RecordService;
use yii\base\Exception;

class InspectRecordController extends BaseController {

    //巡检任务-列表
    public function actionRecordList(){
        try{
            $params = $this->request_params;
            $service = new RecordService();
            $result = $service->recordList($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //巡检任务-任务状态下拉
    public function actionStatusDrop(){
        try{
            $params = $this->request_params;
            $service = new RecordService();
            $result = $service->statusDrop($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //巡检任务-任务执行状态下拉
    public function actionRunStatusDrop(){
        try{
            $params = $this->request_params;
            $service = new RecordService();
            $result = $service->runStatusDrop($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }
}
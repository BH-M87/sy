<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/1/19
 * Time: 10:02
 * Desc: 巡检任务
 */
namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\controllers\UserBaseController;
use common\core\PsCommon;
use service\inspect\RecordService;
use yii\base\Exception;

class InspectRecordController extends UserBaseController {

    public $repeatAction = ['record-export'];

    //巡检任务-列表
    public function actionRecordList(){
        try{
            if(!$this->downgrade['inspect_record_list']){
                return PsCommon::responseFailed($this->downgrade['msg']);
            }
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new RecordService();
            $result = $service->recordListOfDing($params);
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

    //巡检任务-任务关闭
    public function actionCloseRecord(){
        try{
            $params = $this->request_params;
            $service = new RecordService();
            $result = $service->closeRecord($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //巡检任务-任务删除
    public function actionDeleteRecord(){
        try{
            $params = $this->request_params;
            $service = new RecordService();
            $result = $service->deleteRecord($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //巡检任务-详情
    public function actionRecordDetail(){
        try{
            $params = $this->request_params;
            $service = new RecordService();
            $result = $service->recordDetail($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }
}
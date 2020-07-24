<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/23
 * Time: 11:08
 * Desc: 投票活动
 */
namespace app\modules\operation\modules\v1\controllers;

use app\modules\operation\controllers\BaseController;
use service\vote\ActivityService;
use yii\base\Exception;
use common\core\PsCommon;

class ActivityController extends BaseController {

    //新建活动
    public function actionAdd(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->add($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //新建活动
    public function actionEdit(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->edit($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //活动列表
    public function actionList(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new ActivityService();
            $result = $service->getList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //活动详情
    public function actionDetail(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->getDetail($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //活动下拉
    public function actionDropOfActivity(){
        try{
            $service = new ActivityService();
            $result = $service->dropOfActivity();
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //活动中组下拉
    public function actionDropOfGroup(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->dropOfGroup($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //添加选手
    public function actionAddPlayer(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->addPlayer($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //编辑选手
    public function actionEditPlayer(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->editPlayer($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //选手详情
    public function actionPlayerDetail(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->playerDetail($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //删除选手
    public function actionDelPlayer(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->delPlay($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }
}
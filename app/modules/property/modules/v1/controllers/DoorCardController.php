<?php
/**
 * 门卡管理相关接口
 * User: fengwenchao
 * Date: 2019/8/21
 * Time: 14:56
 */

namespace app\modules\property\modules\v1\controllers;


use app\models\DoorCardForm;
use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\door\DoorCardService;

class DoorCardController extends BaseController
{
    public function actionCommon()
    {
        $result = DoorCardService::service()->getCommon();
        return PsCommon::responseSuccess($result);
    }

    public function actionList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result['list'] = DoorCardService::service()->card_list($this->page,$this->pageSize,$this->communityId,$this->request_params);
        $result['totals'] = DoorCardService::service()->card_count($this->communityId,$this->request_params);
        return PsCommon::responseSuccess($result);
    }

    public function actionCardAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        //校验格式
        $valid = PsCommon::validParamArr(new DoorCardForm(),$this->request_params,'add');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = DoorCardService::service()->card_add($this->communityId,$data, $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionCardEdit()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        //校验格式
        $valid = PsCommon::validParamArr(new DoorCardForm(),$this->request_params,'edit');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = DoorCardService::service()->card_edit($this->communityId,$data, $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionView()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        //校验格式
        $valid = PsCommon::validParamArr(new DoorCardForm(),$this->request_params,'detail');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = DoorCardService::service()->card_detail($data);
        if ($result["code"]) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionDelete()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        //校验格式
        $valid = PsCommon::validParamArr(new DoorCardForm(),$this->request_params,'delete');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = DoorCardService::service()->card_delete($data);
        if ($result["code"]) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionStatus()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        //校验格式
        $valid = PsCommon::validParamArr(new DoorCardForm(),$this->request_params,'status');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = DoorCardService::service()->card_status($data);
        if ($result["code"]) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //普通卡/管理卡--启用/禁用(批量)
    public function actionStatusMore()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        //校验格式
        $valid = PsCommon::validParamArr(new DoorCardForm(),$this->request_params,'status');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = DoorCardService::service()->card_status_more($data);
        if ($result["code"]) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //获取授权门禁列表
    public function actionGetDeviceList()
    {
        $result = DoorCardService::service()->device_list($this->communityId,$this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //获取门禁业主信息
    public function actionGetUserList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        //校验格式
        $valid = PsCommon::validParamArr(new DoorCardForm(),$this->communityId,'user-list');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = DoorCardService::service()->get_user_list($this->communityId,$data);
        return PsCommon::responseSuccess($result);
    }
}
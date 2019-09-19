<?php
/**
 * 开门记录相关接口
 * User: fengwenchao
 * Date: 2019/8/21
 * Time: 14:53
 */

namespace app\modules\property\modules\v1\controllers;


use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\door\DoorRecordService;

class OpenRecordController extends BaseController
{
    //公共接口
    public function actionCommon()
    {
        $result = DoorRecordService::service()->getCommon();
        return PsCommon::responseSuccess($result);
    }

    //开门记录
    public function actionList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result = DoorRecordService::service()->getList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //导出
    public function actionExport()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $downUrl = DoorRecordService::service()->export($this->request_params, $this->user_info);
        return PsCommon::responseSuccess(["down_url" => $downUrl]);
    }


}
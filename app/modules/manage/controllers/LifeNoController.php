<?php
/**
 * 消息中心
 * User: fengwenchao
 * Date: 2017/6/12
 * Time: 11:11
 */

namespace app\modules\manage\controllers;

use common\core\PsCommon;
use service\message\BroadcastService;

class LifeNoController extends BaseController
{
    //生活号类型接口
    public function actionTypes()
    {
        $data[] = ['key'=>3,'value'=>'物业'];
        return PsCommon::responseSuccess($data);
    }
    //素材列表
    public function actionMaterialLists()
    {
        $result['common'] = [];
        $result['list'] = [];
        $result['totals'] = [];
        return PsCommon::responseSuccess($result);
    }

    //新增消息
    public function actionAddMessage()
    {
        $result = BroadcastService::service()->create($this->request_params);
        if (!$result['code'] && isset($result['msg'])) {
            return PsCommon::responseFailed($result['msg']);
        } else {
            return PsCommon::responseSuccess();
        }
    }

    //消息列表
    public function actionMessages()
    {
        $result['list'] = BroadcastService::service()->getSends($this->page, $this->pageSize);
        $result['totals'] = BroadcastService::service()->getSendsCount();
        return PsCommon::responseSuccess($result);
    }

    //消息详情
    public function actionMessagesInfo()
    {
        $result = BroadcastService::service()->getMsgInfo($this->request_params);
        if (!$result['code'] && isset($result['msg'])) {
            return PsCommon::responseFailed($result['msg']);
        } else {
            return PsCommon::responseSuccess($result['data']);
        }
    }
}

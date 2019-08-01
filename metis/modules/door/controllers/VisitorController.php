<?php
namespace alisa\modules\door\controllers;

use common\services\door\VisitorService;

class VisitorController extends BaseController
{
    // 重新发送短信 {"user_id":"35","id":"753"}
    public function actionVisitorMsg()
    {
        $result = VisitorService::service()->visitorMsg($this->params);
        return $this->dealResult($result);
    }

    // 取消邀请 {"user_id":"35","id":"753"}
    public function actionVisitorCancel()
    {
        $result = VisitorService::service()->visitorCancel($this->params);
        return $this->dealResult($result);
    }

    // 访客列表 {"user_id":"35","type":"1"}
    public function actionVisitorList()
    {
        $result = VisitorService::service()->visitorList($this->params);
        return $this->dealResult($result);
    }

    // 访客删除 {"user_id":"35","id":"753"}
    public function actionVisitorDelete()
    {
        $result = VisitorService::service()->visitorDelete($this->params);
        return $this->dealResult($result);
    }

    // 访客新增 {"vistor_name":"吴建阳", "vistor_mobile":"18768143435", "user_id":"35", "room_id":"42103", "start_time":"2018-12-04 14:15:52", "end_time":"2018-12-05 14:15:52", "content":"1234"}
    public function actionVisitorAdd()
    {
        $result = VisitorService::service()->visitorAdd($this->params);
        return $this->dealResult($result);
    }
}
<?php
/**
 * User: ZQ
 * Date: 2019/9/6
 * Time: 13:44
 * For: 钉钉端通知通报
 */

namespace app\modules\ding_property_app\modules\v2\controllers;


use common\core\F;
use service\street\NoticeService;

class NoticeController extends BaseController
{

    /**
     * 列表
     * @return null
     */
    public function actionList()
    {
        $result = NoticeService::service()->getMyList($this->request_params,$this->page, $this->pageSize);
        return F::apiSuccess($result);
    }

    /**
     * 详情
     * @return null
     */
    public function actionDetail()
    {
        $result = NoticeService::service()->getMydetail($this->request_params);
        return F::apiSuccess($result);
    }
}
<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use service\park\SmallMyService;


class MyParkController extends UserBaseController
{
    //我的顶部统计数据
    public function actionStatis()
    {
        $result = SmallMyService::service()->getStatis($this->params);
        return self::dealReturnResult($result);
    }

}
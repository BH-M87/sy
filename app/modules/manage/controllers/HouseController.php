<?php

namespace app\modules\manage\controllers;

use Yii;
use app\modules\property\services\CommunityService;
use common\core\PsCommon;
use service\common\AreaService;
use service\RoomService;
use app\models\PsHouseForm;

class HouseController extends BaseController
{
    /**
     * @author wenchao.feng
     * 获取省市区所有数据
     */
    public function actionArea()
    {
        return AreaService::service()->getCacheArea();
    }
}

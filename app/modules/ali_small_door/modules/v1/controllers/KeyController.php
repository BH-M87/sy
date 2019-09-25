<?php
/**
 * User: ZQ
 * Date: 2019/9/24
 * Time: 15:40
 * For: 电子钥匙
 */

namespace app\modules\ali_small_door\modules\v1\controllers;


use app\models\DoorDevicesForm;
use app\modules\ali_small_door\controllers\UserBaseController;
use common\core\F;
use common\core\PsCommon;
use service\door\KeyService;

class KeyController extends UserBaseController
{
    //获取全部钥匙
    public function actionGetKeyList()
    {
        $roomId = PsCommon::get($this->params, 'room_id');
        if (!$roomId) {
            return PsCommon::responseAppFailed("房屋id不能为空");
        }
        $type = PsCommon::get($this->params, 'type');
        $result = KeyService::service()->get_key_list($roomId,$type);
        return self::dealReturnResult($result);
    }
}
<?php
/**
 * User: ZQ
 * Date: 2019/9/24
 * Time: 15:40
 * For: 电子钥匙
 */

namespace app\modules\ali_small_door\modules\v1\controllers;


use app\models\DoorDevicesForm;
use app\models\PsAppMember;
use app\modules\ali_small_door\controllers\UserBaseController;
use common\core\F;
use common\core\PsCommon;
use service\alipay\AlipayBillService;
use service\door\KeyService;
use service\door\OpenKeyService;

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

    //获取开门二维码
    public function actionGetCode()
    {
        if (empty($this->params)) {
            return F::apiFailed("未接受到有效数据");
        }
        $result = OpenKeyService::service()->get_open_code($this->params);
        if ($result["code"]) {
            $member_id = PsAppMember::find()->select(['member_id'])->where(['app_user_id'=>$this->params['user_id']])->asArray()->scalar();
            $room_id = $this->params['room_id'];
            $door = \Yii::$app->db->createCommand("SELECT id FROM `door_room_password` where member_id = '$member_id' and room_id = '$room_id'")->queryOne();
            $code_img = AlipayBillService::service()->create_erweima($result['data']['code_img'], $door['id']); // 调用七牛方法生成二维码
            \Yii::$app->db->createCommand('UPDATE `door_room_password` SET code_img = :code_img where id = :id', [':id' => $door['id'], ':code_img' => $code_img])->execute();
            $result['data']['code_img'] = $code_img; // 二维码图片
            return F::apiSuccess($result['data']);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }
}
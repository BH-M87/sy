<?php
/**
 * User: ZQ
 * Date: 2019/9/23
 * Time: 17:27
 * For: 门禁
 */

namespace app\modules\hard_ware_butt\modules\v1\controllers;


use app\models\DoorRecordForm;
use app\models\PsMember;
use app\modules\hard_ware_butt\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\basic_data\DoorExternalService;
use service\basic_data\PhotosService;

class DoorController extends BaseController
{
    //保存呼叫记录
    public function actionCallRecord()
    {
        if (empty($this->params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        if ($this->requestType != 'POST') {
            return PsCommon::responseFailed("请求方式错误");
        }

        //校验格式
        $valid = F::validParamArr(new DoorRecordForm(),$this->params,'save');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }

        $data = $valid["data"];
        $data['supplier_id'] = $this->supplierId;
        $data['community_id'] = $this->communityId;
        DoorExternalService::service()->dealDoorRecord($data);
        //推送告警信息 by yjh 2019.6.27
        $roomInfo = PhotosService::service()->getRoomInfo($data['roomNo']);
        $member = PsMember::find()->where(['mobile' => $data['userPhone']])->one();
        DoorExternalService::service()->sendWarning([
            'photo' => $this->params['capturePhoto'],
            'open_time' => $this->params['openTime'],
            'member_id' => $member['id'],
            'device_name' =>$this->params['deviceName'],
            'room_id' => $roomInfo['id'],
            'community_id' => $this->communityId,
        ]);
        return PsCommon::responseSuccess();
    }
}
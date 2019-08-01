<?php
/**
 * 蓝牙相关接口
 * User: fengwenchao
 * Date: 2018/8/28
 * Time: 17:25
 */
namespace alisa\modules\door\controllers;
use common\libs\F;
use common\services\door\BlueToothService;

class BlueToothController extends BaseController
{
    //查询设备状态
    public function actionGetDeviceStatus()
    {
        $deviceId = F::value($this->params, 'device_id');
        if (!$deviceId) {
            return F::apiFailed("设备id不能为空！");
        }
        $params['device_id'] = $deviceId;
        $result = BlueToothService::service()->getDeviceStatus($params);
        return $this->dealResult($result);
    }

    //获取命令
    public function actionGetCommand()
    {
        $deviceId = F::value($this->params, 'device_id');
        $type = F::value($this->params, 'type');
        $userId = F::value($this->params, 'user_id');
        if (!$deviceId) {
            return F::apiFailed("设备id不能为空！");
        }
        if (!$type) {
            return F::apiFailed("指令类型不能为空！");
        }
        if (!$userId) {
            return F::apiFailed("业主不能为空！");
        }

        $params['device_id'] = $deviceId;
        $params['type'] = $type;
        $params['app_user_id'] = $userId;
        $result = BlueToothService::service()->getCommand($params);
        return $this->dealResult($result);
    }

    //解析指令
    public function actionParseCommand()
    {
        $deviceId = F::value($this->params, 'device_id');
        $userId = F::value($this->params, 'user_id');
        $roomId = F::value($this->params, 'room_id');
        $encryptData = F::value($this->params, 'encrypt_data');
        if (!$deviceId) {
            return F::apiFailed("设备id不能为空！");
        }
        if (!$encryptData) {
            return F::apiFailed("解析指令内容不能为空！");
        }

        $params['device_id'] = $deviceId;
        $params['user_id'] = $userId;
        $params['room_id'] = $roomId;
        $params['encrypt_data'] = $encryptData;
        $result = BlueToothService::service()->parseCommand($params);
        return $this->dealResult($result);
    }

    //开门记录上报
    public function actionAddOpenRecord()
    {
        $deviceId = F::value($this->params, 'device_id');
        $encryptData = F::value($this->params, 'encrypt_data');
        if (!$deviceId) {
            return F::apiFailed("设备id不能为空！");
        }
        if (!$encryptData) {
            return F::apiFailed("指令内容不能为空！");
        }

        $params['device_id'] = $deviceId;
        $params['encrypt_data'] = $encryptData;
        $result = BlueToothService::service()->addOpenRecord($params);
        return $this->dealResult($result);
    }
}
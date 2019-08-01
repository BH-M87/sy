<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2018/8/28
 * Time: 15:01
 */
namespace alisa\modules\door\modules\v1\services;

class BlueToothService extends BaseService {
    //获取命令
    public function getCommand($params)
    {
        return $this->apiPost('blue-tooth/get-command',$params, false, false);
    }

    //解析命令
    public function parseCommand($params)
    {
        return $this->apiPost('blue-tooth/parse-command',$params, false, false);
    }

    //查询设备状态
    public function getDeviceStatus($params)
    {
        return $this->apiPost('blue-tooth/get-device-status',$params, false, false);
    }

    //开门记录上报
    public function addOpenRecord($params)
    {
        return $this->apiPost('blue-tooth/add-open-record',$params, false, false);
    }

    //狄耐克开门记录上报
    public function addRecord($params)
    {
        return $this->apiPost('blue-tooth/add-record',$params, false, false);
    }
}
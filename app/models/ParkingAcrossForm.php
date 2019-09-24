<?php
/**
 * 车辆入场数据验证 ，处理给第三方供应商的接口参数与数据库字段不一致
 * User: wenchao.feng
 * Date: 2018/5/16
 * Time: 下午3:51
 */

namespace app\models;

use yii\base\Model;

class ParkingAcrossForm extends Model
{
    public $carNum;
    public $arriveDeviceNum;
    public $arriveDeviceName;
    public $arriveTime;
    public $lotCode;
    public $leaveDeviceNum;
    public $leaveDeviceName;
    public $leaveTime;
    public $factMoney;

    public function rules()
    {
        return [
            [['carNum', 'arriveDeviceNum', 'arriveDeviceName', 'arriveTime', 'lotCode'],
                'required','message'=>'{attribute}不能为空!','on'=>['enter', 'exit']],
            ['arriveTime','date','format'=>'yyyy-MM-dd HH:mm:ss',
                'message' => '{attribute}格式有误', 'on'=>['enter', 'exit']],
            [['leaveDeviceNum', 'leaveDeviceName', 'leaveTime'],
                'required', 'message'=>'{attribute}不能为空!', 'on' => 'exit'],

            ['leaveTime','date','format'=>'yyyy-MM-dd HH:mm:ss',
                'message' => '{attribute}格式有误', 'on'=>['exit']],
            ['leaveTime', 'compareTime', 'on' => 'exit']
        ];
    }

    public function attributeLabels()
    {
        return [
            'carNum' => '车牌号',
            'arriveDeviceNum' => '入口设备号',
            'arriveDeviceName' => '入口设备名称',
            'arriveTime' => '入场时间',
            'leaveDeviceNum' => '出口设备号',
            'leaveDeviceName' => '出口设备名称',
            'leaveTime' => '出场时间',
            'factMoney' => '缴费金额',
            'lotCode' => '车场编号',
        ];
    }

    /**
     * 出场时间大于入场时间校验
     * @param $label
     */
    public function compareTime($label) {
        $inDate = $this->arriveTime;
        $outDate = $this->leaveTime;
        if (strtotime($inDate) >= strtotime($outDate)) {
            $this->addError($label, "出场时间必须要大于入场时间");
        }
    }
}
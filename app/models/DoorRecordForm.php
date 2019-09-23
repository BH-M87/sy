<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/6/28
 * Time: 13:53
 */

namespace app\models;


use yii\base\Model;

class DoorRecordForm extends Model
{

    public $buildingNo;
    public $roomNo;
    public $deviceNo;
    public $deviceName;
    public $openType;
    public $openTime;
    public $userName;
    public $userPhone;
    public $capturePhoto;
    public $cardNo;

    public function rules()
    {
        return [
            [[ 'deviceNo', 'deviceName', 'openType', 'openTime'], 'required', 'message' => '{attribute}不能为空!', 'on' => ['save']],
            [[ 'userName', 'userPhone','capturePhoto','cardNo'], 'safe'],
            ['openType','checkOpenType']
        ];
    }

    public function attributeLabels()
    {
        return [
            'communityNo' => '小区编号',
            'buildingNo' => '楼宇编号',
            'roomNo' => '房间编号',
            'deviceNo' => '设备编号',
            'deviceName' => '设备名称',
            'openType' => '开门方式',
            'openTime' => '开门时间',
            'userName' => '业主姓名',
            'userPhone' => '业主手机号',
            'capturePhoto' => '留影图片',
            'cardNo' => '门卡卡号'
        ];
    }

    public function checkOpenType($label)
    {
        $openType = $this->openType;
        $capture_photo = $this->capturePhoto;
        $card_no = $this->cardNo;
        if($openType == 5 && empty($card_no)){
            $this->addError($label, "门卡开门，卡号不能为空！");
        }
    }
}
<?php

namespace app\models;

use common\core\Regular;
use service\alipay\WaterMeterService;
use Yii;
use yii\base\Model;

class PsWaterMeterFrom extends Model
{
    public $community_id;
    public $meter_no;
    public $meter_status;
    public $meter_type;
    public $group;
    public $unit;
    public $building;
    public $room;
    public $start_ton;
    public $cycle_time;
    public $payment_time;
    public $start_time;
    public $latest_record_time;
    public $meter_id;
    public $water_meter_id;
    public $remark;

    public function rules()
    {
        return [
            [['community_id'], 'required','message' => '{attribute}不能为空!', 'on' => ['list','add','import']],
            [['meter_no','meter_status','group','unit','building','room','start_ton','latest_record_time'], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['add','edit','import-post']],

            ['meter_no', 'match', 'pattern' => Regular::letterOrNumber(1, 15),
                'message'=>'{attribute}不正确', 'on' => ['add','edit','import-post']],
            ['meter_status', 'in', 'range' => [1, 2, 3],'on' => ['add','edit']],



            ['meter_status', 'in', 'range' =>array_values(WaterMeterService::$meter_status) ,'on' => ['import-post']],
            ['start_ton', 'double', 'message'=>'{attribute}不正确','on' => ['add','edit','import-post']],
            ['latest_record_time', 'compare', 'compareValue' => 0, 'message'=>'{attribute}不正确','operator' => '>','on' => ['import-post']],
            [['start_time'], 'date','format' => 'yyyy-mm-dd','on' => ['add','edit']],

            [['water_meter_id'], 'required','message' => '{attribute}不能为空!', 'on' => ['water-show']],
            ['water_meter_id', 'integer', 'message'=>'{attribute}不正确','on' => ['water-show']],
            [['meter_id'], 'required','message' => '{attribute}不能为空!', 'on' => ['show']],
            ['meter_id', 'integer', 'message'=>'{attribute}不正确','on' => ['show']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'community_id'     => '小区',
            'meter_no'           => '表身号',
            'meter_status'            => '状态',
            'group'          => '苑/期/区',
            'building'       => '幢',
            'room'           => '房号',
            'unit'           => '单元号',
            'start_ton'      => '起始读数',
            "start_time"   => "上次抄表时间",
            "latest_record_time"   => "上次抄表时间",
            "water_meter_id"  =>"水表",
            "remark"  =>"备注",
        ];
    }

}

<?php

namespace app\models;

use Yii;

class PsInspectRecordPoint extends BaseModel
{
    public static function tableName()
    {
        return 'ps_inspect_record_point';
    }

    public function rules()
    {
        return [
            [['community_id', 'record_id', 'point_id', 'device_status', 'finish_at', 'create_at'], 'integer'],
            [['lon', 'lat'], 'number'],
            [['location'], 'string', 'max' => 50],
            [['imgs'], 'string', 'max' => 500],
            [['record_note', 'picture'], 'string', 'max' => 255],
            // 钉钉端的编辑
            [['id', 'device_status', 'record_note', 'status'], 'required','message' => '{attribute}不能为空!', 'on' => ['edit']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区',
            'record_id' => '记录',
            'point_id' => '巡检点',
            'device_status' => '设备状态',
            'location' => '巡检地理位置',
            'lon' => '巡检经度',
            'lat' => '巡检纬度',
            'imgs' => '巡检图片',
            'picture' => '拍照图片',
            'record_note' => '巡检记录',
            'finish_at' => '巡检提交时间',
            'status' => '巡检状态',
            'create_at' => '创建时间',
        ];
    }
}

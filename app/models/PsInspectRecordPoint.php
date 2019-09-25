<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_inspect_record_point".
 *
 * @property int $id
 * @property int $community_id 小区Id
 * @property int $record_id 记录id
 * @property int $point_id 巡检点id
 * @property int $device_status 设备状态 1正常  2异常
 * @property string $location_name 巡检地理位置
 * @property string $lon 巡检经度
 * @property string $lat 巡检纬度
 * @property string $imgs 巡检图片多图逗号分隔
 * @property string $record_note 巡检记录
 * @property int $status 巡检状态：1未巡检，2已巡检，3漏巡检
 * @property string $point_name 巡检点名称
 * @property int $need_location 巡检点：是否需要定位：1需要，2不需要
 * @property int $need_photo 巡检点：是否需要拍照：1需要，2不需要
 * @property string $point_location_name 巡检点：地理位置
 * @property string $point_lon 巡检点：经度
 * @property string $point_lat 巡检点：纬度
 * @property int $finish_at 巡检提交时间
 * @property int $create_at 创建时间
 */
class PsInspectRecordPoint extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_inspect_record_point';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'record_id', 'point_id', 'device_status', 'finish_at', 'create_at'], 'integer'],
            [['lon', 'lat'], 'number'],
            [['location_name'], 'string', 'max' => 50],
            [['imgs'], 'string', 'max' => 500],
            [['record_note'], 'string', 'max' => 200],
            //钉钉端的编辑
            [['id','device_status','record_note','status'], 'required','message' => '{attribute}不能为空!', 'on' => ['edit']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区',
            'record_id' => '记录',
            'point_id' => '巡检点',
            'device_status' => '设备状态',
            'location_name' => '巡检地理位置',
            'lon' => '巡检经度',
            'lat' => '巡检纬度',
            'imgs' => '巡检图片',
            'record_note' => '巡检记录',
            'finish_at' => '巡检提交时间',
            'status' => '巡检状态',
            'create_at' => '创建时间',
        ];
    }
}

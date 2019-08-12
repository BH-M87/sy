<?php

namespace app\models;


/**
 * This is the model class for table "ps_inspect_point".
 *
 * @property int $id id
 * @property string $name 巡检点名称
 * @property int $category_id 设备分类ID
 * @property int $device_id 设备ID
 * @property string $device_name 设备名称
 * @property string $device_no 设备编号
 * @property int $community_id 小区Id
 * @property int $need_location 是否需要定位：1需要，2不需要
 * @property string $location_name 地理位置
 * @property string $lon 经度
 * @property string $lat 纬度
 * @property int $need_photo 是否需要拍照：1需要，2不需要
 * @property string $code_image 二维码图片
 * @property int $created_at 创建时间
 * @property int $operator_id 创建人id
 */
class PsInspectPoint extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_inspect_point';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'device_id', 'device_name', 'device_no', 'community_id'], 'required'],
            [['category_id', 'device_id', 'community_id', 'need_location', 'need_photo', 'created_at', 'operator_id'], 'integer'],
            [['lon', 'lat'], 'number'],
            [['name', 'location_name'], 'string', 'max' => 50],
            [['device_name', 'device_no'], 'string', 'max' => 15],
            [['code_image'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'category_id' => 'Category ID',
            'device_id' => 'Device ID',
            'device_name' => 'Device Name',
            'device_no' => 'Device No',
            'community_id' => 'Community ID',
            'need_location' => 'Need Location',
            'location_name' => 'Location Name',
            'lon' => 'Lon',
            'lat' => 'Lat',
            'need_photo' => 'Need Photo',
            'code_image' => 'Code Image',
            'created_at' => 'Created At',
            'operator_id' => 'Operator ID',
        ];
    }
}

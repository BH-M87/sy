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
            [['category_id', 'device_id', 'community_id', 'need_location', 'need_photo', 'created_at', 'operator_id'], 'integer'],
            [['lon', 'lat'], 'number'],
            [['community_id', 'name', 'category_id', 'device_id', 'need_location', 'need_photo', 'id', 'device_name'], 'required', 'message' => '{attribute}不能为空!'],
            [['name', 'location_name'], 'string', 'max' => 50,'tooLong' => '{attribute}长度不能超过50个字'],
            [['device_name', 'device_no'], 'string', 'max' => 15,'tooLong' => '{attribute}长度不能超过15个字'],
            [['code_image'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '巡检点ID',
            'name' => '巡检点名称',
            'category_id' => '设备分类',
            'device_id' => '设备',
            'device_name' => '设备名称',
            'device_no' => '设备编号',
            'community_id' => '小区',
            'need_location' => '是否需要定位',
            'location_name' => '地理位置',
            'lon' => '经度',
            'lat' => '纬度',
            'need_photo' => '是否需要拍照',
            'code_image' => '二维码图片',
            'created_at' => '创建时间',
            'operator_id' => '创建人',
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        //各个场景的活动属性
        $scenarios['add'] = ['community_id', 'name', 'category_id', 'device_id', 'need_location', 'need_photo', 'name'];//新增
        $scenarios['update'] = ['id', 'community_id', 'name', 'category_id', 'device_id', 'need_location', 'need_photo'];//编辑
        return $scenarios;
    }
}

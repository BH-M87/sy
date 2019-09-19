<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_community_building".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property int $group_id 苑/期/区id
 * @property string $group_name 苑/期/区名称
 * @property string $name 幢名称
 * @property string $code 楼幢编码
 * @property string $building_code 楼幢唯一code
 * @property int $unit_num 单元数量
 * @property int $floor_num 楼层数
 * @property string $orientation 楼宇朝向
 * @property string $locations 经纬度地址
 * @property string $longitude 经度
 * @property string $latitude 纬度
 */
class PsCommunityBuilding extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_community_building';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'group_id', 'unit_num', 'floor_num'], 'integer'],
            [['longitude', 'latitude'], 'number'],
            [['group_name', 'name'], 'string', 'max' => 50],
            [['code'], 'string', 'max' => 3],
            [['building_code'], 'string', 'max' => 25],
            [['orientation'], 'string', 'max' => 20],
            [['locations'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'group_id' => 'Group ID',
            'group_name' => 'Group Name',
            'name' => 'Name',
            'code' => 'Code',
            'building_code' => 'Building Code',
            'unit_num' => 'Unit Num',
            'floor_num' => 'Floor Num',
            'orientation' => 'Orientation',
            'locations' => 'Locations',
            'longitude' => 'Longitude',
            'latitude' => 'Latitude',
        ];
    }
}

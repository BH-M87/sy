<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_patrol_points".
 *
 * @property int $id id
 * @property string $name 巡更点名称
 * @property int $community_id 小区Id
 * @property int $need_location 是否需要定位：1需要，2不需要
 * @property string $location_name 地理位置
 * @property string $lon 经度
 * @property string $lat 纬度
 * @property int $need_photo 是否需要拍照：1需要，2不需要
 * @property string $code_image 二维码图片
 * @property string $note 巡更说明
 * @property int $created_at 创建时间
 * @property int $is_del 是否已被删除 1正常  0已被删除
 * @property int $operator_id 创建人id
 * @property string $operator_name 创建人名称
 */
class PsPatrolPoints extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_patrol_points';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'need_location', 'need_photo', 'created_at', 'is_del', 'operator_id'], 'integer'],
            [['lon', 'lat'], 'number'],
            [['name', 'location_name'], 'string', 'max' => 50],
            [['code_image'], 'string', 'max' => 255],
            [['note'], 'string', 'max' => 200],
            [['operator_name'], 'string', 'max' => 20],
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
            'community_id' => 'Community ID',
            'need_location' => 'Need Location',
            'location_name' => 'Location Name',
            'lon' => 'Lon',
            'lat' => 'Lat',
            'need_photo' => 'Need Photo',
            'code_image' => 'Code Image',
            'note' => 'Note',
            'created_at' => 'Created At',
            'is_del' => 'Is Del',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
        ];
    }
}

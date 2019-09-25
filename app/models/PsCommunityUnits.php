<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_community_units".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property int $group_id 苑/期/区id
 * @property int $building_id 幢id
 * @property string $group_name 苑/期/区名称
 * @property string $building_name 幢名称
 * @property string $name 单元名称
 * @property string $unit_no 单元编号
 * @property string $unit_code 单元唯一code
 * @property string $code 单元编码
 */
class PsCommunityUnits extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_community_units';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'group_id', 'building_id'], 'integer'],
            [['group_name', 'building_name', 'name'], 'string', 'max' => 50],
            [['unit_no'], 'string', 'max' => 20],
            [['unit_code'], 'string', 'max' => 30],
            [['code'], 'string', 'max' => 2],
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
            'building_id' => 'Building ID',
            'group_name' => 'Group Name',
            'building_name' => 'Building Name',
            'name' => 'Name',
            'unit_no' => 'Unit No',
            'unit_code' => 'Unit Code',
            'code' => 'Code',
        ];
    }
}

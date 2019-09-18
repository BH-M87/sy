<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_channel_year_report".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $community_name
 * @property integer $parent_id
 * @property integer $parent_time
 * @property integer $type
 * @property string $type_name
 * @property string $amount
 * @property integer $total
 * @property integer $create_at
 * @property integer $update_at
 */
class PsChannelYearReport extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_channel_year_report';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'type'], 'required'],
            [['community_id', 'parent_id', 'parent_time', 'type', 'total', 'create_at', 'update_at'], 'integer'],
            [['amount'], 'number'],
            [['community_name', 'type_name'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'community_name' => 'Community Name',
            'parent_id' => 'Parent ID',
            'parent_time' => 'Parent Time',
            'type' => 'Type',
            'type_name' => 'Type Name',
            'amount' => 'Amount',
            'total' => 'Total',
            'create_at' => 'Create At',
            'update_at' => 'Update At',
        ];
    }
}

<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_bill_crontab".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $year
 * @property string $month
 * @property integer $day
 * @property integer $status
 * @property integer $update_at
 * @property integer $create_at
 */
class PsBillCrontab extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_bill_crontab';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'create_at'], 'required'],
            [['community_id', 'day', 'status', 'update_at', 'create_at'], 'integer'],
            [['year'], 'string', 'max' => 10],
            [['month'], 'string', 'max' => 100],
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
            'year' => 'Year',
            'month' => 'Month',
            'day' => 'Day',
            'status' => 'Status',
            'update_at' => 'Update At',
            'create_at' => 'Create At',
        ];
    }
}

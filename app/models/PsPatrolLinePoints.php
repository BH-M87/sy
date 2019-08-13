<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_patrol_line_points".
 *
 * @property int $id
 * @property int $line_id 线路Id
 * @property int $point_id 巡更点id
 * @property int $point_sort
 */
class PsPatrolLinePoints extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_patrol_line_points';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['line_id', 'point_id', 'point_sort'], 'required'],
            [['line_id', 'point_id', 'point_sort'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'line_id' => 'Line ID',
            'point_id' => 'Point ID',
            'point_sort' => 'Point Sort',
        ];
    }
}

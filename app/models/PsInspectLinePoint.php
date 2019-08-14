<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_inspect_line_point".
 *
 * @property int $id
 * @property int $line_id 线路Id
 * @property int $point_id 巡检点id
 */
class PsInspectLinePoint extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_inspect_line_point';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['line_id', 'point_id'], 'required'],
            [['line_id', 'point_id'], 'integer'],
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
        ];
    }
}

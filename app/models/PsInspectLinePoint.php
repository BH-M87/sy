<?php

namespace app\models;

use Yii;

class PsInspectLinePoint extends BaseModel
{
    public static function tableName()
    {
        return 'ps_inspect_line_point';
    }

    public function rules()
    {
        return [
            [['lineId', 'pointId'], 'required'],
            [['lineId', 'pointId'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'lineId' => 'Line ID',
            'pointId' => 'Point ID',
        ];
    }
}

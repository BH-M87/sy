<?php
namespace app\models;

use Yii;
use common\MyException;

class EventTemplate extends BaseModel
{
    public static function tableName()
    {
        return 'event_template';
    }

    public static function type($p)
    {
        $m = self::find()->select('id, title as name')->where(['status' => 1])
            ->andFilterWhere(['=', 'type', $p['type']])
            ->andFilterWhere(['=', 'parent_id', $p['parent_id']])
            ->asArray()->all();

        return $m;
    }
}
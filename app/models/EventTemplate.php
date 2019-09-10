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
    
    // 一级 二级分类
    public static function type($p)
    {
        $m = self::find()->select('id, title as name, parent_id')->where(['status' => 1])
            ->andFilterWhere(['=', 'type', $p['type']])
            ->andFilterWhere(['=', 'parent_id', $p['parent_id']])
            ->asArray()->all();

        return $m;
    }
    
    // 类型描述
    public static function typeDesc($p)
    {
        return self::findOne($p['event_parent_type_id'])->title.'-'.self::findOne($p['event_child_type_id'])->title;
    }
}
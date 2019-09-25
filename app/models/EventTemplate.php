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
    
    // 一级
    public static function type($p)
    {
        $config = self::find()->alias('A')->leftJoin('event_template_config B', 'B.template_id = A.id')
            ->select('distinct(A.parent_id)')->where(['>', 'B.id', 0])
            ->asArray()->all();
        $id = !empty($config) ? array_column($config, 'parent_id') : [];

        $m = self::find()->select('id, title as name, parent_id')->where(['status' => 1])
            ->andFilterWhere(['=', 'type', $p['type']])
            ->andFilterWhere(['=', 'parent_id', $p['parent_id']])
            ->andWhere(['in', 'id', $id])
            ->asArray()->all();

        return $m;
    }

    // 二级分类
    public static function typeChild($p)
    {
        $m = self::find()->alias('A')->leftJoin('event_template_config B', 'B.template_id = A.id')
            ->select('A.id, A.title as name, A.parent_id')->where(['A.status' => 1])
            ->andFilterWhere(['>', 'B.id', 0])
            ->andFilterWhere(['=', 'A.type', 2])
            ->andFilterWhere(['=', 'A.parent_id', $p['parent_id']])
            ->asArray()->all();

        return $m;
    }
    
    // 类型描述
    public static function typeDesc($p)
    {
        return self::findOne($p['event_parent_type_id'])->title.'-'.self::findOne($p['event_child_type_id'])->title;
    }
}
<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_inspect_plan_contab".
 *
 * @property integer $id
 * @property integer $plan_id
 * @property integer $month_start
 * @property string $week_start
 * @property integer $day_start
 * @property integer $hours_start
 * @property integer $minute_start
 * @property integer $month_end
 * @property string $week_end
 * @property integer $day_end
 * @property integer $hours_end
 * @property integer $minute_end
 * @property integer $create_at
 * @property integer $update_at
 */
class PsInspectPlanContab extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_inspect_plan_contab';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['plan_id',  'create_at'], 'required'],
            [['plan_id',  'create_at'], 'integer'],
            [['week_start', 'week_end'], 'string', 'max' => 1],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'plan_id' => '计划',
            'month_start' => '开始月',
            'week_start' => '开始周',
            'day_start' => '开始天',
            'hours_start' => '开始小时',
            'month_end' => '结束月',
            'week_end' => '结束周',
            'day_end' => '结束天',
            'hours_end' => '结束小时',
            'create_at' => '创建时间',
            'update_at' => '更新时间',
        ];
    }
}

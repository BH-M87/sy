<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_shared_periods".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $period_start
 * @property integer $period_end
 * @property string $period_format
 * @property integer $status
 * @property integer $create_at
 */
class PsSharedPeriods extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_shared_periods';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'period_start', 'period_end', 'period_format', 'status', 'create_at'], 'required','message'=>'{attribute}不能为空!','on'=>'add'],
            [['id','community_id', 'period_start', 'period_end', 'period_format', 'create_at'], 'required','message'=>'{attribute}不能为空!','on'=>'edit'],
            [['id','period_start', 'period_end', 'status', 'create_at'], 'integer'],
            [['community_id'],'string','max'=>30],
            [['status'], 'in', 'range' => [1, 2, 3], 'message' => '{attribute}取值范围错误', 'on' => ['add']],
            [['period_format'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区id',
            'period_start' => '账期开始时间',
            'period_end' => '账期结束时间',
            'period_format' => 'Period Format',
            'status' => '账期状态',
            'create_at' => '录入时间',
        ];
    }
}

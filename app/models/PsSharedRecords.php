<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_shared_records".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $period_id
 * @property integer $shared_id
 * @property integer $latest_num
 * @property integer $current_num
 * @property string $amount
 * @property integer $create_at
 */
class PsSharedRecords extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_shared_records';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'period_id', 'shared_type', 'shared_id', 'latest_num', 'current_num', 'amount', 'create_at'], 'required','on'=>'add'],
            [['id','community_id', 'period_id', 'shared_type', 'shared_id', 'latest_num', 'current_num', 'amount', 'create_at'], 'required','on'=>'edit'],
            [['shared_type', 'shared_id', 'latest_num', 'current_num'], 'required','on'=>'get-money'],
            [['period_id', 'shared_type', 'shared_id', 'create_at'], 'integer'],
            [['community_id'],'string','max'=>30],
            [['amount','latest_num', 'current_num'], 'number'],
            ['amount', 'compare', 'compareValue' => 0.005, 'message'=>'金额数值不能低于0.01','operator' => '>','on'=>['add','edit']],

            [['community_id', 'period_id'], 'required','on'=>'create-bill'],


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
            'period_id' => '账期',
            'shared_type' => '公摊项目类型',
            'shared_id' => '公摊项目',
            'latest_num' => '上次读数',
            'current_num' => '本次读数',
            'amount' => '金额',
            'create_at' => 'Create At',
        ];
    }
}

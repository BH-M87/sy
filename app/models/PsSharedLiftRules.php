<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_shared_lift_rules".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $rule_type
 * @property integer $create_at
 */
class PsSharedLiftRules extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_shared_lift_rules';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id'], 'required','on'=>'search'],
            [['community_id', 'rule_type', 'create_at'], 'required','on'=>'add'],
            [['rule_type'], 'in', 'range' => [1, 2, 3], 'message' => '{attribute}取值范围错误', 'on' => ['edit', 'add']],
            [['community_id', 'rule_type', 'create_at'], 'integer'],
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
            'rule_type' => '分摊规则',
            'create_at' => 'Create At',
        ];
    }
}
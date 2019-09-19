<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;

;


/**
 * This is the model class for table "ps_inspect_plan".
 *
 * @property integer $id
 * @property string $name
 * @property integer $community_id
 * @property integer $line_id
 * @property integer $exec_type
 * @property string $user_list
 * @property integer $status
 * @property integer $operator_id
 * @property integer $create_at
 */
class PsInspectPlan extends BaseModel
{
    public $time_list = [];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_inspect_plan';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'community_id', 'line_id', 'exec_type', 'user_list', 'status', 'operator_id'], 'required'],
            [['community_id', 'line_id', 'exec_type', 'status', 'operator_id', 'create_at'], 'integer'],
            [['exec_type'], 'in', 'range' => [1, 2, 3, 4], 'message' => '{attribute}取值范围错误'],
            [['name'], 'string', 'max' => 15],
            [['user_list'], 'string', 'max' => 500],
            ['time_list', 'validateType'],
            ['status', 'default', 'value' => 1],
            ['create_at', 'default', 'value' => time()],
            [['id', 'community_id', 'name', 'line_id', 'user_list', 'time_list', 'operator_id'], 'required', 'message' => '{attribute}不能为空!'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '计划名称',
            'community_id' => '小区',
            'line_id' => '线路',
            'exec_type' => '执行类型',
            'user_list' => '执行人员',
            'status' => '状态',
            'operator_id' => '创建人',
            'create_at' => '创建时间',
            'time_list' => '执行时间',
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        //各个场景的活动属性
        $scenarios['add'] = ['community_id', 'name', 'line_id', 'user_list', 'time_list', 'operator_id', 'exec_type','create_at'];//新增
        $scenarios['update'] = ['id', 'community_id', 'name', 'line_id', 'user_list', 'time_list', 'operator_id', 'exec_type'];//编辑
        return $scenarios;
    }

    public function validateType($attribute)
    {
        if (!is_array($this->$attribute)) {
            $this->addError($this->$attribute, $attribute . '类型有误');
            return false;
        }
        return true;
    }
}

<?php

namespace app\models;

use common\core\Regular;
use Yii;

/**
 * This is the model class for table "ps_inspect_line".
 *
 * @property int $id
 * @property string $name 线路名称
 * @property int $community_id 小区Id
 * @property string $head_name 负责人
 * @property string $head_mobile 联系电话
 * @property int $created_at 创建时间
 * @property int $operator_id 创建人id
 */
class PsInspectLine extends BaseModel
{
    public $pointList = [];//选择的巡检点

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_inspect_line';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'head_mobile', 'community_id', 'name', 'head_name', 'head_mobile', 'operator_id', 'pointList', 'created_at'], 'required', 'message' => '{attribute}不能为空!'],
            [['community_id', 'created_at', 'operator_id'], 'integer'],
            [['name', 'head_name', 'head_mobile'], 'string', 'max' => 15, 'tooLong' => '{attribute}长度不能超过15个字'],
            ['head_mobile', 'match', 'pattern' => Regular::phone(), 'message' => '{attribute}以1开头的11位数字!']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '线路名称',
            'community_id' => '小区',
            'head_name' => '负责人',
            'head_mobile' => '联系电话',
            'created_at' => '创建时间',
            'operator_id' => '创建人',
            'pointList' => '巡检点',
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        //各个场景的活动属性
        $scenarios['add'] = ['head_mobile', 'community_id', 'name', 'head_name', 'operator_id', 'pointList','created_at'];//新增
        $scenarios['update'] = ['id', 'community_id', 'name', 'head_name', 'head_mobile', 'pointList', 'operator_id'];//编辑
        return $scenarios;
    }
}

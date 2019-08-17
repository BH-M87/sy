<?php

namespace app\models;

use common\core\Regular;
use Yii;

/**
 * This is the model class for table "ps_patrol_line".
 *
 * @property int $id
 * @property string $name 线路名称
 * @property int $community_id 小区Id
 * @property string $head_name 负责人
 * @property string $head_moblie 联系电话
 * @property string $note 巡更说明
 * @property int $created_at 创建时间
 * @property int $is_del 是否已被删除  1正常  0 已删除
 * @property int $operator_id 创建人id
 * @property string $operator_name 创建人名称
 */
class PsPatrolLine extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_patrol_line';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id'], 'required','message' => '{attribute}不能为空!', 'on' => ['list','add','edit']],
            [['name','head_name','head_moblie'], 'required','message' => '{attribute}不能为空!', 'on' => ['add','edit']],
            [['id'], 'required','message' => '{attribute}不能为空!', 'on' => ['edit']],
            [['community_id', 'created_at', 'is_del', 'operator_id'], 'integer'],
            [['name', 'head_name'], 'filter', 'filter' => 'trim', 'skipOnArray' => true],
            [['name', 'head_name'], 'string', 'max' => 10, 'tooLong' => '{attribute}不能超过10个字!','on' => ['add','edit']],
            [['note'], 'string', 'max' => 200, 'tooLong' => '{attribute}不能超过200个字!','on' => ['add','edit']],
            ['head_moblie', 'match', 'pattern' => Regular::phone(),'message' => '{attribute}格式出错', 'on' => ['add','edit']],
            [['name'], 'string', 'max' => 50],
            [['head_name', 'head_moblie', 'operator_name'], 'string', 'max' => 20],
            [['note'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'community_id' => 'Community ID',
            'head_name' => 'Head Name',
            'head_moblie' => 'Head Moblie',
            'note' => 'Note',
            'created_at' => 'Created At',
            'is_del' => 'Is Del',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
        ];
    }

}

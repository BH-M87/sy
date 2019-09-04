<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_notice".
 *
 * @property int $id
 * @property int $organization_type 所属组织类型(1街道本级 2社区 ....)
 * @property int $organization_id 所属组织Id
 * @property int $type 消息类型 1通知 2消息
 * @property string $title 公告标题
 * @property string $describe 简介
 * @property string $content 发送内容
 * @property int $operator_id 创建人id
 * @property string $operator_name 创建人姓名
 * @property string $accessory_file 附件，多个以逗号相连
 * @property int $create_at 创建时间
 */
class StNotice extends BaseModel
{
    public $receive_user_list = [];
    public $accessory_file = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_notice';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['organization_type', 'organization_id', 'type', 'operator_id', 'create_at'], 'integer'],
            [['content'], 'string'],
            [['title'], 'string', 'max' => 100],
            [['describe'], 'string', 'max' => 200],
            [['operator_name'], 'string', 'max' => 20],
            [['title','type','describe','receive_user_list','content'], 'required','message' => '{attribute}不能为空!', 'on' => ['add']],
            [['receive_user_list'], 'required','message' => '{attribute}不能为空!', 'on' => ['add','edit']],
            [['id'], 'required','message' => '{attribute}不能为空!', 'on' => ['detail','edit','delete']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'organization_type' => 'Organization Type',
            'organization_id' => 'Organization ID',
            'type' => '消息类型',
            'title' => '标题',
            'describe' => 'Describe',
            'content' => '发送内容',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'accessory_file' => 'Accessory File',
            'create_at' => 'Create At',
            'receive_user_list'=>'接收对象'
        ];
    }
}

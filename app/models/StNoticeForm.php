<?php
/**
 * User: ZQ
 * Date: 2019/9/5
 * Time: 10:54
 * For: 通知通报
 */

namespace app\models;


class StNoticeForm extends BaseModel
{
    public $receive_user_list = [];
    public $accessory_file = [];
    public $title;
    public $type;
    public $describe;
    public $content;
    public $id;

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
            [['title','type','describe','receive_user_list','content'], 'required','message' => '{attribute}不能为空!', 'on' => ['add']],
            [['receive_user_list'], 'required','message' => '{attribute}不能为空!', 'on' => ['add','edit']],
            [['id'], 'required','message' => '{attribute}不能为空!', 'on' => ['detail','edit','delete','remind']],
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
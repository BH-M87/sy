<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_shop_message".
 *
 * @property integer $id
 * @property integer $shop_id
 * @property string $openid
 * @property string $content
 * @property integer $status
 * @property integer $type
 * @property integer $obj_id
 * @property string $msgid
 * @property string $error
 * @property integer $create_at
 * @property integer $send_at
 */
class PsShopMessage extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_shop_message';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value'=>3],
            ['send_at', 'default', 'value'=>0],
            [['shop_id', 'openid', 'obj_id', 'create_at', 'send_at'], 'required'],
            [['shop_id', 'status', 'type', 'obj_id', 'create_at', 'send_at'], 'integer'],
            [['content', 'error'], 'string'],
            [['openid', 'msgid'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => 'Shop ID',
            'openid' => 'Openid',
            'content' => 'Content',
            'status' => 'Status',
            'type' => 'Type',
            'obj_id' => 'Obj ID',
            'msgid' => 'Msgid',
            'error' => 'Error',
            'create_at' => 'Create At',
            'send_at' => 'Send At',
        ];
    }
}

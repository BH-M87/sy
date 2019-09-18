<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_life_broadcast_record".
 *
 * @property integer $id
 * @property integer $broadcast_id
 * @property integer $app_id
 * @property integer $status
 * @property integer $create_at
 * @property integer $send_at
 */
class PsLifeBroadcastRecord extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_life_broadcast_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['broadcast_id',], 'required'],
            [['broadcast_id', 'life_service_id', 'status', 'create_at', 'send_at', 'is_lock'], 'integer'],
            [['error'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'broadcast_id' => 'Broadcast ID',
            'app_id' => 'App ID',
            'status' => 'Status',
            'create_at' => 'Create At',
            'send_at' => 'Send At',
        ];
    }

    public function getBroadcast()
    {
        return $this->hasOne(PsLifeBroadcast::className(), ['id'=>'broadcast_id']);
    }
}

<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_room_user_label".
 *
 * @property int $id
 * @property int $label_id label关联
 * @property int $room_user_id room_user表id
 * @property int $created_at
 */
class PsRoomUserLabel extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_room_user_label';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['label_id', 'room_user_id', 'created_at'], 'required'],
            [['label_id', 'room_user_id', 'created_at'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'label_id' => 'Label ID',
            'room_user_id' => 'Room User ID',
            'created_at' => 'Created At',
        ];
    }
}

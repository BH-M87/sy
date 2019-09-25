<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_room_label".
 *
 * @property int $id
 * @property int $label_id label关联
 * @property int $room_id 小区表id
 * @property int $created_at
 */
class PsRoomLabel extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_room_label';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['label_id', 'room_id', 'created_at'], 'required'],
            [['label_id', 'room_id', 'created_at'], 'integer'],
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
            'room_id' => 'Room ID',
            'created_at' => 'Created At',
        ];
    }
}

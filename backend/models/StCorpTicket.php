<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "st_corp_ticket".
 *
 * @property int $id
 * @property string $corp_id 企业id
 * @property string $ticket ticket
 * @property int $expires_in 有效期
 * @property int $created_at 创建时间
 */
class StCorpTicket extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_corp_ticket';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['expires_in', 'created_at'], 'integer'],
            [['created_at'], 'required'],
            [['corp_id'], 'string', 'max' => 40],
            [['ticket'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'corp_id' => 'Corp ID',
            'ticket' => 'Ticket',
            'expires_in' => 'Expires In',
            'created_at' => 'Created At',
        ];
    }
}

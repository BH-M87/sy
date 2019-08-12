<?php

namespace app\models;

use Yii;
use yii\db\Query;

/**
 * This is the model class for table "ps_user_community".
 *
 * @property integer $manage_id
 * @property integer $community_id
 */
class PsUserCommunity extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_user_community';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['manage_id', 'community_id'], 'required'],
            [['id', 'manage_id', 'community_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Id',
            'manage_id' => 'Manage ID',
            'community_id' => 'Community ID',
        ];
    }
}

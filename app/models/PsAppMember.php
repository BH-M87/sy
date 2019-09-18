<?php
namespace app\models;
use Yii;

/**
 * This is the model class for table "ps_app_member".
 *
 * @property int $id
 * @property int $app_user_id
 * @property int $member_id
 */

class PsAppMember extends BaseModel
{
    public static function tableName()
    {
        return 'ps_app_member';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['app_user_id', 'member_id'], 'required'],
            [['app_user_id', 'member_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'app_user_id' => 'App User ID',
            'member_id' => 'Member ID',
        ];
    }
}

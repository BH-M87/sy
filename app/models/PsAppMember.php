<?php
<<<<<<< HEAD

namespace app\models;

=======
namespace app\models;
>>>>>>> 2f599d095fbcafc2c1aae7f8471a52eb3624805c
use Yii;

/**
 * This is the model class for table "ps_app_member".
 *
 * @property integer $id
 * @property integer $app_user_id
 * @property integer $member_id
 */
class PsAppMember extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_app_member';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['app_user_id', 'member_id'], 'required'],
            [['app_user_id', 'member_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
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

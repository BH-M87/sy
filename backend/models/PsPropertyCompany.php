<?php
namespace backend\models;
use Yii;

/**
 * This is the model class for table "ps_property_company".
 *
 * @property integer $id
 * @property string $user_id
 * @property string $property_name
 * @property integer $parent_id
 * @property integer $seller_id
 * @property string $link_man
 * @property string $link_phone
 * @property string $email
 * @property string $alipay_account
 * @property integer $status
 * @property integer $create_at
 */
class PsPropertyCompany extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_property_company';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'status', 'create_at'], 'integer'],
            ['user_id', 'safe'],
            [['link_man'], 'string', 'max' => 45],
            [['property_name', 'email', 'alipay_account'], 'string', 'max' => 100],
            [['link_phone'], 'string', 'max' => 15],
            [['nonce'], 'string', 'length'=>32],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'property_name' => 'Property Name',
            'parent_id' => 'Parent ID',
            'link_man' => 'Link Man',
            'link_phone' => 'Link Phone',
            'email' => 'Email',
            'alipay_account' => 'Alipay Account',
            'status' => 'Status',
            'create_at' => 'Create At',
            'nonce' => 'Nonce'
        ];
    }
}

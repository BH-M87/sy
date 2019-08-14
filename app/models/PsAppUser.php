<?php
namespace app\models;

/**
 * This is the model class for table "ps_app_user".
 *
 * @property integer $id
 * @property string $nick_name
 * @property string $true_name
 * @property integer $user_type
 * @property string $access_token
 * @property integer $expires_in
 * @property string $refresh_token
 * @property string $channel_user_id
 * @property string $user_id
 * @property string $avatar
 * @property integer $gender
 * @property string $sign
 * @property integer $set_no
 * @property integer $create_at
 */
class PsAppUser extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_app_user';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_ref', 'expires_in', 'gender', 'set_no', 'create_at'], 'integer'],
            [['nick_name'], 'string', 'max' => 20],
            [['phone'], 'string', 'max' => 11],
            [['access_token', 'refresh_token'], 'string', 'max' => 255],
            [['channel_user_id', 'ali_user_id'], 'string', 'max' => 100],
            [['avatar'], 'string', 'max' => 200],
            [['last_city_code'], 'string', 'max' => 64],
            [['last_city_name'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nick_name' => 'Nick Name',
            'phone' => 'Phone',
            'user_ref' => 'User Ref',
            'access_token' => 'Access Token',
            'expires_in' => 'Expires In',
            'refresh_token' => 'Refresh Token',
            'channel_user_id' => 'Channel User ID',
            'ali_user_id' => 'Ali User ID',
            'avatar' => 'Avatar',
            'gender' => 'Gender',
            'set_no' => 'Set No',
            'last_city_code' => 'Last City Code',
            'last_city_name' => 'Last City Name',
            'create_at' => 'Create At',
        ];
    }

    /**
     * Notes: 根据id获取信息
     * Author: J.G.N
     * Date: 2019/7/12 14:43
     * @param $query
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getMobileById($query)
    {
        $dbo = self::find()->select('phone')->where(['id' => $query['id']])->asArray()->one();
        return $dbo;
    }
}

<?php

namespace app\models;

use common\core\Regular;
use Yii;

/**
 * This is the model class for table "ps_member".
 *
 * @property integer $id
 * @property string $name
 * @property integer $sex
 * @property string $mobile
 * @property integer $is_real
 * @property string $card_no
 * @property string $member_card
 * @property string $wallet
 * @property string $face_url
 * @property $app_user_id
 * @property integer $create_at
 */
class PsMember extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_member';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mobile'], 'required'],
            [['mobile'], 'match','pattern'=> Regular::phone(),'message'=>'{attribute}格式错误！'],
            [['create_at', 'is_real'], 'integer'],
            [['wallet'], 'number'],
            [['name', 'card_no', 'member_card'], 'string', 'max' => 20],
            [['face_url'], 'string', 'max' => 255],
            [['sex'], 'in', 'range' => [0, 1, 2]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '姓名',
            'sex' => '性别',
            'mobile' => '手机号',
            'is_real' => 'Is Real',
            'card_no' => '身份证号码',
            'wallet' => '余额',
            'create_at' => '创建时间',
            'member_card' => '会员卡号',
            'face_url' => '人脸头像'
        ];
    }
}

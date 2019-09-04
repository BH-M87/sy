<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_communist".
 *
 * @property int $id 主键
 * @property string $name 姓名
 * @property string $mobile 手机号
 * @property string $image 头像
 * @property int $sex 性别 1男 2女
 * @property int $birth_time 出生日期
 * @property int $join_party_time 入党日期
 * @property int $formal_time 转正日期
 * @property string $branch 所在支部
 * @property string $job 党内职务
 * @property int $type 党员类型：1离退休党员、2流动党员、3困难党员、4下岗失业党员、5在职党员
 * @property int $station_id 先锋岗位id
 * @property string $pioneer_value 获得的先锋值
 * @property int $operator_id 创建人id
 * @property string $operator_name 创建人
 * @property int $create_at 创建时间
 * @property int $is_authentication 是否支付宝认证 1是 2否
 * @property int $user_id 支付宝ps_app_user用户id
 */
class StCommunist extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_communist';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sex', 'birth_time', 'join_party_time', 'formal_time', 'type', 'station_id', 'pioneer_value', 'operator_id', 'create_at', 'is_authentication', 'user_id'], 'integer'],
            [['create_at'], 'required'],
            [['name', 'branch', 'job'], 'string', 'max' => 50],
            [['mobile'], 'string', 'max' => 13],
            [['image'], 'string', 'max' => 200],
            [['operator_name'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'mobile' => 'Mobile',
            'image' => 'Image',
            'sex' => 'Sex',
            'birth_time' => 'Birth Time',
            'join_party_time' => 'Join Party Time',
            'formal_time' => 'Formal Time',
            'branch' => 'Branch',
            'job' => 'Job',
            'type' => 'Type',
            'station_id' => 'Station ID',
            'pioneer_value' => 'Pioneer Value',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'create_at' => 'Create At',
            'is_authentication' => 'Is Authentication',
            'user_id' => 'User ID',
        ];
    }
}

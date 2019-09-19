<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "sms_template".
 *
 * @property int $id
 * @property string $template_code 短信模版code
 * @property string $content 短信模版内容
 * @property int $is_captcha 是否是验证码短信，1是，2不是
 * @property int $created_at 创建时间
 */
class SmsTemplate extends BaseModel
{
    const CAPTCHA = 1;//验证码

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sms_template';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['template_code', 'content', 'is_captcha', 'created_at'], 'required'],
            [['is_captcha', 'created_at'], 'integer'],
            [['template_code'], 'string', 'max' => 50],
            [['content'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'template_code' => '短信模版code',
            'content' => '短信模版内容',
            'is_captcha' => '是否是验证码短信，1是，2不是',
            'created_at' => '创建时间',
        ];
    }
}

<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "sms_history".
 *
 * @property int $id
 * @property int $template_id 短信模版ID
 * @property string $mobile 手机号
 * @property string $params 模版参数json字符串
 * @property string $content 解析后的完整发送内容
 * @property int $is_send 是否发送，1已发送，2未发送
 * @property int $send_status 发送状态，1发送成功，2发送失败，3未发送
 * @property int $send_time 发送时间
 * @property string $send_response 短信发送的返回结果,json字符串
 * @property int $created_at 创建时间
 */
class SmsHistory extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sms_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['template_id', 'mobile', 'content', 'created_at'], 'required'],
            [['template_id', 'is_send', 'send_status', 'send_time', 'created_at'], 'integer'],
            [['mobile'], 'string', 'max' => 12],
            [['params', 'content'], 'string', 'max' => '500'],
            [['send_response'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'template_id' => '短信模版ID',
            'mobile' => '手机号',
            'params' => '模版参数json字符串',
            'content' => '解析后的完整发送内容',
            'is_send' => '是否发送，1已发送，2未发送',
            'send_status' => '发送状态，1发送成功，2发送失败，3未发送',
            'send_time' => '发送时间',
            'send_response' => '短信发送的返回结果,json字符串',
            'created_at' => '创建时间',
        ];
    }
}

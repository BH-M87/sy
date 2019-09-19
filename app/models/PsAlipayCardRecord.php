<?php

namespace app\models;

use Yii;

class PsAlipayCardRecord extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'ps_alipay_card_record';
    }

    public function rules()
    {
        return [];
    }

    public function attributeLabels()
    {

    }
}

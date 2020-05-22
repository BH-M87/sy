<?php
namespace app\models;

use Yii;

class Goods extends BaseModel
{
    public static function tableName()
    {
        return 'ps_goods';
    }

    public function rules()
    {
        return [
        ];
    }

    public function attributeLabels()
    {
        return [
        ];
    }
}

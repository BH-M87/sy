<?php
namespace app\models;

use Yii;

class PsMenuPack extends BaseModel
{
    public static function tableName()
    {
        return 'ps_menu_pack';
    }

    public function rules()
    {
        return [
            [['pack_id', 'menu_id'], 'integer'],
            [['menu_id'], 'required'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pack_id' => 'Pack ID',
            'menu_id' => 'Menu ID',
        ];
    }
}
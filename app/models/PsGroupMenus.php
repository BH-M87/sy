<?php
namespace app\models;

use Yii;

class PsGroupMenus extends BaseModel
{
    public static function tableName()
    {
        return 'ps_group_menus';
    }

    public function rules()
    {
        return [
            [['group_id', 'menu_id'], 'required'],
            [['group_id', 'menu_id'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_id' => 'Group ID',
            'menu_id' => 'Menu ID',
        ];
    }
}

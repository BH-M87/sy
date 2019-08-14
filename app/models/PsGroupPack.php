<?php
namespace app\models;

use Yii;

class PsGroupPack extends BaseModel
{
    public static function tableName()
    {
        return 'ps_group_pack';
    }

    public function rules()
    {
        return [
            [['group_id', 'pack_id'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_id' => 'Group ID',
            'pack_id' => 'Pack ID',
        ];
    }
}
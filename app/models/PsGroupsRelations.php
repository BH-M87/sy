<?php
namespace app\models;

use Yii;

class PsGroupsRelations extends BaseModel
{
    public static function tableName()
    {
        return 'ps_groups_relations';
    }

    public function rules()
    {
        return [
            [['group_id', 'see_group_id'], 'required'],
            [['group_id', 'see_group_id'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_id' => 'Group ID',
            'see_group_id' => 'See Group ID',
        ];
    }
}

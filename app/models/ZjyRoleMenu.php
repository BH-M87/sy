<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "zjy_role_menu".
 *
 * @property int $id
 * @property int $menu_id
 * @property int $role_id
 * @property int $deleted 1：删除 0：未删除
 * @property string $create_people
 * @property string $create_time
 * @property string $modify_time
 * @property string $modify_people
 */
class ZjyRoleMenu extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'zjy_role_menu';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['menu_id', 'role_id'], 'required'],
            [['menu_id', 'role_id', 'deleted'], 'integer'],
            [['create_time', 'modify_time'], 'safe'],
            [['create_people', 'modify_people'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'menu_id' => 'Menu ID',
            'role_id' => 'Role ID',
            'deleted' => 'Deleted',
            'create_people' => 'Create People',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'modify_people' => 'Modify People',
        ];
    }
}

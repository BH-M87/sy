<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "zjy_user_role".
 *
 * @property int $id
 * @property int $user_id
 * @property int $role_id
 * @property int $tenant_id
 * @property int $deleted 1：已删除 0：未删除
 * @property string $create_people
 * @property string $create_time
 * @property string $modify_time
 * @property string $modify_people
 */
class ZjyUserRole extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'zjy_user_role';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'role_id', 'tenant_id'], 'required'],
            [['user_id', 'role_id', 'tenant_id', 'deleted'], 'integer'],
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
            'user_id' => 'User ID',
            'role_id' => 'Role ID',
            'tenant_id' => 'Tenant ID',
            'deleted' => 'Deleted',
            'create_people' => 'Create People',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'modify_people' => 'Modify People',
        ];
    }
}

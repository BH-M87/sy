<?php
namespace app\models;

use Yii;

class PsGroups extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'ps_groups';
    }

    public function rules()
    {
        return [
            [['name', 'parent_id', 'system_type', 'see_limit', 'create_at'], 'required'],
            [['parent_id', 'system_type', 'level', 'create_at', 'obj_id', 'see_limit'], 'integer'],
            [['see_limit', 'parent_id'], 'default', 'value' => 0],
            [['name'], 'string', 'max' => 100],
            ['describe', 'string', 'max' => 100],
            ['system_type', 'in', 'range'=>[1, 2, 3]],
            ['nodes', 'string', 'max' => 200],
            ['see_limit', 'in', 'range' => [0, 1, 2]],
            ['name', 'string', 'max' => 20, 'on' => ['add']],//页面添加的限制是10个字符
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '部门名称',
            'nodes' => '节点',
            'obj_id' => '物业公司ID',
            'parent_id' => '上级部门',
            'system_type' => '系统类型',
            'level' => '级别',
            'describe' => '智能说明',
            'see_limit' => '查看权限',
            'create_at' => '创建时间',
        ];
    }
}

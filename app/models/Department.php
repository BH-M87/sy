<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "department".
 *
 * @property int $id 主键
 * @property string $org_code 部门code
 * @property string $department_name 部门名称
 * @property int $department_level 部门级别
 * @property string $description 部门描述
 * @property int $parent_id 部门父节点id
 * @property int $status 状态 1-为正常，2-为禁用，3-伪删除
 * @property int $is_internal 1-系统内置 2-新建
 * @property int $create_user_id 创建人
 * @property int $modify_user_id 修改人
 * @property string $gmt_create 创建时间
 * @property string $gmt_modified 修改时间
 * @property int $tenant_id 租户编码
 * @property int $node_type 节点类型1:街道2：社区3：民警4：消防5：城管6：小区7：自定义部门
 * @property int $dependent_id 归属依赖id
 * @property int $order_by 排序
 */
class Department extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'department';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['department_level', 'parent_id', 'status', 'is_internal', 'create_user_id', 'modify_user_id', 'tenant_id', 'node_type', 'dependent_id', 'order_by'], 'integer'],
            [['description'], 'string'],
            [['parent_id', 'create_user_id', 'gmt_create', 'gmt_modified'], 'required'],
            [['gmt_create', 'gmt_modified'], 'safe'],
            [['org_code'], 'string', 'max' => 255],
            [['department_name'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'org_code' => 'Org Code',
            'department_name' => 'Department Name',
            'department_level' => 'Department Level',
            'description' => 'Description',
            'parent_id' => 'Parent ID',
            'status' => 'Status',
            'is_internal' => 'Is Internal',
            'create_user_id' => 'Create User ID',
            'modify_user_id' => 'Modify User ID',
            'gmt_create' => 'Gmt Create',
            'gmt_modified' => 'Gmt Modified',
            'tenant_id' => 'Tenant ID',
            'node_type' => 'Node Type',
            'dependent_id' => 'Dependent ID',
            'order_by' => 'Order By',
        ];
    }
}

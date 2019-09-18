<?php

namespace app\models;

use Yii;

class Department extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'department';
    }

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
    
    // 小区对应的所有部门id 数组返回
    public static function getDept($community_id)
    {
        $xq_orgcode = PsCommunityModel::findOne($community_id)->event_community_no;
        
        $org_code = DepartmentCommunity::find()
            ->select('jd_org_code, sq_org_code, ga_org_code, xf_org_code, cg_org_code')
            ->where(['xq_orgcode' => $xq_orgcode])->asArray()->one();

        $m = Department::find()->select('id')->where(['in', 'org_code', array_values($org_code)])->asArray()->all();

        return array_column($m, 'id');
    }
}

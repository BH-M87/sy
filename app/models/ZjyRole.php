<?php

namespace app\models;

use Yii;
use common\MyException;

/**
 * This is the model class for table "zjy_role".
 *
 * @property int $id
 * @property string $role_name 角色名称
 * @property string $role_desc 角色描述
 * @property int $role_group_id 所属角色组
 * @property int $obj_type 所在机构类型 0=代理商 1=租户 2=公司
 * @property int $obj_id 所在机构ID
 * @property string $sys_code 系统编码
 * @property int $tenant_id 租户id
 * @property int $deleted 1：已删除 0：未删除
 * @property string $create_people
 * @property string $create_time
 * @property string $modify_people
 * @property string $modify_time
 * @property int $user_id
 */
class ZjyRole extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'zjy_role';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['role_group_id', 'obj_type', 'obj_id', 'tenant_id', 'deleted', 'user_id'], 'integer'],
            [['create_time', 'modify_time'], 'safe'],
            [['role_name'], 'string', 'max' => 64],
            [['role_desc'], 'string', 'max' => 100],
            [['sys_code'], 'string', 'max' => 32],
            [['create_people', 'modify_people'], 'string', 'max' => 45],
            [['create_time', 'modify_time'], 'default', 'value' => date("Y-m-d H:i", time())],
            [['tenant_id'], 'default', 'value' => '0'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'role_name' => 'Role Name',
            'role_desc' => 'Role Desc',
            'role_group_id' => 'Role Group ID',
            'obj_type' => 'Obj Type',
            'obj_id' => 'Obj ID',
            'sys_code' => 'Sys Code',
            'tenant_id' => 'Tenant ID',
            'deleted' => 'Deleted',
            'create_people' => 'Create People',
            'create_time' => 'Create Time',
            'modify_people' => 'Modify People',
            'modify_time' => 'Modify Time',
            'user_id' => 'User ID',
        ];
    }


    /**
     * 获取角色列表-編輯員工時使用
     * @param $params
     * @return array
     */
    public static function getList($params)
    {
        $model = self::find()->where(['deleted' => '0'])
            ->andFilterWhere(['obj_type' => $params['obj_type'] ?? null])
            ->andFilterWhere(['obj_id' => $params['obj_id'] ?? null])
            ->andFilterWhere(['role_group_id' => $params['role_group_id'] ?? null])
            ->select("id,role_name as name");
        $count = $model->count();
        if ($count > 0) {
            $model->orderBy('id desc');
            $data = $model->asArray()->all();
        }
        return $data ?? [];
    }

    /**
     * 验证是否唯一
     * @param $params
     */
    public static function checkInfo($params)
    {
        $info = self::find()->where(['role_name' => $params['role_name'],'deleted'=>'0'])
            ->andFilterWhere(['obj_type' => $params['obj_type'] ?? null])
            ->andFilterWhere(['obj_id' => $params['obj_id'] ?? null])
            ->andFilterWhere(['!=', 'id', $params['role_id'] ?? null])
            ->one();
        if (!empty($info)) {
            throw new MyException('角色重复!');
        }
    }

    /**
     * 新增编辑角色
     * @param $params
     * @return array
     */
    public static function AddEditRole($params)
    {
        $model = !empty($params['role_id']) ? self::model()->findOne($params['role_id']) : new self();;
        $model->load($params, '');   # 加载数据
        if ($model->validate()) {  # 验证数据
            //查看名称是否重复
            self::checkInfo($params);
            if (!$model->save()) {
                $errors = array_values($model->getErrors());
                throw new MyException($errors[0][0]);
            }
            //新增角色菜单
            $params['role_id'] = $model->id;
            self::AddRoleMenu($params);
        } else {
            $errors = array_values($model->getErrors());
            throw new MyException($errors[0][0]);
        }
    }

    /**
     * 新增编辑角色
     * @param $params
     * @return array
     */
    public static function AddRoleMenu($params)
    {
        foreach ($params['menu_id'] as $id){
            $data['role_id'] = $params['role_id'];
            $data['menu_id'] = $id;
            $model = new ZjyRoleMenu();
            $model->load($data, '');   # 加载数据
            $model->save();
        }
    }

    /**
     * 删除角色
     * @param $params
     * @return array
     */
    public static function DelRole($params)
    {
        $model = self::model()->findOne($params['role_id']);
        if(!empty($model)){
            $model->deleted=1;
            $model->save();
            //将所有菜单关系数据删除
            ZjyRoleMenu::updateAll(['deleted'=>'1'],['role_id'=>$params['id']]);
        }else{
            throw new MyException('角色不存在');
        }
    }

    /**
     * 获取角色最后一层按钮的菜单id
     * @param $params
     * @return array
     */
    public static function getLastMenuIdById($params)
    {
        return ZjyRoleMenu::find()->select("menu_id")->where(["role_id"=>$params['role_id'],'deleted'=>'0'])->column();
    }

    /**
     * 获取角色最后一层按钮的菜单id
     * @param $params
     * @return array
     */
    public static function getRoleInfoById($params)
    {
        $role =  self::find()->select("id,role_name,role_group_id")->where(["id"=>$params['role_id'],'deleted'=>'0'])->one();
        if(!empty($role)){
            $roleGroup =  ZjyRoleGroup::find()->select("id,role_group_name")->where(["id"=>$role['role_group_id'],'deleted'=>'0'])->one();
            $data['id'] = $role['id'];
            $data['roleName'] = $role['role_name'];
            $data['roleGroupId'] = $roleGroup['id'];
            $data['groupName'] = $roleGroup['role_group_name'];
            return $data;
        }
        return '角色不存在';
    }

}


<?php

namespace app\models;

use Yii;

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
            [['tenant_id'], 'required'],
            [['create_time', 'modify_time'], 'safe'],
            [['role_name'], 'string', 'max' => 64],
            [['role_desc'], 'string', 'max' => 100],
            [['sys_code'], 'string', 'max' => 32],
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
            ->andFilterWhere(['obj_id' => $params['obj_id'] ?? null]);
        $count = $model->count();
        if ($count > 0) {
            $model->orderBy('id desc');
            $data = $model->asArray()->all();
            self::afterList($data, $params);
        }
        return $data ?? [];
    }

    /**
     * 列表结果格式化
     * @author ckl
     * @param $data
     */
    public static function afterList(&$data, $params)
    {
        foreach ($data as &$v) {
            $v['id'] = $v['id'];
            $v['name'] = $v['role_group_name'];
            $params['role_group_id'] = $v['id'];
        }
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
            ->andFilterWhere(['!=', 'id', $params['id'] ?? null])
            ->one();
        if (!empty($info)) {
            throw new MyException('角色组重复!');
        }
    }

    /**
     * 新增编辑角色
     * @param $params
     * @return array
     */
    public static function AddEditRoleGroup($params)
    {
        $model = !empty($params['id']) ? self::model()->findOne($params['id']) : new self();;
        $model->load($params, '');   # 加载数据
        if ($model->validate()) {  # 验证数据
            //查看名称是否重复
            self::checkInfo($params);
            if (!$model->save()) {
                $errors = array_values($model->getErrors());
                throw new MyException($errors[0][0]);
            }
        } else {
            $errors = array_values($model->getErrors());
            throw new MyException($errors[0][0]);
        }
    }

    /**
     * 删除角色
     * @param $params
     * @return array
     */
    public static function DelRoleGroup($params)
    {
        $model = self::model()->findOne($params['id']);
        if(!empty($model)){
            $model->deleted=1;
            $model->save();
        }else{
            throw new MyException('角色不存在');
        }
    }


}

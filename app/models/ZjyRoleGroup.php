<?php

namespace app\models;

use Yii;
use common\MyException;

/**
 * This is the model class for table "zjy_role_group".
 *
 * @property int $id
 * @property string $role_group_name 角色组名称
 * @property int $role_group_order 组序号
 * @property int $obj_type 所在机构类型 0=代理商 1=租户 2=公司
 * @property int $obj_id 所在机构ID
 * @property string $role_group_desc 描述
 * @property int $tenant_id 租户id
 * @property int $deleted 1:已删除 0：未删除
 * @property string $create_people
 * @property string $create_time
 * @property string $modify_people
 * @property string $modify_time
 */

use app\models\ZjyRole;

class ZjyRoleGroup extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'zjy_role_group';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['role_group_name'], 'required'],
            [['role_group_order', 'obj_type', 'obj_id', 'tenant_id', 'deleted'], 'integer'],
            [['create_time', 'modify_time'], 'safe'],
            [['create_time', 'modify_time'], 'default', 'value' => date("Y-m-d H:i", time())],
            [['role_group_name', 'create_people', 'modify_people'], 'string', 'max' => 45],
            [['role_group_desc'], 'string', 'max' => 100],
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
            'role_group_name' => 'Role Group Name',
            'role_group_order' => 'Role Group Order',
            'obj_type' => 'Obj Type',
            'obj_id' => 'Obj ID',
            'role_group_desc' => 'Role Group Desc',
            'tenant_id' => '租户id',
            'deleted' => 'Deleted',
            'create_people' => 'Create People',
            'create_time' => 'Create Time',
            'modify_people' => 'Modify People',
            'modify_time' => 'Modify Time',
        ];
    }

    /**
     * 获取角色组列表
     * @param $params
     * @return array
     */
    public static function getList($params)
    {
        $model = self::find()->where(['deleted' => '0'])
            ->andFilterWhere(['obj_type' => $params['obj_type'] ?? null])
            ->andFilterWhere(['obj_id' => $params['obj_id'] ?? null])
            ->select("id,role_group_name as name");
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
            $params['role_group_id'] = $v['id'];
            $v['children'] = ZjyRole::getList($params);
        }
    }

    /**
     * 验证是否唯一
     * @param $params
     */
    public static function checkInfo($params)
    {
        $info = self::find()->where(['role_group_name' => $params['role_group_name'],'deleted'=>'0'])
            ->andFilterWhere(['obj_type' => $params['obj_type'] ?? null])
            ->andFilterWhere(['obj_id' => $params['obj_id'] ?? null])
            ->andFilterWhere(['!=', 'id', $params['id'] ?? null])
            ->one();
        if (!empty($info)) {
            throw new MyException('角色组重复!');
        }
    }

    /**
     * 新增编辑角色组
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
     * 删除角色组
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
            throw new MyException('角色组不存在');
        }
    }

}

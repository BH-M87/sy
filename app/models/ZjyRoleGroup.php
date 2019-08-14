<?php

namespace app\models;

use Yii;

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
            [['role_group_name', 'tenant_id'], 'required'],
            [['role_group_order', 'obj_type', 'obj_id', 'tenant_id', 'deleted'], 'integer'],
            [['create_time', 'modify_time'], 'safe'],
            [['role_group_name', 'create_people', 'modify_people'], 'string', 'max' => 45],
            [['role_group_desc'], 'string', 'max' => 100],
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
            'tenant_id' => 'Tenant ID',
            'deleted' => 'Deleted',
            'create_people' => 'Create People',
            'create_time' => 'Create Time',
            'modify_people' => 'Modify People',
            'modify_time' => 'Modify Time',
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['create_time', 'modify_time'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['modify_time']
                ],
                'value' => date('Y-m-d H:i',time())
            ]
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
            ->andFilterWhere(['obj_id' => $params['obj_id'] ?? null]);
        $count = $model->count();
        if ($count > 0) {
            $model->orderBy('id desc');
            $data = $model->asArray()->all();
            self::afterList($data, $params);
        }
        return ['totals' => $count, 'list' => $data ?? []];
    }

    /**
     * 列表结果格式化
     * @author yjh
     * @param $data
     */
    public static function afterList(&$data, $params)
    {
        foreach ($data as &$v) {
            $v['id'] = $v['id'];
            $v['name'] = $v['role_group_name'];
            $params['role_group_id'] = $v['id'];
            $v['children'] = ZjyRole::getList($params);
        }
    }

    //验证是否唯一
    public static function checkInfo($params)
    {
        $info = self::find()->where(['role_group_name' => $params['role_group_name']])
            ->andFilterWhere(['obj_type' => $params['obj_type'] ?? null])
            ->andFilterWhere(['obj_id' => $params['obj_id'] ?? null])
            ->andFilterWhere(['!=','id' ,$params['id'] ?? null])
            ->one();
        if (!empty($info)) {
            return self::addError('role_group_name', '角色组重复！');
        }
    }

    /**
     * 新增角色组
     * @param $params
     * @return array
     */
    public static function AddRoleGroup($params,$userinfo)
    {
        $model = new self();
        $model->load($params, '');   # 加载数据
        if ($model->validate()) {  # 验证数据
            //查看名称是否重复
            self::checkInfo($params);
            $model->save();
            return self::success();
        }
        return $model->getErrors();
    }
}

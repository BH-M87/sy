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
            [['create_time', 'modify_time'], 'default', 'value' => date("Y-m-d H:i", time())],
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


    /**
 * 获取角色列表-編輯員工時使用
 * @param $params
 * @return array
 */
    public static function getList($params)
    {
        $model = self::getRoleList($params);
        self::afterList($model, $params);
        return $model ?? [];
    }

    /**
     * 获取角色列表-編輯員工時使用
     * @param $params
     * @return array
     */
    public static function getRoleList($params)
    {
        if(!empty($params['role_group_id'])){
            $filed = "role.id,role.role_name as name,1 as type";
        }else{
            $filed = "group.id,group.role_group_name as name,0 as type";
        }
        $where = " `rala`.`user_id`= {$params['id']}";
        $sql = " SELECT {$filed} FROM `zjy_user_role` `rala` LEFT JOIN `zjy_role` `role` ON role.id = rala.role_id LEFT JOIN `zjy_role_group` `group` ON group.id = role.role_group_id WHERE `rala`.`deleted`='0' AND {$where} GROUP BY `id` ORDER BY `id` DESC";
        echo $sql;die;
        $data = Yii::$app->db->createCommand($sql)->queryAll();
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
            $v['children'] = self::getRoleList($params);
        }
    }

    /**
     * 获取登录用户的角色id
     * @param $params
     * @return array
     */
    public static function getUserRole($user_id)
    {
        return  self::model()->find()->select("role_id")->where(['user_id'=>$user_id,'deleted'=>'0'])->asArray()->column();
    }


}

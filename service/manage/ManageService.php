<?php
namespace service\manage;

use Yii;
use yii\db\Query;
use yii\base\Exception;

use common\core\PsCommon;

use service\BaseService;

use app\models\PsUser;
use app\models\PsUserCommunity;

use service\rbac\UserService;
use service\rbac\GroupService;
use service\rbac\MenuService;

use service\common\SmsService;

class ManageService extends BaseService 
{
    // 查看物业公司下用户列表
    public function lists($reqArr, $groupId, $propertyId)
    {
        $name = !empty($reqArr['name']) ? $reqArr['name'] : '';
        $rows = !empty($reqArr['rows']) ? $reqArr['rows'] : Yii::$app->params['list_rows'];
        $page = !empty($reqArr['page']) ? $reqArr['page'] : 1;
        $seeIds = GroupService::service()->getCanSeeIds($groupId); // 当前用户的部门所拥有的权限
        $systemType = !empty($reqArr['system_type']) ? $reqArr['system_type'] : 1;

        $query = new Query();
        $query->from("ps_user A")
            ->leftJoin("ps_groups B", "A.group_id = B.id")
            ->where(["A.system_type" => $systemType, 'obj_id' => $propertyId])
            ->andFilterWhere(['A.group_id' => $seeIds]) // 查看的部门权限
            ->andFilterWhere(['A.group_id' => PsCommon::get($reqArr, 'group_id')]); // 指定部门
        if ($name) {
            $query->andWhere(["or", ["like", "A.mobile", $name], ["like", "truename", $name]]);
        }
        $totals = $query->count();
        $query->select(["A.id","A.truename","A.sex","A.level", "A.group_id","B.name as group_name","A.mobile","A.is_enable"])
            ->orderBy("A.create_at desc");
        $offset = ($page-1) * $rows;
        $query->offset($offset)->limit($rows);
        $models = $query->createCommand()->queryAll();
        foreach ( $models as $key => $model) {
            $models[$key]["communitys"] = CommunityService::service()->getUserCommunitys($model["id"]);
            $models[$key]["menus"] = MenuService::service()->getSecondMenu($model["group_id"]);
            $models[$key]["is_enable_desc"] = $model["is_enable"]==1 ? "启用" :"禁用";
        }

        return ["list" => $models, 'totals' => $totals];
    }

    public function addUser($data, $communitys) 
    {
        $connection = Yii::$app->db;
        // 判断手机号码在表中是否存在
        $uniqueMobile = $connection->createCommand( "select count(id) from ps_user where system_type=:system_type  and mobile=:mobile",
            [":mobile" => $data["mobile"],":system_type"=>$data["system_type"] ])->queryScalar();
        if( $uniqueMobile >= 1 ) {
            return $this->failed("系统已存在手机号");
        }

        $transaction = $connection->beginTransaction();
        try {
            $password =rand(100000,999999);
            $user_arr = [
                "username" => $data["mobile"],
                "truename" => $data["name"],
                "mobile" => $data["mobile"],
                "sex" => $data["sex"],
                "system_type" =>  $data["system_type"],
                "creator"=> $data["operate_id"],
                "create_at"=>time(),
                "group_id"=>$data["group_id"],
                "level"=>2,
                "property_company_id"=>$data["property_id"],
                "is_enable" => $data['is_enable'] ? $data['is_enable'] : 1,//运营后台默认启用
                "password" => Yii::$app->security->generatePasswordHash($password),
            ];
            $connection->createCommand()->insert('ps_user', $user_arr)->execute();
            $user_id =$connection->getLastInsertID();

            CommunityService::service()->batchInsertUserCommunity($user_id, $communitys);
            if ($data["system_type"] == 2) {
                SmsService::service()->init(9, $data['mobile'])->send([$password]);
            } else {
                SmsService::service()->init(15, $data['mobile'])->send([$password]);
            }
            $transaction->commit();
            return $this->success();

        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->failed("系统错误:".$e->getMessage());
        }
    }

    public function editUser($data, $communitys) 
    {
        $connection = Yii::$app->db;
        $user = $connection->createCommand("select * from ps_user where id=:id",["id"=>$data["user_id"]])->queryOne();
        if( empty( $user)) {
            return $this->failed("未找到用户");
        }

        //查询手机号是否已存在
        $uniqueMobile = $connection->createCommand("select count(id) from ps_user WHERE 
            system_type=:system_type  and mobile=:mobile and id!=:user_id ",[":mobile" => $data["mobile"],":system_type"=>$user["system_type"],":user_id"=>$data["user_id"] ])->queryScalar();
        if( $uniqueMobile >= 1 ) {
            return $this->failed("系统已存在手机号");
        }
        if ( $data["mobile"] == $user["mobile"] ) {
            $userArr = [
                "truename" => $data["name"],
                "sex" => $data["sex"],
                "group_id" => $data["group_id"],
            ];
        } else {
            $password = rand(100000,999999);
            $userArr = [
                "username" => $data["mobile"],
                "truename" => $data["name"],
                "mobile" => $data["mobile"],
                "sex" => $data["sex"],
                "group_id" => $data["group_id"],
                "password" => Yii::$app->security->generatePasswordHash($password),
            ];
        }
        if ($data['is_enable']) {
            $userArr['is_enable'] = $data['is_enable'];
        }

        $transaction = $connection->beginTransaction();
        try {
            UserService::service()->changeUser($data['user_id'], $userArr);

            CommunityService::service()->batchInsertUserCommunity($data['user_id'], $communitys);
            $transaction->commit();
            if ( $data["mobile"] != $user["mobile"] ) {
                SmsService::service()->init(15,$data["mobile"])->send([$password]);
            }
            return $this->success();
        } catch (Exception $e) {
            return $this->failed("系统错误".$e->getMessage());
        }
    }

    // 查看用户详情
    public function showUser($user_id)
    {
        $connection = Yii::$app->db;
        $where = [":manage_id" => $user_id];
        $user = $connection->createCommand( "select pu.id,pu.truename as name,pu.mobile,pu.is_enable,pu.sex,pu.group_id,pg.name as group_name  from ps_user pu left join  ps_groups pg on pg.id=pu.group_id where pu.id=:manage_id",$where)->queryOne();
        if( !empty($user) ) {
            $user["communitys"]  = CommunityService::service()->getUserCommunitys($user["id"]);
            $model["menu_list"] =MenuService::service()->menusList($user["group_id"],2);
            $user["is_enable_desc"] = $user["is_enable"]==1 ? "启用" :"禁用";
        }
        return $user;
    }

    // 启/禁用户
    public function  changeStatus( $userId, $isEnable )
    {
        $connection = Yii::$app->db;
        $user = $connection->createCommand("select * from ps_user where id=:id",[":id"=>$userId])->queryOne();
        if( empty( $user)) {
            return $this->failed("未找到用户");
        }
        if($user["is_enable"] == $isEnable) {
            return $this->failed("用户状态已修改");
        }
        UserService::service()->changeUser($userId, ['is_enable' => $isEnable]);
        return $this->success();
    }

    // 获取物业公司下所有的小区
    public  function communitys( $property_id)
    {
        $where = [":property_id"=>$property_id];
        $select_sql = "select id,name from ps_community where pro_company_id=:property_id";
        $models = Yii::$app->db->createCommand($select_sql,$where)->queryAll();
        return $models;
    }

    // 查询小区下所有有权限的用户
    public function getAllUserByCommunitys($communitys)
    {
        $users = PsUserCommunity::find()
            ->select(['manage_id'])
            ->where(['community_id' => $communitys])
            ->asArray()
            ->column();
        return $users;
    }
}
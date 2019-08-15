<?php
namespace service\manage;

use common\core\PsCommon;
use app\models\PsUser;
use app\models\PsAgent;
use phpDocumentor\Reflection\Types\Self_;
use service\BaseService;
use Yii;
use yii\base\Exception;
use yii\db\Query;
use service\rbac\UserService;
class AgentService extends  BaseService {
    //机构类型 1 代表代理商
    public static $_Type= [
        '2' =>'物管协会',
        '3' =>'政府',
        '4'  => '其他',
        '5' => '街道办',
        '6' => '业委会',
    ];
    /*帐号状态*/
    public static $_Status = [
         '1' =>'启用',
         '2' =>'禁用'
    ];

    private function _search($reqArr)
    {
        return PsAgent::find()->alias('t')
            ->leftJoin(['u' => PsUser::tableName()], 't.user_id=u.id')
            ->where(['t.type' => !empty($reqArr['type'])?PsCommon::get($reqArr, 'type'):[2,3,4,5,6]])
            ->andFilterWhere(['like', 't.name', PsCommon::get($reqArr, 'name')])
            ->andFilterWhere(['t.status' => !empty($reqArr['status'])?PsCommon::get($reqArr, 'status'):''])
            ->andFilterWhere(['like', 't.link_phone', PsCommon::get($reqArr, 'link_phone')])
            ->andFilterWhere(['like', 't.link_man', PsCommon::get($reqArr, 'link_man')])
            ->andFilterWhere(['like', 'u.username', PsCommon::get($reqArr, 'login_name')])
            ->andFilterWhere(['t.id' => PsCommon::get($reqArr, 'agent_no')]);
    }

    /** 获取代理商-组织机构列表*/
    public function getList( $reqArr, $page, $rows) {
        $data = $this->_search($reqArr)
            ->select('t.id, t.name, t.link_man, t.link_phone, t.user_id, u.group_id, u.username as login_name, t.login_phone, t.type, t.status, t.create_at')
            ->orderBy('t.id desc')
            ->offset(($page - 1) * $rows)->limit($rows)
            ->asArray()->all();
        $result = [];
        foreach ( $data as $v) {
            $v['is_bind_user'] = $v['user_id'] > 0 ? 1 : 0;
            $v['create_at'] = $v['create_at'] ? date('Y-m-d H:i:s', $v['create_at']) : '';
            $v['packs'] = PackService::service()->getGroupPack($v["group_id"]);
            $v['communitys'] = $v['user_id'] ? CommunityService::service()->getUserCommunitys($v['user_id']) : [];
            $result[] = $v;
        }
        return $result;
    }

    /**
     * 代理商列表总数
     * @param $reqArr
     * @return int|string
     */
    public function getListCount($reqArr)
    {
        return $this->_search($reqArr)->count();
    }

    /*新增组织机构代理商*/
    public function add($data) {
        $valid =  $this->_validLoginMobile( $data["login_phone"],0);
        if( !$valid) {
            return $this->failed("关联手机号重复");
        }
        $valid =  $this->_validName( $data["name"], $data["type"],0);
        if( !$valid) {
            return $this->failed("名称重复");
        }
        $agentArr = [
            "user_id" => 0,
            "name" => $data["name"],
            "link_man" => $data["link_man"],
            "link_phone" => $data["link_phone"],
            "email" => PsCommon::get($data, 'email'),
            "type"  =>$data["type"] ,
            "alipay_account" =>  $data["alipay_account"],
            "login_phone" =>  $data["login_phone"],
            "create_at" => time(),
        ];
        $agentId=$this->_saveAgent($agentArr);
        return $this->success(["agent_id"=>$agentId]);
    }
    /*编辑组织机构代理商*/
    public function edit($data) {
       $agent =  $this->_showAgent($data["agent_id"]);
       if ( empty($agent)) {
           return $this->failed("代理商不存在");
       }
       $valid =  $this->_validName($data["name"],$data["type"],$data["agent_id"]);
       if( !$valid) {
           return $this->failed("代理商名称重复");
       }
       $valid =  $this->_validLoginMobile($data["login_phone"],$agent["user_id"],$data["agent_id"]);
       if( !$valid) {
           return $this->failed("关联手机号重复");
       }
       $agentArr =[
           "name" => $data["name"],
           "link_man" => $data["link_man"],
           "link_phone" => $data["link_phone"],
           "email" => $data["email"],
           "type" => $data["type"] ?  $data["type"] : $agent["type"],
           "login_phone" =>  $data["login_phone"],
           "alipay_account" => $data["alipay_account"],
       ];
       $this->_saveAgent($agentArr,$data["agent_id"]);
       return $this->success();
   }
    /**
     * 代理商组织机构绑定用户绑定用户
     */
    public function bindUser($data) {
        $query = new Query();
        $model = $query->select(["*"])->from("ps_agent")->where(["id"=>$data["agent_id"]])->one();
        if(empty( $model)) {
            return $this->failed("未找到数据");
        }
        if($model["user_id"]!=0) {
            return $this->failed("已绑定登录用户");
        }
        switch ($model['type']) {
            //街道办，开通街道办系统帐号
            case 5:
                $systemType = 3;break;
            //业委会，开通业委会系统帐号
            case 6:
                $systemType = 2;break;
            //默认开通运营系统帐号(运营，代理商)
            default:
                $systemType = 1;break;
        }


        if(!$this->_validLoginName($data["login_name"])) {
            return $this->failed("用户名重复");
        }
        $userArr = [
            "truename" => $data["login_name"],
            "mobile" => $model["login_phone"],
            "creator" => !empty($data["createor"]) ?  $data["createor"] :1,
            "is_enable" => $data["status"],
            "property_company_id"=>$data["agent_id"],
        ];

        $user =  CompanyService::service()->_saveUser($userArr, $systemType, $model['name']);
        Yii::$app->db->createCommand()->update("ps_agent",["user_id"=>$user["user_id"],"status"=>$data["status"]],["id"=>$data["agent_id"]])->execute();
        PackService::service()->_saveGroupPack($user["group_id"],$data["packs"]);
        if(!empty($data["communitys"])) {
            CommunityService::service()->batchInsertUserCommunity($user['user_id'], $data['communitys']);
        }
        return $this->success();
    }
    /**
     * 代理商组织机构绑定用户绑定用户编辑
     */
    public function editBindUser($data) {
        if( empty($data["packs"]) ) {
            return $this->failed("用户套餐包不能为空");
        }
        $user = Yii::$app->db->createCommand("select * from ps_user where id=:id",[":id"=>$data["user_id"]])->queryOne();
        if( empty( $user)) {
            return $this->failed("用户未找到");
        }
        if(!$this->_validLoginName($data["login_name"],$data["user_id"])) {
            return $this->failed("用户名重复");
        }

        if(!empty($data["communitys"])) {
            CommunityService::service()->batchInsertUserCommunity($user['id'], $data['communitys']);
        }
        Yii::$app->db->createCommand()->update("ps_agent",["status"=>$data["status"]],["id"=>$user["property_company_id"]])->execute();
        $userArr = [
            "username" => $data["login_name"],
            "truename" => $data["login_name"],
            "is_enable" => $data["status"],
        ];
        UserService::service()->changeUser($data['user_id'], $userArr);
        PackService::service()->_saveGroupPack($user["group_id"],$data["packs"]);
        return $this->success();
    }
   public function  bindUserShow($userId) {
       $query = new Query();
       $query->from("ps_user A")
           ->leftJoin("ps_agent B","B.id=A.property_company_id")
           ->where(["A.id"=>$userId]);
       $query->select(["A.group_id","A.username  as login_name","B.type","A.is_enable as status"]);
       $model    = $query->createCommand()->queryOne();
       if( !empty($model)) {
           $model['id']  = (int)$model['group_id'];
           $model['status']  = (int)$model['status'];
           $model['packs']   = PackService::service()->getLevelGroupPack($model["group_id"]);
            if($model["type"] !=1) {
                $model['communitys'] = AgentService::service()->getAgentUserCommunity($userId);
            }
       }
       return $model;
   }
    /**
     *查看代理商-组织机构详情 */
    public function show($agentId) {
        $model = $this->_showAgent($agentId);
        if(! empty($model) ) {
            $model["status_desc"] = $model["status"]==1 ? "启用" : "禁用";
            $model["communitys"] =  $model["user_id"]  > 0 ?  CommunityService::service()->getUserCommunitys($model["user_id"]) : [];
            $model["type_desc"] = $model["type"]==1 ? "代理商" : self::$_Type[ $model["type"]];
            $model["packs"] =  PackService::service()->getGroupPack($model["group_id"]);
        }
        return $model;
    }
    /**
     * 获取用户的小区
     * @param $user_id
     * @param int $property_id 物业公司id
     * @return array
     */
    public function getUserCommunitys($reqWhere)
    {
        $query = new Query();
        $query->select(["A.id","A.name","C.id as agent_id","C.name as agent_name"])->from("ps_community A")
            ->leftJoin("ps_property_company B"," A.pro_company_id=B.id")
            ->leftJoin("ps_agent C","C.id=B.agent_id")
            ->where(["A.comm_type"=>1])
            ->andWhere(["A.status"=>1]);
        if( isset($reqWhere["property_id"]) && $reqWhere["property_id"]>0) {
            $query->andWhere(["B.id"=>$reqWhere["property_id"]]);
        }
        $models  = $query->orderBy("A.id asc")->all();
        $communitys =[];
        if(!empty($models)) {
            foreach ($models as $model) {
                $children= [
                    "key"=>'2-'.$model["id"],
                    "id"=>$model["id"],
                    "name"=>$model["name"],
                ];
                if(!isset($result[$model["agent_id"]])) {
                    $communitys[$model["agent_id"]]["key"]='1-'.$model["agent_id"];
                    $communitys[$model["agent_id"]]["agent_id"]=$model["agent_id"];
                    $communitys[$model["agent_id"]]["agent_name"]=$model["agent_name"];
                }
                $communitys[$model["agent_id"]]["children"][]=$children;
            }
        }
        return array_values($communitys);
    }
    /**
     * 获取用户的小区
     * @param $user_id
     * @param int $property_id 物业公司id
     * @return array
     */
    public function getAgentUserCommunity($userId)
    {
        $query = new Query();
        $query->select(["A.id","A.name","C.id as agent_id","C.name as agent_name"])
            ->from("ps_user_community U")
            ->leftJoin("ps_community A","U.community_id=A.id")
            ->leftJoin("ps_property_company B"," A.pro_company_id=B.id")
            ->leftJoin("ps_agent C","C.id=B.agent_id")
            ->where(["A.comm_type"=>1])->andWhere(["U.manage_id"=>$userId]);
        $models  = $query->orderBy("A.id asc")->all();
        $communitys =[];
        if(!empty($models)) {
            foreach ($models as $model) {
                $children= [
                    "key"=>'2-'.$model["id"],
                    "id"=>$model["id"],
                    "name"=>$model["name"],
                ];
                if(!isset($result[$model["agent_id"]])) {
                    $communitys[$model["agent_id"]]["key"]='1-'.$model["agent_id"];
                    $communitys[$model["agent_id"]]["agent_id"]=$model["agent_id"];
                    $communitys[$model["agent_id"]]["agent_name"]=$model["agent_name"];
                }
                $communitys[$model["agent_id"]]["children"][]=$children;
            }
        }
        return array_values($communitys);
    }

    /**
     * 保存代理商-组织机构数据
     * */
    public function _saveAgent($agentArr, $agentId = 0){
        if($agentId==0) {
            $agentArr["create_at"] = time();
            Yii::$app->db->createCommand()->insert('ps_agent',$agentArr)->execute();
            $agentId =  Yii::$app->db->getLastInsertID();
        } else {
            Yii::$app->db->createCommand()->update('ps_agent',$agentArr,["id"=>$agentId])->execute();
        }
        return $agentId;
    }

    /**
     *验证代理商和组织机构名称
     **/
    private function _validName( $validName, $type = 1,$agentId = 0 ) {
        /*同一级下名称不能重复*/
        $query = new Query();
        $total =  $query->select(["count(id)"])->from("ps_agent")
            ->where(["name"=>$validName])->andWhere(["type"=>$type])
            ->andWhere(["<>","id",$agentId])
            ->scalar();
        return $total > 0 ? false : true;
    }
   /**
   *验证登录验证名
   */
    private function _validLoginName( $validStr ,$userId = 0 ) {
        /*同一级下名称不能重复*/
        $query = new Query();
        $total =  $query->select(["count(id)"])->from("ps_user")
                ->where(["username"=>$validStr])->andWhere(["system_type"=>1])
                ->andWhere(["<>","id",$userId])
                ->scalar();
        return $total > 0 ? false : true;
    }
    /**
     *验证登录手机号
    */
    private function _validLoginMobile( $validStr ,$userId = 0,$agentId=0 ) {
        /*同一级下名称不能重复*/
        $query = new Query();
        $total = $query->select(["count(id)"])->from("ps_user")
            ->where(["mobile"=>$validStr])->andWhere(["system_type"=>1])
            ->andWhere(["<>","id",$userId])
            ->scalar();
        $total2 = $query->select(["count(id)"])->from("ps_agent")->where(["login_phone"=>$validStr])->andWhere(["<>","id",$agentId])->scalar();
        return $total > 0 ? false : ($total2>0 ? false :true);
    }
   /**
    *查看代理商或者详情 */
    private function _showAgent($agentId) {
        $query = new Query();
        $model = $query->select(["A.user_id","A.name","A.link_man",
            "A.link_phone","A.link_man","A.email",
            "A.type","A.alipay_account","A.status",
            "B.group_id","A.login_phone","B.username as login_name"])
            ->from("ps_agent A")
            ->leftJoin("ps_user B","A.user_id=B.id")
            ->where(["A.id"=>$agentId])
            ->one();
        return $model;
    }
    /**
    *更改代理商和组织机构状态
     */
    public function  changeStatus($agentId,$status){
        $agent =Yii::$app->db->createCommand("select id,user_id,name,type,status from ps_agent where id =:id ", [":id"=>$agentId]) ->queryOne();
        if(empty($agent)) {
            return $this->failed("数据未找到到");
        }
        if( $status == $agent["status"] ) {
            return $this->failed("同状态无需修改");
        }
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $connection->createCommand()->update('ps_agent', ['status' =>$status, ],["id"=>$agentId] )->execute();
            UserService::service()->changeUser($agent['user_id'], ['is_enable' => $status]);

            $transaction->commit();
            return $this->success();
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->failed("系统错误");
        }
    }
    /*
     * 获取所有代理商
     * */
    public function getAgent(){
       $agent = Yii::$app->db->createCommand( "select id,name from ps_agent where type=1 ")->queryAll();
        return $agent;
    }

}

<?php
/**
 * 物业公司service
 * @author shenyang
 * @date 2017-06-06
 */

namespace service\manage;

use common\core\F;
use app\models\PsUser;
use app\models\PsPropertyCompany;
use Yii;
use yii\base\Exception;
use yii\db\Query;
use service\BaseService;
use service\rbac\GroupService;
use service\rbac\UserService;

Class CompanyService extends BaseService
{
    CONST COMPANY_TYPE_PROPERTY = 1;

    public function getList($reqArr, $page, $rows)
    {
        $query = new Query();
        $query->from("ps_property_company A")
            ->leftJoin("ps_user B", "A.user_id=B.id")
            ->leftJoin("ps_agent C", "C.id=A.agent_id")
            ->where("1=1");
        if ($reqArr["agent_id"] != 1) {
            $query->andWhere(["A.agent_id" => $reqArr["agent_id"]]);
        }
        if ($reqArr["status"]) {
            $query->andWhere(["A.status" => $reqArr["status"]]);
        }
        if ($reqArr["property_name"]) {
            $query->andWhere(["like", "A.property_name", $reqArr["property_name"]]);
        }
        if ($reqArr["alipay_account"]) {
            $query->andWhere(["like", "A.alipay_account", $reqArr["alipay_account"]]);
        }
        if ($reqArr["login_name"]) {
            $query->andWhere(["like", "B.username", $reqArr["login_name"]]);
        }
        if ($reqArr["link_phone"]) {
            $query->andWhere(["like", "A.link_phone", $reqArr["link_phone"]]);
        }
        if ($reqArr["link_man"]) {
            $query->andWhere(["like", "A.link_man", $reqArr["link_man"]]);
        }
        if ($reqArr["property_type"]) {
            $query->andWhere(["A.property_type" => $reqArr["property_type"]]);
        }
        $totals = $query->count();
        $query->select(["A.id", "A.property_name", "A.nonce", "A.property_type", "A.link_man", "A.link_phone", "A.status", "A.create_at", 'A.agent_id', 'C.name as agent_name',
            "A.user_id", "B.group_id", "B.username as login_name", "B.mobile as login_phone"])
            ->orderBy("A.create_at desc");
        $offset = ($page - 1) * $rows;
        $query->offset($offset)->limit($rows);
        $models = $query->createCommand()->queryAll();
        if (!empty($models)) {
            foreach ($models as $key => $model) {
                $models[$key]['auth_url'] = $model['nonce'] ? Yii::$app->params['auth_to_us_url'] . "&nonce=" . $model['nonce'] : '';
                $models[$key]['login_name'] = !empty($model['login_name']) ? $model['login_name'] : "";
                $models[$key]['login_phone'] = !empty($model['login_name']) ? $model['login_phone'] : "";
                $models[$key]['is_bind_user'] = $model['user_id'] > 0 ? "1" : "0";
                $models[$key]['property_type_label'] = CompanyService::getTypeNameById($model['property_type']);
                $models[$key]['create_at'] = date('Y-m-d', $model['create_at']);
                $models[$key]['communitys'] = $this->getCommunity($model["id"]);
                $models[$key]['packs'] = [];
                if ($model['property_type'] == 1 && $model['user_id'] > 0) {
                    $models[$key]['packs'] = PackService::service()->getGroupPack($model["group_id"]);
                }
            }
        }
        return ["list" => $models, 'totals' => $totals];
    }

    public function getCommunity($propertyId)
    {
        $query = new Query();
        return $query->select(["id", "name"])->from("ps_community")->where(["pro_company_id" => $propertyId])->all();
    }

    public function getCompany($userInfo)
    {
        $where = "status = :status";
        $params = [":status" => 1];
        if ($userInfo["id"] != 1) {
            $where .= " AND agent_id =:agent_id";
            $params = array_merge($params, [':agent_id' => $userInfo["property_company_id"]]);
        }
        $list = Yii::$app->db->createCommand("SELECT id,property_name as companyName FROM ps_property_company where " . $where, $params)->queryAll();
        return $list;
    }

    public function addCompany($data)
    {
        if (!$this->_validName($data['property_name'])) {
            return $this->failed("公司已经存在");
        }
        if (!$this->_validBusinessLicense($data['business_license'])) {
            return $this->failed("营业执照号已经存在");
        }
        if (!empty($data['login_phone'])) {
            if (!$this->_validLoginPhone($data['login_phone'], 0)) {
                return $this->failed("关联手机号已存在");
            }
        }
        $nonce = F::companyCode();
        $companyArr = [
            'agent_id' => $data['agent_id'],
            'property_type' => $data['property_type'],
            'property_name' => $data['property_name'],
            'business_license' => $data['business_license'],
            'business_img' => $data['business_img'],
            'business_img_local' => $data['business_img_local'],
            'mcc_code' => $data['mcc_code'],
            'link_man' => $data['link_man'],
            'link_phone' => $data['link_phone'],
            'login_phone' => $data['login_phone'],
            'email' => $data['email'],
            'status' => 1,
            'alipay_account' => $data['alipay_account'],
            'nonce' => $nonce
        ];
        $companyId = $this->_saveCompany($companyArr, 0);
        return $this->success(["property_id" => $companyId]);
    }

    public function editCompany($data)
    {
        if (!$this->_validName($data['property_name'], $data["property_id"])) {
            return $this->failed("公司已经存在");
        }
        if (!$this->_validBusinessLicense($data['business_license'], $data["property_id"])) {
            return $this->failed("营业执照号已经存在");
        }
        $connection = Yii::$app->db;
        $pro = $connection->createCommand("select * from ps_property_company where id =:id ", ["id" => $data["property_id"]])->queryOne();
        if (empty($pro)) {
            return $this->failed("物业公司不存在");
        }
        if (!empty($data['login_phone'])) {
            if (!$this->_validLoginPhone($data['login_phone'], $pro["user_id"], $data["property_id"])) {
                return $this->failed("关联手机号已存在");
            }
            if ($pro["user_id"] != 0 && $data["login_phone"] != $pro["login_phone"]) {
                $connection->createCommand()->update("ps_user", ["mobile" => $data['login_phone']], ["id" => $pro["user_id"]])->execute();
            }
        }
        /*切换代理商*/
        if ($pro["agent_id"] != $data["agent_id"]) {
            $agent_user_id = $connection->createCommand("select user_id from ps_agent where  id=:agent_id", [":agent_id" => $data["agent_id"]])->queryScalar();
            /*判断当前物业下是否有小区*/
            $communitys = $connection->createCommand("select id from ps_community where pro_company_id=:property_id", [":property_id" => $data["property_id"]])->queryColumn();
            if (!empty($communitys)) {
                //删除公司下所有小区权限
                $userIds = PsUser::find()->select('id')->where(['system_type' => 1, 'property_company_id' => $pro['agent_id']])->column();
                CommunityService::service()->deleteUserCommunity($userIds, $communitys);
                CommunityService::service()->batchInsertUserCommunity($agent_user_id, $communitys, false);
            }
        }

        $companyArr = [
            'agent_id' => $data['agent_id'],
            'property_name' => $data['property_name'],
            'business_license' => $data['business_license'],
            'mcc_code' => $data['mcc_code'],
            'link_man' => $data['link_man'],
            'link_phone' => $data['link_phone'],
            'login_phone' => $data['login_phone'],
            'email' => $data['email'],
            'alipay_account' => $data['alipay_account'],
            'business_img_local' => $data['business_img_local'],
            'business_img' => $data['business_img'],
            'property_type' => $data['property_type'],
            'status' => $data['status']
        ];
        $this->_saveCompany($companyArr, $data["property_id"]);
        return $this->success();
    }

    /**
     * 启用/停用物业公司*/
    public function onOff($propertyId, $status)
    {
        $pro = Yii::$app->db->createCommand("select * from ps_property_company where id =:id ",
            ["id" => $propertyId])
            ->queryOne();
        if (empty($pro)) {
            return $this->failed("物业公司不存在");
        }
        if ($pro["status"] == $status) {
            return $this->failed("物业公司已" . ($status == 1 ? "启用" : "禁用"));
        }
        Yii::$app->db->createCommand()->update('ps_property_company', ['status' => $status], ["id" => $propertyId])->execute();
        if ($pro['user_id'] != 0) {
            Yii::$app->db->createCommand()->update('ps_user', ['is_enable' => $status], ["id" => $pro['user_id']])->execute();
        }
        return $this->success();
    }

    /**
     * 为物业公司绑定用户
     */
    public function bindUser($data)
    {
        $query = new Query();
        $model = $query->select(["*"])->from("ps_property_company")->where(["id" => $data["property_id"]])->one();
        if (empty($model)) {
            return $this->failed("未找到物业公司");
        }
        if ($model["user_id"] != 0) {
            return $this->failed("已绑定登录用户");
        }

        /*      if(!$this->_validLoginPhone($model['login_phone'],0)) {
                  return $this->failed("关联手机号已存在");
              }*/

        if (!$this->_validLoginName($data["login_name"])) {
            return $this->failed("用户名重复");
        }
        $userArr = [
            "truename" => $data["login_name"],
            "mobile" => $model["login_phone"],
            "creator" => $data["createor"] ? $data["createor"] : 1,
            "is_enable" => $data["status"],
            "property_company_id" => $data["property_id"],
        ];
        $user = $this->_saveUser($userArr, 2, $model['property_name']);
        Yii::$app->db->createCommand()->update("ps_property_company", ["user_id" => $user["user_id"], "status" => $data["status"]], ["id" => $data["property_id"]])->execute();
        PackService::service()->_saveGroupPack($user["group_id"], $data["packs"]);
        return $this->success();
    }

    /**
     * 编辑物业公司绑定用户
     */
    public function editBindUser($data)
    {
        if (empty($data["packs"])) {
            return $this->failed("用户套餐包不能为空");
        }
        $user = Yii::$app->db->createCommand("select * from ps_user where id=:id", [":id" => $data["user_id"]])->queryOne();
        if (empty($user)) {
            return $this->failed("用户未找到");
        }
        $userArr = [
            "username" => $data["login_name"],
            "truename" => $data["login_name"],
            "is_enable" => $data["status"],
        ];
        UserService::service()->changeUser($data["user_id"], $userArr);
        Yii::$app->db->createCommand()->update("ps_property_company", ["status" => $data["status"]], ["id" => $user["property_company_id"]])->execute();
        PackService::service()->_saveGroupPack($user["group_id"], $data["packs"]);
        return $this->success();
    }

    /*保存物业公司*/
    private function _saveCompany($companyArr, $companyId = 0)
    {
        if ($companyId == 0) {
            $companyArr["create_at"] = time();
            Yii::$app->db->createCommand()->insert('ps_property_company', $companyArr)->execute();
            $companyId = Yii::$app->db->getLastInsertID();
        } else {
            Yii::$app->db->createCommand()->update('ps_property_company', $companyArr, ["id" => $companyId])->execute();
        }
        return $companyId;
    }

    /**
     * 保存用户数据
     *$groupName 用户组名称,$user 用户信息组
     */
    public function _saveUser($user, $systemType, $propertyName = '')
    {
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            //超级管理员部门
            $groupId = GroupService::service()->addBySystem($propertyName, $systemType, $user["property_company_id"]);
            if (!$groupId) {
                throw new Exception('添加部门失败');
            }
            $user["username"] = $user["truename"];
            $user["create_at"] = time();
            $user["system_type"] = $systemType;
            $user["level"] = 1;
            $user["group_id"] = $groupId;
            $user["password"] = Yii::$app->security->generatePasswordHash('zhujiayi360');

            $connection->createCommand()->insert("ps_user", $user)->execute();
            $userId = $connection->getLastInsertID();

            $transaction->commit();
            return ["group_id" => $groupId, "user_id" => $userId];
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->failed();
        }
    }

    private function _validName($name, $companyId = 0)
    {
        /*同一级下名称不能重复*/
        $query = new Query();
        $total = $query->select(["count(id)"])->from("ps_property_company")
            ->where(["property_name" => $name])
            ->andWhere(["<>", "id", $companyId])
            ->scalar();
        return $total > 0 ? false : true;
    }

    private function _validBusinessLicense($businessLicense, $companyId = 0)
    {
        /*同一级下名称不能重复*/
        $query = new Query();
        $total = $query->select(["count(id)"])->from("ps_property_company")
            ->where(["business_license" => $businessLicense])
            ->andWhere(["<>", "id", $companyId])
            ->scalar();
        return $total > 0 ? false : true;
    }

    private function _validLoginName($validStr, $userId = 0)
    {
        $query = new Query();
        $total = $query->select(["count(id)"])->from("ps_user")
            ->where(["system_type" => 2])
            ->andWhere(["username" => $validStr])
            ->andWhere(["<>", "id", $userId])
            ->scalar();
        return $total > 0 ? false : true;
    }

    private function _validLoginPhone($validStr, $userId = 0, $propertyId = 0)
    {
        $query = new Query();
        $total = $query->select(["count(id)"])->from("ps_user")
            ->where(["system_type" => 2])
            ->andWhere(["mobile" => $validStr])
            ->andWhere(["<>", "id", $userId])
            ->scalar();
        $total2 = $query->select(["count(id)"])->from("ps_property_company")->where(["login_phone" => $validStr])
            ->andWhere(["<>", "id", $propertyId])->scalar();
        return $total > 0 ? false : ($total2 > 0 ? false : true);
//        return $total > 0 ? false : true;
    }

    /**
     * 查看物业公司
     */
    public function proShow($propertyId)
    {
        $query = new Query();
        $query->from("ps_property_company A")
            ->leftJoin("ps_user B", "A.user_id=B.id")
            ->where(["A.id" => $propertyId]);
        $query->select(["A.id", "A.property_name", "A.mcc_code", "A.agent_id",
            "A.property_type", "A.link_man", "A.link_phone", "A.status", 'A.alipay_account', 'A.business_license',
            "A.create_at", "A.business_img", "A.business_img_local", "A.email",
            "A.user_id", "B.group_id", "B.username as login_name",
            "A.login_phone"]);
        $model = $query->createCommand()->queryOne();
//        $query = new Query();
//        $model = $query->select(["*"])->from("ps_property_company")->where(["id"=>$propertyId])->one();
        if (!empty($model)) {
            $model['login_name'] = !empty($model['login_name']) ? $model['login_name'] : "";
            $model['login_phone'] = !empty($model['login_phone']) ? $model['login_phone'] : "";
            $model['is_bind_user'] = $model['user_id'] > 0 ? "1" : "0";
            $model['property_type_label'] = CompanyService::getTypeNameById($model['property_type']);
            $model['create_at'] = date('Y-m-d', $model['create_at']);
            if ($model['property_type'] == 1 && $model['user_id'] > 0) {
                $model['packs'] = PackService::service()->getGroupPack($model["group_id"]);
            }
            $model["mcc_code_name"] = $this->getMcNameByCode($model['mcc_code']);
        }
        return $model;
    }

    public function proUserShow($userId)
    {
        $query = new Query();
        $query->from("ps_user A")
            ->where(["A.id" => $userId]);
        $query->select(["A.group_id", "A.username  as login_name", "A.is_enable as status"]);
        $model = $query->createCommand()->queryOne();
        if (!empty($model)) {
            $model['packs'] = PackService::service()->getLevelGroupPack($model["group_id"]);
        }
        return $model;
    }


    public function getUserPro($id)
    {
        $model = Yii::$app->db->createCommand("select * from ps_property_company where user_id='" . $id . "'")->queryOne();
        return $model;
    }

    //经营类目
    public function mccodeList()
    {
        $code = [
            0 => [
                'key' => 'S_S02_7013',
                'value' => '不动产代理——房地产经纪',
            ],
            1 => [
                'key' => 'S_S02_6513',
                'value' => '不动产管理－物业管理',
            ],
            2 => [
                'key' => 'S_S02_1520',
                'value' => '房地产开发商',
            ],
            3 => [
                'key' => 'C_C04_5411',
                'value' => '超市（非平台类）',
            ],
            4 => [
                'key' => 'C_C04_5300',
                'value' => '会员制批量零售店',
            ],
            5 => [
                'key' => 'C_C04_5999',
                'value' => '其他专业零售店',
            ],
            6 => [
                'key' => 'C_C03_5714',
                'value' => '窗帘、帷幕、室内装潢',
            ],
            7 => [
                'key' => 'C_C03_5719',
                'value' => '各种家庭装饰专营',
            ],
            8 => [
                'key' => 'C_C03_5712',
                'value' => '家具/家庭摆设',
            ],
        ];
        return $code;
    }

    //公司类型
    public function propertyTypeList()
    {
        $types = [
            0 => [
                'key' => '1',
                'value' => '物业',
            ],
            1 => [
                'key' => '2',
                'value' => '开发商',
            ],
            2 => [
                'key' => '3',
                'value' => '家装',
            ],
            3 => [
                'key' => '4',
                'value' => '零售',
            ],
            4 => [
                'key' => '5',
                'value' => '其他',
            ]
        ];
        return $types;
    }

    //根据key值获取类目名称
    public function getMcNameByCode($code)
    {
        $codeList = $this->mccodeList();
        foreach ($codeList as $key => $val) {
            if ($val['key'] == $code) {
                return $val['value'];
            }
        }
        return "";
    }

    //根据key值获取公司类型名称
    public function getTypeNameById($id)
    {
        $typeList = $this->propertyTypeList();
        foreach ($typeList as $key => $val) {
            if ($val['key'] == $id) {
                return $val['value'];
            }
        }
        return "";
    }

    /**
     * 根据物业公司ID，获取物业公司名称
     * @param $id
     * @return false|null|string
     */
    public function getNameById($id)
    {
        return PsPropertyCompany::find()->select('property_name')
            ->where(['id' => $id])
            ->scalar();
    }

}
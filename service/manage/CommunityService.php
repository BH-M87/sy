<?php
/**
 * Created by PhpStorm.
 * User: wenchao.feng
 * Date: 2017/3/6
 * Time: 19:32
 */

namespace service\manage;

use app\models\Department;
use app\models\PsAreaAli;
use app\models\PsCommunityConvention;
use common\MyException;
use service\alipay\AliTokenService;
use service\BaseService;
use common\core\F;
use common\core\PsCommon;
use common\core\Pinyin;
use common\core\Client;
use service\template\TemplateService;
use service\template\CommunityConventionService;
use app\models\PsAgent;
use app\models\PsRepairType;
use service\common\AreaService;
use service\rbac\OperateService;
use app\models\PsCommunityRoominfo;
use app\models\PsUserCommunity;
use app\models\PsCommunityModel;
use app\models\PsPropertyCompany;
use yii\db\Exception;
use yii\db\Query;
use yii\helpers\FileHelper;
use Yii;

class CommunityService extends BaseService
{
    //高德地图
    const ACCESS_KEY = "c38ZLfSEfktHZw54O6XCgmPwjg0bQAW1svvFWQ6b";
    const SECRET_KEY = "DxYXfBO6Q0e4dcuAvRCpNzaNQo_zSbq7-3NwArq4";

    const ACTION_WAIT_SERVICE = "WAIT_SERVICE_PROVISION|INVOKER";
    const ACTION_WAIT_PRO_VER = "WAIT_PROD_VERIFICATION|MERCHANT";
    const ACTION_WAIT_ONLINE = "WAIT_ONLINE_APPLICATION|INVOKER";

    const COMM_WUYE = 1;//物业
    const COMM_XINFANG = 2;//新房

    const COMMUNITY_ONLINE = 1;//小区上线
    const COMMUNITY_OFFLINE = 2;//小区下线

    public static $areaCode = [
        '330100' => 'hz',
        '610100' => 'xh',
        '500100' => 'cq',
        '520100' => 'gy',
        '420100' => 'wh',
        '370200' => 'qingdao',
        '510100' => 'cd',
        '310100' => 'sh',
        '370100' => 'jn',
        '320200' => 'wuxi',//无锡
        '340100' => 'hf',
        '320100' => 'nj',
        '410100' => 'zz',
        '440100' => 'gz',
        '360100' => 'nc',
        '530100' => 'km',
        '450100' => 'nn',
        '460100' => 'hk',
        '460200' => 'sanya'
    ];

    public function guideImage()
    {   //echo F::ossImagePath('2019092910041480231.jpg');die;
        $tb = 'ps_guide1';
        $m = Yii::$app->db->createCommand("SELECT * FROM $tb where id = 58")->queryAll();
        foreach ($m as $k => $v) {
            $id = $v['id'];
            $img_url = F::trunsImg($v['img_url']);
            Yii::$app->db->createCommand("update $tb set img_url = '$img_url' where id = '$id'")->execute();
        }
        echo 1;die;
    }

    public function carLabelRela()
    {
        $m = Yii::$app->db->createCommand("SELECT A.label_id, B.mobile, A.created_at, A.data_id
            FROM ps_member_car_label A left join ps_member_police B 
            on A.data_id = B.id where A.community_id = 6 and A.data_type = 2")
            ->queryAll();
        foreach ($m as $k => $v) {
            $data_id = $v['data_id'];
            $community_id = 37;
            $type = 1;
            $labels_id = $v['label_id'];
            $created_at = $v['created_at'];

            $insert[] = ['labels_id' => $labels_id, 'data_id' => $data_id, 'data_type' => 3, 'created_at' => $created_at, 'community_id' => $community_id, 'type' => $type];
        }

        Yii::$app->db->createCommand()
                    ->batchInsert('ps_labels_rela_inport', ['labels_id', 'data_id', 'data_type', 'created_at', 'community_id', 'type'], $insert)->execute();
        echo 1;die;
    }

    public function inportLabelRela()
    {
        $m = Yii::$app->db->createCommand("SELECT A.label_id, B.mobile, A.created_at
            FROM ps_member_car_label A left join ps_member_police B 
            on A.data_id = B.id where A.community_id = 6 and A.data_type = 1")
            ->queryAll();
        foreach ($m as $k => $v) {
            $mobile = $v['mobile'];
            $room = Yii::$app->db->createCommand("SELECT id, community_id, status
                FROM ps_room_user where mobile = '$mobile'")
                ->queryOne();

            $data_id = $room['id'];
            $community_id = $room['community_id'];
            $type = $room['status'];
            $labels_id = $v['label_id'];
            $created_at = $v['created_at'];

            $insert[] = ['labels_id' => $labels_id, 'data_id' => $data_id, 'data_type' => 2, 'created_at' => $created_at, 'community_id' => $community_id, 'type' => $type];
        }

        Yii::$app->db->createCommand()
                    ->batchInsert('ps_labels_rela', ['labels_id', 'data_id', 'data_type', 'created_at', 'community_id', 'type'], $insert)->execute();
        echo 1;die;
    }

    /**
     * 添加小区
     * @param $data
     * @return array
     */
    public function addCommunity($data, $userinfo)
    {
        //物业公司查询
        $psCommany = PsPropertyCompany::findOne($data['pro_company_id']);
        if (!$psCommany) {
            return $this->failed('此物业公司不存在');
        }

        if (preg_match('/^\d*$/', $data['name'])) {
            return $this->failed('小区名称不能为纯数字！');
        }

        //判断小区是否已经存在
        $communityInfo = PsCommunityModel::find()->select(['id'])->where(['name' => $data['name']])->one();
        if ($communityInfo) {
            return $this->failed('小区已经存在，不能重复添加');
        }

        //参数判断
        $data['house_id'] = "";

        //经纬度转换
        $data['locations'] = self::getLonLat($data['province_code'], $data['city_id'], $data['district_code'], $data['address']);

        if (!$data['locations']) {
            return $this->failed('经纬度转换失败，请重新填写小区地址');
        }
        $pinyin = new Pinyin();
        $locationArr = explode('|', $data['locations']);
        $today = date("Ymd", time());
        $communityNo = $today . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
        $community = new PsCommunityModel();
        $community->community_no = $communityNo;
        $community->province_code = $data['province_code'];
        $community->city_id = $data['city_id'];
        $community->district_code = $data['district_code'];
        $community->pro_company_id = $data['pro_company_id'];
        $community->name = $data['name'];
        $community->locations = $data['locations'];
        $community->address = $data['address'];
        $community->phone = $data['phone'];
        $community->pinyin = $pinyin->pinyin($data['name'], true) ? strtoupper($pinyin->pinyin($data['name'], true)) : '#';
        $community->status = 1;
        $community->comm_type = $data['comm_type'];
        $community->house_type = $data['house_type'];
        $community->area_sign = isset(self::$areaCode[$data['city_id']]) ? self::$areaCode[$data['city_id']] : '';
        $community->create_at = $data['create_at'];
        $community->longitude = !empty($locationArr[0]) ? $locationArr[0] : 0;
        $community->latitude = !empty($locationArr[1]) ? $locationArr[1] : 0;

        if ($community->save()) {
            // 新小区生成默认模板
            TemplateService::service()->templateDefault($community->id);
            //添加物业公司管理员的小区权限
            $this->addUserCommunity($psCommany->user_id, $community->id);
            //添加代理商用户的小区权限
            $this->addPropertyCommunity($data['pro_company_id'], $community->id);
            //删除超管帐号缓存
            Yii::$app->redis->del($this->_userCommunityCacheKey(1));
            //添加默认报事报修类型
            $this->addRepairType($community->id);
            //添加默认社区公约
            $this->addConvention($community->id);
            $content = '小区名称：' . $data['name'] . ',';
            if (!empty($data['group'])) {
                $content .= '苑/期/区：' . $data['group'] . ',';
            }
            $content .= '状态：' . ($data['status'] == 1 ? "上线" : "下线") . ',';
            $content .= '物业电话：' . $data['phone'] . ',';
            $content .= '关联物业公司：' . $data['pro_company_id'] . ',';
            $content .= '省市区编码：' . $data['province_code'] . "|" . $data['city_id'] . "|" . $data['district_code'] . ',';
            $operate = [
                "operate_menu" => "小区管理",
                "operate_type" => "新增小区",
                "operate_content" => $content,
            ];
            OperateService::add($userinfo, $operate);
            return $this->success();
        } else {
            echo 1;die;
            $errorArr = array_values($community->getErrors());
            print_r($errorArr);die;
            return $this->failed($errorArr[0][0]);
        }
    }

    /**
     * 编辑小区
     * @param $data
     */
    public function editCommunity($data, $userinfo)
    {
        //小区是否存在
        $community = PsCommunityModel::findOne($data['id']);
        if (!$community) {
            echo json_encode(['code' => 50001, 'data' => [], 'error' => ['errorMsg' => ' ']], JSON_PRETTY_PRINT);
            exit;
        }
        $data['community_id'] = $community->community_no;

        //物业公司查询
        $psCommany = PsPropertyCompany::findOne($data['pro_company_id']);
        if (!$psCommany) {
            echo json_encode(['code' => 50001, 'data' => [], 'error' => ['errorMsg' => '此物业公司不存在']], JSON_PRETTY_PRINT);
            exit;
        }

        if (preg_match('/^\d*$/', $data['name'])) {
            echo json_encode(['code' => 50001, 'data' => [], 'error' => ['errorMsg' => '小区名称不能为纯数字！']], JSON_PRETTY_PRINT);
            exit;
        }

        //判断小区名称是否重复
        $communityRe = PsCommunityModel::find()->select(['id'])->where(['name' => $data['name']])->andWhere(['!=', 'id', $data['id']])->one();
        if ($communityRe) {
            echo json_encode(['code' => 50001, 'data' => [], 'error' => ['errorMsg' => '小区已经存在，不能重复添加']], JSON_PRETTY_PRINT);
            exit;
        }

        //参数判断
        $data['house_id'] = '';

        //经纬度转换
        $data['locations'] = self::getLonLat($data['province_code'], $data['city_id'], $data['district_code'], $data['address']);
        if (!$data['locations']) {
            echo json_encode(['code' => 50001, 'data' => [], 'error' => ['errorMsg' => '经纬度转换失败，请重新填写小区地址']], JSON_PRETTY_PRINT);
            exit;
        }
        $pinyin = new Pinyin();
        //小区同步成功
        $locationArr = explode('|', $data['locations']);
        $community->province_code = $data['province_code'];
        $community->city_id = $data['city_id'];
        $community->district_code = $data['district_code'];
        $community->pro_company_id = $data['pro_company_id'];
        $community->name = $data['name'];
        $community->locations = $data['locations'];
        $community->address = $data['address'];
        $community->phone = $data['phone'];
        $community->status = $data['status'];
        $community->comm_type = $data['comm_type'];
        $community->house_type = $data['house_type'];
        $community->pinyin = $pinyin->pinyin($data['name'], true) ? strtoupper($pinyin->pinyin($data['name'], true)) : '#';
        $community->area_sign = isset(self::$areaCode[$data['city_id']]) ? self::$areaCode[$data['city_id']] : '';
        $community->longitude = !empty($locationArr[0]) ? $locationArr[0] : 0;
        $community->latitude = !empty($locationArr[1]) ? $locationArr[1] : 0;

        if ($community->save()) {
            //添加关联关系 v4.0.0版本，产品沟通后，物业公司不可切换
            $content = '小区名称：' . $data['name'] . ',';
            if (!empty($data['group'])) {
                $content .= '苑/期/区：' . $data['group'] . ',';
            }

            $content .= '状态：' . ($data['status'] == 1 ? "上线" : "下线") . ',';
            $content .= '物业电话：' . $data['phone'] . ',';
            $content .= '关联物业公司：' . $data['pro_company_id'] . ',';
            $content .= '省市区编码：' . $data['province_code'] . "|" . $data['city_id'] . "|" . $data['district_code'] . ',';
            $operate = [
                "operate_menu" => "小区管理",
                "operate_type" => "编辑小区",
                "operate_content" => $content,
            ];
            OperateService::add($userinfo, $operate);

            return true;
        }


    }

    /**
     * 获取高德地图经纬度
     * @param $provinceCode
     * @param $cityCode
     * @param $districtCode
     * @param $address
     * @return string
     */
    public function getLonLat($provinceCode, $cityCode, $districtCode, $address)
    {
        $provinceStr = AreaService::service()->getNameByCode($provinceCode);
        $cityStr = AreaService::service()->getNameByCode($cityCode);
        $districtStr = AreaService::service()->getNameByCode($districtCode);

        $newAddress = $provinceStr . $cityStr . $districtStr . $address;
        $client = new Client();
        $source_url = \Yii::$app->params['gaode_url'];
        $params = [
            'address' => $newAddress,
            'output' => 'JSON',
            'key' => \Yii::$app->params['gaode_key']
        ];

        //地理位置数组
        $locationArr = [];
        $response = $client->fetch($source_url, $params, "GET");
        $response = json_decode($response, true);
        if ($response['status'] == 1) {
            $reoCodes = $response['geocodes'];
            foreach ($reoCodes as $code) {
                array_push($locationArr, str_replace(",", "|", $code['location']));
            }
        }

        return implode(",", $locationArr);
    }

    /**
     * 添加物业与小区的关联关系
     * @param $property_id
     * @param $community_id
     * @return bool
     * @throws \yii\db\Exception
     */
    public function addPropertyCommunity($property_id, $community_id)
    {
        $propertyUserId = PsAgent::find()->alias('t')->select('t.user_id')
            ->leftJoin(['c' => PsPropertyCompany::tableName()], 't.id=c.agent_id')
            ->where(['c.id' => $property_id])
            ->scalar();
        if ($propertyUserId) {
            return $this->addUserCommunity($propertyUserId, $community_id);
        }
        return true;
    }

    /**
     * 获取小区及对应的物业公司信息
     * @param $communityId
     * @return array
     */
    public function getCommunityInfo($communityId)
    {
        $communityInfo = PsCommunityModel::find()
            ->select(['ps_community.id', 'community_no', 'pro_company_id', 'name', 'ps_community.province_code', 'city_id', 'district_code', 'company.property_name', 'company.alipay_account'])
            ->leftJoin('ps_property_company as company', 'ps_community.pro_company_id = company.id')
            ->where(['ps_community.id' => $communityId])
            ->andWhere(['ps_community.status' => 1])
            ->asArray()
            ->one();
        //获取此物业公司的授权token
        if ($communityInfo) {
            //查询所在市，区名称
            $communityInfo['city_name'] = AreaService::service()->getNameByCode($communityInfo['city_id']);

            //查询所在区名称
            $communityInfo['district_name'] = AreaService::service()->getNameByCode($communityInfo['district_code']);
            $communityInfo['auth_token'] = AliTokenService::service()->getTokenByCompany($communityInfo['pro_company_id']);
        }

        return $communityInfo;
    }

    /**
     * 根据小区id获取小区详情
     * @param $communityId
     * @return array|bool
     */
    public function getInfoById($communityId)
    {
        $query = new Query();
        $model = $query->select(["A.id", "A.community_no", "A.name", "B.property_name as company_name", "B.id as company_id", "B.alipay_account as company_account", "B.link_phone"])
            ->from("ps_community A")
            ->leftJoin("ps_property_company B", "A.pro_company_id=B.id")
            ->where(["A.id" => $communityId])
            ->one();
        return !empty($model) ? $model : [];
    }

    /**
     * @update 2017-12-02 by shenyang: 增加对搜索省，区的支持
     * 获取小区列表 limit $limit, $rows
     */
    public function communityList($data, $userInfo)
    {
        $name = !empty($data['name']) ? $data['name'] : '';
        $community_no = !empty($data['community_no']) ? $data['community_no'] : '';
        $city_id = !empty($data['city_id']) ? $data['city_id'] : '';
        $phone = !empty($data['phone']) ? $data['phone'] : '';
        $status = !empty($data['status']) ? $data['status'] : '';
        $company_name = !empty($data['company_name']) ? $data['company_name'] : '';
        $page = !empty($data['page']) ? intval($data['page']) : 1;
        $rows = !empty($data['rows']) ? intval($data['rows']) : 20;
        $commType = !empty($data['comm_type']) ? intval($data['comm_type']) : '';
        $provinceCode = !empty($data['province_code']) ? $data['province_code'] : '';
        $districtCode = !empty($data['district_code']) ? $data['district_code'] : '';
        $limit = ($page - 1) * $rows;

        $where = " 1 = 1";
        $params = [];

        if ($community_no) {
            $params = array_merge($params, [':community_no' => '%' . $community_no . '%']);
            $where .= " AND A.community_no like :community_no";
        }

        if ($name) {
            $params = array_merge($params, [':name' => '%' . $name . '%']);
            $where .= " AND A.name like :name";
        }

        if ($city_id) {
            $params = array_merge($params, [':city_id' => $city_id]);
            $where .= " AND A.city_id = :city_id";
        }

        if ($provinceCode) {
            $params = array_merge($params, [':province_code' => $provinceCode]);
            $where .= " AND A.province_code = :province_code";
        }

        if ($districtCode) {
            $params = array_merge($params, [':district_code' => $districtCode]);
            $where .= " AND A.district_code = :district_code";
        }


        if ($phone) {
            $params = array_merge($params, [':phone' => '%' . $phone . '%']);
            $where .= " AND A.phone like :phone";
        }

        if ($status) {
            //1已上线小区 2已下线小区 3待上线小区
            /*   $params = array_merge($params, [':status' => $status]);
               $where .= " AND A.status = :status";*/
            if ($status == 1) {
                $params = array_merge($params, [':status' => $status]);
                $where .= " AND A.status = :status";
                $params = array_merge($params, [':ali_status' => 'ONLINE']);
                $where .= " AND A.ali_status = :ali_status";
            } elseif ($status == 2) {
                $params = array_merge($params, [':status' => $status]);
                $where .= " AND A.status = :status";
            } elseif ($status == 3) {
                $params = array_merge($params, [':status' => 1]);
                $where .= " AND A.status = :status";
                $params = array_merge($params, [':ali_status' => 'PENDING_ONLINE']);
                $where .= " AND A.ali_status = :ali_status";
            }
        }

        if ($company_name) {
            $params = array_merge($params, [':property_name' => '%' . $company_name . '%']);
            $where .= " AND B.property_name like :property_name";
        }

        if ($commType) {
            $params = array_merge($params, [':comm_type' => $commType]);
            $where .= " AND A.comm_type = :comm_type ";
        }
        $db = Yii::$app->db;

        if ($userInfo["id"] == 1) {
            $table = "  ps_community A left join ps_property_company B on A.pro_company_id = B.id ";
        } else {
            $params = array_merge($params, [':manage_id' => $userInfo["id"]]);
            $where .= " AND C.manage_id = :manage_id ";
            $table = "  ps_community A left join ps_property_company B on A.pro_company_id = B.id  left join ps_user_community C on  C.community_id=A.id ";
        }
        $totals = $db->createCommand("SELECT COUNT(A.id) FROM " . $table . " where " . $where, $params)->queryScalar();

        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }
        $list = $db->createCommand("SELECT  A.*, B.property_name as company_name  FROM" . $table . " where " . $where . " order by id desc limit $limit, $rows", $params)->queryAll();
        $areaCodes = array_merge(array_column($list, 'province_code'), array_column($list, 'city_id'));
        $area = AreaService::service()->getNamesByCodes($areaCodes);
        foreach ($list as $key => $val) {
            $list[$key]['has_park_service'] = 1;
            $list[$key]['service_list'] = '';
            $list[$key]['create_at'] = date('Y-m-d', $val['create_at']);
            $list[$key]['province_name'] = PsCommon::get($area, $val['province_code']);
            $list[$key]['city_name'] = PsCommon::get($area, $val['city_id']);
            $list[$key]['comm_type_name'] = PsCommon::getCommType($val['comm_type']);
            $list[$key]['house_type_name'] = !empty($val['house_type']) ? PsCommon::getHouseType($val['house_type']) : '';
            $list[$key]['comm_status_name'] = $val['status'] == 1 ? "启用" : "禁用";
        }
        return ['list' => $list, 'totals' => $totals];
    }

    /**
     * 小区详情
     */
    public function communityShow($id)
    {
        $model = Yii::$app->db->createCommand("SELECT A.*, B.property_name as company_name, B.alipay_account as company_account, B.link_phone
            FROM ps_community A left join ps_property_company B 
            on A.pro_company_id = B.id where A.id = :id")
            ->bindValue(':id', $id)
            ->queryOne();
        if ($model) {
            $model['img_list'] = [];
            $model['service_list'] = [];

            //区域转换
            $model['area_codes'] = [
                0 => $model['province_code'],
                1 => $model['city_id'],
                2 => $model['district_code']
            ];
            $model['comm_type_name'] = PsCommon::getCommType($model['comm_type']);
            return $model;
        }
    }

    /**
     * 启用禁用小区
     */
    public function communityCheck($data, $userinfo)
    {
        $id = $data['community_id'];
        $status = $data['status'];

        $community = Yii::$app->db->createCommand("SELECT id,name FROM ps_community where id = :id")
            ->bindValue(':id', $id)
            ->queryOne();

        if (!empty($community)) {
            if (2 == $status) { // 下线操作时 判断改小区是否有未交费账单
                $exist = Yii::$app->db->createCommand("SELECT id FROM ps_bill 
                    where community_id = :community_id and status = 1")
                    ->bindValue(':community_id', $id)
                    ->queryScalar();

                if ($exist) {
                    return $this->failed('该小区有未缴纳账单，不能直接进行下线操作！');
                }
            }

            Yii::$app->db->createCommand()->update('ps_community', ['status' => $status], 'id =' . $id)->execute();
            $content = "小区名称:" . $community['name'] . ',';
            $content .= "操作类型:" . ($status == 1 ? "显示" : "隐藏");
            $operate = [
                "operate_menu" => "小区管理",
                "operate_type" => "显示/隐藏",
                "operate_content" => $content,
            ];
            OperateService::add($userinfo, $operate);
            return $this->success();
        } else {
            return $this->failed('小区ID不存在');
        }
    }

    /**
     * 获取单个小区名称
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getCommunityName($id)
    {
        return PsCommunityModel::find()->select('id, name, logo_url')
            ->where(['id' => $id])
            ->asArray()->one();
    }

    /**
     * 删除旧小区与用户的绑定关系
     * @param $communityId
     */
    public function deleteOldCommunity($communityId)
    {
        PsUserCommunity::deleteAll(['community_id' => $communityId]);
    }

    /**
     * 删除多个用户和多个小区的权限关系
     * @param $userIds
     * @param $communityIds
     */
    public function deleteUserCommunity($userIds, $communityIds = [])
    {
        $this->deleteUserCache($userIds);//删除缓存
        $condition = ['manage_id' => $userIds];
        if ($communityIds) {
            $condition['community_id'] = $communityIds;
        }
        PsUserCommunity::deleteAll($condition);
    }

    /**
     * 删除用户的小区权限
     * @param $userId
     */
    public function deleteUserCache($userIds)
    {
        $userIds = is_array($userIds) ? $userIds : [$userIds];
        foreach ($userIds as $userId) {
            Yii::$app->redis->del($this->_userCommunityCacheKey($userId));
        }
    }

    /**
     * 批量插入ps_user_community
     * @param $userId
     * @param $communityIds
     * @return bool
     */
    public function batchInsertUserCommunity($userId, $communityIds, $delete = true)
    {
        if ($delete) {//重新生成
            PsUserCommunity::deleteAll(['manage_id' => $userId]);
        }
        $data['manage_id'] = $userId;
        $data['community_id'] = (array)$communityIds;
        //删除缓存
        $this->deleteUserCache($userId);
        return PsUserCommunity::model()->batchInsert($data, true);
    }

    /**
     * 用户添加单个小区权限
     * @param $userId
     * @param $communityIds
     * @return bool
     */
    public function addUserCommunity($userId, $communityId)
    {
        $psUserCommunity = PsUserCommunity::find()->where(['manage_id' => $userId, 'community_id' => $communityId])->exists();
        if ($psUserCommunity) {
            return true;
        } else {
            $psUserCommunity = new PsUserCommunity();
            $psUserCommunity->manage_id = $userId;
            $psUserCommunity->community_id = $communityId;
            //删除缓存
            Yii::$app->redis->del($this->_userCommunityCacheKey($userId));
            return $psUserCommunity->save();
        }
    }

    /**
     * 用户小区权限缓存key
     * @param $userId
     * @return string
     */
    private function _userCommunityCacheKey($userId)
    {
        return 'lyl:userCommunitys:' . YII_ENV . ':' . $userId;
    }

    /**
     * 获取小区下拉选项(id, name)(唯一方法!!!)
     * 缓存30分钟，ps_user_community表变化的时候，会重新更新缓存
     * @param integer $userId
     * @param array $params
     * @return array
     */
    public function getUserCommunitys($userId)
    {
        $cackeKey = $this->_userCommunityCacheKey($userId);;
        if (!$data = Yii::$app->redis->get($cackeKey)) {
            if ($userId == 1) {//超级管理员，用来分配权限用的账号
                $data = PsCommunityModel::find()->select('id, name')
                    ->where(['status' => 1, 'comm_type' => 1])->orderBy('id desc')
                    ->asArray()->all();
            } else {
                $data = PsUserCommunity::find()->alias('t')
                    ->select('c.id, c.name')
                    ->leftJoin(['c' => PsCommunityModel::tableName()], 't.community_id=c.id')
                    ->where(['c.status' => 1, 'c.comm_type' => 1, 't.manage_id' => $userId])
                    ->orderBy('id desc')->asArray()->all();
            }
            if ($data) {//只有有数据的时候，才存缓存
                $data = json_encode($data);
                Yii::$app->redis->set($cackeKey, $data, 'EX', 1800);
            } else {
                return [];
            }
        }
        return json_decode($data, true);
    }

    /**
     * 获取用户有权限的小区ID数组
     * @param $userId
     * @return array
     */
    public function getUserCommunityIds($userId)
    {
        $communitys = $this->getUserCommunitys($userId);
        return array_column($communitys, 'id');
    }

    /**
     * 获取用户有权限的小区ID数组
     * @param $userId
     * @return array
     */
    public function getUserCommunityIdsStreet($userId, $community_name)
    {
        $communitys = $this->getUserCommunitys($userId);
        $cids = array();
        if (!empty($communitys)) {
            foreach ($communitys as $k => $v) {
                if (substr_count($v['name'], $community_name) > 0) {
                    array_push($cids, $v['id']);
                }
            }
        }
        return $cids;
    }

    /**
     * 用户是否有某小区权限
     * @param $userId
     * @param $communityId
     * @return boolean
     */
    public function communityAuth($userId, $communityId)
    {
        if (!$communityId || !$userId) {
            return false;
        }
        $communityIds = $this->getUserCommunityIds($userId);
        return in_array($communityId, $communityIds);
    }

    /*获取最高用户组包含的小区包含的小区*/
    public function getParnetCommunitys($propertyId, $systemType)
    {
        //管理员ID
        if ($systemType == 2) {
            $managerId = PsPropertyCompany::find()->select('user_id')->where(['id' => $propertyId])->scalar();
        } else {
            $managerId = PsAgent::find()->select('user_id')->where(['id' => $propertyId])->scalar();
        }
        if (!$managerId) {
            return [];
        }
        return $this->getUserCommunitys($managerId);
    }

    //小区logo
    public function getLogo($communityId)
    {
        return PsCommunityModel::find()->select('logo_url')->where(['id' => $communityId])
            ->asArray()->scalar();
    }

    //从缓存中获取房号ID
    public function getRoomId($communityId, $group, $building, $unit, $room)
    {
        $cacheKey = 'roomIds.' . $communityId;
        $data = $this->cache($cacheKey, 3600, function () use ($communityId) {
            $result = PsCommunityRoominfo::find()->select('id, group, building, unit, room')
                ->where(['community_id' => $communityId])
                ->asArray()->all();
            $data = [];
            foreach ($result as $v) {
                $data[$v['group']][$v['building']][$v['unit']][$v['room']] = $v['id'];
            }
            return $data;
        });
        if (!empty($data[$group][$building][$unit][$room])) {
            return $data[$group][$building][$unit][$room];
        }
        return false;
    }

    /**
     * 根据经纬度查询最近的一个小区及其服务
     * @param $reqArr
     * @return int
     */
    public function getAroundService($reqArr)
    {
        $longitude = $reqArr['longitude'];
        $latitude = $reqArr['latitude'];
        $sql = "SELECT `id`, `name`, (2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*({$longitude}-longitude)/360),2)+COS(PI()*{$latitude}/180)* COS(latitude * PI()/180)*POW(SIN(PI()*({$latitude}-latitude)/360),2)))) AS juli FROM `ps_community` 
ORDER BY juli ASC LIMIT 1";
        $community = Yii::$app->db->createCommand($sql)->queryOne();
        if (!$community) {
            return -1;
        }
        //查询生活号
        $psLifeService = PsLifeServices::find()
            ->select(['id'])
            ->where(['community_id' => $community['id']])
            ->asArray()
            ->one();
        if (!$psLifeService) {
            return -2;
        }

        //查询服务列表
        $serviceList = PsLifeServicesMenu::find()
            ->select(['service.name', 'service.img_url as iconUrl', 'ps_life_services_menu.link_url as gotoUrl'])
            ->leftJoin('ps_service service', 'ps_life_services_menu.service_id = service.id')
            ->where(['ps_life_services_menu.life_id' => $psLifeService['id']])
            ->andWhere(['service.name' => ['阳光公告', '社区指南', '联系物业']])
            ->asArray()
            ->all();
        if (count($serviceList) < 1) {
            return -2;
        }

        foreach ($serviceList as $key => $val) {
            if ($val['name'] == "联系物业") {
                $serviceList[$key]['gotoUrl'] = "tel://" . $val['gotoUrl'];
            } else {
                $serviceList[$key]['gotoUrl'] = $val['gotoUrl'] . "?from_type=1&comm_id=" . $community['id'];
            }
        }
        $re['name'] = $community['name'];
        $re['services'] = $serviceList;
        return $re;
    }

    /**
     * 查询支付宝账号对应的企业信息及授权token值
     * @param $reqArr
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getAlipayInfoByAccount($reqArr)
    {
        $account = $reqArr['account'];
        $company = PsPropertyCompany::find()
            ->select(['property_name', 'id as property_id', 'has_sign_qrcode'])
            ->where(['alipay_account' => $account])
            ->asArray()
            ->one();
        if ($company) {
            if ($company['has_sign_qrcode']) {
                $tokenInfo = PsPropertyIsvToken::find()
                    ->select(['token'])
                    ->where(['type_id' => $company['property_id']])
                    ->asArray()
                    ->one();
            } else {
                $tokenInfo = PsAliToken::find()
                    ->select(['token'])
                    ->where(['type_id' => $company['property_id']])
                    ->asArray()
                    ->one();
            }

            if ($tokenInfo) {
                $company['auth_token'] = $tokenInfo['token'];
            }
        }

        return $company;
    }

    /*
     * 根据支付宝小区ID获取基本信息
     * @param $communityNo
     */
    public function getInfoByNo($communityNo)
    {
        return PsCommunityModel::find()->alias('t')
            ->select('t.*, c.property_name as company_name, c.alipay_account as company_account, c.link_phone')
            ->leftJoin(['c' => PsPropertyCompany::tableName()], 't.pro_company_id=c.id')
            ->where(['t.community_no' => $communityNo])
            ->asArray()->one();
    }

    //获取生活号基本信息(生活号)
    public function getInfo($communityId)
    {
        return PsCommunityModel::find()
            ->select('id, name, phone, community_no')
            ->where(['id' => $communityId])
            ->asArray()->one();
    }

    //添加默认的报事报修类型
    private function addRepairType($communityId)
    {
        $typeModel = new PsRepairType();
        $typeModel->community_id = $communityId;
        $typeModel->name = '室内';
        $typeModel->level = 1;
        $typeModel->parent_id = 0;
        $typeModel->is_relate_room = 1;
        $typeModel->status = 1;
        $typeModel->created_at = time();
        $typeModel->save();

        $typeModel = new PsRepairType();
        $typeModel->community_id = $communityId;
        $typeModel->name = '公共区域';
        $typeModel->level = 1;
        $typeModel->parent_id = 0;
        $typeModel->is_relate_room = 0;
        $typeModel->status = 1;
        $typeModel->created_at = time();
        $typeModel->save();
    }

    //添加默认的社区公约
    public function addConvention($communityId)
    {
        $param['community_id'] = $communityId;
        CommunityConventionService::service()->addConvention($param);
//        $model = new PsCommunityConvention();
//        $model->community_id = $communityId;
//        $model->title = '社区公约';
//        $model->content = '<p>遵守行车秩序，礼让行人，禁止鸣笛；</p><p>按规定方向停车、不跨线、压线、不占用他人车位；</p><p>外出遛狗时，需佩戴牵引绳，并及时清理宠物的粪便保持公共场所的环境整洁，生活垃圾分类处理；</p><p>不往窗外抛洒物品、垃圾，不在窗台、阳台边缘放置易坠落物品；</p><p>在清晨和夜晚，主动将室内音量降低，不扰邻；亲友到访主动登记，出入社区谨防尾随，发现可疑情况及时告知物管人员；</p><p>组建社区群组，及时传达咨询，并积极开展社区活动；</p><p>不破坏绿化及公物，严禁群租，恪守社区功德；</p><p>关爱呵护孩子自尊，在公共场合避免责骂；</p><p>孝敬服务，关爱老人，主动为老人提供帮助；邻里之间发生矛盾，以和为贵，各自退让，及时化解。</p>';
//        $model->create_at = time();
//        $model->update_at = time();
//        $model->save();
    }

    //#####################社脑SN##########################

    /**
     * 添加小区
     * @author yjh
     * @param $data
     * @return array|bool
     * @throws MyException
     */
    public function addSnCommunity($data)
    {
        $data['build_time'] = !empty($data['build_time']) ? strtotime($data['build_time']) : '0';
        $data['delivery_time'] = !empty($data['delivery_time']) ? strtotime($data['delivery_time']) : '0';
        $data['acceptance_time'] = !empty($data['acceptance_time']) ? strtotime($data['acceptance_time']) : '0';
        $data['right_start'] = !empty($data['right_start']) ? strtotime($data['right_start']) : '0';
        $data['right_end'] = !empty($data['right_end']) ? strtotime($data['right_end']) : '0';
        $data['register_time'] = !empty($data['register_time']) ? strtotime($data['register_time']) : '0';
        if ($data['right_end'] < $data['right_start']) throw new MyException('产权终止时间不能小于起始时间');
        $data['status'] = 1;
        $community = new PsCommunityModel();
        $community->validParamArr($data,'create');

        //物业公司查询
        $psCommany = PsPropertyCompany::findOne($data['pro_company_id']);
        if (!$psCommany) {
            throw new MyException('此物业公司不存在！');
        }
        if (preg_match('/^\d*$/', $data['name'])) {
            throw new MyException('小区名称不能为纯数字！');
        }
        //判断小区是否已经存在
        $communityInfo = PsCommunityModel::find()->select(['id'])->where(['name' => $data['name']])->one();
        if ($communityInfo) {
            throw new MyException('小区已经存在，不能重复添加');
        }
        //参数补充
        $org_code = Department::find()->select('org_code')->where(['department_name' => $data['district_name'],'node_type' => 2])->one()['org_code'];
        $communityNo = $this->getCommunityNo($org_code);
        $community->community_no = $communityNo;
        $pinyin = new Pinyin();
        $province_name = PsAreaAli::find()->select('areaName')->where(['areaCode' => $data['province_code']])->one()['areaName'];
        $community->province = $province_name;
        $community->locations = $data['longitude'].'|'.$data['latitude'];
        $community->pinyin = $pinyin->pinyin($data['name'], true) ? strtoupper($pinyin->pinyin($data['name'], true)) : '#';
        $community->comm_type = '1';
        $community->area_sign = isset(self::$areaCode[$data['city_id']]) ? self::$areaCode[$data['city_id']] : '';
        if ($community->save()) {
            //添加默认报事报修类型
            $this->addRepairType($community->id);
            //添加默认社区公约
            $this->addConvention($community->id);
            return true;
        } else {
            $errorArr = array_values($community->getErrors());
            throw new MyException($errorArr[0][0]);
        }
    }

    /**
     * 编辑小区
     * @author yjh
     * @param $data
     * @return array|bool
     * @throws MyException
     */
    public function editSnCommunity($data)
    {
        $data['build_time'] = !empty($data['build_time']) ? strtotime($data['build_time']) : '0';
        $data['delivery_time'] = !empty($data['delivery_time']) ? strtotime($data['delivery_time']) : '0';
        $data['acceptance_time'] = !empty($data['acceptance_time']) ? strtotime($data['acceptance_time']) : '0';
        $data['right_start'] = !empty($data['right_start']) ? strtotime($data['right_start']) : '0';
        $data['right_end'] = !empty($data['right_end']) ? strtotime($data['right_end']) : '0';
        $data['register_time'] = !empty($data['register_time']) ? strtotime($data['register_time']) : '0';
        if ($data['right_end'] < $data['right_start']) throw new MyException('产权终止时间不能小于起始时间');
        if (empty($data['id'])) throw new MyException('小区ID不能为空');
        $community = PsCommunityModel::find()->where(['id' => $data['id']])->one();
        //判断小区是否已经存在
        if (!$community) {
            throw new MyException('小区ID错误');
        }
        $community->validParamArr($data,'edit');
        //物业公司查询
        $psCommany = PsPropertyCompany::findOne($data['pro_company_id']);
        if (!$psCommany) {
            throw new MyException('此物业公司不存在！');
        }
        if (preg_match('/^\d*$/', $data['name'])) {
            throw new MyException('小区名称不能为纯数字！');
        }
        //参数补充
        $pinyin = new Pinyin();
        $province_name = PsAreaAli::find()->select('areaName')->where(['areaCode' => $data['province_code']])->one()['areaName'];
        $community->province = $province_name;
        $community->locations = $data['longitude'].'|'.$data['latitude'];
        $community->pinyin = $pinyin->pinyin($data['name'], true) ? strtoupper($pinyin->pinyin($data['name'], true)) : '#';
        $community->comm_type = '1';
        $community->area_sign = isset(self::$areaCode[$data['city_id']]) ? self::$areaCode[$data['city_id']] : '';
        if ($community->save()) {
            return true;
        } else {
            $errorArr = array_values($community->getErrors());
            throw new MyException($errorArr[0][0]);
        }
    }

    /**
     * 修改状态
     * @author yjh
     * @param $data
     * @throws MyException
     */
    public function editSnCommunityStatus($data)
    {
        if (empty($data['id'])) throw new MyException('小区ID不能为空');
        $community = PsCommunityModel::find()->where(['id' => $data['id']])->one();
        //判断小区是否已经存在
        if (!$community) {
            throw new MyException('小区ID错误');
        }
        $community->status = $community->status == '1' ? '2' : '1';
        $community->save();
    }

    /**
     * 删除小区
     * @author yjh
     * @param $data
     * @throws Exception
     * @throws MyException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteSnCommunity($data)
    {
        if (empty($data['id'])) throw new MyException('小区ID不能为空');
        $community = PsCommunityModel::find()->where(['id' => $data['id']])->one();
        //判断小区是否已经存在
        if (!$community) {
            throw new MyException('小区ID错误');
        }
        $transaction = Yii::$app->getDb()->beginTransaction();
        try {
            PsRepairType::deleteAll(['community_id' => $community->id]);
            PsCommunityConvention::deleteAll(['community_id' => $community->id]);
            $community->delete();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new MyException($e->getMessage());
        }
    }

    public function getSnCommunityList($params)
    {
        if (!in_array($params['house_type'],[1,2,3]) && !empty($params['house_type'])) {
            throw new MyException('小区类型错误');
        }
        return PsCommunityModel::getList($params);
    }

    public function getSnCommunityInfo($data)
    {
        if (empty($data['id'])) throw new MyException('小区ID不能为空');
        $community = PsCommunityModel::find()->where(['id' => $data['id']])->asArray()->one();
        //判断小区是否已经存在
        if (!$community) {
            throw new MyException('小区ID错误');
        }
        $city =  PsAreaAli::find()->where(['areaCode' => $community['city_id']])->asArray()->one();
        $district =  PsAreaAli::find()->where(['areaCode' => $community['district_code']])->asArray()->one();
        $community['province_name'] = $community['province'];
        $community['city_name'] = $city['areaName'];
        $community['area_name'] = $district['areaName'];
        $community['build_time'] = !empty($community['build_time']) ? date('Y-m-d',$community['build_time']) : '无';
        $community['delivery_time'] = !empty($community['delivery_time']) ? date('Y-m-d',$community['delivery_time']) : '无';
        $community['acceptance_time'] = !empty($community['acceptance_time']) ? date('Y-m-d',$community['acceptance_time']) : '无';
        $community['right_start'] = !empty($community['right_start']) ? date('Y-m-d',$community['right_start']) : '无';
        $community['right_end'] = !empty($community['right_end']) ? date('Y-m-d',$community['right_end']) : '无';
        $community['register_time'] = !empty($community['register_time']) ? date('Y-m-d',$community['register_time']) : '无';
        $community['house_type_desc'] = PsCommunityModel::$house_type_desc[$community['house_type']];
        $community['company_name'] = PsPropertyCompany::find()->where(['id' => $community['pro_company_id']])->one()['property_name'];
        unset($community['province']);
        return $community;
    }

    /**
     * 获取小区编码
     * @author yjh
     * @param $org_code
     * @return string
     * @throws MyException
     */
    public function getCommunityNo($org_code)
    {
        $redis = Yii::$app->redis;
        $num = $redis->get($org_code);
        if(!$num){
            //如果redis数据丢失，需要从mysql重新获取
            $community = PsCommunityModel::find()->where(['like', 'community_no', $org_code.'%', false])->orderBy('community_no desc')->one();
            if (!$community) {
                $redis->set($org_code, 1);
                $num = 1;
            } else {
                $num = substr($community->community_no,-4)+1;
                $redis->set($org_code, $num);
            }
        } else {
            $redis->incr($org_code);
            $num = $num+1;
        }
        $len = strlen($num);
        if ($len == 3) {
            $no = $num;
        } else if($len < 3) {
            $no = str_repeat('0',3 - $len).$num;
        } else {
            throw new MyException('小区编码超出范围');
        }
        return $org_code.'00000'.$no;
    }

    public function getCommunityIdByCode($code)
    {
       $code = PsCommunityModel::find()->select('id')->where(['event_community_no' => $code])->one()['id'];
       if (!$code) {
           throw new MyException('小区code错误');
       }
       return $code;
    }


}
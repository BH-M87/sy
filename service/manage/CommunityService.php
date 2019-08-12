<?php
/**
 * Created by PhpStorm.
 * User: wenchao.feng
 * Date: 2017/3/6
 * Time: 19:32
 */

namespace app\modules\property\services;

use app\common\core\F;
use app\common\core\PsCommon;
use app\common\core\Client;
use app\common\core\Pinyin;
use app\modules\alipay\services\AliCommunityService;
use app\modules\property\services\TemplateService;
use app\modules\property\models\PsAgent;
use app\modules\property\models\PsAliToken;
use app\modules\property\models\PsLifeServices;
use app\modules\property\models\PsLifeServicesMenu;
use app\modules\property\models\PsPropertyIsvToken;
use app\modules\property\models\PsRepairType;
use app\modules\qiniu\services\UploadService;
use app\services\AreaService;
use app\services\QrcodeService;
use Yii;
use app\modules\property\models\BillFrom;
use app\modules\property\models\PsBill;
use app\modules\property\models\PsCommunityRoominfo;
use app\modules\property\models\PsHouseForm;
use app\modules\property\models\PsUserCommunity;
use app\modules\alipay\services\AlipayBillService;
use app\modules\property\models\PsCommunityModel;
use app\modules\property\models\PsCommunityOpenService;
use app\modules\property\models\PsPropertyCompany;
use yii\db\Exception;
use yii\db\Query;
use yii\helpers\FileHelper;

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

        if (preg_match('/^\d*$/',$data['name'])) {
            return $this->failed('小区名称不能为纯数字！');
        }

        //判断小区是否已经存在
        $community = PsCommunityModel::find()
            ->select(['id'])
            ->where(['name' => $data['name']])
            ->one();
        if ($community) {
            return $this->failed('小区已经存在，不能重复添加');
        }

        //参数判断
        $data['house_id'] = "";

        //经纬度转换
        $data['locations'] = self::getLonLat($data['province_code'], $data['city_id'], $data['district_code'], $data['address']);

        if (!$data['locations']) {
            return $this->failed('经纬度转换失败，请重新填写小区地址');
        }

        //将小区同步到支付宝
        if ($data['comm_type'] == 2 || $data['pro_company_id']==321) {//不是南京物业则发布到支付宝:19-4-27陈科浪修改
            $re['code'] = 10000;
        } else {
            //edit by wenchao.feng 测试环境不走支付宝沙箱环境创建小区（总提示经纬度错误）
            if (YII_ENV == 'master' || YII_ENV == 'release') {
                $aliCommunityReqData = $this->processCommunityData($data);
                $re = AliCommunityService::service()->init($data['pro_company_id'])->addCommunity($aliCommunityReqData);
            } else {
                $re['code'] = 10000;
                $re['community_id'] = PsCommon::getNoRepeatChar('', YII_ENV.'communityUniqueList', 13);
                $re['next_action'] = 'WAIT_SERVICE_PROVISION|INVOKER';
                $re['status'] = 'PENDING_ONLINE';
            }
        }
        if ($re['code'] == 10000) {
            //小区同步成功
            $data['create_at'] = time();
            $data['is_init_service'] = 0;
            $pinyin = new Pinyin();
            $community = new PsCommunityModel();
            if ($data['comm_type'] == 1) {
                $communityNo = $re['community_id'];
            } else {
                $today = date("Ymd", time());
                $communityNo = $today . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
            }
            $locationArr = explode('|', $data['locations']);

            $community->community_no = $communityNo;
            $community->province_code = $data['province_code'];
            $community->city_id = $data['city_id'];
            $community->district_code = $data['district_code'];
            $community->pro_company_id = $data['pro_company_id'];
            $community->name = $data['name'];
            $community->group = !empty($data['group']) ? $data['group'] : '';
            $community->locations = $data['locations'];
            $community->address = $data['address'];
            $community->phone = $data['phone'];
            $community->is_init_service = 0;
            $community->pinyin = $pinyin->pinyin($data['name'], true) ? strtoupper($pinyin->pinyin($data['name'], true)) : '#';
            $community->status = 1;
            $community->comm_type = $data['comm_type'];
            $community->house_type = $data['house_type'];
            $community->house_id = $data['house_id'] ? $data['house_id'] : '';
            $community->area_sign = isset(self::$areaCode[$data['city_id']]) ? self::$areaCode[$data['city_id']] : '';
            $community->ali_next_action = !empty($re['next_action']) ? $re['next_action'] : 'WAIT_SERVICE_PROVISION|INVOKER';
            $community->ali_status = !empty($re['status']) ? $re['status'] : 'PENDING_ONLINE';
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
                $errorArr = array_values($community->getErrors());
                return $this->failed($errorArr[0][0]);
            }
        } else {
            return $this->failed($re['sub_msg']);
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

        if (preg_match('/^\d*$/',$data['name'])) {
            echo json_encode(['code' => 50001, 'data' => [], 'error' => ['errorMsg' => '小区名称不能为纯数字！']], JSON_PRETTY_PRINT);
            exit;
        }

        //判断小区名称是否重复
        $communityRe = PsCommunityModel::find()
            ->select(['id'])
            ->where(['name' => $data['name']])
            ->andWhere(['!=', 'id', $data['id']])
            ->one();
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

        //将小区同步到支付宝
        if ($data['comm_type'] == 2) {
            $re['code'] = 10000;
        } else {
            if (YII_ENV == "master" && $community->community_no) {
                $aliCommunityReqData = $this->processCommunityData($data);
                $aliCommunityReqData['community_id'] = $data['community_id'];
                $re = AliCommunityService::service()->init($data['pro_company_id'])->editCommunity($aliCommunityReqData);
            } else {
                $re['code'] = 10000;
            }
        }
        if ($re['code'] == 10000) {
            $oldCompanyId = $community->pro_company_id;

            $pinyin = new Pinyin();
            //小区同步成功
            $locationArr = explode('|', $data['locations']);
            $community->province_code = $data['province_code'];
            $community->city_id = $data['city_id'];
            $community->district_code = $data['district_code'];
            $community->pro_company_id = $data['pro_company_id'];
            $community->name = $data['name'];
            $community->group = !empty($data['group']) ? $data['group'] : '';
            $community->locations = $data['locations'];
            $community->address = $data['address'];
            $community->phone = $data['phone'];
            $community->status = $data['status'];
            $community->comm_type = $data['comm_type'];
            $community->house_type = $data['house_type'];
            $community->house_id = $data['house_id'];
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
        } else {
            echo json_encode(['code' => 50001, 'data' => [], 'error' => ['errorMsg' => $re['sub_msg']]], JSON_PRETTY_PRINT);
            exit;
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
     * 给小区开通服务
     * @param $communityId
     * @param $serviceIds
     * @return bool
     */
    public function openServiceToCommunity($communityId, $serviceIds)
    {
        $service_names = "";
        if ($serviceIds && is_array($serviceIds)) {
            //删除之前的服务
            PsCommunityOpenService::deleteAll("community_id = {$communityId}");
            foreach ($serviceIds as $service) {
                $service_name = Yii::$app->db->createCommand("SELECT name FROM ps_service where id = :id")
                    ->bindValue(':id', $service)
                    ->queryScalar();
                if (!$service_name) {
                    continue;
                }
                $communityOpenService = new PsCommunityOpenService();
                $communityOpenService->service_id = $service;
                $communityOpenService->community_id = $communityId;
                $communityOpenService->service_name = $service_name;
                $communityOpenService->create_at = time();
                $communityOpenService->save();
                $service_names .= $service_name . ',';
            }
        }
        return $service_names;
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
     * 小区初始化服务
     * @param $communityId
     * @return bool
     */
    public function communityInitService($communityId)
    {
        //查询小区是否存在
        $psCommunity = PsCommunityModel::find()->where(['id' => $communityId, 'status' => '1'])
            ->asArray()
            ->one();
        if (!$psCommunity) {
            echo json_encode(['code' => 50001, 'data' => [], 'error' => ['errorMsg' => '小区不存在']], JSON_PRETTY_PRINT);
            exit;
        }

        //如果小区未调用支付宝创建接口，需要先调用创建接口
        if (!$psCommunity['community_no'] || !$psCommunity['ali_next_action']) {
            $addToAliPayReq = [
                'pro_company_id' => $psCommunity['pro_company_id'],
                'name' => $psCommunity['name'],
                'address' => $psCommunity['address'],
                'district_code' => $psCommunity['district_code'],
                'city_id' => $psCommunity['city_id'],
                'province_code' => $psCommunity['province_code'],
                'locations' => $psCommunity['locations'],
                'phone' => $psCommunity['phone']
            ];

            $aliCommunityReqData = $this->processCommunityData($addToAliPayReq);
            $addToAliPayRes = AliCommunityService::service()->init($psCommunity['pro_company_id'])->addCommunity($aliCommunityReqData);
            if ($addToAliPayRes['code'] == 10000) {
                $communityNo = $addToAliPayRes['community_id'];
                $aliNextAction = !empty($addToAliPayRes['next_action']) ? $addToAliPayRes['next_action'] : self::ACTION_WAIT_SERVICE;
                $aliStatus = !empty($addToAliPayRes['status']) ? $addToAliPayRes['status'] : 'PENDING_ONLINE';
                $psCommunityModel = PsCommunityModel::findOne($communityId);
                $psCommunityModel->community_no = $communityNo;
                $psCommunityModel->ali_next_action = $aliNextAction;
                $psCommunityModel->ali_status = $aliStatus;
                $psCommunityModel->save();
            } else {
                echo json_encode(['code' => 50001,
                    'data' => [],
                    'error' => ['errorMsg' => $addToAliPayRes['sub_msg']]],
                    JSON_PRETTY_PRINT);
                exit;
            }
        }

        //查询小区是否已经被初始化
        if ($psCommunity['is_init_service'] == 1) {
            echo json_encode([
                'code' => 50001,
                'data' => [],
                'error' => ['errorMsg' => '小区已初始化成功']],
                JSON_PRETTY_PRINT);
            exit;
        }

        //查询小区状态
        if ($psCommunity['ali_next_action'] != self::ACTION_WAIT_SERVICE) {
            echo json_encode([
                'code' => 50001,
                'data' => [],
                'error' => ['errorMsg' => '小区状态不正确']],
                JSON_PRETTY_PRINT);
            exit;
        }

        //初始化服务
        $initServiceReq['community_id'] = $psCommunity['community_no'];
        $initServiceReq['service_type'] = 'PROPERTY_PAY_BILL_MODE';
        $initServiceReq['external_invoke_address'] = Yii::$app->params['external_invoke_address'];
        $re = AliCommunityService::service()->init($psCommunity['pro_company_id'])->initBaseService($initServiceReq);
        if ($re !== false && $re['code'] == '10000') {
            $psCommunityModel = PsCommunityModel::findOne($communityId);
            $psCommunityModel->is_init_service = 1;
            $psCommunityModel->ali_status = $re['status'];
            $psCommunityModel->ali_next_action = $re['next_action'];
            $psCommunityModel->bill_pay_auth_url = !empty($re['bill_pay_auth_url']) ? $re['bill_pay_auth_url'] : '';
            $psCommunityModel->save();

            //查看小区信息
            $re = AliCommunityService::service()->init($psCommunity['pro_company_id'])->communityInfo(['community_id' => $psCommunity['community_no']]);
            if ($re !== false && $re['code'] == '10000' && !empty($re['community_services'])) {
                $commnuityServices = $re['community_services'][0];
                $psCommunityModel->qr_code_type = !empty($commnuityServices['qr_code_type']) ? $commnuityServices['qr_code_type'] : '';
                $psCommunityModel->qr_code_image = !empty($commnuityServices['qr_code_image']) ? $commnuityServices['qr_code_image'] : '';
                $psCommunityModel->qr_code_expires = !empty($commnuityServices['qr_code_type']) ? strtotime($commnuityServices['qr_code_type']) : 0;
                if ($psCommunityModel->qr_code_image) {
                    $psCommunityModel->has_ali_code = 1;
                }
                $psCommunityModel->save();
            }
            return true;
        }

        return false;
    }

    /**
     * 小区申请支付宝上线
     * @param $communityId
     * @return bool
     */
    public function communityOnlineApply($communityId)
    {
        //查询小区是否存在
        $psCommunity = PsCommunityModel::find()->where(['id' => $communityId, 'status' => '1'])->asArray()->one();
        if (!$psCommunity) {
            echo json_encode(['code' => 50001, 'data' => [], 'error' => ['errorMsg' => '小区不存在']], JSON_PRETTY_PRINT);
            exit;
        }

        //查询小区状态
        if ($psCommunity['ali_next_action'] != self::ACTION_WAIT_PRO_VER && $psCommunity['ali_next_action'] != self::ACTION_WAIT_ONLINE) {
            echo json_encode(['code' => 50001, 'data' => [], 'error' => ['errorMsg' => '小区状态不正确']], JSON_PRETTY_PRINT);
            exit;
        }

        //小区上线
        $aliCommunityReqData['community_id'] = $psCommunity['community_no'];
        $aliCommunityReqData['service_type'] = 'PROPERTY_PAY_BILL_MODE';
        $aliCommunityReqData['status'] = 'ONLINE';
        $aliCommunityReqData['external_invoke_address'] = Yii::$app->params['external_invoke_address'];
        $re = AliCommunityService::service()->init($psCommunity['pro_company_id'])->baseServiceModify($aliCommunityReqData);
        //支付宝新的小区不上线
        if ($re !== false && $re['code'] == '10000') {
            $psCommunity = PsCommunityModel::findOne($communityId);
            $psCommunity->is_apply_online = 1;
            $psCommunity->ali_status = 'ONLINE';
            $psCommunity->ali_next_action = $re['next_action'];
            $psCommunity->save();

            //默认开通临停服务
            $tmpService = PsCommunityOpenService::find()
                ->where(['service_id' => Yii::$app->params['park_service_id'], 'community_id' => $communityId])
                ->one();
            if (!$tmpService) {
                $communityOpenService = new PsCommunityOpenService();
                $communityOpenService->service_id = Yii::$app->params['park_service_id'];
                $communityOpenService->community_id = $communityId;
                $communityOpenService->service_name = '临时停车';
                $communityOpenService->create_at = time();
                $communityOpenService->save();
            }

            return true;
        }

        return false;
    }

    /**
     * 给小区添加一条房屋信息
     * @param $communityId
     * @return array
     */
    public function batchRoomInfo($communityId)
    {
        //查询小区是否存在
        $psCommunity = PsCommunityModel::findOne($communityId);
        if (!$psCommunity) {
            echo json_encode(['code' => 50001, 'data' => [], 'error' => ['errorMsg' => '小区不存在']], JSON_PRETTY_PRINT);
            exit;
        }
        //测试数据
        $testData = ['group' => '欢乐颂', 'building' => '999幢', 'unit' => '999单元', 'room' => '999室',
            'charge_area' => '999', 'status' => '1', 'property_type' => '1',
            'community_id' => $communityId, 'intro' => ''
        ];

        $model = new PsHouseForm;
        $model->setScenario('import');
        $model->load($testData, '');
        if (!$model->validate()) {
            return $this->failed($this->getError($model));
        }
        $roomRe = PsCommunityRoominfo::find()
            ->where(['community_id' => $communityId, 'group' => $testData['group'], 'building' => $testData['building'],
                'unit' => $testData['unit'], 'room' => $testData['room']])
            ->asArray()
            ->one();
        if ($roomRe) {
            return $this->success();
        }
        $outRoomStr = '';
        preg_match_all("/[A-Za-z0-9]+/", $outRoomStr, $address_arr);
        $testData['out_room_id'] = date('YmdHis', time()) . $communityId . implode("", $address_arr[0]) . rand(1000, 9999);
        $testData['address'] = $testData['group'] . $testData['building'] . $testData['unit'] . $testData['room'];
        $testData['create_at'] = time();

        //存入房屋信息
        $psRoominfos = new PsCommunityRoominfo();
        $psRoominfos->load($testData, '');
        if ($psRoominfos->save()) {
            //同步房屋到支付宝
            $batch_id = date("YmdHis", time()) . '1' . rand(1000, 9000);
            $data = [
                'batch_id' => $batch_id,
                'community_id' => !empty($psCommunity->community_no) ? $psCommunity->community_no : '',
                'room_info_set' => [[
                    "out_room_id" => $testData['out_room_id'],
                    'group' => $testData['group'],
                    'building' => $testData['building'],
                    "unit" => $testData['unit'],
                    'room' => $testData['room'],
                    'address' => $testData['address'],
                ]]
            ];
            HouseService::service()->uploadRoominfo($data);
            return $this->success();
        } else {
            return $this->failed($this->getError($psRoominfos));
        }
    }

    /**
     * 给小区添加账单测试数据
     * @param $communityId
     * @return array
     */
    public function addTestBill($communityId)
    {
        //查询小区是否存在
        $community_info = $this->communityShow($communityId);
        if (!$community_info) {
            return $this->failed('未找到小区信息');
        }
        $testRoom = ['group' => '欢乐颂', 'building' => '999幢', 'unit' => '999单元', 'room' => '999室', 'community_id' => $communityId];
        $ps_room = RoomService::service()->getRoom($testRoom);
        if (!$ps_room) {
            return $this->failed('测试房屋不存在');
        }
        //查询测试房屋下是否已经存在账单
        $flag = PsBill::find()
            ->where(['community_id' => $communityId, 'out_room_id' => $ps_room["out_room_id"], 'is_del' => '1'])
            ->andWhere(['bill_entry_amount' => 0.01])
            ->andWhere(['cost_type' => 1])->exists();
        if ($flag) {
            return $this->failed('已有测试账单，无需重复添加');
        }
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            $bill_entry_id = date('YmdHis', time()) . '1' . rand(1000, 9999);
            $billData = [
                'community_id' => $communityId,
                'group' => $testRoom['group'],
                'building' => $testRoom['building'],
                'unit' => $testRoom['unit'],
                'room' => $testRoom['room'],
                'area' => '999',
                'bill_entry_id' => $bill_entry_id,
                'community_name' => $community_info['name'],
                'out_room_id' => $ps_room["out_room_id"],
                'charge_area' => $ps_room["charge_area"],
                'property_type' => $ps_room['property_type'],
                'address' => $ps_room['address'],
                'release_day' => date('Ymd'),
                'deadline' => '20991231',
                'cost_id' => 1,
                'cost_type' => 1,
                'cost_name' => '物业管理费',
                'property_company' => $community_info['company_name'],
                'property_account' => $community_info['company_account'],
                'status' => 3,
                'bill_entry_amount' => 0.01,
                'create_at' => time(),
                'acct_period_start' => 1293811200,
                'acct_period_end' => 1293811200,
                'task_id' => 0, 'order_id' => 0,
                'company_id' => $community_info['pro_company_id'],
                'room_id' => $ps_room["id"]
            ];
            //存入房屋信息
            $psBillinfos = new PsBill();
            $psBillinfos->load($billData, '');
            if (!$psBillinfos->save()) {
                throw new Exception($this->getError($psBillinfos));
            }

            //新增订单
            $orderData = [
                "bill_id" => $psBillinfos->id,
                "company_id" => $billData["company_id"],
                "community_id" => $billData["community_id"],
                "order_no" => F::generateOrderNo(),
                "product_id" => 1,
                "product_type" => 1,
                "product_subject" => '物业管理费',
                "bill_amount" => 0.01,
                "pay_amount" => 0.01,
                "status" => "3",
                "pay_status" => "0",
                "create_at" => time(),
            ];
            $orderResult = OrderService::service()->addOrder($orderData);
            if ($orderResult["code"]) {
                //更新账单表的订单id字段
                PsBill::updateAll(['order_id' => $orderResult['data']], ['id' => $psBillinfos->id]);
            } else {
                throw new Exception($orderResult['msg']);
            }
            //发布到支付宝
            $batch_id = date("YmdHis", time()) . '2' . rand(1000, 9999);
            $bill_set[0] = [
                "bill_entry_id" => $billData["bill_entry_id"],
                "out_room_id" => $billData["out_room_id"],
                "cost_type" => $billData["cost_name"],
                "room_address" => $billData["address"],
                "acct_period" => '20110101-20110101',
                'bill_entry_amount' => $billData["bill_entry_amount"],
                "release_day" => $billData["release_day"],
                "deadline" => $billData["deadline"],
                "remark_str" => "zjy753",
            ];

            $data = [
                "batch_id" => $batch_id,
                "community_id" => $community_info["community_no"],
                "bill_set" => $bill_set,
            ];
            $token = AliTokenService::service()->getTokenByCompany($community_info["pro_company_id"]);
            $r = AlipayBillService::service($community_info["community_no"])->batchBill($token, $data);
            if (!$r['code']) {
                throw new Exception('上传支付宝错误：' . $r['msg']);
            }
            $batchId = !empty($r['data']['batch_id']) ? $r['data']['batch_id'] : '';
            if ($batchId) {
                $psBillModel = PsBill::findOne($psBillinfos->id);
                $psBillModel->batch_id = $batchId;
                $psBillModel->status = 1;
                $psBillModel->save();
            }
            $trans->commit();
            return $this->success();
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /**
     * 生成小区二维码图片，并保存到七牛
     * @param string $savePath 图片保存路径
     * @param string $url 二维码对应的URL地址
     * @param string $commId 小区id
     * @param string $logoUrl 小区logo图片地址
     * @return string
     */
    public function generateCommCodeImage($savePath, $url, $commId, $logoUrl, $commObject = null)
    {
        $imgUrl = "";

        //设置上传路径
        if (!file_exists($savePath)) {
            FileHelper::createDirectory($savePath, 0755, true);
        }

        $img_name = $commId . '.png';
        if (!$logoUrl) {
            $logoUrl = Yii::$app->basePath . '/web/img/alilogo.png';
        }
        //生成一个二维码图片
        QrcodeService::service()->png($url, $savePath . $img_name, QR_ECLEVEL_H, '100')->withLogo($logoUrl);

        if (file_exists($savePath . $img_name)) {
            chmod($savePath . $img_name, 0755);
            //图片上传到七牛
            $key_name = md5(uniqid(microtime(true), true)) . '.png';
            $new_file = $savePath . $img_name;
            $imgUrl = UploadService::service()->saveQiniu($key_name, $new_file);
        }

        if ($imgUrl && $commObject) {
            $commObject->code_image = $imgUrl;
            $commObject->save();
        }

        return $imgUrl;
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
     * 2016-12-15
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
            $list[$key]['house_type_name'] = !empty($val['house_type'])?PsCommon::getHouseType($val['house_type']):'';
            if ($val['status'] == 1) {
                if ($val['ali_status'] == "PENDING_ONLINE") {
                    $list[$key]['comm_status_name'] = "待上线";
                } else if ($val['ali_status'] == "ONLINE") {
                    $list[$key]['comm_status_name'] = "已上线";
                }
            } else {
                $list[$key]['comm_status_name'] = "已下线";
            }
        }
        return ['list' => $list, 'totals' => $totals];
    }

    /**
     * 2016-12-15
     * 查看小区
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
     * 查询小区详情，包含生活号二维码
     * @param $id
     * @return array|false
     * @throws \yii\db\Exception
     */
    public function getShowCommunityInfo($id)
    {
        $db = Yii::$app->db;
        $param = [":community_id" => $id];
        $sql = "select community_no,name,logo_url,code_image from ps_community where id=:community_id ";
        $model = $db->createCommand($sql, $param)->queryOne();
        if (!empty($model)) {
            $sql = "select code_image from ps_life_services where community_id=:community_id ";
            $code_img = $db->createCommand($sql, $param)->queryColumn();
            $model["code_image"] = !empty($code_img) ? $code_img : $model["code_image"];
        }
        return $model;
    }

    /**
     * 根据小区id查询对应的生活号信息
     * @param $id
     * @return array|false
     * @throws \yii\db\Exception
     */
    public function getShowLifeInfo($id)
    {
        $db = Yii::$app->db;
        $param = [":community_id" => $id];
        $sql = "select logo as logo_url,code_image from ps_life_services where community_id=:community_id ";
        $model = $db->createCommand($sql, $param)->queryOne();
        return $model;
    }

    /**
     * 2016-12-15
     * 上线下线小区
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
        $cackeKey = $this->_userCommunityCacheKey($userId);
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
        if (!empty($communitys)) {foreach ($communitys as $k => $v) {
            if(substr_count($v['name'],$community_name) > 0){
                array_push($cids, $v['id']);
            }
        }}
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

    /**
     * 将小区的数据处理成在支付宝创建小区需要的数据
     * @param $data
     * @return array
     */
    private function processCommunityData($data)
    {
        $reqData = [];
        $reqData['community_name'] = $data['name'];
        $reqData['community_address'] = $data['address'];

        //区域传入特殊处理,例如海南省万宁市万城镇数据处理
        $reqData['district_code'] = $data['district_code'];
        $area = AreaService::service()->load($data['district_code']);
        if ($area && $area['areaType'] == 5) {
            $reqData['district_code'] = $data['city_id'];
        }

        $reqData['city_code'] = $data['city_id'];
        $reqData['province_code'] = $data['province_code'];

        $reqData['community_locations'] = explode(",", $data['locations']);
        $reqData['hotline'] = $data['phone'];

        return $reqData;
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


    //获取生活号基本信息(生活号)
    public function getInfo($communityId)
    {
        return PsCommunityModel::find()
            ->select('id, name, phone, community_no')
            ->where(['id' => $communityId])
            ->asArray()->one();
    }
}
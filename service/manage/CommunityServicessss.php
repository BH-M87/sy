<?php
/**
 * Created by PhpStorm.
 * User: wenchao.feng
 * Date: 2017/3/6
 * Time: 19:32
 */

namespace service\manage;

use service\BaseService;

use app\modules\property\services\TemplateService;
use app\modules\property\models\PsAgent;
use app\modules\property\models\PsRepairType;
use app\modules\qiniu\services\UploadService;

use app\services\QrcodeService;
use app\services\AreaService;
use app\models\PsUserCommunity;
use app\models\PsCommunityModel;
use app\models\PsCommunityOpenService;
use app\models\PsPropertyCompany;

use common\core\F;
use common\core\PsCommon;
use common\core\Pinyin;
use common\core\Client;
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
        $data['create_at'] = time();
        $data['is_init_service'] = 0;
        $pinyin = new Pinyin();
        $community = new PsCommunityModel();
        if ($data['comm_type'] == 1) {
            $communityNo = PsCommon::getNoRepeatChar('', YII_ENV.'communityUniqueList', 13);
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
        $community->ali_next_action =  '';
        $community->ali_status =  '';
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

}
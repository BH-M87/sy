<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2019-8-29
 * Time: 17:00
 */
namespace service\door;

use app\models\PsAppUser;
use service\BaseService;
use Yii;

class AppUserService extends BaseService
{
    //支付宝网关地址
    const ALI_GATEWAY_URL = "https://openapi.alipay.com/gateway.do";
    //小程序ID
    const APPID           = "2017011305044290";
    //验签方式
    const SIGN_TYPE       = "RSA2";
    //昵称编号前缀
    const PRE_NICKNAME    = "shequ_17";
    //租客端昵称前缀
    const PRE_TENANT_NAME = 'u_';
    //业主已认证
    const ROOM_HASCERTIFIY = 2;
    //业主未认证
    const ROOM_UNCERTIFIY  = 1;


    /**
     * 支付宝小程序用户认证
     * @param $authCode    授权码
     * @param $communityId 小区id
     * @return array
     */
    public static function aliAuth($authCode, $communityId) {
        $re = [];
        //调用支付宝接口，开始认证
        $rsaPrivateKeyFile      = Yii::$app->basePath."/modules/webapp/rsa_file/dev/rsa_private_key.txt";
        $alipayRsaPublicKeyFile = Yii::$app->basePath."/modules/webapp/rsa_file/dev/alipay_public_key.txt";
        $rsaPrivateKey          = file_get_contents($rsaPrivateKeyFile);
        $alipayRsaPublicKey     = file_get_contents($alipayRsaPublicKeyFile);
        if ($rsaPrivateKey === false) {
            return false;
        }
        if ($alipayRsaPublicKey === false) {
            return false;
        }

        $aop                     = new AopClient();
        $aop->gatewayUrl         = self::ALI_GATEWAY_URL;
        $aop->appId              = self::APPID;
        $aop->rsaPrivateKey      = $rsaPrivateKey;
        $aop->format             = "json";
        $aop->charset            = "UTF-8";
        $aop->signType           = self::SIGN_TYPE;
        $aop->alipayrsaPublicKey = $alipayRsaPublicKey;

        //获取oauth_token
        $request = new AlipaySystemOauthTokenRequest();
        $request->setCode($authCode);
        $request->setGrantType("authorization_code");
        $response = $aop->execute($request);
        if (!$response) {
            return false;
        }

        $resArr = json_decode(json_encode($response),true);
        if(!empty($resArr['error_response'])){
            return $resArr['error_response']['sub_msg'];
        }

        $access_token   = $resArr['alipay_system_oauth_token_response']['access_token'];
        $alipay_user_id = $resArr['alipay_system_oauth_token_response']['alipay_user_id'];
        $refresh_token  = $resArr['alipay_system_oauth_token_response']['refresh_token'];
        $user_id        = $resArr['alipay_system_oauth_token_response']['user_id'];
        $expires_in     = $resArr['alipay_system_oauth_token_response']['expires_in'];
        $sign           = $resArr['sign'];

        //获取用户信息
        if ($access_token) {
            $requestUserInfo = new AlipayUserInfoShareRequest();
            //授权类接口执行API调用时需要带上accessToken
            $responseUser = $aop->execute($requestUserInfo,$access_token);
            if (!$responseUser) {
                return false;
            }

            $resUserArr = json_decode(json_encode($responseUser),true);
            if (!empty($resUserArr['alipay_user_info_share_response']['sub_msg'])) {
                return $resUserArr['alipay_user_info_share_response']['sub_msg'];
            }

            $simpleRes = $resUserArr['alipay_user_info_share_response'];
            $avatar    = $simpleRes['avatar'];
            $gender    = $simpleRes == "f" ? 2 : 1;

            //系统生成昵称
            $setNo     = 0;
            if (empty($simpleRes['nickname'])) {
                $reNickName = self::generalNickName();
                $nickname   = $reNickName['nick_name'];
                $setNo      = $reNickName['set_no'];
            } else {
                $nickname   = $simpleRes['nickname'];
            }

            //存入数据库
            //查询是否存在
            $appUser = PsAppUser::find()->where(['channel_user_id' => $user_id])->one();
            if (!$appUser) {
                $appUser = new PsAppUser();
            }
            $appUser->nick_name       = $nickname;
            $appUser->phone           = "";
            $appUser->user_type       = 1;
            $appUser->user_ref        = 2;
            $appUser->access_token    = $access_token;
            $appUser->expires_in      = $expires_in;
            $appUser->refresh_token   = $refresh_token;
            $appUser->channel_user_id = $user_id;
            $appUser->ali_user_id     = $alipay_user_id ? $alipay_user_id : '';
            $appUser->avatar          = $avatar;
            $appUser->gender          = $gender;
            $appUser->sign            = $sign;
            $appUser->set_no          = $setNo;
            $appUser->create_at       = time();
            if (!$appUser->save()) {
                return false;
            }

            $re['app_user_id']     = $appUser->id;
            $re['nick_name']       = $nickname;
            $re['access_token']    = $access_token;
            $re['channel_user_id'] = $user_id;
            $re['avatar']          = $avatar;
        }
        return $re;
    }

    /**
     * 保存用户信息
     * @param $data
     * @return bool
     */
    public static function saveAppUser($data,$type = '1')
    {
        //存入数据库
        $appUser = PsAppUser::find()->where(['channel_user_id' => $data['user_id'], 'user_type' => 1])->one();
        if ($appUser) {

            if (!empty($data['id_card'])) {
                $appUser->id_card = $data['id_card'];
            }

            if (!empty($data['phone'])) {
                $appUser->phone = $data['phone'];
            }

            if (!empty($data['true_name'])) {
                $appUser->true_name = !empty($appUser->true_name) ? $appUser->true_name : $data['true_name'];
            }

            if (!empty($data['nickName'])) {
                $appUser->nick_name = $data['nickName'];
            }

            if (!empty($data['is_certified'])) {
                $appUser->is_certified = $data['is_certified'] == "T" ? 1 : 2; // 是否通过实名认证。T是通过 F是没有实名认证。1通过 2未通过
            }

            if (!empty($data['avatar'])) {
                $appUser->avatar = !empty($data['avatar']) ? $data['avatar'] : '';
            }
            
            if (!empty($data['token_type'])) { // 邻易联小程序 门禁小程序 会员卡
                $appUser->authtoken = $data['access_token'];
            } else {
                if($type == 1){
                    $appUser->access_token = $data['access_token'];
                }
            }
            if($type == 1){
                $appUser->expires_in      = time() + $data['expires_in'];
                $appUser->refresh_token   = $data['refresh_token'];
            }
            $gender = $data['gender'] == "f" ? 2 : 1;
            $appUser->gender          = $gender;
            
            if ($appUser->save()) {
                return ['id'=>$appUser->id,'is_certified'=>$appUser->is_certified,'true_name'=>$appUser->true_name,'sex'=>$gender];
            }
        } else {
            //系统生成昵称
            $setNo   = 0;
            $userRef = 1;

            if (empty($data['nick_name'])) {
                $reNickName = self::generalNickName();
                $nickname   = $reNickName['nick_name'];
                $setNo      = $reNickName['set_no'];
            } else {
                $nickname   = $data['nick_name'];
            }

            if (!empty($data['user_ref'])) {
                $userRef = $data['user_ref'];
            }

            $gender = $data['gender'] == "f" ? 2 : 1;

            $appUser = new PsAppUser();
            $appUser->nick_name       = $nickname;

            if (!empty($data['id_card'])) {
                $appUser->id_card = $data['id_card'];
            }

            if (!empty($data['true_name'])) {
                $appUser->true_name = $data['true_name'];
            }

            if (!empty($data['phone'])) {
                $appUser->phone = $data['phone'];
            }

            if (!empty($data['is_certified'])) {
                $appUser->is_certified = $data['is_certified'] == "T" ? 1 : 2; // 是否通过实名认证。T是通过 F是没有实名认证。1通过 2未通过
            }

            if (!empty($data['avatar'])) {
                $appUser->avatar = !empty($data['avatar']) ? $data['avatar'] : '';
            }

            if (!empty($data['token_type'])) { // 邻易联小程序 门禁小程序 会员卡
                $appUser->authtoken = $data['access_token'];
            } else {
                $appUser->access_token = $data['access_token'];
            }

            $appUser->user_type       = 1;
            $appUser->set_no          = $setNo;
            $appUser->user_ref        = $userRef;
            $appUser->expires_in      = time() + $data['expires_in'];
            $appUser->refresh_token   = $data['refresh_token'];
            $appUser->channel_user_id = $data['user_id'];
            $appUser->ali_user_id     = $data['alipay_user_id'] ? $data['alipay_user_id'] : '';
            $appUser->gender          = $gender;
            $appUser->create_at       = time();
            if ($appUser->save()) {
                return ['id'=>$appUser->id,'is_certified'=>$appUser->is_certified,'true_name'=>$appUser->true_name,'sex'=>$gender];
            }
        }
        return false;
    }

    /**
     * Notes: 获取支付宝app user数据
     * Author: J.G.N
     * Date: 2019/7/15 16:55
     * @param $query
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getAppUserInfo($query)
    {
        //获取用户信息
        $appUser = PsAppUser::find()->where(['id' => $query['user_id'], 'user_type' => 1])->asArray()->one();
        return $appUser;
    }

    /**
     * 保存租客端用户
     * @param $data
     * @return bool|int
     */
    public static function saveTenantUser($data)
    {
        //存入数据库
        $appUser = PsTenantUser::find()->where(['channel_user_id' => $data['user_id']])->one();
        if ($appUser) {
            if(!empty($data['nick_name'])) {
                $appUser->nick_name = $data['nick_name'];
            }
            $appUser->access_token    = $data['access_token'];
            $appUser->expires_in      = time() + $data['expires_in'];
            $appUser->refresh_token   = $data['refresh_token'];
            $gender = $data['gender'] == "f" ? 2 : 1;
            $appUser->avatar          = !empty($data['avatar']) ? $data['avatar'] : '';
            $appUser->gender          = $gender;
            if ($appUser->save()) {
                return $appUser->id;
            }
        } else {
            //系统生成昵称
            $setNo     = 0;
            if (empty($data['nick_name'])) {
                $reNickName = self::generalTenantNickName();
                $nickname   = $reNickName['nick_name'];
                $setNo      = $reNickName['set_no'];
            } else {
                $nickname   = $data['nick_name'];
            }

            $gender = $data['gender'] == "f" ? 2 : 1;

            $appUser = new PsTenantUser();
            $appUser->nick_name       = $nickname;
            $appUser->set_no          = $setNo;
            $appUser->user_ref        = 1;
            $appUser->access_token    = $data['access_token'];
            $appUser->expires_in      = time() + $data['expires_in'];
            $appUser->refresh_token   = $data['refresh_token'];
            $appUser->channel_user_id = $data['user_id'];
            $appUser->ali_user_id     = $data['alipay_user_id'];
            $appUser->avatar          = !empty($data['avatar']) ? $data['avatar'] : '';
            $appUser->gender          = $gender;
            $appUser->create_at       = time();
            if ($appUser->save()) {
                return $appUser->id;
            }
        }
        return false;
    }

    /**
     * 保存用户最近访问城市
     * @param $data
     * @return bool|int
     */
    public static function saveUserHistoryCity($appUserId, $cityCode, $cityName, $cityProvince)
    {
        //存入数据库
        $appUser = PsAppUser::findOne($appUserId);
        $lastCityCode = "";
        $lastCityName = "";
        if ($cityCode) {
            //查询城市
            $psAreaAli = AreaService::service()->load($cityCode);
            if ($psAreaAli) {
                if ($psAreaAli['areaType'] == 4){
                    $lastCityName = AreaService::service()->getNameByCode($psAreaAli['areaParentId']);
                } else {
                    $lastCityName = $psAreaAli['areaName'];
                }
            }
            $lastCityCode = $cityCode;
        } else if ($cityName){
            //查询城市
            $provinceCode = AreaService::service()->getCodeByName($cityProvince, 2);//省份信息
            if ($provinceCode) {
                $lastCityCode = AreaService::service()->getCodeByName($cityName, 3, $provinceCode);
            }
            $lastCityName = $cityName;
        }
        $appUser->last_city_code = $lastCityCode;
        $appUser->last_city_name = $lastCityName;

        if ($appUser->save()) {
            return $appUser->id;
        }

        return false;
    }

    /**
     * 查询用户最近访问城市
     * @param $data
     * @return bool|int
     */
    public static function getUserHistoryCity($appUserId)
    {
        //存入数据库
        $appUser = PsAppUser::findOne($appUserId);
        if ($appUser) {
            $re['last_city_code'] = $appUser->last_city_code;
            $re['last_city_name'] = $appUser->last_city_name;
            return $re;
        }
        return false;
    }

    /**
     * 生成昵称
     * @return string
     */
    public static function generalNickName()
    {
        $tmpSetNo = 1;
        $webAppUser = PsAppUser::find()
            ->select(['set_no'])
            ->orderBy('set_no desc')
            ->limit(1)
            ->asArray()
            ->one();
        if ($webAppUser) {
            $tmpSetNo = $webAppUser['set_no'] + 1;
        }
        $nickName = self::PRE_NICKNAME.str_pad($tmpSetNo, 6, '0', STR_PAD_LEFT);
        $re['set_no']    = $tmpSetNo;
        $re['nick_name'] = $nickName;
        return $re;
    }

    /**
     * 生成租客端昵称
     * @return mixed
     */
    public static function generalTenantNickName()
    {
        $tmpSetNo = 1;
        $webAppUser = PsTenantUser::find()
            ->select(['set_no'])
            ->orderBy('set_no desc')
            ->limit(1)
            ->asArray()
            ->one();
        if ($webAppUser) {
            $tmpSetNo = $webAppUser['set_no'] + 1;
        }
        $nickName = self::PRE_TENANT_NAME.str_pad($tmpSetNo, 6, '0', STR_PAD_LEFT);
        $re['set_no']    = $tmpSetNo;
        $re['nick_name'] = $nickName;
        return $re;
    }

    /**
     * 根据用户的app_user_id 获取此用户关联的小区列表
     * @param int $appUserId
     * @param string $name
     * @param string $city_code
     * @param string $comm_type 小区类型
     * @param $type 生活号或者纯小区
     * @return array
     */
    public static function getMyCommunitysByUserId($appUserId = 0, $name = "", $city_code = "", $comm_type = "", $type = "")
    {
        //定位
        $psAreaAli = AreaService::service()->load($city_code);
        if ($psAreaAli && $psAreaAli['areaType'] == 4) {
            $city_code = $psAreaAli['areaParentId'];
        }

        $query = (new \yii\db\Query())
            ->select(['comm.id as community_id', 'comm.name', 'comm.pinyin'])
            ->from('ps_community as comm')
            ->where(['comm.status' => 1]);

        if ($name) {
            $query->andWhere(['like', 'comm.name', $name]);
        }

        if ($city_code) {
            $query->andWhere(['comm.city_id' => $city_code]);
        }

        if ($comm_type) {
            $query->andWhere(['comm.comm_type' => $comm_type]);
        }


        if ($type == "life-no") {
            //查询所有有生活号的小区
            $communityIds = PsLifeServices::find()
                ->select(['community_id'])
                ->where(['status' => 2])
                ->asArray()
                ->column();
            $query->andWhere(['comm.id' => $communityIds]);
        }

        $query->orderBy('comm.pinyin asc');
        $command    = $query->createCommand();
        $communitys = $command->queryAll();
        $newCommunity = [];
        if ($communitys) {
            foreach ($communitys as $community) {
                $lifeNo = PsLifeServices::find()
                    ->select(['name as service_name','url'])
                    ->where(['community_id' => $community['community_id']])
                    ->asArray()
                    ->one();

                $singleCommunity = [
                    'community_id' => $community['community_id'],
                    'name' => $community['name'],
                    'url' => $lifeNo['url']
                    //'is_auth' => $community['is_auth']
                ];
                if (isset($newCommunity[$community['pinyin']])) {
                    array_push($newCommunity[$community['pinyin']], $singleCommunity);
                } else {
                    $newCommunity[$community['pinyin']] = [];
                    array_push($newCommunity[$community['pinyin']], $singleCommunity);
                }
            }
        }
        return $newCommunity;
    }

    /**
     * 新增报修单之后发送短信验证码
     * @param $repairId
     * @param $process
     * @return bool
     */
    public static function orderFinishRemind($repairId, $process)
    {
        $repair = PsRepair::findOne($repairId);
        if (!$repair) {
            return false;
        }

        //查询添加此记录的用户信息
        $memberInfo = PsMember::findOne($repair->member_id);
        if (!$memberInfo) {
            return false;
        }

        $sendRe = "";

        //订单提交
        $isRemind = ($process == 1) ? 0 : 1;
        $smsConfig = [
            'isNew'=>$process,
            'isRemind'=>$isRemind,
            'objectId'=>$repair->id
        ];
        //给物业公司发送短信
        if ($process == 1) {
            //查询物业公司手机号
            $psCommunity = PsCommunityModel::findOne($repair->community_id);
            if (!$psCommunity) {
                return false;
            }
            $psCompany = PsPropertyCompany::findOne($psCommunity->pro_company_id);
            if (!$psCompany) {
                return false;
            }

            $mobile = $psCompany->link_phone;
            if (!$mobile) {
                return false;
            }
            $sendRe = SmsService::service()->init(5, $mobile, $smsConfig)->send([$memberInfo->name, $memberInfo->mobile]);
        } elseif ($process == 2) {
            //判断只发送一次
            $sendMsg = PsSmsHistory::find()->where(['customer_id' => $repairId, 'is_remind' => 1, 'is_new' => 2])->one();
            if ($sendMsg) {
                return true;
            }
            //订单已处理
            $sendRe = SmsService::service()->init(6, $memberInfo->mobile, $smsConfig)->send();
        } elseif ($process == 3) {
            //判断只发送一次
            $sendMsg = PsSmsHistory::find()->where(['customer_id' => $repairId, 'is_remind' => 1, 'is_new' => 3])->one();
            if ($sendMsg) {
                return true;
            }

            //订单已处理完成
            $sendRe = SmsService::service()->init(7, $memberInfo->mobile, $smsConfig)->send();
        }
        return $sendRe;
    }

    /**
     * 报事报修短信提醒
     * @param $repairId 报事报修id
     * @param $process  add 报事报修单提交 assign报事报修单指派 payment报事报修单支付提醒
     * @param $receiveMobile
     * @param $sendContent
     * @return bool
     */
    public static function repairRemind($repairId, $process, $receiveMobile, $data)
    {
        //订单提交
        if ($process == "add") {
            $templateId = 473;
        } elseif ($process == "assign") {
            $templateId = 474;
        } elseif ($process == "payment") {
            $templateId = 475;
        }
        $remind = $process == 1 ? 0 : 1;
        $smsConfig = [
            'isRemind'=>$remind,
            'objectId'=>$repairId
        ];
        return SmsService::service()->init(8, $receiveMobile, $smsConfig)->send($data);
    }

    /**
     * 根据app用户id获取此用户信息
     * @param $appUserId
     * @return bool
     */
    public static function exist($appUserId)
    {
        return PsAppUser::find()->where(['id' => $appUserId])->exists();
    }

    /**
     * 查询最近一周的时间
     */
    public static function getWeekDate()
    {
        $day2 = date("Y-m-d",strtotime("+2 day"));
        $day3 = date("Y-m-d",strtotime("+3 day"));
        $day4 = date("Y-m-d",strtotime("+4 day"));
        $day5 = date("Y-m-d",strtotime("+5 day"));
        $day6 = date("Y-m-d",strtotime("+6 day"));

        $week = [
            0 => '今天',
            1 => '明天',
            2 => $day2,
            3 => $day3,
            4 => $day4,
            5 => $day5,
            6 => $day6,
        ];
        return $week;
    }

    public static function getAllCitys($unCityId = 0, $comm_type = 0, $type = "", $lifeNoType = "")
    {
        if ($unCityId) {
            $psAreaAli = AreaService::service()->load($unCityId);
            if ($psAreaAli && $psAreaAli['areaType'] == 4) {
                $unCityId = $psAreaAli['areaParentId'];
            }
        }

        //查询所有有生活号的小区id
        if ($type) {
            $query = PsLifeServices::find()
                ->select(['community_id'])
                ->where(['status' => 2]);
            if ($lifeNoType) {
                $query->andWhere(['type' => $lifeNoType]);
            }
            $communityIds = $query->asArray()->column();
            $psCommunity = PsCommunityModel::find()
                ->select(['city_id'])
                ->groupBy('city_id')
                ->where(['id' => $communityIds])
                ->asArray()
                ->all();
        } else {
            $query = PsCommunityModel::find()
                ->select(['city_id'])
                ->groupBy('city_id')
                ->where(['status' => [1,2]]);
            if ($comm_type) {
                $query->andWhere(['comm_type' => $comm_type]);
            }
            $psCommunity = $query->asArray()->all();
        }
        $pinyin = new Pinyin();
        if($psCommunity) {
            foreach($psCommunity as $key => $comm) {
                //查询城市信息
                $cityName = AreaService::service()->getNameByCode($comm['city_id']);
                $psCommunity[$key]['city_name'] = $cityName;
                $psCommunity[$key]['pinyin']    = $pinyin->pinyin($cityName, true);

            }
        }

        $newCityArr = [];
        foreach ($psCommunity as $community) {
            if ($unCityId && $community['city_id'] == $unCityId) {
                continue;
            }
            $singleCity = [
                'city_code' => $community['city_id'],
                'city_name' => $community['city_name'],
            ];
            if(isset($newCityArr[$community['pinyin']]) && is_array($newCityArr[$community['pinyin']])) {
                array_push($newCityArr[$community['pinyin']], $singleCity);
            } else {
                $newCityArr[$community['pinyin']] = [];
                array_push($newCityArr[$community['pinyin']], $singleCity);
            }
            $key = array_search($community['pinyin'], $newCityArr);
            if ($key !== false) {
                $newCityArr[$key] = [];
                array_push($newCityArr[$key], $singleCity);
            } else {
                $newCityArr[$community['pinyin']] = [];
                array_push($newCityArr[$community['pinyin']], $singleCity);
            }
        }
        return $newCityArr;
    }

    //获取AppUser信息
    public function getAppUser($appUserId)
    {
        return PsAppUser::find()->select('id, nick_name, phone')->where(['id'=>$appUserId])->asArray()->one();
    }

    //品牌馆绑定手机号
    public function bindPhone($phone, $appUserId, $code)
    {
        $r = SmsService::service()->init(4, $phone)->valid($code);
        if(!$r) {
            return $this->failed('验证失败');
        }
        $user = PsAppUser::findOne($appUserId);
        if(!$user) {
            return $this->failed('用户不存在');
        }
        $user->phone = $phone;
        if($user->save()) {
            return $this->success();
        }
        return $this->failed();
    }
}
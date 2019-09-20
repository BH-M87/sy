<?php
/**
 * 物业相关服务
 * 特别声明 此服务应用于目前没有做数据库拆分的情况，如果数据库拆分，此服务对应的代码需要修改
 * User: wenchao.feng
 * Date: 2018/6/4
 * Time: 15:41
 */

namespace service\basic_data;

use common\core\Curl;
use common\core\F;
use yii\db\Query;
use Yii;

class PropertyService extends BaseService {

    /**
     * 获取支付宝调用配置信息
     * @param $propertyId
     * @return array
     */
    public function getAopConfig($propertyId)
    {
        $aop = [];
        $aop['appAuthToken'] = null;
        //停车使用未来社区应用
        if (YII_ENV == 'prod' || YII_ENV == 'release') {
            $aop['gatewayUrl']         = Yii::$app->params['gate_way_url'];
            $aop['appId']              = Yii::$app->params['property_isv_app_id'];
            $aop['alipayrsaPublicKey'] = file_get_contents(Yii::$app->params['property_isv_alipay_public_key_file']);
            $aop['rsaPrivateKey']      = file_get_contents(Yii::$app->params['property_isv_merchant_private_key_file']);
            $aop['signType'] = 'RSA2';

            if ($propertyId) {
                $tokenInfo = (new Query())
                    ->select(['token'])
                    ->from('ps_property_isv_token')
                    ->where(['type' => 1, 'type_id' => $propertyId])
                    ->one();
                $aop['appAuthToken'] = $tokenInfo['token'];
            }
        } else {
            //使用沙箱环境
            $aop['alipayPublicKey'] = Yii::$app->params['alipay_public_key_file'];
            $aop['rsaPrivateKeyFilePath'] = Yii::$app->params['merchant_private_key_file'];
            $aop['gatewayUrl'] = Yii::$app->params['gate_way_url'];
            $aop['appId'] = Yii::$app->params['property_app_id'];
            $aop['signType'] = 'RSA';
            $aop['appAuthToken'] = '201901BB2108ce59566a4fea834937f1bcca1X20';
        }

        return $aop;
    }

    /**
     * 根据小区id获取物业公司id
     * @param $communityId
     * @return int
     */
    public function getPropertyIdByCommunityId($communityId)
    {
        $communityInfo = (new Query())
            ->select(['id', 'pro_company_id', 'name', 'phone'])
            ->from('ps_community')
            ->where(['id' => $communityId])
            ->one();
        if ($communityInfo) {
            return $communityInfo['pro_company_id'];
        }
        return 0;
    }

    /**
     * 根据小区id获取小区详情
     * @param $communityId
     * @return array|bool
     */
    public function getCommunityInfoById($communityId)
    {
        //house_type小区类型 add by zq 2019-3-15
        $communityInfo = (new Query())
            ->select(['comm.id', 'comm.pro_company_id', 'comm.name', 'comm.phone', 'comm.community_no',
                'comm.province_code', 'comm.address', 'comm.locations', 'comm.longitude','comm.latitude',
                'comm.city_id', 'comm.district_code', 'life.name as life_name',
                'life.app_id', 'life.status', 'life.id as life_id', 'life.logo as life_logo',
                'company.property_name', 'company.id as company_id', 'company.link_man','comm.house_type'
            ])
            ->from('ps_community comm')
            ->leftJoin('ps_life_services life', 'life.community_id = comm.id')
            ->leftJoin('ps_property_company company', 'company.id = comm.pro_company_id')
            ->where(['comm.id' => $communityId])
            ->one();
        if ($communityInfo) {
            //生活号未上架
            $communityInfo['life_id'] = $communityInfo['life_name'] = $communityInfo['life_logo'] = $communityInfo['app_id'] = $communityInfo['status'] = '';
            //查询省市
            $areaCodeInfo = (new Query())
                ->select(['areaName'])
                ->from('ps_area_ali')
                ->where(['areaCode' => [$communityInfo['province_code'], $communityInfo['city_id'], $communityInfo['district_code']]])
                ->all();
            $communityInfo['province_name'] = $areaCodeInfo[0]['areaName'];
            $communityInfo['city_name'] = $areaCodeInfo[1]['areaName'];
            $communityInfo['district_name'] = $areaCodeInfo[2]['areaName'];
        }
        return $communityInfo;
    }

    //查询小区信息
    public function getCommunityNoById($communityId)
    {
        $communityInfo = (new Query())
            ->select(['community_no'])
            ->from('ps_community')
            ->where(['id' => $communityId])
            ->one();
        return !empty($communityInfo) ? $communityInfo['community_no'] : '';
    }

    //查询用户是否关注了生活号
    public function getIsAttention($lifeId, $userId)
    {
        $params['life_id']  = $lifeId;
        $params['user_id']        = $userId;
        $url = \Yii::$app->params['api_host'] . '/webapp/life/get-is-attention';
        //F::writeLog('alipay-auth', 'auth.txt', 'get-is-attention-params'.json_encode($params)."\r\n", FILE_APPEND);
        $response = Curl::getInstance()->post($url, $params);
        //F::writeLog('alipay-auth', 'auth.txt', 'get-is-attention'.$response."\r\n", FILE_APPEND);
        if ($response) {
            $resArr = json_decode($response, true);
            if ($resArr['code'] == "20000") {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }
}
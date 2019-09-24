<?php
/**
 * 支付宝停车模块方法接入
 * User: wenchao.feng
 * Date: 2018/6/4
 * Time: 15:13
 */
namespace service\parking;

use common\core\ali\AopRedirect;
use service\BaseService;
use Yii;
use yii\base\Exception;

class AliParkService extends BaseService
{
    private $_aop;// AopRedirect实例
    private $_app_auth_token; //当面付应用授权token值


    /*根据企业是否签署新当面付协议进行判断，签署了新当面付的，
    使用 【筑家易未来社区】应用操作小区，未签署的使用【快乐家】应用操作小区，测试环境及本地环境使用沙箱操作小区*/
    function __construct($propertyId = null)
    {
        $this->_aop = new AopRedirect();
//        $aopConfig = PropertyService::service()->getAopConfig($propertyId);
//        if (empty($aopConfig)) {
//            throw new Exception("公司授权信息获取失败");
//        }
//        $this->_aop->gatewayUrl = $aopConfig['gatewayUrl'];
//        $this->_aop->appId = $aopConfig['appId'];
//        if ($aopConfig['signType'] == "RSA2") {
//            $this->_aop->alipayrsaPublicKey = $aopConfig['alipayrsaPublicKey'];
//            $this->_aop->rsaPrivateKey = $aopConfig['rsaPrivateKey'];
//        } else {
//            $this->_aop->alipayPublicKey = $aopConfig['alipayPublicKey'];
//            $this->_aop->rsaPrivateKeyFilePath = $aopConfig['rsaPrivateKeyFilePath'];
//        }
//        $this->_aop->signType = $aopConfig['signType'];
//        $this->_app_auth_token = $aopConfig['appAuthToken'];

        $this->_aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $this->_aop->appId = "2018111662174637";
        $this->_aop->alipayrsaPublicKey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApxj3CyI3MT+Ih8+g7r2tJpnun/qtR5ppR+2YYOBFuH3H5+a/57hThS9kz3YMQyvH5L7vZWwgbKBLIvI/+pvWReHh6Rrb+dweENxtIACo3c8Kza/cF6djD1I+WW/QhJtCaIQ6MXFQwNCXcKxsTiXKe/mR5pJ6V2HlMNT1BtbHsvnTaKNbbcOXy15mqJNELV5MGTMkYaHwXy6d19nHk1r6rAVJHd3ua3YmdcKIIHINjeX3sSGX/0azkLcdYuTSPs/gFm7BnHEhYoK8LzJr+t/IOGHkHkKiBpYmAu5oDE+lvbbTF9PHT/Z5B+dSRIrx/DUpnzRwlZ0fpkTUOGkkW2rOwwIDAQAB";
        $this->_aop->rsaPrivateKey = "MIIEpAIBAAKCAQEAuqlWilXT5pApms1sewtdORF0uqrN7DZdLx2juTVXeFxe2E/QNocwVZmAP7150u4x/lNEkjxbdbViyFL3N1GKWAa0crEUBSI1E+kEaGbuVOZD7nNOYFKfmPFp/eKY0jwmvAs2QkrWE5ztu8w1fGkTIJDpOaIdLwBuzIE5pqr6GpX6BLtf9avQvm3LE/mzeDaz2dEa9qSvr/HMChxdJKmiOgBXoUca3geePixnlbDU1QkChcRbIA0H2GHVRcfCkq4fww8E3fKEWuqfeKCgksENypGJ8t6sa4kiJEh0OFi6b3VDNUjRDZ8twn4Gz7A5tc4k/nN+Kb2k50ZExOLWuZqYtQIDAQABAoIBAA7nkJpxKf9iCYBr4LqeeK4i37A8DT9MQ/qMIlOal8ZrkcMx9E02VDyxihUM2xplnKZTHcmTejYW6rFWNpk7MJHAz03NJ+0VAyn41DpF5cfvrwLuQBbe4hGDTVRRcKRw2yLuvkFi2l8si6sQLkEl3rod+BF7CVuEbFR2CRVu15OhvTCtdl47TjKMFAgYaQO7cCIKYYWVecagMxV/1C0lnG0nHAHWyQVS4qcc6Xp8N7g6G85Hq+vxApluWj+I+HrU8Le4ju/w9dkQyumJQqSv7ms7j79PgXPlnR1fPFPMdl6xxAa8NSiwzAONkgK2dc6OzhJfbspCeAhqbq4gr10CgQ0CgYEA3KU6p3/i8VG463XQIm4V7mA6lmNg2m7dyAHzLFxHU66M0QnkREWPBsDu3ap9VcrNwaxYy3UmhEsAO95s6PXbTKXFqxQLQMnBHtIwAyJivI2Dm2w8i8nJkxmZ0JI0bdR1ZTYHStldPZ5o3Uj4mR3it0t+JSXO3XZMDb4yDNGz7YMCgYEA2JIaeZBEOpIE7YCw02reyNO5E7fCeFl9mZiT81cLyaPJkZnXni9lEt3IDVkj1Owa3dDJbe52dbwzSZI/TqO/6h+fBuSq9Ju/GhjbJ5esGG8Z0gL583VsWEBtsBK0zTWUvs9zCWnT1Te4VTK/O8SN0Cq9U3QYJr5lP3ZCjBXkg2cCgYEArDR0s7fXGnqqtXJssAJ1MWd/MlJ7i9+NToVfEdcvf/syQg/TTQlw2FeJ1g0y5ttyfN6TKq/TENssYo11ONhxgL+8p4nsQnN0OybWfAvBPeHAvnPaSUuC/EC10JfbPYDc4tIpHNzKrcXNmC9UfsRZZq8P09RPQH8MWol/rIuaPxsCgYEAqyjVGawl8DBFCruBhKVay1dhVy8M1/bKKCEJFPF/lG4IuTTfztngRMfY+ouvoPC0zwfamjIzlxsVYZjexbTu0QcKtPT2E3ofz21Djwf08B6mRm8pwfrUlO7egaBXGjO1ihQD87WawFFYMqV3s7HE7ndIx/Lhv4UMGdrJ/1KyFhMCgYBa5c9HAiumRVswNFseVtsfqbE0QsQPZ6a/QLUssYjpLkG5zoj/rTWgTtSroHe0RtG4j3EwleZdQHJawut+mo3Wg9C5VPdHfQ1qv2I+En2PpMJXZaEZyFCqbw/R7D/RTRNLURnJvRdOs9R9sLsxZxM9C6508bPtb50LLWs+UhLgZw==";
        $this->_aop->signType = "RSA2";
        $this->_app_auth_token = null;
    }

    //ISV系统配置
    public function isvConfig($reqArr)
    {
        $params['biz_content'] = json_encode($reqArr,JSON_UNESCAPED_UNICODE);
        return $this->_aop->execute('alipay.eco.mycar.parking.config.set', $params, null, null);
    }

    //isv系统配置查询
    public function isvConfigQuery($reqArr)
    {
        $params['biz_content'] = json_encode($reqArr,JSON_UNESCAPED_UNICODE);
        return $this->_aop->execute('alipay.eco.mycar.parking.config.query', $params, null, null);
    }

    //停车场新增
    public function parkingLotCreate($reqArr)
    {
        $params['biz_content'] = json_encode($reqArr,JSON_UNESCAPED_UNICODE);
        return $this->_aop->execute('alipay.eco.mycar.parking.parkinglotinfo.create', $params, null, null);
    }

    //停车场修改
    public function parkingLotUpdate($reqArr)
    {
        $params['biz_content'] = json_encode($reqArr,JSON_UNESCAPED_UNICODE);
        return $this->_aop->execute('alipay.eco.mycar.parking.parkinglotinfo.update', $params, null, $this->_app_auth_token);
    }

    //车辆驶入
    public function parkingEnterInfo($reqArr)
    {
        $params['biz_content'] = json_encode($reqArr,JSON_UNESCAPED_UNICODE);
        return $this->_aop->execute('alipay.eco.mycar.parking.enterinfo.sync', $params, null, $this->_app_auth_token);
    }

    //车辆驶出
    public function parkingExitInfo($reqArr)
    {
        $params['biz_content'] = json_encode($reqArr,JSON_UNESCAPED_UNICODE);
        return $this->_aop->execute('alipay.eco.mycar.parking.exitinfo.sync', $params, null, $this->_app_auth_token);
    }

    //车牌账单查询
    public function parkingVehicleQuery($reqArr, $authToken)
    {
        $params['biz_content'] = json_encode($reqArr,JSON_UNESCAPED_UNICODE);
        return $this->_aop->execute('alipay.eco.mycar.parking.vehicle.query', $params, $authToken, null);
    }

    //订单同步接口
    public function parkingOrderSync($reqArr)
    {
        $params['biz_content'] = json_encode($reqArr,JSON_UNESCAPED_UNICODE);
        return $this->_aop->execute('alipay.eco.mycar.parking.order.sync', $params, null, $this->_app_auth_token);
    }

    //订单更新接口
    public function parkingOrderUpdate($reqArr)
    {
        $params['biz_content'] = json_encode($reqArr,JSON_UNESCAPED_UNICODE);
        return $this->_aop->execute('alipay.eco.mycar.parking.order.update', $params, null, $this->_app_auth_token);
    }

    //获取用户授权令牌
    public function parkingOauthToken($reqArr)
    {
        $params['code'] = $reqArr['code'];
        $params['grant_type'] = 'authorization_code';
        return $this->_aop->execute('alipay.system.oauth.token', $params, null, null);
    }

    //wap支付
    public function parkingWapPay($reqArr, $notifyUrl, $returnUrl)
    {
        $params['biz_content'] = json_encode($reqArr,JSON_UNESCAPED_UNICODE);
        $this->_aop->setNotifyUrl($notifyUrl);
        $this->_aop->setReturnUrl($returnUrl);
        return $this->_aop->pageExecute('alipay.trade.wap.pay', $params, $this->_app_auth_token);
    }

    public function check($arr)
    {
        if ($this->_aop->signType == "RSA2") {
            return $this->_aop->rsaCheckV1($arr, $this->_aop->alipayrsaPublicKey, $this->_aop->signType);
        } else {
            return $this->_aop->rsaCheckV1($arr, $this->_aop->alipayPublicKey, $this->_aop->signType);
        }
    }
}
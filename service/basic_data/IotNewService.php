<?php
/**
 * Created by PhpStorm.
 * User: zhangqiang
 * Date: 2019-06-04
 * Time: 16:49
 */

namespace service\basic_data;

use common\core\Curl;
use common\core\F;
use yii\db\Query;

class IotNewService extends BaseService
{
    private $appkey = 'community';
    private $appSecret = '9f1bbb1b06797a3541c4ab5afafbaf6c';
    private $url = 'http://101.37.135.54:8844';

    /**
     * 生成java签名
     * @param $params
     * @param $appSecret
     * @return string
     */
    public function sign($params,$appSecret)
    {
        $signParams = [];
        foreach($params as $k =>$v){
            if($k !== 'sign' && !is_array($v) && $v !== '' && $v !== null){
                $signParams[$k] = $v;
            }
        }
        ksort($signParams);
        $string = http_build_query($signParams).$appSecret;
        $string = urldecode($string);
        return md5($string);

    }

    /**
     * 统一处理公共参数
     * @param $paramData
     * @return false|string
     */
    public function dealPostData($paramData)
    {
        $paramData['appKey'] = \Yii::$app->params['iotNewAppKey'];
        $paramData['timestamp'] = time();
        $paramData['sign'] = $this->sign($paramData,\Yii::$app->params['iotNewAppSecret']);
        return json_encode($paramData);
    }

    /**
     * 统一处理返回结果
     * @param $res
     * @return bool|string
     * todo 由于现在JAVA系统还不是很稳定目前需要先记录日志，后续稳定以后可以去除成功的日志
     */
    public function javaPost($url,$postData)
    {
        $postUrl = \Yii::$app->params['iotNewUrl'].$url;
        $options['CURLOPT_HTTPHEADER'] = ['Content-TYpe:application/json'];
        $res = Curl::getInstance($options)->post($postUrl,$this->dealPostData($postData));
        if ($res) {
            $result = json_decode($res,true);
            if($result['code'] == 1){
                \Yii::info('success-url:'.$postUrl.'--request-data:'.json_encode($postData).'--response-data:'.json_encode($result),'iot-request');
                unset($postData['faceData']);
                return $this->success($result['data']);
            } else {
                \Yii::info('error-url:'.$postUrl.'--request-data:'.json_encode($postData).'--response-data:'.json_encode($result),'iot-request');
                unset($postData['faceData']);
                return $this->failed($result['message']);
            }
        } else {
            return $this->failed("java接口未返回有效信息");
        }
    }

    /**
     * 获取供应商SN列表
     * @param int $type
     * @return bool|string
     */
    public function getProductSn($type = '')
    {
        $url ='/community/door/communityDoorDevice/deviceFunctionList';
        $postData = [];
        if($type){
            $postData['deviceType'] = $type;
        }
        return $this->javaPost($url,$postData);
    }

    public function checkPostData($paramData,$postArray,$check = true)
    {
        if(!empty($postArray)){
            $postData = [];
            foreach($postArray as $key){
                $data = F::get($paramData,$key);
                if(!empty($data) || $check){
                    $postData[$key] = $data;
                }else{
                    return $this->failed($key."不能为空");
                }
            }
            return $this->success($postData);
        }else{
            return $this->failed("请求java接口的参数不能为空");
        }
    }


    /**
     * 新增设备
     * @param $paramData
     * @return array
     */
    public function deviceAdd($paramData)
    {
        $url ='/community/door/communityDoorDevice/addDevice';
        $postArray = ['tenantId','communityNo','buildingNo','deviceNo','deviceName','deviceType','productSn','authCode'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            return $this->javaPost($url,$postDataResult['data']);
        }else{
            return $postDataResult;
        }

    }

    /**
     * 编辑设备
     * @param $paramData
     * @return array
     */
    public function deviceEdit($paramData)
    {
        $url ='/community/door/communityDoorDevice/updateDevice';
        $postArray = ['tenantId','communityNo','buildingNo','deviceNo','deviceName','deviceType','productSn','authCode'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            return $this->javaPost($url,$postDataResult['data']);
        }else{
            return $postDataResult;
        }

    }

    /**
     * 删除设备
     * @param $paramData
     * @return array
     */
    public function deviceDelete($paramData)
    {
        $url ='/community/door/communityDoorDevice/updateDeviceStatus';
        $postArray = ['tenantId','deviceInfo'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            $postData = $postDataResult['data'];
            $postData['status'] = 3;
            return $this->javaPost($url,$postData);
        }else{
            return $postDataResult;
        }

    }

    /**
     * 删除设备-真删除
     * @param $paramData
     * @return array
     */
    public function deviceDeleteTrue($paramData)
    {
        $url ='/community/door/communityDoorDevice/deleteDevice';
        $postArray = ['tenantId'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            $postData = $postDataResult['data'];
            $postData['deviceInfo'] = F::value($paramData,'deviceInfo',[]);
            return $this->javaPost($url,$postData);
        }else{
            return $postDataResult;
        }

    }

    //住户添加和编辑
    public function roomUserAdd($paramData)
    {
        $url ='/community/door/communityDoorUser/addUserBatch';
        $postArray = ['tenantId','communityNo','communityName','userList'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            return $this->javaPost($url,$postDataResult['data']);
        }else{
            return $postDataResult;
        }

    }

    //住户删除
    public function roomUserDelete($paramData)
    {
        $url ='/community/door/communityDoorUser/deleteUser';
        $postArray = ['tenantId','communityNo','buildingNo','roomNo','userId','userType'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            $postData = $postDataResult['data'];
            $postData['deviceInfo'] = F::value($paramData,'deviceInfo',[]);
            return $this->javaPost($url,$postData);
        }else{
            return $postDataResult;
        }

    }

    //住户人脸新增和修改
    public function roomUserFace($paramData)
    {
        $url ='/community/door/communityDoorUser/addOrEditUserFace';
        $postArray = ['tenantId','communityNo','communityName','gardenName','buildingNo','buildingName','unitName','roomName','roomNo',
        'userName','userPhone','userType','userId','visitorId','faceData','faceUrl','visitTime','exceedTime'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            $postData = $postDataResult['data'];
            $postData['deviceInfo'] = F::value($paramData,'deviceInfo',[]);
            $postData['userSex'] = F::value($paramData,'userSex',1);
            return $this->javaPost($url,$postData);
        }else{
            return $postDataResult;
        }

    }

    //获取动态二维码，用于反扫码
    public function getQrCode($paramData)
    {

        $url ='/community/door/communityDoorUser/generateQRCode';
        $postArray = ['tenantId','userId','communityNo','buildingNo','roomNo','visitorId','visitTime','exceedTime','userType'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            $postData = $postDataResult['data'];
            $postData['productSn'] = F::value($paramData,'productSn',[]);
            $postData['faceUrl'] = F::value($paramData,'faceUrl');
            return $this->javaPost($url,$postData);
        }else{
            return $postDataResult;
        }

    }

    //新增访客预约
    public function visitorAdd($paramData)
    {

        $url ='/community/door/communityDoorUser/addVisitor';
        $postArray = ['tenantId','communityNo','communityName','gardenName','buildingNo','buildingName','unitName','roomName','roomNo',
            'visitorId','visitorName','visitorPhone','visitTime','exceedTime',
            'userId','parkCode','carNum','enterModel','exitModel'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            $postData = $postDataResult['data'];
            $postData['productSn'] = F::value($paramData,'productSn',[]);
            return $this->javaPost($url,$postData);
        }else{
            return $postDataResult;
        }

    }

    //取消访客预约
    public function visitorCancle($paramData)
    {

        $url ='/community/door/communityDoorUser/delVisitor';
        $postArray = ['tenantId','parkCode','roomNo','visitorTel','memberId','visitorId'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            return $this->javaPost($url,$postDataResult['data']);
        }else{
            return $postDataResult;
        }

    }

    //远程开门
    public function openDoor($paramData)
    {
        $url ='/community/door/communityDoorUser/remoteOpen';
        $postArray = ['tenantId','communityNo','roomNo','userId','deviceNo','userType'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            return $this->javaPost($url,$postDataResult['data']);
        }else{
            return $postDataResult;
        }

    }

    //扫描二维码开门
    public function openDoorQrcode($paramData)
    {
        $url = '/community/door/communityDoorUser/verifyPositiveScanQRCode';
        $postArray = ['tenantId','communityNo','buildingNo','roomNo','userId','userType','qrCodeData'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            $postData = $postDataResult['data'];
            if($postData['userType'] == 4){
                $postData['visitorId'] = $paramData['visitorId'];
                $postData['visitTime'] = $paramData['visitTime'];
                $postData['exceedTime'] = $paramData['exceedTime'];
            }
            return $this->javaPost($url,$postData);
        }else{
            return $postDataResult;
        }
    }



}
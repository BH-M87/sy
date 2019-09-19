<?php
/**
 * Created by PhpStorm.
 * User: zhangqiang
 * Date: 2019-06-04
 * Time: 16:49
 */

namespace service\alipay;

use common\core\F;
use service\basic_data\IotNewService;
use service\BaseService;

class IotParkingService extends BaseService
{

    public function javaPost($url,$postData)
    {
        return IotNewService::service()->javaPost($url,$postData);
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

    public function getParkInfo($paramData)
    {
        $url ='/community/gate/communityGatePark/getParkInfo';
        $postArray = ['productSn'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            $postData = $postDataResult['data'];
            $postData['isUsed'] = $paramData['isUsed'];//未配置的
            return $this->javaPost($url,$postData);
        }else{
            return $postDataResult;
        }

    }

    public function updatePark($paramData)
    {
        $url ='/community/gate/communityGatePark/updatePark';
        $postArray = ['tenantId','parkId','parkCode','communityCode','communityName','communityAddress','productSn','isDeploy'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            $postData = $postDataResult['data'];
            return $this->javaPost($url,$postData);
        }else{
            return $postDataResult;
        }

    }

    public function addCar($paramData)
    {
        $url ='/community/gate/communityGateCar/addCar';
        $postArray = ['memberId','memberName','personPhone','parkCode','carNum','tenantId','carType','listType'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            $postData = $postDataResult['data'];
            $postData['start'] = $paramData['start'];
            $postData['end'] = $paramData['end'];
            $res = $this->javaPost($url,$postData);
            $dataPost = [
                'postData' => $postData ?? '',
                'result' => $res ?? '',
            ];
            return $res;
        }else{
            return $postDataResult;
        }

    }

    public function deleteCar($paramData)
    {
        $url ='/community/gate/communityGateCar/deleteCar';
        $postArray = ['memberId','personPhone','parkCode','carNum','tenantId'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            $postData = $postDataResult['data'];
            $return = $this->javaPost($url,$postData);
            return $return;
        }else{
            return $postDataResult;
        }

    }

    public function applyCalculationFee($paramData)
    {
        $url ='/community/gate/communityGatePay/applyCalculationFee';
        $postArray = ['parkCode','orderId','carNum','couponTime'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            $postData = $postDataResult['data'];
            //\Yii::info("--get-lk-parking-fee-request:".json_encode($postData, JSON_UNESCAPED_UNICODE), 'parkingapi');
            $res = $this->javaPost($url,$postData);
            $dataPost = [
                'postData' => $postData ?? '',
                'result' => $res ?? '',
            ];
            return $res;
        }else{
            return $postDataResult;
        }

    }

    public function sendPayResult($paramData)
    {
        $url ='/community/gate/communityGatePay/sendPayResult';
        $postArray = ['parkCode','orderId','transactionID','carNum','payCharge','realCharge','payTime','payType','payChannel','getTimes','outTradeNo'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if ($postDataResult['code'] == 1) {
            $postData = $postDataResult['data'];
            //\Yii::info("--send-pay-result-to-lk-request:".json_encode($postData, JSON_UNESCAPED_UNICODE), 'parkingapi');
            $res = $this->javaPost($url,$postData);
            //TODO 支付结果下发失败的处理，放到java还是php
            $dataPost = [
                'postData' => $postData ?? '',
                'result' => $res ?? '',
            ];
            return $res;
        } else {
            return $postDataResult;
        }
    }

    public function couponLower($paramData)
    {
        $url ='/community/gate/communityGatePay/couponLower';
        $postArray = ['parkCode'];
        $postDataResult = $this->checkPostData($paramData,$postArray);
        if($postDataResult['code'] == 1){
            $postData = $postDataResult['data'];
            $postData['coupons'] = F::get($paramData,'coupons',[]);
            $res = $this->javaPost($url,$postData);
            return $res;
        }else{
            return $postDataResult;
        }
    }
}
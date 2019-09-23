<?php
/**
 * 主要用于获取物业公司授权信息
 */
namespace app\modules\property\modules\v1\controllers;

use service\alipay\AlipayApplyService;
use Yii;
use service\alipay\AliTokenService;
use yii\web\Controller;

class CplifeController extends Controller
{
    /**
     * 手机wap支付授权，授权码换取token || 旧token换新token
     * @param     string    $codeToken    授权码 或 token
     * @param     mix       $type         默认null | code 授权码获取token | token 旧token换取新token
     * @return    string                  false 调用失败 | array 返回的数据
     */
    public function actionGetAuthToken()
    {
        $code = Yii::$app->request->get('app_auth_code');
        if(!$code) {
            return false;
        }
        $result = AliTokenService::service()->getToken($code);
        if(empty($result['app_auth_token'])) {
            return '抱歉，授权失败，请联系技术人员协助处理';
        }
        $nonce = Yii::$app->request->get('nonce');
        $t = Yii::$app->request->get('t');

        if($nonce && $t) {
            $typeId = AliTokenService::service()->getTypeId($t, $nonce);
            if($typeId) {
                $r = AliTokenService::service()->addToken($t, $typeId, $result);//添加ps_ali_token记录
                AlipayApplyService::service()->endApply($nonce);
                if($r) {
                    return '恭喜，授权成功';
                }
            }
        }
        return '系统错误';
    }

    /**
     * 当面付用户授权
     * 授权码换取token || 旧token换新token
     */
    public function actionGetScanAuthToken()
    {
        $code = Yii::$app->request->get('app_auth_code');
        if(!$code) {
            return false;
        }

        $nonce = Yii::$app->request->get('nonce');
        $msg = "--get-token:------code:{$code}---nonce:{$nonce}---"."\r\n";

        $result = AliTokenService::service()->getScanToken($code, false, $nonce);
        $msg .= "--get-token-result:------".json_encode($result). "\r\n";
        file_put_contents("get-isv-token.txt",$msg,FILE_APPEND);
        if(empty($result['app_auth_token'])) {
            return '抱歉，授权失败，请联系技术人员协助处理';
        }

        $t = Yii::$app->request->get('t');
        if($nonce && $t) {
            $typeId = AliTokenService::service()->getTypeId($t, $nonce);
            if($typeId) {
                $r = AliTokenService::service()->addScanToken($t, $typeId, $result);//添加ps_ali_token记录
                    AlipayApplyService::service()->endApply($nonce);
                if($r) {
                    return '恭喜，授权成功';
                }
            }
        }
        return '系统错误';
    }
}

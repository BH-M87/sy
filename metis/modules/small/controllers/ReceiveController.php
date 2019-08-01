<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * 企业内部应用获取注册回调信息
 * Date: 2019-3-19
 * Time: 11:37
 */
namespace alisa\modules\small\controllers;

use common\services\rent\ElectromobileService;
use dingding\jdk\api\Activate;
use dingding\jdk\api\ISVService;
use dingding\jdk\crypto\DingtalkCrypt;
use dingding\jdk\util\Cache;
use dingding\jdk\util\Log;
use dingding\services\DingdingService;
use yii\web\Controller;
use dingding\services\AddressBookService;
use Yii;

class ReceiveController extends Controller {
    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $signature = !empty($_GET["signature"]) ? $_GET["signature"] : '';
        $timeStamp = !empty($_GET["timestamp"]) ? $_GET["timestamp"] : '';
        $nonce     = !empty($_GET["nonce"]) ? $_GET["nonce"] : '';
        $postdata  = file_get_contents("php://input");
        $postList  = json_decode($postdata,true);
        $encrypt   = $postList['encrypt'];
        $res = ElectromobileService::service()->getModel();
        Log::i("api返回的res：".json_encode($res));
        $token = $res['data']['token'];
        $aes_key = $res['data']['aes_key'];
        $cropId = $res['data']['corpid'];
        $crypt     = new DingtalkCrypt($token, $aes_key, $cropId);

        $msg = "";
        $errCode = $crypt->DecryptMsg($signature, $timeStamp, $nonce, $encrypt, $msg);

        if ($errCode != 0) {
            Log::e(json_encode($_GET) . "  ERR:" . $errCode);
            /**
             * 创建套件时检测回调地址有效性，使用CREATE_SUITE_KEY作为SuiteKey
             */
            $crypt = new DingtalkCrypt($token, $aes_key, $cropId);
            $errCode = $crypt->DecryptMsg($signature, $timeStamp, $nonce, $encrypt, $msg);
            if ($errCode == 0) {
                Log::i("DECRYPT CREATE SUITE MSG SUCCESS " . json_encode($_GET) . "  " . $msg);
                $eventMsg = json_decode($msg);
                $eventType = $eventMsg->EventType;
                if ("check_create_suite_url" === $eventType) {
                    $random = $eventMsg->Random;
                    $testSuiteKey = $eventMsg->TestSuiteKey;

                    $encryptMsg = "";
                    $errCode = $crypt->EncryptMsg($random, $timeStamp, $nonce, $encryptMsg);
                    if ($errCode == 0) {
                        Log::i("CREATE SUITE URL RESPONSE: " . $encryptMsg);
                        echo $encryptMsg;
                    } else {
                        Log::e("CREATE SUITE URL RESPONSE ERR: " . $errCode);
                    }
                } else if ("check_url" === $eventType) {
                    $random = $eventMsg->Random;
                    $testSuiteKey = $eventMsg->TestSuiteKey;

                    $encryptMsg = "";
                    $errCode = $crypt->EncryptMsg($random, $timeStamp, $nonce, $encryptMsg);
                    if ($errCode == 0) {
                        Log::i("CREATE SUITE URL RESPONSE: " . $encryptMsg);
                        echo $encryptMsg;
                    } else {
                        Log::e("CREATE SUITE URL RESPONSE ERR: " . $errCode);
                    }
                }
            } else {
                Log::e(json_encode($_GET) . "CREATE SUITE ERR:" . $errCode);
            }
            return;
        } else {
            /**
             * 套件创建成功后的回调推送
             */
            Log::i("DECRYPT MSG SUCCESS " . json_encode($_GET) . "  " . $msg);
            $eventMsg = json_decode($msg);
            $eventType = $eventMsg->EventType;

            if ("bpms_task_change" === $eventType) {
                //审核中间过程
                Log::e(json_encode($_GET) . "  ERR:bpms_task_change");

            }else if ("bpms_instance_change" === $eventType) {
                Log::e(json_encode($_GET) . "  ERR:bpms_instance_change");
                $type = $eventMsg->type;
                //已经审核完成的情况下进行结果回调
                if($type == 'finish'){
                    $result = $eventMsg->result;
                    $params['process_instance_id'] = $eventMsg->processInstanceId;//审批流ID
                    $params['status'] = ($result == 'agree') ? 2: 3;//2审批通过，3审批拒绝
                    $params['audit_time'] = floor($eventMsg->finishTime/1000);//审核完成时间
                    ElectromobileService::service()->updateProcess($params);
                }

            }

            $res = "success";
            $encryptMsg = "";
            $errCode = $crypt->EncryptMsg($res, $timeStamp, $nonce, $encryptMsg);
            file_put_contents("receive.txt",json_encode($errCode)."\r\n",FILE_APPEND);
            if ($errCode == 0) {
                echo $encryptMsg;
                Log::i("RESPONSE: " . $encryptMsg);
            } else {
                Log::e("RESPONSE ERR: " . $errCode);
            }
        }
    }
}
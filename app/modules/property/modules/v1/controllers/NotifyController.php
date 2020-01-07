<?php
namespace app\modules\property\modules\v1\controllers;
use app\models\PsRepairBill;
use service\alipay\BillService;
use service\alipay\OrderService;
use service\alipay\AlipayBillService;
use yii\helpers\FileHelper;
use yii\web\Controller;
use Yii;
use yii\web\Response;
use common\core\PsCommon;
use app\models\PsOrder;

/**
 * 小区初始化时需传入支付回调参数 external_invoke_address ，此为支付宝物业缴费回调地址
 */
class NotifyController extends Controller
{
    public $enableCsrfValidation =false;

    //钉钉扫码支付回调
    public function actionDing(){
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'text/xml; charset=UTF-8');
        $data = $_REQUEST;
        return BillService::service()->alipayNotifyDing($data);
    }

    // 小程序支付回调
    public function actionSmall()
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'text/xml; charset=UTF-8');
        $data = $_REQUEST;
        return BillService::service()->alipayNotifySmall($data);
    }

    // 小程序报事报修支付回调
    public function actionSmallRepair()
    {
        $data = $_REQUEST;
        return BillService::service()->alipayNotifySmallRepair($data);
    }
}
<?php
namespace app\controllers;

use service\common\AliPayQrCodeService;
use Yii;
use yii\web\Controller;

/**
 * Site controller
 */
class TestController extends Controller
{

    public function actionIndex()
    {
        $service = new AliPayQrCodeService();
        $url_param = "page/index/index";
        $query_param = "backCode=1&community_id=123456";
        $desc = '测试二维码';
        $service->createQrCode($url_param, $query_param, $desc);
    }
}

<?php
/**
 * 回调控制器
 * @author shenyang
 * @date 2017/9/15
 */
namespace alisa\modules\sharepark\controllers;

use alisa\services\AlipaySmallApp;
use common\services\park\UserService;
use Yii;
use yii\web\Controller;

Class CallbackController extends Controller {
    public $enableCsrfValidation = false;

    //支付宝回调
    public function actionAlipay()
    {
        $notify = Yii::$app->request->post();
        $service = new AlipaySmallApp('sharepark');
        if(!$service->rsaCheck($notify)) {//非正常支付宝请求
            return false;
        }
        if($notify['trade_status'] == 'TRADE_SUCCESS' || $notify['trade_status'] == 'TRADE_FINISHED') {
            $params = [
                'trade_no'=>$notify['out_trade_no'],
                'buyer_id'=>$notify['buyer_id'],//买家支付宝帐号ID
                'payment_no'=>$notify['trade_no'],//支付宝交易凭证
                'total_fee'=>$notify['total_amount'],
                'pay_time'=>$notify['gmt_payment'],//交易付款时间
            ];
            //交易成功
            UserService::service()->paySuccess($params);
        } else {//交易失败
            $params = [
                'trade_no'=>$notify['out_trade_no'],
                'error'=>'alipay_err'
            ];
            UserService::service()->payFail($params);
        }
        return 'success';
    }
}

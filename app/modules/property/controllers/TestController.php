<?php
/**
 * 陈科浪测试，支付宝消息发送
 * @author ckl
 * @date 2019-9-19
 */

namespace app\modules\property\controllers;

use service\alipay\SmallSendService;
use service\common\AlipaySmallApp;
use yii\web\Controller;
use Yii;

Class TestController extends Controller
{
    public function actionSend(){
        //获取支付宝会员信息
        $service = new AlipaySmallApp();
        $to_user_id = '2088702944312592';
        $form_id = 'MjA4ODcwMjk0NDMxMjU5Ml8xNTc3MjYwMzE2MzQzXzA3OA==';  //表单id，保修表中有存
        $id = '301';    //保修id
        $r = $service->sendRepairMsg($to_user_id,$form_id,$id);
        print_r($r);
    }

}

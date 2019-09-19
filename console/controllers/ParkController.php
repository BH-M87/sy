<?php
/**
 * 共享停车脚本
 * @author shenyang
 * @date 2017/09/28
 */
namespace console\controllers;

use common\services\park\MessageService;
use common\services\park\UserService;
use EasyWeChat\Foundation\Application;
use Yii;

Class ParkController extends ConsoleController
{
    //public $lock = YII_ENV == 'master' ? true : false;

    //创建/更新菜单
    public function actionMenu()
    {
        $app = new Application(Yii::$app->params['wechat']['sharepark']['wechat']);
        $app->menu->destroy();
        $host = Yii::$app->params['wechat']['sharepark']['host'];
        $menus = [
            [
                'type'=>'view',
                'name'=>'找车位',
                'url'=>$host
            ],
            [
                'type'=>'view',
                'name'=>'共享车位',
                'url'=>$host."/park/pub"
            ],
            [
                'type'=>'view',
                'name'=>'我',
                'url'=>$host."/my"
            ],
        ];
        $flag = $app->menu->add($menus);
        var_dump($flag);
    }

    //发送模版消息
    public function actionSendMessage()
    {
        $msgs = MessageService::service()->unsends();
        foreach($msgs as $msg) {
            try {
                $url = $this->_setUrl($msg);//配置消息详情跳转链接
                //发送
                $app = new Application(Yii::$app->params['wechat']['sharepark']['wechat']);
                $result = $app->notice->send([
                    'touser'=>$msg['openid'],
                    'template_id'=>$msg['template_id'],
                    'url'=>$url,
                    'data'=>$msg['data']
                ]);

                MessageService::service()->sentReport($msg['id'], $result);
            } catch (\Exception $e) {
                $this->log($e->getMessage());
            }
        }
    }

    //返回不同类型的消息跳转链接
    private function _setUrl($msg)
    {
        $route = '';
        if($msg['type'] == 1) {
            $route = '/my/set-time?id='.$msg['type_id'];
        } elseif($msg['type'] == 2) {
            $route = '/my/reason?id='.$msg['type_id'];
        } elseif($msg['type'] == 3) {//出租记录详情页
            $route = '/my/rent-detail?id='.$msg['type_id'];
        } elseif($msg['type'] == 4) {//提现，钱包页
            $route = '/my/wallet';
        } elseif($msg['type'] == 5) {
            $route = '/my/park-detail?id='.$msg['type_id'];//停车记录详情页
        }
        return Yii::$app->params['wechat']['sharepark']['host'].$route;
    }

    //提现，企业付款
    public function actionPick()
    {
        return '';//修改为页面直接转账，不走脚本
        $picks = UserService::service()->getUnPick();
        if(!$picks) {
            return true;
        }
        $app = new Application(Yii::$app->params['wechat']);
        $merchantPay = $app->merchant_pay;
        foreach ($picks as $pick) {
            $data = [
                'partner_trade_no'=>$pick['trade_no'],
                'openid'=>$pick['openid'],
                'check_name'=>'NO_CHECK',
                'amount'=>($pick['amount']*100),//单位分
                'desc'=>'停车驿用户提现',
                'spbill_create_ip'=>'127.0.0.1'
            ];

            try {
                $result = $merchantPay->send($data);
                UserService::service()->pickResult($pick['trade_no'], $result);
            } catch (\Exception $e) {
                $this->log([$pick['id'], $e->getMessage()]);
            }
        }
        return true;
    }
}

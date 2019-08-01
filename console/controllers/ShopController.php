<?php
/**
 * 商家脚本
 * @author shenyang
 * @date 2017/9/29
 */
namespace console\controllers;

use common\services\shop\ShopService;
use EasyWeChat\Foundation\Application;
use Yii;

Class ShopController extends ConsoleController
{
    //public $lock = YII_ENV == 'master' ? true : false;
    /**
     * 菜单
     */
    public function actionMenu()
    {
        $app = new Application(Yii::$app->params['wechat']['shop']['wechat']);
        $app->menu->destroy();
        $host = Yii::$app->params['wechat']['shop']['host'];
        $menus = [
            [
                'type'=>'view',
                'name'=>'我的账户',
                'url'=>$host.'/shop/home'
            ]
        ];
        $flag = $app->menu->add($menus);
        var_dump($flag);
    }

    /**
     * 消息推送
     */
    public function actionNotice()
    {
        $r = ShopService::service()->unsends();
        $msgs = $r['data'];
        $r = ShopService::service()->templates();
        $templates = $r['data'];
        foreach($msgs as $msg) {
            $template = !empty($templates[$msg['type']]) ? $templates[$msg['type']] : [];
            if(!$template) {
                ShopService::service()->state($msg['id'], [], '', 'empty template');
                continue;
            }
            //详情地址
            $url = Yii::$app->params['wechat']['shop']['host'].'/site/record?type='.$msg['type'].'&id='.$msg['obj_id'];
            $r = ShopService::service()->messageData($msg['obj_id'], $msg['type'], $msg['shop_id']);
            $data = $r['data'];
            if(!$data) {
                ShopService::service()->state($msg['id'], [], '', 'empty data');
                continue;
            }

            //发送
            $app = new Application(Yii::$app->params['wechat']);
            $result = $app->notice->send([
                'touser'=> $msg['openid'],
                'template_id'=>$template['template_id'],
                'url'=>$url,
                'data'=>$data
            ]);

            if($result['errcode']==0 && $result['errmsg'] == 'ok') {//成功
                ShopService::service()->state($msg['id'], $data, $result['msgid']);
                echo $msg['id']." success \n";
            } else {//失败
                ShopService::service()->state($msg['id'], $data, $result['msgid'], $result['errmsg']);
                echo $msg['id']." failed \n";
            }
        }
    }
}

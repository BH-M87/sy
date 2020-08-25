<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/8/21
 * Time: 9:16
 */
namespace service\visit;

use service\alipay\IsvLifeService;
use service\BaseService;
use Yii;

class AlipaysTemplateService extends BaseService{

    public $template_id = "3c6d726e4476484dbe1467dd70d78127"; //共享停车小程序消息模板id

    //获取阿里实例
    public function getAliService(){
        $alipayPublicKey = file_get_contents(Yii::$app->params['out_alipay_public_key_file']);
        $rsaPrivateKey = file_get_contents(Yii::$app->params['out_rsa_private_key_file']);
        $alipayLifeService = new IsvLifeService(Yii::$app->params['out_app_id'], null, null, $alipayPublicKey, $rsaPrivateKey);
        return $alipayLifeService;
    }


    /**
     * 获取支付宝二维码
     * @param $url_param
     * @param $query_param
     * @param $desc
     * @return string
     */
    public function sendMessage($params){

        if(empty($params['to_user_id'])){
            return $this->failed('触达消息的支付宝user_id不能为空！');
        }
        if(empty($params['form_id'])){
            return $this->failed('小程序产生表单提交的表单号不能为空！');
        }
        if(empty($params['page'])){
            return $this->failed('小程序的跳转页面不能为空！');
        }
        if(empty($params['data'])){
            return $this->failed('开发者需要发送模板消息中的自定义部分来替换模板的占位符不能为空！');
        }
        $params['user_template_id'] = $this->template_id;

        $alipayLifeService = $this->getAliService();
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }
        $result = $alipayLifeService->smallPushMsg($params);
        if ($result['code'] = '10000') {
            return true;
        } else {
            return "";
        }
    }
}
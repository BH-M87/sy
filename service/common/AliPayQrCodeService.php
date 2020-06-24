<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/7/2
 * Time: 17:36
 */

namespace service\common;

use common\core\Curl;
use common\core\F;
use common\MyException;
use service\alipay\MemberCardService;
use service\BaseService;
use service\qiniu\UploadService;
use yii\helpers\FileHelper;

class AliPayQrCodeService extends BaseService
{
    /**
     * 获取支付宝二维码
     * @param $url_param
     * @param $query_param
     * @param $desc
     * @return string
     */
    public static function getAliQrCode($url_param, $query_param, $desc,$type='park')
    {
        //组装参数
        $params['type'] = $type;//指向哪个小程序
        $params['url_param'] = $url_param;//url 地址
        $params['query_param'] = $query_param; //参数
        $params['describe'] = $desc;//二维码描述
        //二维码方法
        $result = MemberCardService::service()->getQrcode($params);
        if ($result['code'] = '10000') {
            $url = $result['qr_code_url'].'.jpg';
            return $url;
        } else {
            return "";
        }
    }

    /**
     * 获取支付宝二维码
     * @param $url_param
     * @param $query_param
     * @param $desc
     * @return string
     */
    public static function sendMessage($to_user_id, $form_id, $page,$content)
    {
        //data数据示例
        $data['keyword1'] = ['value'=>$content];
        //组装参数
        $params['to_user_id'] = $to_user_id;//支付宝用户id
        $params['form_id'] = $form_id; //表单id
        $params['page'] = $page;//小程序的跳转页面
        //开发者需要发送模板消息中的自定义部分来替换模板的占位符。 注意：占位符必须和申请模板时的关键词一一匹配。
        //{“keyword1”: {“value” : “12:00”}, “keyword2”: {“value” : “20180808”}, “keyword3”: {“value” : “支付宝”}}
        $params['data'] = json_encode($data);//二维码描述
        $result = MemberCardService::service()->sendMessage($params);
        if ($result['code'] = '10000') {
            return true;
        } else {
            return "";
        }
    }

}
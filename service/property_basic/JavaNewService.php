<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2019/11/27
 * Time: 14:51
 * Desc: java接口调用service
 */

namespace service\property_basic;

use service\BaseService;
use common\core\Curl;

class JavaNewService extends BaseService
{
	private $appkey = 'community-property';
    private $appSecret = 'cMchTBCquBl3IWnHmt07i4pVSTXB18rqWR';
    private $url = 'https://test-communityb.lvzhuyun.com';

    /**
     * 生成java签名
     * @param $params
     * @param $appSecret
     * @return string
     */
    public function sign($params,$appSecret)
    {
        $signParams = [];
        foreach($params as $k =>$v){
            if($k !== 'sign' && !is_array($v) && $v !== '' && $v !== null){
                $signParams[$k] = $v;
            }
        }
        ksort($signParams);
        $string = http_build_query($signParams).$appSecret;
        $string = urldecode($string);
        return md5($string);
    }

    /**
     * 统一处理公共参数
     * @param $paramData
     * @return false|string
     */
    public function dealPostData($paramData)
    {
        $paramData['appKey'] = \Yii::$app->modules['property']->params['iotNewAppKey'];
        $paramData['timestamp'] = time();
        $paramData['sign'] = $this->sign($paramData,\Yii::$app->modules['property']->params['iotNewAppSecret']);
        //print_r($paramData);die;
        return json_encode($paramData);
    }

    /**
     * 统一处理返回结果
     * @param $res
     * @return bool|string
     * todo 由于现在JAVA系统还不是很稳定目前需要先记录日志，后续稳定以后可以去除成功的日志
     */
    public function javaPost($url,$postData)
    {
        $postUrl = \Yii::$app->modules['property']->params['iotNewUrl'].$url;
        $options['CURLOPT_HTTPHEADER'] = ['Content-TYpe:application/json'];
        $model = new Curl($options);
        $res = $model->post($postUrl, $this->dealPostData($postData));
        if ($res) {
            $result = json_decode($res,true);
            if($result['code'] == 1){
                return $this->success($result['data']);
            } else {
                error_log('[' . date('Y-m-d H:i:s', time()) . ']' . PHP_EOL . "请求url：".$postUrl . "请求参数：".$this->dealPostData($postData) . PHP_EOL . '返回结果：' . json_encode($res).PHP_EOL, 3, \Yii::$app->getRuntimePath().'/logs/java_error.log');
                return $this->failed($result['message']);
            }
        } else {
            return $this->failed("java接口未返回有效信息");
        }
    }
}
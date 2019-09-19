<?php
/**
 * 接口
 * @author shenyang
 * @date 2017/09/14
 */
namespace common\core;

use Yii;
use yii\base\Exception;

Class Api
{
    public static $_instance;
    public $url;
    public $query;
    public $secret='8JN2W*fC';

    public function __construct()
    {
    }

    /**
     * 单例
     * @return Api
     */
    public static function getInstance()
    {
        if(!self::$_instance || !is_object(self::$_instance))
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 生成签名
     * @param $data
     * @return string
     */
    private function _sign($data)
    {
//        if(empty(Yii::$app->params['secret'])) {
//            throw new Exception('需要为项目配置Api密钥，并在服务端保持一致');
//        }
        ksort($data);
        return md5(http_build_query($data).$this->secret);
    }

    /**
     * 参数整理
     * @param $data
     * @return array
     */
    private function _requestData($data)
    {
        $data['time'] = time();
        $sign = $this->_sign($data);
        $data['sign'] = $sign;
        $query = ['data'=>json_encode($data)];
        $this->query = $query;
        return $query;
    }

    private function _result($data, $log=false)
    {
        $response = json_decode($data, true);
        if(!$response || $log || !isset($response['code']) || !isset($response['data'])) {
            //API请求错误+强制生成日志
            $logData['url'] = $this->url;
            $logData['data'] = $this->query;
            $logData['response'] = $data;
            Yii::info(json_encode($logData, JSON_UNESCAPED_UNICODE), 'api');
        }

        if(!$response || !isset($response['code']) || !isset($response['data'])) {
            return [
                'errCode'=>'50001',
                'data'=>[],
                'errMsg'=>'服务器错误'
            ];
        }

        $result['errCode'] = $response['code'] == 20000 ? 0 : $response['code'];
        $result['data'] = $response['data'];
        $result['errMsg'] = !empty($response['error']['errorMsg']) ? $response['error']['errorMsg'] : (!empty($response['errorMsg']) ? $response['errorMsg'] : '');
        return $result;
    }

    /**
     * 单个get
     * @param $route
     * @param $paramFormat 请求参数格式化，默认为需要格式化，转化成  $param['data'] = '{"name":"西城"}';
     * @param $hasSign 接口是否需要验签
     * @param array $data
     */
    public function get($url, $data=[])
    {
        $this->url = $url;
        $result = Curl::getInstance()->get($url, $data);
        //print_r($result);exit;
        return $this->_result($result, true);
    }

    /**
     * 单个post
     * @param $route
     * @param array $data
     * @param $hasSign 接口是否需要验签
     * @return array
     */
    public function post($url, $data=[])
    {
        $this->url = $url;
        $result = Curl::getInstance()->post($url, $data);
        return $this->_result($result, true);
    }
}

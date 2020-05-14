<?php
/**
 * Created by PhpStorm.
 * User: J.G.N
 * Date: 2019/11/18
 * Time: 10:46
 */
namespace common\core;

use Yii;

class JavaCurl {

    private static $instance = null;
    //java B端 host
//    const API_HOST = 'https://test-communityb.lvzhuyun.com';
//    const C_API_HOST = 'https://test-communityc.lvzhuyun.com';
//    const API_HOST = 'https://communityb.lvzhuyun.com';
//    const C_API_HOST = 'https://communityc.lvzhuyun.com';
    //日志文件
    private static $pushLog = '/logs/java_push.log';  //java推送过来数据日志
    private static $pullLog = '/logs/java_pull.log';  //拉取java数据日志

    const API_GET_ACCESS_TOKEN = '/get_access_token';   //获取java token

    private $config = [];
    private $serverType = '';
    private $contentType = "application/json";//类型

    private function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
    }

    public static function getInstance($config = [])
    {
        self::$instance or self::$instance = new self($config);
        return self::$instance;
    }

    /**
     * Notes: 获取请求token
     * Author: J.G.N
     * Date: 2019/11/18 10:59
     * @param $query
     * @return mixed
     */
    public function getAccessToken($query)
    {
        self::pullLog(json_encode($query).'==获取token参数==');
        $params = [

        ];
        $response = $this->request(self::API_GET_ACCESS_TOKEN, $params, [], 'post');
        $corpAccessToken = $response['access_token'];
        return $corpAccessToken;
    }

    /**
     * Notes: B端拉取java数据
     * Author: J.G.N
     * Date: 2019/11/18 11:10
     * @param $query
     * @return mixed
     */
    public function pullHandler($query)
    {
        $this->serverType = 'B';//B端请求
        $query['timestamp'] = time();
        $query['appKey'] = 'community-platform';
        $response = $this->request($query['route'], $query, [], 'POST');
        return $response;
    }

    /**
     * Notes: C端请求java接口
     * Author: J.G.N
     * Date: 2019/11/20 11:14
     * @param $query
     * @return mixed
     */
    public function clientHandler($query)
    {
        $this->serverType = 'C';//C端请求
        $query['timestamp'] = time();
//        $query['appKey'] = 'community-platform';
        $response = $this->request($query['route'], $query, [], 'POST');
        return $response;
    }

    /**
     * Notes: curl 请求
     * Author: J.G.N
     * Date: 2019/11/18 14:26
     * @param string $route
     * @param $postData
     * @param array $getData
     * @param string $method
     * @return mixed
     */
    public function request($route = '', $postData, $getData = [], $method = 'POST')
    {
        $log = Yii::$app->getRuntimePath().self::$pullLog;

        if (!empty($postData['uploadFile'])) {
            $this->contentType = 'multipart/form-data';
        }

        //设置请求header
        $options = [
            CURLOPT_HTTPHEADER => [
                "Content-Type:".$this->contentType,
            ]
        ];

        if ($this->serverType == 'B') {
            array_push($options[CURLOPT_HTTPHEADER], "Authorization: ".$postData['token']);
        }
        if ($this->serverType == 'C') {
            array_push($options[CURLOPT_HTTPHEADER], "OpenAuthorization: ".$postData['token']);
        }

        $ch = curl_init();
        //重写请求路由
        $url = $this->parseUrl($route, $getData);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }

        if ($method == 'POST') {
            $postData = json_encode($postData);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //开启调试，查看请求header信息
        curl_setopt($ch, CURLOPT_VERBOSE, true);
//        curl_setopt($ch, CURLOPT_STDERR, fopen($log,'a+'));
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);

        if ($route == '/user/validate-token') {
            error_log('[' . date('Y-m-d H:i:s', time()) . ']' . PHP_EOL . "请求url：".$url . "请求参数：".$postData . PHP_EOL . '返回结果：' . json_encode($response).PHP_EOL, 3, \Yii::$app->getRuntimePath().'/logs/javaToken.log');
        } else {
            error_log('[' . date('Y-m-d H:i:s', time()) . ']' . PHP_EOL . "请求url：".$url . "请求参数：".$postData . PHP_EOL . '返回结果：' . json_encode($response).PHP_EOL, 3, \Yii::$app->getRuntimePath().'/logs/java.log');
        }
        
        if (!$response['code'] || !in_array($response['code'], [0,1,200])) {
//            self::pullLog($url . PHP_EOL . $postData."getParams:".json_encode($getData) . PHP_EOL . json_encode($response) . PHP_EOL);
            if ($route != '/member/integral/grant') {
                self::errorResult($response);
            }
        }

        return $response['data'];
    }

    /**
     * Notes: 错误异常返回
     * Author: J.G.N
     * Date: 2019/11/18 11:09
     * @param $response
     * @throws \yii\base\Exception
     */
    private function errorResult($response) {
        $result = [
            'code' => $response['code'],
            'data' => (object)[],
            'message' => $response['message'],
        ];
//        throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        exit(json_encode($result, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 解析接口 url
     * @param $route
     * @param array $getData
     * @return string
     */
    private function parseUrl($route, $getData = [])
    {
        switch (YII_ENV) {
            case "master":
                $api_host = "https://communityb.lvzhuyun.com";
                $c_api_host = "https://communityc.lvzhuyun.com";
                break;
            case "test":
                $api_host = "https://test-communityb.lvzhuyun.com";
                $c_api_host = "https://test-communityc.lvzhuyun.com";
                break;
            default :
                $api_host = "https://test-communityb.lvzhuyun.com";
                $c_api_host = "https://test-communityc.lvzhuyun.com";
                break;
        }

        switch ($this->serverType) {
            case 'B':
                $url = $api_host . $route;break;
            case 'C':
                $url = $c_api_host . $route;break;
            default :
                $url = $api_host . $route;
        }
        if (!empty($getData)) {
            $query = '';
            foreach ($getData as $k => $v) {
                $query .= $k . '=' . $v . '&';
            }
            $query = substr($query, 0, count($query) - 2);
            $url .= ( strpos($url, '?') === false ? '?' : '&' ) . $query;
        }
        return $url;
    }

    /**
     * 钉钉的推送日志，包含get、post 密文，以及解密后的 post 明文
     * @param $info
     */
    private static function pushLog($info)
    {
        self::_log($info, Yii::$app->getRuntimePath().self::$pushLog);
    }

    /**
     * 拉取钉钉接口日志，包含 url、post，以及钉钉的返回值
     * @param $info
     */
    private static function pullLog($info)
    {
        self::_log($info, Yii::$app->getRuntimePath().self::$pullLog);
    }

    private static function _log($info, $file)
    {
        error_log('[' . date('Y-m-d H:i:s', time()) . ']' . PHP_EOL . $info . PHP_EOL, 3, $file);
    }


//    ============================== 定时脚本获取数据==================================
    /**
     * Notes: 通过appkey appsecret拉取java数据
     * Author: zhd
     * Date: 2019/11/21 11:20
     * @param $query
     * @return mixed
     */
    public function pullHandlerZhd($route, $query)
    {
        $query['timestamp'] = time();
        $query['appKey'] = Yii::$app->params['appKey'];
        $query['signType'] = 'MD5';
        $query['sign'] = self::sign($query,Yii::$app->params['appSecret']);
        $response = $this->request($route, $query, [], 'POST');
        return $response;
    }

    /**
     * 生成java签名
     * * Author: zhd
     * Date: 2019/11/21 11:20
     * @param $params
     * @param $appSecret
     * @return string
     */
    public static function sign($params,$appSecret)
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

}
<?php
/**
 * Created by PhpStorm.
 * User: hyb
 * Date: 2016/12/2
 * Time: 11:44
 */

namespace common\core;

use Yii;
use yii\base\Exception;

/**
 * Curl工具类
 * @package app\utils
 */
class Curl
{

    //单例
    private static $instance = null;
    //curl句柄
    private static $ch = null;
    //默认配置
    private static $defaults = [];
    //配置条件
    private static $options = [];
    //http状态码
    private static $httpCode = 0;
    //请求结果
    private static $response = null;
    //CURL信息
    private static $curlInfo = [];
    //头信息
    private static $header = null;

    /**
     * CurlUtil constructor.
     * @param $options
     */
    public function __construct($options)
    {
        //初始化
        self::$ch = self::init($options);
    }

    /**
     * 单例实例化
     * @param array $options
     */
    public static function getInstance($options = [])
    {
        if (empty(self::$instance)) {
            self::$instance = new self($options);
        }
        return self::$instance;
    }

    /**
     * 初始化，开启句柄
     */
    private static function init($options = [])
    {
        $default = [
            'CURLOPT_CONNECTTIMEOUT' => 30,
            'CURLOPT_HEADER' => 0,
            'CURLOPT_RETURNTRANSFER' => 1,
        ];
        self::$options = array_merge($default, $options);
        //默认配置在此设置
        $options = [];
        $ch = curl_init();
        foreach (self::$options as $k => $v) {
            $options[constant($k)] = $v;
        }
        curl_setopt_array($ch, $options);    //批量配置设置
        return $ch;
    }

    /**
     * 发送请求获取报文
     * @return bool|null|string
     */
    private static function request()
    {
        self::$response = self::toUtf8(curl_exec(self::$ch));
        if (curl_errno(self::$ch)) {
            self::sendError(curl_error(self::$ch));
            return false;
        }
        if (self::$options['CURLOPT_HEADER']) {    //开启头信息
            $headerSize = curl_getinfo(self::$ch, CURLINFO_HEADER_SIZE);
            self::$header = substr(self::$response, 0, $headerSize);    //存储头信息
            return substr(self::$response, $headerSize);    //返回body
        }


        return self::$response;
    }

    /**
     * GET操作
     * @param $url
     * @param string $query
     * @return bool|null|string
     */
    public static function get($url, $query = '')
    {
        if (!empty($query)) {
            $url .= strpos($url, '?') === false ? '?' : '&';
            $url .= is_array($query) ? http_build_query($query) : $query;
        }
        curl_setopt(self::$ch, CURLOPT_HTTPGET, 1);    //GET
        curl_setopt(self::$ch, CURLOPT_URL, $url);
        //if(YII_ENV != 'prod'){
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYHOST, 0);
        //}
        return self::request();
    }

    /**
     * POST操作，支持form和json两种形式
     * @param $url
     * @param string $query
     * @param bool $isJson
     * @return bool|null|string
     */
    public static function post($url, $query = '', $isJson = false)
    {
        if (!empty($query)) {
            curl_setopt(self::$ch, CURLOPT_POST, 1);    //POST
            curl_setopt(self::$ch, CURLOPT_URL, $url);
            if (!$isJson) {
                //form
                //curl_setopt(self::$ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=utf-8"]);
            } else {
                //json
                curl_setopt(self::$ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json; charset=utf-8"]);
            }
            if (is_array($query)) {
                $parameters = http_build_query($query, null, '&');
            } else {
                $parameters = $query;
            }
            curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $parameters);
            if(YII_ENV != 'prod'){
                curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt(self::$ch, CURLOPT_SSL_VERIFYHOST, 0);
            }
        } else {
            self::get($url);
        }
        return self::request();
    }

    /**
     * SSL安全连接，链式操作
     * @return null
     */
    public static function ssl()
    {
        //是否检测证书，默认1。从证书中检查SSL加密算法是否存在，
        //0-不检查，1-检查证书中是否有CN(common name)字段，2-在1的基础上校验当前的域名是否与CN匹配
        curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, 0);
        //[0、2]，1貌似不支持，经常被开发者用错，所以去掉了，默认2
        curl_setopt(self::$ch, CURLOPT_SSL_VERIFYHOST, 0);
        return self::$instance;
    }

    /**
     * 获取报文头
     * @return null
     */
    public static function getHeader()
    {
        return self::$header;
    }

    /**
     * 获取HTTP状态码
     * @return int|mixed
     */
    public static function getHttpCode()
    {
        if (is_resource(self::$ch) && empty(self::$httpCode)) {
            self::$httpCode = curl_getinfo(self::$ch, CURLINFO_HTTP_CODE);
        }
        return self::$httpCode;
    }

    /**
     * 获取CURL信息
     * @return array|mixed
     */
    public static function getCurlInfo()
    {
        if (is_resource(self::$ch) && empty(self::$curlInfo)) {
            self::$curlInfo = curl_getinfo(self::$ch);
        }
        return self::$curlInfo;
    }

    /**
     * 将报文转为UTF-8编码
     * @param $str
     * @return string
     */
    private static function toUtf8($str)
    {
        if (json_encode($str) == 'null') {
            return iconv('GB2312', 'UTF-8//IGNORE', $str);
        }
        return $str;
    }

    /**
     * 拼接url
     * @param $url
     * @param $query
     * @return string
     */
    private static function buildUrl($url, $query)
    {
        $url .= strpos($url, '?') === false ? '?' : '&';
        $url .= is_array($query) ? http_build_query($query) : '';
        return $url;
    }

    /**
     * 并发get
     * key => [
     *        'url'=>'xxx',
     *        'query'=>[],
     *        'options'=>[],//特殊的curlopt配置
     *        ''
     * ]
     * @param $urls
     * @return array
     */
    public static function multiGet($urls)
    {
        $mh = curl_multi_init();
        $chs = [];
        foreach ($urls as $key => $data) {
            if (empty($data['url'])) continue;
            $data['query'] = !empty($data['query']) ? $data['query'] : [];
            $data['options'] = !empty($data['options']) ? $data['options'] : [];

            $url = self::buildUrl($data['url'], $data['query']);
            $options = array_merge([
                'CURLOPT_URL' => $url,
                'CURLOPT_HTTPGET' => 1,
            ], $data['options']);
            $ch = self::init($options);

            curl_multi_add_handle($mh, $ch);
            $chs[$key] = $ch;
        }

        do {
            curl_multi_exec($mh, $running);
            //阻塞直到cURL批处理连接中有活动连接,不加这个会导致CPU负载超过90%.
            curl_multi_select($mh);
        } while ($running > 0);

        $result = [];
        foreach ($chs as $key => $v) {
            $result[$key] = curl_multi_getcontent($v);
            curl_multi_remove_handle($mh, $v);
        }

        curl_multi_close($mh);
        return $result;
    }

    /**
     * 打印错误
     * @param $errMsg
     * @throws Exception
     */
    private static function sendError($errMsg)
    {
        throw new Exception($errMsg);
    }

    /**
     * 关闭句柄
     */
    private static function close()
    {
        if (is_resource(self::$ch)) {
            curl_close(self::$ch);
        }
    }

    /**
     * 防止克隆对象
     */
    private function __clone()
    {
        //防止clone函数克隆对象，破坏单例模式
    }

    /**
     * 析构
     */
    public function __destruct()
    {
        self::close();
    }

}

?>
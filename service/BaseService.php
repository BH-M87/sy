<?php
namespace service;
use Yii;
use common\core\Api;

class BaseService {

    private static $_services = [];

    /**
     * 单例容器
     */
    public static function service($params = null) {
        $name = get_called_class();
        if(!isset(self::$_services[$name]) || !is_object(self::$_services[$name])) {
            $instance = self::$_services[$name] = new static($params);
            return $instance;
        }
        return self::$_services[$name];
    }

    /**
     * 防止克隆
     */
    private function __clone() {}

    /**
     * 操作失败
     * @param string $msg
     * @return array
     */
    public function failed($msg = '系统错误', $code=0) {
        return ['code'=>$code, 'msg'=>$msg];
    }

    /**
     * 操作成功
     * @param array $data
     * @return array
     */
    public function success($data=[]) {
        return ['code'=>1, 'data'=>$data];
    }

    /**
     * 缓存
     * @param $cacheKey
     * @param $expire
     * @param $closure
     * @return mixed
     */
    public function cache($cacheKey, $expire, $closure) {
        if(!$expire) {
            return $closure();
        }
        if(!$data = Yii::$app->cache->get($cacheKey)) {
            $data = $closure();
            Yii::$app->cache->set($cacheKey, $data, $expire);
        }
        return $data;
    }


    //get请求接口
    public function apiGet($url, $data = [], $log = false, $paramFormat = true, $hasSign = false)
    {
        return Api::getInstance()->get($url, $data);
    }

    //post请求接口
    public function apiPost($url, $data = [], $log = false, $paramFormat = true, $hasSign = false)
    {
        return Api::getInstance()->post($url, $data);
    }
}
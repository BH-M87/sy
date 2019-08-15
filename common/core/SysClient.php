<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/6/11
 * Time: 10:32
 */

namespace common\core;

class SysClient
{
    public static $_instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$_instance) || !is_object(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    //获取websocket服务端的ip+端口号
    private function getUrl()
    {
        $port = YII_ENV == 'master' ? 9800 : 9801;
        return 'http://127.0.0.1:' . $port;
    }

    //TODO 使用之前的
    private function postData($data)
    {
        if (is_array($data)) {
            $data = http_build_query($data);
        }
        try {
            //超时时间设置3s，超过3s，视为无法发送，否则影响接口性能
            Curl::getInstance(['CURLOPT_TIMEOUT' => 3])->post($this->getUrl(), $data);
            return true;
        } catch (\Exception $e) {
            \Yii::error($this->getUrl() . ' post failed : ' . json_encode($data));
            return false;
        }
    }

    //发送需要更新的数据
    public function send($type, $communityId, $uid, $data)
    {
        $data = ['type' => $type, 'community_id' => $communityId, 'uid' => $uid, 'data' => $data];
        $this->postData($data);
    }

    //重启
    public function reload()
    {
        $data = ['type' => 'reload'];
        $this->postData($data);
    }

    //关闭
    public function stop()
    {
        $data = ['type' => 'stop'];
        $this->postData($data);
    }
}
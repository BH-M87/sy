<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/6/10
 * Time: 14:12
 */

namespace common\core\WebSocket;


use common\core\SysClient;
use service\message\MessageService;
use service\rbac\UserService;
use Swoole\WebSocket\Server;

class WebSocket
{
    private $server;

    public function run()
    {
        $params = \Yii::$app->params['webSocket']['tcp'];
        $host = $params['host'] ?? '127.0.0.1';
        $port = $params['port'] ?? '9801';
        $setting = $params['swoole_setting'];
        $this->server = new Server($host, $port);
        $this->server->set($setting);
        $this->server->on('Start', [$this, 'start']);
        $this->server->on('workerStart', [$this, 'workerStart']);
        $this->server->on('open', [$this, 'open']);
        $this->server->on('message', [$this, 'message']);
        $this->server->on('request', [$this, 'request']);
        $this->server->on('close', [$this, 'close']);
        $this->server->start();
    }

    /**
     * @api 主进程
     * @author wyf
     * @date 2019/6/10
     */
    public function start()
    {
        var_dump("start事件触发");
    }

    /**
     * @param $server
     * @param $request
     * @api 工作进程
     * @author wyf
     * @date 2019/6/10
     */
    public function workerStart($server, $request)
    {
        var_dump("workerStart事件触发");
    }

    public function open($server, $request)
    {
        if (!empty($request->header['origin'])) {//记录访问的origin
            $this->log($request->fd . ' Open From Origin:' . $request->header['origin']);
        } else {
            $server->close($request->fd);//无有效origin，主动关闭
        }
    }

    /**
     * @param $server
     * @param $frame
     * @return string
     * @author wyf
     * @date 2019/6/10
     * @api 接受客户端发送的请求
     */
    public function message($server, $frame)
    {
        $this->log($frame->fd . ' onMessage -> ' . $frame->data);
        $message = json_decode($frame->data, true);
        if (!$message) {
            return '';
        }
        $communityId = !empty($message['community_id']) ? $message['community_id'] : 0;
        if (!$communityId) {
            $this->log($frame->fd . '小区ID不能为空');
            return $server->close($frame->fd);
        }
        //验证token
        $token = !empty($message['token']) ? $message['token'] : '';
        $userInfo = UserService::service()->getInfoByToken($token);
        if (!$userInfo['code']) {
            $this->log($frame->fd . ':token:' . $token . ' invalid');
            return $server->close($frame->fd);
        }
        $uid = $userInfo['data']['id'];
        //存入用户,小区和fd的绑定关系
        \Yii::$app->redis->sadd($this->setCacheName($communityId . $uid), $frame->fd);
        //存入fd和communityId的关系
        \Yii::$app->redis->hset($this->hashFdCache(), $frame->fd, $communityId . $uid);
    }

    /**
     * @param $request
     * @param $response
     * @return string
     * @author wyf
     * @date 2019/6/11
     * @api TODO 安全性 工作提醒推送
     */
    public function request($request, $response)
    {
        $data = $request->post;
        $type = $data['type'] ?? '';
        switch ($type) {
            case 'reload':
                $this->server->reload();
                break;
            case 'stop':
                $this->server->shutdown();
                break;
        }
        if (empty($data['uid'])) {
            return "";
        }
        if (empty($data['community_id'])) {
            return "";
        }
        if (is_array($data['uid'])) {
            $fdArray = [];
            foreach ($data['uid'] as $item) {
                $connections = $this->getAllFds($data['community_id'] . $item);
                if ($connections) {
                    $fdArray = array_merge($connections, $fdArray);
                }
            }
            $connections = $fdArray;
        } else {
            $connections = $this->getAllFds($data['community_id'] . $data['uid']);
        }
        if ($connections) {
            //推送的数据
            $info = $this->success($data['data']);
            $json_info = json_encode($info, JSON_UNESCAPED_UNICODE);
            foreach ($connections as $fd) {
                try {
                    if ($this->server->exist($fd)) {
                        $this->server->push($fd, $json_info);
                    }
                } catch (\Exception $e) {
                    $this->log($fd . ' invalid');
                    $this->server->close($fd);
                    continue;
                }
            }
        }
    }

    protected function transFrom($community_id, $uid)
    {
        $data['remind'] = MessageService::service()->getWorkerRemind(['community_id' => $community_id, 'user_id' => $uid])['data']['list'];
        return $this->success($data);
    }

    private function success($data)
    {
        return [
            'code' => 20000,
            'data' => $data,
            'error' => [
                'errorMsg' => ''
            ],
        ];
    }

    //获取当前小区所有的fd连接
    private function getAllFds($communityId)
    {
        return \Yii::$app->redis->smembers($this->setCacheName($communityId));
    }

    /**
     * @param $server
     * @param $fd
     * @api 关闭连接,清空redis里边的用户和fd绑定的信息
     * @author wyf
     * @date 2019/6/11
     */
    public function close($server, $fd)
    {
        \Yii::$app->redis->open();
        $communityId = \Yii::$app->redis->hget($this->hashFdCache(), $fd);
        if ($communityId) {//如果存在小区ID，从集合中移除
            \Yii::$app->redis->srem($this->setCacheName($communityId), $fd);
        }
        \Yii::$app->redis->hdel($this->hashFdCache(), $fd);
        var_dump("关闭成功");
    }

    /**
     * @return mixed
     * @author wyf
     * @date 2019/6/10
     * @api 重启
     */
    public function reload()
    {
        return SysClient::getInstance()->reload();
    }

    /**
     * @return mixed
     * @author wyf
     * @date 2019/6/10
     * @api 停止
     */
    public function stop()
    {
        return SysClient::getInstance()->stop();
    }

    //小区ID和fd关系cache name
    private function setCacheName($communityId)
    {
        return 'WuYe:webSocket:connections:' . YII_ENV . ':' . $communityId;
    }

    //fd和小区关系cache name
    private function hashFdCache()
    {
        return 'WuYe:webSocket:connections:' . YII_ENV;
    }

    //记录输出日志
    private function log($message)
    {
        $filePath = dirname(__DIR__) . '/../runtime/logs/swoole_out.log';
        $message = date('H:i:s') . ' ' . $message . PHP_EOL;
        \swoole_async_writefile($filePath, $message, function () {
        }, FILE_APPEND);
    }
}
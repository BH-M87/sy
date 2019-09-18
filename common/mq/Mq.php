<?php

namespace common\mq;


class Mq
{
    private static $connection_instance = null;
    private static $channel_instance = null;

    public static $exchange_instances = [];

    /*
     * @return AMQPConnection
     * @throws AMQPException
     */
    public static function getConnectionInstance()
    {

        $config = self::getConfig();
        if (!isset(static::$connection_instance)) {
            try {
                $connection = new \AMQPConnection($config);
                $connection->connect();
                return static::$connection_instance = $connection;
            } catch (\AMQPException $e) {
                self::error($e);
            }
        }

        return static::$connection_instance;
    }


    public static function getChannel()
    {
        if (!isset(self::$channel_instance)) {
            return self::$channel_instance = new \AMQPChannel(self::getConnectionInstance());
        }
        return self::$channel_instance;
    }

    public static function getExchange($name)
    {
        $exchange_key = md5(serialize($name));
//        $exchange_key = $name . $type . md5(serialize($arguments));

        if (!isset(self::$exchange_instances[$exchange_key])) {
            $exchange = new \AMQPExchange(self::getChannel());
            $exchange->setName($name);
//            $exchange->setType($type); //AMQP_EX_TYPE_DIRECT( =) AMQP_EX_TYPE_FANOUT (广播) AMQP_EX_TYPE_TOPIC(like)
//            $exchange->setFlags(AMQP_DURABLE); //设置AMQP_DURABLE后exchange{$name}将落地 否则进程结束,该exchange会自动释放掉
//            if ($arguments) {
//                $exchange->setArguments($arguments);
//            }
//            $exchange->declareExchange(); //create 如果是已经创建过的持久化交换机 不执行也行
            return self::$exchange_instances[$exchange_key] = $exchange;
        }
        return self::$exchange_instances[$exchange_key];
    }

    public static function getQueue($name)
    {
        $queue = new \AMQPQueue(self::getChannel());
        $queue->setName($name);
//        $queue->setFlags(AMQP_DURABLE); //设置AMQP_DURABLE后消息队列{$name}将落地 否则进程结束,该队列会自动释放掉
//        $queue->declareQueue(); //create 如果是已经创建过的持久化队列 不执行也行

        return $queue;
    }

    public static function getConfig()
    {
        if (!isset(\Yii::$app->params['rabbitmq'])) {
            throw new \Exception('rabbitmq文件不存在');
        }
        return \Yii::$app->params['rabbitmq'];
    }


    private static function error(\AMQPException $e)
    {
        $errCode = $e->getCode();

        $errCodeArr = [
            403 => 'Login was refused using authentication mechanism PLAIN',
            530 => 'access to vhost not allowed',
            504 => 'CHANNEL_ERROR  second \'channel.open\' seen'
        ];

        if (in_array($errCode, array_keys($errCodeArr), true)) {
            $errMsg = $errCodeArr[$errCode];
        } else {
            $errMsg = $e->getMessage();
        }

        throw new \AMQPException($errMsg, $e->getCode());
    }


    private function __clone()
    {
    }  //覆盖__clone()方法，禁止克隆

}
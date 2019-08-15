<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/6/17
 * Time: 14:37
 */

namespace console\controllers;


use common\mq\Enums\QueueEnums;
use common\mq\Mq;
use service\message\MessagePushService;
use yii\console\Controller;
use Yii;

class MessageQueueController extends Controller
{
    //发送间隔时间，分钟为单位
    public $sendSpace = [
        '1' => '1',
        '2' => '1',
        '3' => '5',
        '4' => '30',
        '5' => '60',
        '6' => '120',
        '7' => '180',
        '8' => '240'
    ];

    public function actionSend()
    {
        if (YII_ENV == "master"){
            $queue = Mq::getQueue(QueueEnums::MESSAGE_QUEUE);
        }else{
            $queue = Mq::getQueue(QueueEnums::TEST_MESSAGE_QUEUE);
        }
        while (true) {
            $queue->consume(function (\AMQPEnvelope $envelope, \AMQPQueue $queue) {
                try {
                    $routeKey = $envelope->getRoutingKey();
                    echo date('Y-m-d H:i:s') . "-------\n"; //stdout 输出到日志
                    $args = $envelope->getBody();
                    echo "----队列详情----\n";
                    $bodyData = json_decode($args, true);
                    echo json_encode($bodyData, JSON_UNESCAPED_UNICODE) . "\n";
                    //TODO 数据处理
                    $curlRe = MessagePushService::service()->addMessage($bodyData);
                    echo "----返回值---\n";
                    echo $curlRe . "\n";
                    if ($curlRe === true) {
                        //请求成功，踢出队列
                        $queue->ack($envelope->getDeliveryTag());
                    } else {
                        //失败的销毁
                        echo "----未成功数据---\n";
                        echo json_encode($bodyData, JSON_UNESCAPED_UNICODE). "\n";
                        $queue->nack($envelope->getDeliveryTag(), AMQP_NOPARAM);
                    }
                } catch (\yii\db\Exception $dbException) {
                    print_r($dbException->getMessage());
                    $offset = stripos($dbException->getMessage(), 'MySQL server has gone away');
                    if ($offset == true) {
                        echo "--数据库断线重连,塞回队列1--\n";
                        \Yii::$app->db->close();//释放文件句柄
                        \Yii::$app->db->open();
                    }
                    $queue->nack($envelope->getDeliveryTag(), AMQP_REQUEUE);
                } catch (\Exception $exception) {
                    echo "----捕捉异常---\n";
                    print_r($exception->getCode());
                    print_r("error:" . $exception->getMessage());
                    $offset = stripos($exception->getMessage(), 'MySQL server has gone away');
                    if ($offset == true) {
                        echo "--数据库断线重连,塞回队列2--\n";
                        \Yii::$app->db->close();//释放文件句柄
                        \Yii::$app->db->open();
                        $queue->nack($envelope->getDeliveryTag(), AMQP_REQUEUE);
                    } else {
                        $queue->nack($envelope->getDeliveryTag(), AMQP_NOPARAM);
                    }
                    // AMQP_REQUEUE 塞回队列 如果业务代码出错可能导致不停循环
                    // AMQP_NOPARAM 丢弃
                }
            });
        }
    }




}
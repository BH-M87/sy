<?php
/**
 * mq 生产者服务
 * User: wenchao.feng
 * Date: 2018/11/3
 * Time: 16:57
 */
namespace service\producer;

use common\mq\Enums\ExchangeEnums;
use common\mq\Enums\RouteKeyEnums;
use common\mq\Mq;

class MqProducerService extends CoreService {
    /************主要用于湖州项目，数据同步到数据平台*************/
    //基础数据推送
    public function basicDataPush($param)
    {
        $data = json_encode($param);
        $routeKey = RouteKeyEnums::BASICDATA;
        return Mq::getExchange(ExchangeEnums::BASICDATA_DIRECT_EXCHANGE)->publish($data, $routeKey);
    }

    //人行，车行数据推送
    public function passDataPush($param)
    {
        $data = json_encode($param);
        $routeKey = RouteKeyEnums::PASSDATA;
        return Mq::getExchange(ExchangeEnums::PASSDATA_DIRECT_EXCHANGE)->publish($data, $routeKey);
    }

    /************设备车行，人行数据上报，用队列做处理*************/
    //车辆进出
    public function carrecordDataPush($param)
    {
        $data = json_encode($param);
        $routeKey = RouteKeyEnums::CARRECORD_DATA;
        return Mq::getExchange(ExchangeEnums::CARRECORD_DIRECT_EXCHANGE)->publish($data, $routeKey);
    }

    //行人进出
    public function doorrecordDataPush($param)
    {
        $data = json_encode($param);
        $routeKey = RouteKeyEnums::DOORRECORD_DATA;
        return Mq::getExchange(ExchangeEnums::DOORRECORD_DIRECT_EXCHANGE)->publish($data, $routeKey);
    }

    //设备状态上报
    public function devicestatusDataPush($param)
    {
        $data = json_encode($param);
        $routeKey = RouteKeyEnums::DEVICESTATUS_DATA;
        return Mq::getExchange(ExchangeEnums::DEVICESTATUS_DIRECT_EXCHANGE)->publish($data, $routeKey);
    }

    /************IOT数据上报，用于异步同步数据到IOT*************/
    public function basicDataPushToIot($param)
    {
        $data = json_encode($param);
        $routeKey = RouteKeyEnums::BASICDATATOIOT;
        return Mq::getExchange(ExchangeEnums::BASICDATATOIOT_DIRECT_EXCHANGE)->publish($data, $routeKey);
    }
    //消息中心推送
    public function messagePush($param)
    {
        $data = json_encode($param);
        $routeKey = RouteKeyEnums::MESSAGE_DATA;
        return Mq::getExchange(ExchangeEnums::MESSAGE_DIRECT_EXCHANGE)->publish($data, $routeKey);
    }

    //消息中心推送(测试)
    public function testMessagePush($param)
    {
        $data = json_encode($param);
        $routeKey = RouteKeyEnums::TEST_MESSAGE_DATA;
        return Mq::getExchange(ExchangeEnums::TEST_MESSAGE_DIRECT_EXCHANGE)->publish($data, $routeKey);
    }
}
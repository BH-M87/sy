<?php

namespace common\mq\Enums;

class QueueEnums
{
    /************主要用于湖州项目，数据同步到数据平台*************/
    //基础数据
    const BASICDATA_QUEUE = 'basicdata.queue';
    //人行，车行数据
    const PASSDATA_QUEUE = 'passdata.queue';

    /************设备车行，人行数据上报，用队列做处理*************/
    //车辆出入场队列
    const CARRECORD_QUEUE = 'carrecord.queue';
    //行人出入记录队列
    const DOORRECORD_QUEUE = 'doorrecord.queue';
    //设备状态队列
    const DEVICESTATUS_QUEUE = 'devicestatus.queue';

    /************IOT数据上报，用于异步同步数据到IOT*************/
    const BASICDATATOIOT_QUEUE = 'basicdatatoiot.queue';

    /************消息中心，用队列做处理*************/
    const MESSAGE_QUEUE = 'message.queue';
    const TEST_MESSAGE_QUEUE = 'testmessage.queue';
}
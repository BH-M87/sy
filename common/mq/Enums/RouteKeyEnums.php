<?php

namespace common\mq\Enums;

class RouteKeyEnums
{
    /************主要用于湖州项目，数据同步到数据平台*************/
    //基础数据路由
    const BASICDATA = 'basicdata.push';
    //人行，车行数据路由
    const PASSDATA = 'passdata.push';

    /************设备车行，人行数据上报，用队列做处理*************/
    //车辆出入场路由
    const CARRECORD_DATA = 'carrecord.push';
    //行人出入记录路由
    const DOORRECORD_DATA = 'doorrecord.push';
    //设备状态路由
    const DEVICESTATUS_DATA = 'devicestatus.push';

    /************IOT数据上报，用于异步同步数据到IOT*************/
    const BASICDATATOIOT = 'basicdatatoiot.push';

    /************消息中心，用队列做处理*************/
    const MESSAGE_DATA = 'message.push';
    const TEST_MESSAGE_DATA = 'testmessage.push';
}
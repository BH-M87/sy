<?php

namespace common\mq\Enums;

class ExchangeEnums
{
    /************主要用于湖州项目，数据同步到数据平台*************/
    //基础数据推送，推送到数据平台
    const BASICDATA_DIRECT_EXCHANGE = 'basedata.direct.exchange';
    //车行，人行数据推送，推送到数据平台
    const PASSDATA_DIRECT_EXCHANGE = 'passdata.direct.exchange';

    /************设备车行，人行数据上报，用队列做处理*************/
    //车辆出入场频道
    const CARRECORD_DIRECT_EXCHANGE = 'carrecord.direct.exchange';
    //行人出入记录频道
    const DOORRECORD_DIRECT_EXCHANGE = 'doorrecord.direct.exchange';
    //设备状态频道
    const DEVICESTATUS_DIRECT_EXCHANGE = 'devicestatus.direct.exchange';

    /************IOT数据上报，用于异步同步数据到IOT*************/
    const BASICDATATOIOT_DIRECT_EXCHANGE = 'basedatatoiot.direct.exchange';

    /*****************消息中心,用队列处理******************/
    const MESSAGE_DIRECT_EXCHANGE = 'message.direct.exchange';
    const TEST_MESSAGE_DIRECT_EXCHANGE = 'testmessage.direct.exchange';
}
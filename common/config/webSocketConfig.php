<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/6/10
 * Time: 14:14
 */
return [
    'tcp' => [
        'host' => '0.0.0.0',                //服务监听ip
        'port' => YII_ENV == 'prod' ? 9800 : 9801,             //监听端口
        'swoole_setting' => [               //swoole配置
            'pack_max_length' => 1024 * 1024 * 2,
            'worker_num' => 4,
            'daemonize' => 1,
            'max_request' => 10000,
            'heartbeat_idle_time' => 600,//心跳，10分钟前端未发送数据则主动断开
            'heartbeat_check_interval' => 60,//心跳, 1分钟的遍历一次
            'log_file' => dirname(__DIR__) . '/../runtime/logs/swoole.log',
        ]
    ],
];
#!/bin/bash
#每分钟执行的脚本
#房屋迁出
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-fy command/move-out)
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-hf command/move-out)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-wc command/move-out)
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-fy command/move-out)
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-hf command/move-out)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-wc command/move-out)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-saas command/move-out)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-yanshi command/move-out)
#人行记录同步
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-fy command/record-sync-door)
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-hf command/record-sync-door)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-wc command/record-sync-door)
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-fy command/record-sync-door)
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-hf command/record-sync-door)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-wc command/record-sync-door)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-saas command/record-sync-door)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-yanshi command/record-sync-door)
#车行记录同步
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-fy command/record-sync-car)
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-hf command/record-sync-car)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-wc command/record-sync-car)
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-fy command/record-sync-car)
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-hf command/record-sync-car)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-wc command/record-sync-car)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-saas command/record-sync-car)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-yanshi command/record-sync-car)
#门禁出入记录设别名称修复同步
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-fy command/door-device-name)
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-hf command/door-device-name)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-wc command/door-device-name)
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-fy command/door-device-name)
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-hf command/door-device-name)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-wc command/door-device-name)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-saas command/door-device-name)
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-yanshi command/door-device-name)
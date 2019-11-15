#!/bin/bash
#每天0点执行的脚本
#线上富阳的同步设备厂商脚本
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-fy command/sync)
#线上合肥的同步设备厂商脚本
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-hf command/sync)
#线上五常的同步设备厂商脚本
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-wc command/sync)
#测试富阳的同步设备厂商脚本
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-fy command/sync)
#测试合肥的同步设备厂商脚本
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-hf command/sync)
#测试五常的同步设备厂商脚本
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-wc command/sync)
#测试saas的同步设备厂商脚本
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-saas command/sync)
#测试演示的同步设备厂商脚本
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-yanshi command/sync)
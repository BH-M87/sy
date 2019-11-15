#!/bin/bash
step=2 #间隔的秒数，不能大于60

for (( i = 0; i < 60; i=(i+step) )); do
    $(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-fy command/iot-data)
    $(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-hf command/iot-data)
    $(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-fy command/iot-data)
    $(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-hf command/iot-data)
    $(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-fy command/iot-face)
    $(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-hf command/iot-face)
    $(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-fy command/iot-face)
    $(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-hf command/iot-face)
    sleep $step
done

exit 0
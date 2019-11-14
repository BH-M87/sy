#!/bin/bash
step=2 #间隔的秒数，不能大于60

for (( i = 0; i < 60; i=(i+step) )); do
    $(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/iot-data)
    $(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/iot-face)
    $(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api_ss/yii command/iot-data)
    $(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api_wc/yii command/iot-data)
    sleep $step
done

exit 0
#!/bin/bash
step=3 #间隔的秒数，不能大于60

for (( i = 0; i < 60; i=(i+step) )); do
    $(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/iot-data)
    $(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/iot-face)
    sleep $step
done

exit 0
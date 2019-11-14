#!/bin/bash
#每天0点执行的脚本
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/sync)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api_ss/yii command/sync)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api_wc/yii command/sync)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_fy/yii command/sync)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_ss/yii command/sync)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_wc/yii command/sync)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_saas/yii command/sync)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_yanshi/yii command/sync)
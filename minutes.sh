#!/bin/bash
#每分钟执行的脚本
#房屋迁出
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/move-out)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api_ss/yii command/move-out)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api_wc/yii command/move-out)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_fy/yii command/move-out)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_ss/yii command/move-out)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_wc/yii command/move-out)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_saas/yii command/move-out)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_yanshi/yii command/move-out)
#人行记录同步
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/record-sync-door)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api_ss/yii command/record-sync-door)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api_wc/yii command/record-sync-door)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_fy/yii command/record-sync-door)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_ss/yii command/record-sync-door)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_wc/yii command/record-sync-door)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_saas/yii command/record-sync-door)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_yanshi/yii command/record-sync-door)
#车行记录同步
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/record-sync-car)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api_ss/yii command/record-sync-car)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api_wc/yii command/record-sync-car)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_fy/yii command/record-sync-car)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_ss/yii command/record-sync-car)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_wc/yii command/record-sync-car)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_saas/yii command/record-sync-car)
$(/usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/test/api_yanshi/yii command/record-sync-car)

#!/bin/bash
#每天9点30执行的脚本
#线上富阳的发任务通知脚本
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-fy command/street-index)
#线上合肥的发任务通知脚本
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-hf command/street-index)
#线上五常的发任务通知脚本
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-wc command/street-index)
#测试富阳的发任务通知脚本
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-fy command/street-index)
#测试合肥的发任务通知脚本
$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-hf command/street-index)
#测试五常的发任务通知脚本
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-wc command/street-index)
#测试saas的发任务通知脚本
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-saas command/street-index)
#测试演示的发任务通知脚本
#$(/usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-test-yanshi command/street-index)

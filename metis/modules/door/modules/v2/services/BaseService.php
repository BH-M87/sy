<?php
/**
 * 门禁开门方法
 * @author ZQ
 * Date: 2018/7/26
 * Time: 11:14
 */
namespace alisa\modules\door\modules\v2\services;

use Yii;
use common\services\BaseService as Base;

Class BaseService extends Base
{
    //获取接口完整地址
    public function apiUrl($route)
    {
        return Yii::$app->params['api_host'].'/door/v2/'.ltrim($route, '/');
    }
}
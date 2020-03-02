<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2019/11/27
 * Time: 14:51
 * Desc: java接口调用service
 */

namespace service\property_basic;

use service\BaseService;
use common\core\JavaDDCurl;

class JavaDDService extends BaseService
{
    /*
     * B 端调用
     */
    public function returnBData($query)
    {
        $result = JavaDDCurl::getInstance()->pullHandler($query);
        return $result;
    }


    // 获取公司钉钉token
    public function getCompanyToken($query)
    {
        $query['route'] = '/dd/access-token';
        return self::returnBData($query);
    }
}
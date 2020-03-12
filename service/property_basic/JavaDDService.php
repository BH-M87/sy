<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2019/11/27
 * Time: 14:51
 * Desc: java接口调用service 钉钉模块
 */

namespace service\property_basic;

use service\BaseService;
use common\core\JavaDDCurl;

class JavaDDService extends BaseService
{

    public function returnData($query)
    {
        $result = JavaDDCurl::getInstance()->pullHandler($query);
        return $result;
    }


    // b1 钉钉列表
    public function getB1List($query)
    {
        $query['route'] = '/ddserver/biz/b1-list';
        return self::returnData($query);
    }
}
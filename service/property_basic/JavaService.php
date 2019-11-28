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
use common\core\JavaCurl;

class JavaService extends BaseService{

    /**
     * Notes: 小区下拉列表
     */
    public function communityList($query){
        $query['route'] = '/community/nameList';
        $result =  JavaCurl::getInstance()->pullHandler($query);
        return $result;
    }
}
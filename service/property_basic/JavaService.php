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

    /*
     * 住户列表
     */
    public function residentList($query){
        $query['route'] = '/resident/list';
        $result =  JavaCurl::getInstance()->pullHandler($query);
        return $result;
    }

    /*
     * 房屋列表
     */
    public function roomList($query){
        $query['route'] = '/room/list';
        $result =  JavaCurl::getInstance()->pullHandler($query);
        return $result;
    }
}
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


    /*
     * c 端调用
     */
    public function returnCData($query){
        $result =  JavaCurl::getInstance()->pullHandler($query);
        return $result;
    }

    /**
     * Notes: 小区下拉列表
     */
    public function communityNameList($query){
        $query['route'] = '/community/nameList';
        return self::returnCData($query);
    }

    /*
     * 住户列表
     */
    public function residentList($query){
        $query['route'] = '/resident/list';
        return self::returnCData($query);
    }

    /*
     * 房屋列表
     */
    public function roomList($query){
        $query['route'] = '/room/list';
        return self::returnCData($query);
    }

    /*
     * 苑期区名称下拉
     */
    public function groupNameList($query){
        $query['route'] = '/group/nameList';
        return self::returnCData($query);
    }

    /*
     *楼栋名称下拉
     */
    public function buildingNameList($query){
        $query['route'] = '/building/nameList';
        return self::returnCData($query);
    }

    /*
     *单元名称下拉
     */
    public function unitNameList($query){
        $query['route'] = '/building/unit/nameList';
        return self::returnCData($query);
    }

    /*
     * 房屋名称下拉
     */
    public function roomNameList($query){
        $query['route'] = '/room/nameList';
        return self::returnCData($query);
    }
}
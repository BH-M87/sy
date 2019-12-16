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

class JavaOfCService extends BaseService{


    /*
     * B 端调用
     */
    public function returnCData($query){
        $result =  JavaCurl::getInstance()->clientHandler($query);
        return $result;
    }

    /**
     * Notes: 支付宝授权码获取openId/访问令牌
     */
    public function loginAuth($query){

        $query['accountType'] = 1;
        //支付宝授权码获取openId/访问令牌
        $query['route'] = '/member/api/login-auth';
        return self::returnCData($query);
    }

    /*
     * 获得会员信息
     */
    public function memberBase($query){

        $query['route'] = '/member/api/member-base';
        return self::returnCData($query);
    }

    /*
     * 首页展示的房屋信息[鉴权]
     */
    public function lastChosenRoom($query){
        $query['route'] = '/member/home/lastRoom';
        return self::returnCData($query);
    }

    // 房屋信息
    public function roomInfo($query)
    {
        $query['route'] = '/member/room/roomInfo';
        return self::returnCData($query);
    }

    // 图片上传
    public function uploadImg($query)
    {
        $query['route'] = '/member/file-tool/upload-img';
        return self::returnCData($query);
    }
}
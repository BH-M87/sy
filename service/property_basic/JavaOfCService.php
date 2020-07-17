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
     * C 端调用
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

    // 住户详情
    public function residentDetail($query)
    {
        $query['route'] = '/member/room/residentDetail';
        return self::returnCData($query);
    }

    // 授权码换取token信息
    public function exchangeAuthValue($query)
    {
        $query['route'] = '/member/auth/exchangeAuthValue';
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

    // 选择苑-幢
    public function blockList($query)
    {
        $query['route'] = '/member/room/blockList';
        return self::returnCData($query);
    }

    // 选择苑-幢(包含未审核数据)
    public function blockListForPhp($query)
    {
        $query['route'] = '/member/room/blockListForPhp';
        return self::returnCData($query);
    }

    // 选择单元-室
    public function roomList($query)
    {
        $query['route'] = '/member/room/roomList';
        return self::returnCData($query);
    }

    // 选择单元-室(包含未审核数据)
    public function selectRoomList($query)
    {
        $query['route'] = '/member/room/selectRoomListForPhp';
        return self::returnCData($query);
    }

    // 小区总户数和总房屋面积
    public function getTotalResidentAndAreaSize($query)
    {
        $query['route'] = '/member/room/getTotalResidentAndAreaSize';
        return self::returnCData($query);
    }

    // 图片上传
    public function uploadImg($query)
    {
        $query['route'] = '/member/file-tool/upload-img';
        return self::returnCData($query);
    }

    // 图片上传
    public function qiniuToken($query)
    {
        $query['route'] = '/member/file-tool/qiniu-token';
        return self::returnCData($query);
    }

    //积分新增
    public function integralGrant($query){
        $query['route'] = '/member/integral/grant';
        return self::returnCData($query);
    }

    // 统一收单交易 创建
    public function tradeCreate($query)
    {
        $query['route'] = '/payment/trade/create';
        return self::returnCData($query);
    }

    // 会员第三方信息查询
    public function thirdRelation($query)
    {
        $query['route'] = '/member/api/thirdRelation';
        return self::returnCData($query);
    }

    // 我的房屋列表
    public function myRoomList($query)
    {
        $query['route'] = '/member/room/myRoomList';
        return self::returnCData($query);
    }

    //获得住户房屋地址
    public function getResidentFullAddress($query){
        $query['route'] = '/member/room/getResidentFullAddress';
        return self::returnCData($query);
    }

    //小区文明码java统计
    public function civilizationStatistics($query){
        $query['route'] = '/php/statistics';
        return self::returnCData($query);
    }

    //获得小区一区一码模板生成的小程序二维码(生成二维码)
    public function selectCommunityQrCode($query){
        $query['route'] = '/appletTemplate/template-gateway';
        return self::returnCData($query);
    }

    //获得物业租户id
    public function selectCommunityById($query){
        $query['route'] = '/member/home/selectCommunityById';
        return self::returnCData($query);
    }

    //获取会员信息
    public function selectMemberInfo($query){
        $query['route'] = '/member/api/selectMemberInfo';
        return self::returnCData($query);
    }
}
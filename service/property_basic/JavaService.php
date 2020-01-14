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

class JavaService extends BaseService
{
    /*
     * c 端调用
     */
    public function returnCData($query)
    {
        $result = JavaCurl::getInstance()->pullHandler($query);
        return $result;
    }

    /**
     * Notes: 小区下拉列表
     */
    public function communityNameList($query)
    {
        $query['route'] = '/community/nameList';
        return self::returnCData($query);
    }

    /*
     * 住户列表
     */
    public function residentList($query)
    {
        $query['route'] = '/resident/list';
        return self::returnCData($query);
    }

    /*
     * 住户详情
     */
    public function residentDetail($query)
    {
        $query['route'] = '/resident/detail';
        return self::returnCData($query);
    }

    /*
     * 房屋列表
     */
    public function roomList($query)
    {
        $query['route'] = '/room/list';
        return self::returnCData($query);
    }

    /*
     * 苑期区名称下拉
     */
    public function groupNameList($query)
    {
        $query['route'] = '/group/nameList';
        return self::returnCData($query);
    }

    /*
     *楼栋名称下拉
     */
    public function buildingNameList($query)
    {
        $query['route'] = '/building/nameList';
        return self::returnCData($query);
    }

    /*
     *单元名称下拉
     */
    public function unitNameList($query)
    {
        $query['route'] = '/building/unit/nameList';
        return self::returnCData($query);
    }

    /*
     * 房屋名称下拉
     */
    public function roomNameList($query)
    {
        $query['route'] = '/room/nameList';
        return self::returnCData($query);
    }

    /*
     * 住户身份枚举
     */
    public function memberTypeEnum($query)
    {
        $query['route'] = '/resident/memberTypeEnum';
        return self::returnCData($query);
    }

    /*
      * 部门列表
      */
    public function treeList($query)
    {
        $query['route'] = '/dept/tree';
        return self::returnCData($query);
    }

    /*
    * 员工列表
    */
    public function userList($query)
    {
        $query['route'] = '/user/simple-list';
        return self::returnCData($query);
    }

    /*
     * 房屋详情
     */
    public function roomDetail($query)
    {
        $query['route'] = '/room/detail';
        return self::returnCData($query);
    }

    // 小区详情
    public function communityDetail($query)
    {
        $query['route'] = '/community/detail';
        return self::returnCData($query);
    }

    // 员工详情
    public function userDetail($query)
    {
        $query['route'] = '/user/detail';
        return self::returnCData($query);
    }

    // 添加日志
    public function logAdd($query)
    {
        $query['route'] = '/log/add';
        return self::returnCData($query);
    }

    // 根据用户id获取小区列表
    public function relCommunityList($query)
    {
        $query['route'] = '/user/rel-community-list';
        return self::returnCData($query);
    }

    // 图片上传
    public function qiniuToken($query)
    {
        $query['route'] = '/common/qiniu-token';
        return self::returnCData($query);
    }

    // 用户权限列表
    public function permissions($query)
    {
        $query['route'] = '/user/permissions';
        return self::returnCData($query);
    }

    // 发送钉钉oa通知
    public function sendOaMsg($query)
    {
        $query['route'] = '/user/send-oa-msg';
        return self::returnCData($query);
    }

    // 根据住户查询会员第三方信息
    public function memberRelation($query)
    {
        $query['route'] = '/resident/memberRelation';
        return self::returnCData($query);
    }

    // 获得单元树
    public function unitTree($query){
        $query['route'] = '/unit/unitTree';
        $result = self::returnCData($query);
        if(!empty($result['list'])){
            foreach($result['list'] as $key=>$value){
                if(!empty($value['children'])){
                    foreach($value['children'] as $ck=>$cv){
                        if(!empty($cv['children'])){
                            unset($result['list'][$key]['children'][$ck]['children']);
                        }
                    }
                }
            }
        }
        return $result;
    }

    //根据条件查询房屋列表
    public function roomQueryList($query){
        $query['route'] = '/room/query/list';
        return self::returnCData($query);
    }

    //根据条件查询房屋列表(分页)
    public function roomQueryPagingList($query){
        $query['route'] = '/room/query/paging/list';
        return self::returnCData($query);
    }

    //登录密码验证
    public function userValidatePwd($query){
        $query['route'] = '/user/validate-pwd';
        return self::returnCData($query);
    }

    //根据名称查询房屋详情
    public function roomQueryByName($query){
        $query['route'] = '/room/query/by-name';
        return self::returnCData($query);
    }

    // 统一收单交易 交易预创建
    public function tradePrecreate($query)
    {
        $query['route'] = '/payment/trade/precreate';
        return self::returnCData($query);
    }

    // 统一收单交易 退款
    public function tradeRefund($query)
    {
        $query['route'] = '/payment/trade/refund';
        return self::returnCData($query);
    }
    
    //根据小区ID查询全部住户
    public function residentSelectAllByCommunityId($query){
        $query['route'] = '/resident/selectAllByCommunityId';
        return self::returnCData($query);
    }

    //新增消息推送
    public function messageInsert($query){
        $query['route'] = '/message/insert';
        return self::returnCData($query);
    }
}
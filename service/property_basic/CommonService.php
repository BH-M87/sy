<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2019/12/27
 * Time: 17:13
 * Desc: 数据验证
 */
namespace service\property_basic;

use service\BaseService;

class CommonService extends BaseService  {

    /**
     * Notes: 小区验证
     * Author: zph
     * Date: 2019/12/27 17:30
     * @param $params input community_id token
     * @return bool true 存在 false 不存在
     */
    public function communityVerification($params){
        $flag = false;
        if(!empty($params['community_id'])&&!empty($params['token'])){
            //获得小区下拉
            $service = new JavaService();
            $javaParam['token'] = $params['token'];
            $result = $service->communityNameList($javaParam);
            if(!empty($result['list'][0])){
                $communityArr = array_column($result['list'],"name","key");
                if(array_key_exists($params['community_id'],$communityArr)){
                    $flag = true;
                }
            }
        }
        return $flag;
    }

    /**
     * Notes: 小区验证 返回小区名称
     * Author: zph
     * Date: 2019/12/27 17:30
     * @param $params input community_id token
     * @return bool true 存在 false 不存在
     */
    public function communityVerificationReturnName($params){
        $name = "";
        if(!empty($params['community_id'])&&!empty($params['token'])){
            //获得小区下拉
            $service = new JavaService();
            $javaParam['token'] = $params['token'];
            $result = $service->communityNameList($javaParam);
            if(!empty($result['list'][0])){
                $communityArr = array_column($result['list'],"name","key");
                if(array_key_exists($params['community_id'],$communityArr)){
                    $name = $communityArr[$params['community_id']];
                }
            }
        }
        return $name;
    }

    /*
     * 验证房屋是否存在
     * input: token,roomId,communityId,groupId,buildingId,unitId
     */
    public function roomVerification($params){
        $data = [];
        if(!empty($params['roomId'])&&!empty($params['token'])){
            //获得小区下拉
            $service = new JavaService();
            $result = $service->roomList($params);
            if(!empty($result['list'][0])){
                $data = $result['list'][0];
            }
        }
        return $data;
    }

    /*
     * 登录密码验证
     * input:token,password
     */
    public function passwordVerification($params){
        $service = new JavaService();
        $flag = $service->userValidatePwd($params);
        return $flag;
    }
}
<?php
/**
 * 主页相关接口
 * User: fengwenchao
 * Date: 2018/8/24
 * Time: 11:21
 */
namespace alisa\modules\door\modules\v2\services;
use common\libs\F;

class HomeService extends BaseService
{
    /**
     * @api 业主认证
     * @author wyf
     * @date 2019/5/31
     * @param $params
     * @return array
     */
    public function authTo($params)
    {
        return $this->apiPost('user/auth-to',$params, false, false);
    }

    /**
     * @api 智能门禁首页数据
     * @edit wyf
     * @date 2019/5/31
     * @param $params
     * @return array
     */
    public function getIndexData($params)
    {
        return $this->apiPost('user/index-data', $params, false, false);
    }

    /**
     * @api 人脸列表
     * @edit wyf
     * @date 2019/5/31
     * @param $params
     * @return array
     */
    public function faceList($params)
    {
        return $this->apiPost('user/face-list', $params, false, false);
    }

    /**
     * @api 获取住户列表
     * @edit wyf
     * @date 2019/5/31
     * @param $params
     * @return array
     */
    public function getResidentList($params)
    {
        return $this->apiPost('user/resident-list',$params, false, false);
    }

    /**
     * @api 取消蒙层
     * @author wyf
     * @date 2019/5/23
     * @param $params
     * @return array
     */
    public function userGuide($params)
    {
        return $this->apiPost('user/user-guide',$params, false, false);
    }

    /**
     * @api 欢迎回家页面
     * @author wyf
     * @date 2019/5/23
     * @param $params
     * @return array
     */
    public function userInfo($params)
    {
        return $this->apiPost('user/user-info', $params, false, false);
    }
}
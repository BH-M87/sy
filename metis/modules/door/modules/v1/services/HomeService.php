<?php
/**
 * 主页相关接口
 * User: fengwenchao
 * Date: 2018/8/24
 * Time: 11:21
 */
namespace alisa\modules\door\modules\v1\services;
use common\libs\Curl;
use common\libs\F;

class HomeService extends BaseService
{
    public function getUserId($params)
    {
        return $this->apiPost('user/get-app-user-id',$params, false, false);
    }

    //获取用户基本数据
    public function getUserData($params)
    {
        return $this->apiPost('user/get-user-data',$params, false, false);
    }

    //发送验证码
    public function sendNote($params)
    {
        return $this->apiPost('user/send-note',$params, false, false);
    }

    //业主认证
    public function authTo($params)
    {
        return $this->apiPost('user/auth-to',$params, false, false);
    }

    //获取首页数据
    // TODO 需要删除，老的逻辑
    public function getHomeData($params)
    {
        return $this->apiPost('user/home-data',$params, false, false);
    }

    //获取首页数据
    public function getIndexData($params)
    {
        return $this->apiPost('user/index-data', $params, false, false);
    }

    //人脸列表
    public function faceList($params)
    {
        return $this->apiPost('user/face-list', $params, false, false);
    }

    //获取住户列表
    public function getResidentList($params)
    {
        return $this->apiPost('user/resident-list',$params, false, false);
    }

    //删除住户
    public function delResident($params)
    {
        return $this->apiPost('user/resident-del',$params, false, false);
    }

    //住户详情
    public function getResidentDetail($params)
    {
        return $this->apiPost('user/resident-detail',$params, false, false);
    }

    //新增住户详情
    public function addResident($params)
    {
        return $this->apiPost('user/resident-add',$params, false, false);
    }
    
    // 会员卡开卡
    public function openCard($params)
    {
        return $this->apiPost('user/open-card',$params, false, false);
    }

    // 会员卡信息
    public function cardInfo($params)
    {
        return $this->apiPost('user/card-info',$params, false, false);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/8/24
 * Time: 16:11
 */

namespace alisa\modules\door\modules\v1\services;


class SelfService extends BaseService
{
    public function owner_home($params)
    {
        return $this->apiPost('self/owner-home',$params, false, false);
    }

    public function community_list($params)
    {
        return $this->apiPost('self/community-list',$params, false, false);
    }

    public function house_list($params)
    {
        return $this->apiPost('self/house-list',$params, false, false);
    }

    public function audit_submit($params)
    {
        return $this->apiPost('self/audit-submit',$params, false, false);
    }

    public function audit_detail($params)
    {
        return $this->apiPost('self/audit-detail',$params, false, false);
    }

    public function audit_house($params)
    {
        return $this->apiPost('self/audit-house',$params, false, false);
    }

    public function get_common($params)
    {
        return $this->apiPost('self/common',$params, false, false);
    }

    public function get_biz_id($params)
    {
        return $this->apiPost('self/get-biz-id',$params, false, false);
    }

    //支付宝人脸识别后保存图片
    public function ali_add_image($params)
    {
        return $this->apiPost('self/ali-add-image',$params, false, false);
    }

    //删除人脸
    public function clearFace($params)
    {
        return $this->apiPost('self/clear-face', $params, false, false);
    }
}
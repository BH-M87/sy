<?php
/**
 * 一人一档数据
 * User: wenchao.feng
 * Date: 2019/10/31
 * Time: 11:32
 */

namespace app\modules\street\modules\v1\controllers;


use app\models\PsMember;
use common\core\F;
use common\core\PsCommon;
use service\street\BasicDataService;
use service\street\PersonDataService;
use service\street\UserService;

class PersonDataController extends BaseController
{
    //列表
    public function actionList()
    {
        //登录者身份区县，街道，社区
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];

        $this->request_params['street_code'] = F::value($this->request_params, 'street_code', '');
        $this->request_params['district_code'] = F::value($this->request_params, 'district_code', '');
        if ($this->user_info['node_type'] == 1) {
            if ($this->request_params['street_code'] && $this->request_params['street_code'] != $this->user_info['dept_id']) {
                return PsCommon::responseFailed("无此街道的数据查看权限");
            }
            $this->request_params['street_code'] = $this->user_info['dept_id'];
        }
        if ($this->user_info['node_type'] == 2) {
            $this->request_params['district_code'] = $this->user_info['dept_id'];
            $this->request_params['street_code'] = UserService::service()->getStreetCodeByDistrict($this->request_params['district_code']);
        }

        $this->request_params['community_code'] = F::value($this->request_params, 'community_code', '');
        if ($this->request_params['community_code']) {
            $departInfo = BasicDataService::service()->getDepartInfoByCommunityCode($this->request_params['community_code']);
            $this->request_params['street_code'] = $departInfo?$departInfo['jd_org_code'] : '';
            $this->request_params['district_code'] = $departInfo?$departInfo['sq_org_code'] : '';
        }
        $this->request_params['member_name'] = F::value($this->request_params, 'member_name', '');
        $this->request_params['card_no'] = F::value($this->request_params, 'card_no', '');
        $this->request_params['label_id'] = F::value($this->request_params, 'label_id', []);

        $result = PersonDataService::service()->getList($this->request_params,$this->page,$this->pageSize);
        if($result) {
            return PsCommon::responseSuccess($result);
        } else {
            return PsCommon::responseFailed("数据获取失败");
        }

    }

    //详情
    public function actionView()
    {
        $id = F::value($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("用户id不能为空");
        }

        $result = PersonDataService::service()->view($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //人行记录
    public function actionAcrossDayReport()
    {
        $id = F::value($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("用户id不能为空");
        }
        //TODO
        $result = PersonDataService::service()->getDayReport($id);
        return PsCommon::responseSuccess($result);
    }

    //人行记录每天详情
    public function actionAcrossDayDetail()
    {
        $id = F::value($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("用户id不能为空");
        }
        $day = F::value($this->request_params, 'day', '');
        if (!$day) {
            return PsCommon::responseFailed("查询日期不能为空");
        }
        $result = PersonDataService::service()->getAcrossDayDetail($this->request_params,$this->page,$this->pageSize);
        return PsCommon::responseSuccess($result);
    }

    //人行记录规律图
    public function actionAcrossLineStatistic()
    {
        $id = F::value($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("用户id不能为空");
        }
        //TODO
        $result = PersonDataService::service()->getDayReport($id);
        return PsCommon::responseSuccess($result);
    }

    //关联家人
    public function actionRelatedFamily()
    {

    }

    //关联访客
    public function actionRelatedVisitor()
    {

    }

    //关联车辆
    public function actionRelatedCar()
    {

    }

}
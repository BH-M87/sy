<?php
/**
 * 标签管理
 * User: wenfeng.chao
 * Date: 2019/10/31
 * Time: 17:54
 */

namespace app\modules\street\modules\v1\controllers;


use app\models\Department;
use common\core\F;
use common\core\PsCommon;
use service\street\BasicDataService;
use service\street\LabelsService;
use service\street\UserService;


class LabelController extends BaseController
{
    // 标签添加
    public function actionAdd()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $result = LabelsService::service()->add($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 标签修改
    public function actionEdit()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        unset($this->request_params['user_id']);
        $result = LabelsService::service()->edit($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 获取标签列表
    public function actionList()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];

        if ($this->user_info['node_type'] == 2) {
            $this->request_params['organization_type'] = 1;
            $streetCodeData = UserService::service()->getStreetCodeByDistrict($this->user_info['dept_id']);
            $this->request_params['organization_id'] = $streetCodeData[0];
        }
        $result = LabelsService::service()->list($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    // 标签详情
    public function actionShow()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];

        if ($this->user_info['node_type'] == 2) {
            $this->request_params['organization_type'] = 1;
            $streetCodeData = UserService::service()->getStreetCodeByDistrict($this->user_info['dept_id']);
            $this->request_params['organization_id'] = $streetCodeData[0];
        }
        $result = LabelsService::service()->show($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }

    }

    // 标签删除
    public function actionDelete()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];

        if ($this->user_info['node_type'] == 2) {
            $this->request_params['organization_type'] = 1;
            $streetCodeData = UserService::service()->getStreetCodeByDistrict($this->user_info['dept_id']);
            $this->request_params['organization_id'] = $streetCodeData[0];
        }

        $result = LabelsService::service()->delete($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }

    }

    // 获取标签属性
    public function actionLabelAttribute()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];

        if ($this->user_info['node_type'] == 2) {
            $this->request_params['organization_type'] = 1;
            $streetCodeData = UserService::service()->getStreetCodeByDistrict($this->user_info['dept_id']);
            $this->request_params['organization_id'] = $streetCodeData[0];
        }
        $result = LabelsService::service()->labelAttribute($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    // 获取标签分类
    public function actionLabelType()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];

        if ($this->user_info['node_type'] == 2) {
            $this->request_params['organization_type'] = 1;
            $streetCodeData = UserService::service()->getStreetCodeByDistrict($this->user_info['dept_id']);
            $this->request_params['organization_id'] = $streetCodeData[0];
        }
        $result = LabelsService::service()->labelType($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    // 标签下拉
    public function actionDifferenceList()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];

        if ($this->user_info['node_type'] == 2) {
            $this->request_params['organization_type'] = 1;
            $streetCodeData = UserService::service()->getStreetCodeByDistrict($this->user_info['dept_id']);
            $this->request_params['organization_id'] = $streetCodeData[0];
        }
        $result = LabelsService::service()->differenceList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    // 添加 标签关联关系
    public function actionAddRelation()
    {
        $data_id = $this->request_params['data_id'];
        $labels_id = $this->request_params['labels_id'];
        $data_type = $this->request_params['data_type'];

        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];

        if ($this->user_info['node_type'] == 2) {
            $this->request_params['organization_type'] = 1;
            $streetCodeData = UserService::service()->getStreetCodeByDistrict($this->user_info['dept_id']);
            $this->request_params['organization_id'] = $streetCodeData[0];
        }
        $result = LabelsService::service()->addRelation($data_id, $labels_id, $data_type,$this->request_params['organization_type'],$this->request_params['organization_id']);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        }

        return PsCommon::responseFailed($result['msg']);
    }

    // 删除 标签关联关系
    public function actionDeleteRelation()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];

        if ($this->user_info['node_type'] == 2) {
            $this->request_params['organization_type'] = 1;
            $streetCodeData = UserService::service()->getStreetCodeByDistrict($this->user_info['dept_id']);
            $this->request_params['organization_id'] = $streetCodeData[0];
        }
        $result = LabelsService::service()->deleteRelation($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //按照标签统计
    public function actionStatistics()
    {
        $streetCode = F::value($this->request_params, 'street_code', '');
        $dataType = F::value($this->request_params, 'search_type', '');
        if (!$dataType) {
            return PsCommon::responseFailed('查询类型不能为空');
        }

        if ($this->user_info['node_type'] == 1) {
            //当前登录账号为街道账号，默认无搜索条件，查询当前街道数据
            $streetCode = $this->user_info['dept_id'];
        }
        if ($this->user_info['node_type'] == 2 && !$streetCode) {
            $streetData = UserService::service()->getStreetCodeByDistrict($this->user_info['dept_id']);
            $this->request_params['street_code'] = $streetData[0];
        }
        $labelList = BasicDataService::service()->getLabelStatistics($streetCode, $dataType, $this->user_info['node_type']);
        return PsCommon::responseSuccess($labelList);
    }


}
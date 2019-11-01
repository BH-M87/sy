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
        $result = LabelsService::service()->list($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    // 标签详情
    public function actionShow()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
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
        $result = LabelsService::service()->labelAttribute($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    // 获取标签分类
    public function actionLabelType()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $result = LabelsService::service()->labelType($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    // 标签下拉
    public function actionDifferenceList()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
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

        $result = LabelsService::service()->addRelation($data_id, $labels_id, $data_type,$this->user_info['node_type'],$this->user_info['dept_id']);
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
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $streetCode = F::value($this->request_params, 'street_code', '');

        if ($this->request_params['organization_type'] == 0) {
            //区县账号，只查询系统内置标签
        }
    }


}
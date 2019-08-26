<?php // 标签管理
namespace app\modules\property\modules\v1\controllers;

use Yii;

use common\core\PsCommon;

use service\label\LabelsService;

use app\modules\property\controllers\BaseController;

class LabelController extends BaseController
{
    // 标签添加
    public function actionAdd()
    {
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
        $result = LabelsService::service()->list($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 标签详情
    public function actionShow()
    {
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
        $result = LabelsService::service()->labelAttribute($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 获取标签分类
    public function actionLabelType()
    {
        $result = LabelsService::service()->labelType($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 标签下拉
    public function actionDifferenceList()
    {
        $result = LabelsService::service()->differenceList($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 标签下拉
    public function actionAddRelation()
    {
        $data_id = $this->request_params['data_id'];
        $labels_id = $this->request_params['labels_id'];
        $data_type = $this->request_params['data_type'];

        $result = LabelsService::service()->addRelation($data_id, $labels_id, $data_type);

        return PsCommon::responseSuccess($result);
    }
}
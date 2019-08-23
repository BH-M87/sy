<?php
/**
 * User: ZQ
 * Date: 2019/8/21
 * Time: 13:55
 * For: 标签管理
 */

namespace app\modules\property\modules\v1\controllers;

use app\models\PsLabels;
use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\label\LabelsService;
use Yii;

class LabelController extends BaseController
{
    public $request;
    private $method_array;
    protected $service;

    public function init()
    {
        parent::init();
        $this->request = Yii::$app->request;
        $this->method_array = [
            'get' => ['list', 'label-type', 'difference-list'],
            'post' => ['add', 'edit', 'delete']
        ];

    }

    public function beforeAction($action)
    {

        if (!parent::beforeAction($action)) {
            return false;
        }
        $this->service = LabelsService::service($this->user_info);
        return true;

    }

    //标签添加
    public function actionAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsLabels(), $this->request_params, 'add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = $this->service->LabelAddUpdate($this->request_params, 1);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }

    }

    //标签修改
    public function actionEdit()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsLabels(), $this->request_params, 'edit');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = $this->service->labelAddUpdate($this->request_params, 2);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }

    }

    //获取标签列表
    public function actionList()
    {
        $params = $this->request_params;
        if (empty($params['community_id']) || !is_numeric($params['community_id'])) {
            return PsCommon::responseFailed("参数错误");
        }
        $result = $this->service->labelList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //标签删除
    public function actionDelete()
    {
        $params = $this->request_params;
        if (empty($params['id']) || !is_numeric($params['id'])) {
            return PsCommon::responseFailed("参数错误");
        }
        $result = $this->service->labelDelete($params['id']);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }

    }

    //获取标签类型
    public function actionLabelType()
    {
        foreach (PsLabels::$type as $k => $v) {
            $data[] = ['id' => $k, 'name' => $v];
        }
        return PsCommon::responseSuccess(['list' => $data]);
    }

    //获取分类下拉数据
    public function actionDifferenceList()
    {
        $valid = PsCommon::validParamArr(new PsLabels(), $this->request_params, 'typelist');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = $this->service->getTypeList($this->request_params);
        return PsCommon::responseSuccess(['list' => $result]);
    }
}
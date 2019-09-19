<?php
namespace app\modules\property\modules\v1\controllers;

use service\rbac\OperateService;
use Yii;
use common\core\F;
use common\core\PsCommon;
use app\modules\property\controllers\BaseController;

use service\alipay\TemplateService;

Class TemplateController extends BaseController
{
    // 默认模板
    public function actionTemplateDefault()
    {
        $result = TemplateService::service()->templateDefault();

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess($result['data']);
    }

    // 模板 详情 第二步 {"id": "1"}
    public function actionTemplateConfigShow()
    {
        $result = TemplateService::service()->templateConfigShow($this->request_params);

        return PsCommon::responseSuccess($result['data']);
    }

    // 模板 新增 第二步 {"template_id": "1","type": "1","field_name_list": [{"field_name": "title"},{"field_name": "logo"}],"width": "1","logo_img": "1.jpg","note": "说明"}
    public function actionTemplateConfigAdd()
    {
        $result = TemplateService::service()->templateConfigAdd($this->request_params);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 显示内容 删除 {"id":"1"}
    public function actionTemplateConfigDelete()
    {
        $result = TemplateService::service()->templateConfigDelete($this->request_params);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 显示内容下拉
    public function actionTypeDropDown()
    {
        if (empty($this->request_params['template_type'])) {
            return PsCommon::responseFailed('模板类型必填 1、通知单模板 2、收据模板！');
        }

        if (empty($this->request_params['type'])) {
            return PsCommon::responseFailed('类型必填 1、页眉 2、表格 3、页脚！');
        }

        $list = TemplateService::service()->typeDropDown($this->request_params);

        $result['list'] = !empty($list) ? array_values($list) : [];

        return PsCommon::responseSuccess($result);
    }

    // 模板 列表 {"page":"1", "rows":"10", "type":"1", "community_id":"11", "name":"缴费"}
    public function actionTemplateList()
    {
        if (empty($this->request_params['community_id'])) {
            return PsCommon::responseFailed('小区ID必填！');
        }

        $result = TemplateService::service()->templateList($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 模板 删除 {"id":"1"}
    public function actionTemplateDelete()
    {
        $detail = TemplateService::service()->templateShow($this->request_params);
        $result = TemplateService::service()->templateDelete($this->request_params);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }
        $content = "模板名称:" . $detail['data']['name'];
        $operate = ["community_id" => $this->request_params['community_id'],
            "operate_menu" => "票据模板",
            "operate_type" => "删除模板",
            "operate_content" => $content,
        ];
        OperateService::addComm($this->user_info, $operate);
        return PsCommon::responseSuccess();
    }

    // 模板 详情 {"id":"1"}
    public function actionTemplateShow()
    {
        $result = TemplateService::service()->templateShow($this->request_params);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess($result['data']);
    }

    // 模板 新增 {"community_id":"11", "layout":"1", "name":"缴费", "note":"备注", "num":"2", "paper_size":"1", "type":"2"}
    public function actionTemplateAdd()
    {
        $result = TemplateService::service()->templateAdd($this->request_params);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }
        $content = "模板名称:" . $this->request_params['name'];
        $operate = ["community_id" => $this->request_params['community_id'],
            "operate_menu" => "票据模板",
            "operate_type" => "新增模板",
            "operate_content" => $content,
        ];
        OperateService::addComm($this->user_info, $operate);
        return PsCommon::responseSuccess($result['data']);
    }

    // 模板 编辑 {"id":"1", "community_id":"11", "layout":"1", "name":"缴费", "note":"备注", "num":"2", "paper_size":"1", "type":"2"}
    public function actionTemplateEdit()
    {
        $result = TemplateService::service()->templateEdit($this->request_params);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }
        $content = "模板名称:" . $this->request_params['name'];
        $operate = ["community_id" => $this->request_params['community_id'],
            "operate_menu" => "票据模板",
            "operate_type" => "编辑模板",
            "operate_content" => $content,
        ];
        OperateService::addComm($this->user_info, $operate);
        return PsCommon::responseSuccess($result['data']);
    }

    // 模板 下拉列表 {}
    public function actionTemplateDropDown()
    {
        $result = TemplateService::service()->templateDropDown($this->request_params);
        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }
        return PsCommon::responseSuccess($result['data']);
    }
}
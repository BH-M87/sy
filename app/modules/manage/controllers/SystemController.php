<?php

namespace app\modules\manage\controllers;

use app\common\core\PsCommon;
use app\modules\property\services\OperateService;
use app\modules\property\services\VersionService;

Class SystemController extends BaseController
{
    // 版本历史列表
    public function actionVersionList()
    {
        $data['list'] = VersionService::service()->getList($this->request_params, $this->page, $this->pageSize);
        $data['totals'] = VersionService::service()->getListCount($this->request_params);

        return PsCommon::responseSuccess($data);
    }

    // 新增版本
    public function actionVersionAdd()
    {
        $r = VersionService::service()->create($this->request_params);

        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 版本详情
    public function actionVersionDetail()
    {
        $id = PsCommon::get($this->request_params, 'id');

        if (!$id) {
            return PsCommon::responseFailed('ID不能为空');
        }

        $data['detail'] = VersionService::service()->detail($id);

        return PsCommon::responseSuccess($data);
    }

    // 版本编辑
    public function actionVersionEdit()
    {
        $id = PsCommon::get($this->request_params, 'id');

        if (!$id) {
            return PsCommon::responseFailed('ID不能为空');
        }

        $r = VersionService::service()->edit($id, $this->request_params);

        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 版本更新设为已读
    public function actionVersionRead()
    {
        $userId = PsCommon::get($this->user_info, 'id');
        $r = VersionService::service()->read($userId);

        if (!$r) {
            //return PsCommon::responseFailed('网络异常');
        }

        return PsCommon::responseSuccess();
    }

    //操作日志列表
    public function actionOperateLog()
    {
        $resultData = OperateService::service()->lists($this->request_params, $this->page, $this->pageSize, $this->user_info);
        return PsCommon::responseSuccess($resultData);
    }
}

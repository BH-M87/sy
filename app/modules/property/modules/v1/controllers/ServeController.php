<?php
/**
 * 社区服务
 * @author shenyang
 * @date 2017/11/13
 */

namespace app\modules\property\modules\v1\controllers;

use common\core\PsCommon;
use service\property_basic\ComplaintService;
use service\property_basic\GuideService;
use service\property_basic\PackageService;
use app\modules\property\controllers\BaseController;
use Yii;

Class ServeController extends BaseController
{
    public $repeatAction = ['guide-create', 'package-create'];

    /**
     * 社区指南列表
     */
    public function actionGuideList()
    {
        $data['list'] = GuideService::service()->getList($this->communityId, $this->page, $this->pageSize);
        $data['totals'] = GuideService::service()->getListCount($this->communityId);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 社区指南 显示、隐藏
     */
    public function actionGuideOpenDown()
    {
        $id = PsCommon::get($this->request_params, 'id');
        $status = PsCommon::get($this->request_params, 'status');
        if (!$id) {
            return PsCommon::responseFailed('ID不能为空');
        }
        if (!$status) {
            return PsCommon::responseFailed('状态不能为空');
        }
        $r = GuideService::service()->openDown($id, $this->communityId, $status);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        return PsCommon::responseSuccess();
    }

    /**
     * 社区指南 删除
     */
    public function actionGuideRemove()
    {
        $id = PsCommon::get($this->request_params, 'id');
        if (!$id) {
            return PsCommon::responseFailed('ID不能为空');
        }
        $r = GuideService::service()->remove($id, $this->communityId,$this->user_info);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        return PsCommon::responseSuccess();
    }

    /**
     * 社区指南 详情
     */
    public function actionGuideDetail()
    {
        $id = PsCommon::get($this->request_params, 'id');
        if (!$id) {
            return PsCommon::responseFailed('ID不能为空');
        }
        $r = GuideService::service()->detail($id, $this->communityId);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        $data['detail'] = $r['data'];
        return PsCommon::responseSuccess($data);
    }

    /**
     * 社区指南 新增
     */
    public function actionGuideCreate()
    {
        $r = GuideService::service()->create($this->request_params,$this->user_info);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        return PsCommon::responseSuccess();
    }

    /**
     * 社区指南 编辑
     */
    public function actionGuideEdit()
    {
        $id = PsCommon::get($this->request_params, 'id');
        if (!$id) {
            return PsCommon::responseFailed('ID不能为空');
        }
        $r = GuideService::service()->edit($id, $this->request_params,$this->user_info);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        return PsCommon::responseSuccess();
    }

    /**
     * 社区指南 类型
     */
    public function actionGuideTypes()
    {
        $types = array_values(GuideService::service()->types);
        return PsCommon::responseSuccess(['type' => $types]);
    }

    /**
     * 投诉列表
     */
    public function actionComplaintList()
    {
        $data = ComplaintService::service()->getList($this->request_params, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 投诉类型
     */
    public function actionComplaintTypes()
    {
        $data = array_values(ComplaintService::service()->types);
        return PsCommon::responseSuccess(['types' => $data]);
    }

    /**
     * 投诉状态
     */
    public function actionComplaintStatus()
    {
        $data = array_values(ComplaintService::service()->status);
        return PsCommon::responseSuccess(['status' => $data]);
    }


    /**
     * 投诉详情
     */
    public function actionComplaintDetail()
    {
        $id = PsCommon::get($this->request_params, 'id');
        if (!$id) {
            return PsCommon::responseFailed('ID不能为空');
        }
        $r = ComplaintService::service()->detail($id, $this->communityId);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        return PsCommon::responseSuccess(['detail' => $r['data']]);
    }

    /**
     * 投诉 标记为已处理
     */
    public function actionComplaintDone()
    {
        $id = PsCommon::get($this->request_params, 'id');
        $content = PsCommon::get($this->request_params, 'content');
        if (!$id) {
            return PsCommon::responseFailed('ID不能为空');
        }
        if (!$content) {
            return PsCommon::responseFailed('处理内容不能为空');
        }
        $r = ComplaintService::service()->done($id, $this->communityId, $content,$this->user_info);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        return PsCommon::responseSuccess();
    }

    /**
     * 包裹列表
     */
    public function actionPackageList()
    {
        $data['list'] = PackageService::service()->getList($this->request_params, $this->page, $this->pageSize);
        $data['totals'] = PackageService::service()->getListCount($this->request_params);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 新增包裹
     */
    public function actionPackageCreate()
    {
        $r = PackageService::service()->create($this->request_params);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        return PsCommon::responseSuccess();
    }

    /**
     * 包裹详情
     */
    public function actionPackageDetail()
    {
        $id = PsCommon::get($this->request_params, 'id');
        if (!$id) {
            return PsCommon::responseFailed('ID不能为空');
        }
        $data['detail'] = PackageService::service()->detail($id, $this->communityId);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 编辑包裹
     */
    public function actionPackageEdit()
    {
        $id = PsCommon::get($this->request_params, 'id');
        if (!$id) {
            return PsCommon::responseFailed('ID不能为空');
        }
        $r = PackageService::service()->edit($id, $this->request_params);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        return PsCommon::responseSuccess();
    }

    /**
     * 确认领取
     */
    public function actionPackageReceive()
    {
        $id = PsCommon::get($this->request_params, 'id');
        if (!$id) {
            return PsCommon::responseFailed('ID不能为空');
        }
        $r = PackageService::service()->receive($id, $this->communityId);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        return PsCommon::responseSuccess();
    }

    /**
     * 快递公司
     */
    public function actionPackageDelivery()
    {
        $data['delivery'] = array_values(PackageService::service()->delivery);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 包裹状态
     */
    public function actionPackageStatus()
    {
        $data['status'] = array_values(PackageService::service()->status);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 包裹备注
     */
    public function actionPackageNote()
    {
        $data['note'] = array_values(PackageService::service()->note);
        return PsCommon::responseSuccess($data);
    }
}

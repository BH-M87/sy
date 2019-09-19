<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\parking\CarAcrossService;

class CarAcrossController extends BaseController
{
    //在库车辆
    public function actionInList()
    {
        $community_id = F::value($this->request_params, 'community_id');
        if (!$community_id) {
            return PsCommon::responseFailed('小区ID不能为空！');
        }
        $data['list'] = CarAcrossService::service()->inList($this->request_params, $this->page, $this->pageSize);
        $data['totals'] = CarAcrossService::service()->inListCount($this->request_params);
        return PsCommon::responseSuccess($data);
    }

    //出库记录
    public function actionOutList()
    {
        $community_id = F::value($this->request_params, 'community_id');
        if (!$community_id) {
            return PsCommon::responseFailed('小区ID不能为空！');
        }
        $data['list'] = CarAcrossService::service()->outList($this->request_params, $this->page, $this->pageSize);
        $data['totals'] = CarAcrossService::service()->outListCount($this->request_params);
        return PsCommon::responseSuccess($data);
    }

    //公共接口
    public function actionGetCommon()
    {
        $community_id = F::value($this->request_params, 'community_id');
        if (!$community_id) {
            return PsCommon::responseFailed('小区ID不能为空！');
        }
        $data['types'] = array_values(CarAcrossService::service()->carTypes);
        return PsCommon::responseSuccess($data);
    }
}
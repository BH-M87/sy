<?php
/**
 * 值班排班相关接口
 * User: wenchao.feng
 * Date: 2019/9/5
 * Time: 17:49
 */

namespace app\modules\street\modules\v1\controllers;

use common\core\PsCommon;
use service\street\SchedulingService;

class SchedulingController extends BaseController {

    //排班详情
    public function actionView()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $result['list'] = SchedulingService::service()->view($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //排班发布
    public function actionPublish()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $result = SchedulingService::service()->publish($this->request_params, $this->user_info);
        if($result) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed("排班编辑失败");
        }
    }
}
<?php

/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/7/26
 * Time: 14:44
 */
namespace app\modules\small\controllers;

use common\core\PsCommon;
use common\core\F;
use common\CoreController;

class BaseController extends CoreController
{
    public $enableCsrfValidation = false;
    public $enableAction = [];
    public $request_params;
    public $appUserId;
    public $communityId;
    public $repeatAction = [];//验证重复请求的方法数组
    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) {
            return false;
        }

        $this->request_params = !empty($_REQUEST) ? $_REQUEST : [];

        $this->appUserId   = PsCommon::get($this->request_params, 'app_user_id');
        $this->communityId = PsCommon::get($this->request_params, 'community_id');

        //重复请求过滤 TODO 1. 接口时间响应过长导致锁提前失效 2. 未执行完即取消请求，锁未主动释放，需等待30s
        if (in_array($action->id, $this->repeatAction) && F::repeatRequestSmall(10)) {
            echo PsCommon::responseFailed('请勿重复提交');
            return false;
        }

        return true;
    }

    public function dealReturnResult($result)
    {
        if($result['code'] == 1){
            return PsCommon::responseAppSuccess($result['data']);
        } else {
            if (!empty($result['code'])) {
                return PsCommon::responseAppFailed($result['msg'], $result['code']);
            }
            return PsCommon::responseAppFailed($result['msg']);
        }
    }


}


<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/21
 * Time: 14:04
 */

namespace app\modules\ali_small_door\modules\v1\controllers;


use common\core\F;
use common\core\PsCommon;
use yii\web\Controller;

class BaseController extends Controller
{
    public $enableCsrfValidation = false;
    public $enableAction = [];
    public $request_params;
    public $repeatAction = [];//验证重复请求的方法数组
    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) {
            return false;
        }
        $this->request_params = !empty($_REQUEST) ? $_REQUEST : [];

        //重复请求过滤 TODO 1. 接口时间响应过长导致锁提前失效 2. 未执行完即取消请求，锁未主动释放，需等待30s
        if (in_array($action->id, $this->repeatAction) && F::repeatRequestSmall(10)) {
            echo PsCommon::responseFailed('请勿重复提交');
            return false;
        }

        return true;
    }

    public function dealReturnResult($result, $mode = 1)
    {
        if($result['code'] == 1){
            if ($mode == 1) {
                return PsCommon::responseAppSuccess($result['data']);
            } else {
                return PsCommon::responseSuccess($result['data']);
            }
        } else {
            if (!empty($result['code'])) {
                return PsCommon::responseAppFailed($result['msg'], $result['code']);
            }
            return PsCommon::responseAppFailed($result['msg']);
        }
    }
}
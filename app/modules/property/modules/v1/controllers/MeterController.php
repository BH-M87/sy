<?php
/**
 * 公摊项目管理
 * @author chenkelang
 * @date 2018-03-16
 */

namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\alipay\MeterService;
use Yii;

class MeterController extends BaseController
{

    /**
     * 删除仪表数据
     * @author yjh
     * @return json
     */
    public function actionDelete()
    {
        $result = MeterService::service()->delete($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /**
     * 导出仪表数据
     * @author yjh
     * @return json
     */
    public function actionExport()
    {
        $result = MeterService::service()->export($this->request_params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }



}

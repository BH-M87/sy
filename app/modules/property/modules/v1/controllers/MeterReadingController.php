<?php
/**
 * 公摊项目管理
 * @author chenkelang
 * @date 2018-03-16
 */

namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\alipay\MeterReadingService;
use service\rbac\OperateService;
use service\alipay\SharedService;
use service\alipay\MeterService;
use app\models\PsShared;
use service\alipay\WaterRecordService;
use service\common\ExcelService;
use Yii;

class MeterReadingController extends BaseController
{

    //重复请求过滤方法
//    public $repeatAction = ['generate-bill'];

    /**
     * 新增周期
     * @author yjh
     * @return mixed
     */
    public function actionAdd()
    {
        $result = MeterReadingService::service()->add($this->request_params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /**
     * 删除周期
     * @author yjh
     * @return mixed
     */
    public function actionDelete()
    {
        $result = MeterReadingService::service()->delete($this->request_params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /**
     * 获取周期列表
     * @author yjh
     * @return mixed
     */
    public function actionList()
    {
        $result = MeterReadingService::service()->getList($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /**
     * 获取抄表列表
     * @author yjh
     * @return mixed
     */
    public function actionListRecord()
    {
        $result = MeterReadingService::service()->getListRecord($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /**
     * 导出抄表数据
     * @author yjh
     * @return mixed
     */
    public function actionExport()
    {
        $result = WaterRecordService::service()->export($this->request_params);
        if ($result['code']) {
            $operate = [
                "community_id" => $this->request_params["community_id"],
                "operate_menu" => "抄表管理",
                "operate_type" => "抄表详情",
                "operate_content" => '导出记录',
            ];
            OperateService::addComm($this->user_info, $operate);

            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /**
     * 修改读数
     * @author yjh
     * @return mixed
     */
    public function actionEditMeterNum()
    {
        $result = WaterRecordService::service()->updateMeterNun($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /**
     * 生成账单
     * @author yjh
     * @return mixed
     */
    public function actionGenerateBill()
    {
        $result = MeterReadingService::service()->generateBill($this->request_params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

}

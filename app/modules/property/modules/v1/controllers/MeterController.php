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

    /*
     * 模板下载 水表电表
     */
    public function actionGetDown(){
        $type = $this->request_params['type'];
        if(empty($type)){
            return PsCommon::responseFailed('下载类型必填');
        }
        if($type==1){
            //水表
            $downUrl = F::downloadUrl('import_water_meter_templates.xlsx', 'template', 'MoBan.xlsx');
        }else{
            //电表
            $downUrl = F::downloadUrl('import_electrict_meter_templates.xlsx', 'template', 'MuBan.xlsx');
        }
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }



}

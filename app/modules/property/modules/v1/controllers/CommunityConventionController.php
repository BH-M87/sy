<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use app\models\PsCommunityModel;
use app\models\PsCommunityConvention;

use service\property_basic\CommunityConventionService;

class CommunityConventionController extends BaseController 
{
    // 邻里公约初始化
    public function actionAdd()
    {
        $m = PsCommunityModel::find()->select('id')->orderBy('id asc')->asArray()->all();

        foreach ($m as $v) {
            $con = PsCommunityConvention::find()->where(['community_id' => $v['id']])->asArray()->one();
            if (empty($con)) {
                CommunityConventionService::service()->addConvention(['community_id' => $v['id']]);
            }
        }

        return PsCommon::responseSuccess();
    }
    
    // 新增公约
    public function actionAddConvention()
    {
        $r = CommunityConventionService::service()->addConvention($this->request_params);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        return PsCommon::responseSuccess();
    }

    // 修改公约
    public function actionUpdateConvention()
    {
        $r = CommunityConventionService::service()->updateConvention($this->request_params,$this->user_info);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        return PsCommon::responseSuccess();
    }

    // 公约详情
    public function actionConventionDetail()
    {
        $r = CommunityConventionService::service()->conventionDetail($this->request_params);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        $data = $r['data'];
        return PsCommon::responseSuccess($data);
    }
}
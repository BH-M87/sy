<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/21
 * Time: 14:12
 */

namespace app\modules\ali_small_common\modules\v1\controllers;


use app\modules\ali_small_common\controllers\UserBaseController;
use common\core\F;
use common\core\PsCommon;
use service\small\FamilyManageService;

class FamilyManageController extends UserBaseController
{
    public $enableAction = ['delete'];
    //获取住户信息
    public function actionList()
    {
        $result = FamilyManageService::service()->getResidentList($this->params);
        return F::apiSuccess($result);
    }

    //住户删除
    public function actionDelete()
    {
        $data['resident_id'] = PsCommon::get($this->params, 'resident_id');
        $data['rid'] = PsCommon::get($this->params, 'rid');
        $data['user_id'] = PsCommon::get($this->params, 'user_id');
        $result = FamilyManageService::service()->delResidentList($data);
        return F::apiSuccess($result);
    }

    //住户详情
    public function actionView()
    {
        $data['app_user_id'] = PsCommon::get($this->params, 'user_id');
        $data['resident_id'] = PsCommon::get($this->params, 'resident_id');
        $data['rid'] = PsCommon::get($this->params, 'rid');
        if($data['resident_id']){
            $result = FamilyManageService::service()->getResidentDetail($data);
        }else{
            $result = FamilyManageService::service()->getFamilyResidentDetail($data);
        }
        return F::apiSuccess($result);
    }

    //住户新增
    public function actionAdd()
    {
        \Yii::info("111","api");
        FamilyManageService::service()->addResident($this->params);
        return F::apiSuccess();
    }

    //住户编辑
    public function actionEdit()
    {
        FamilyManageService::service()->editResident($this->params);
        return F::apiSuccess();
    }
}
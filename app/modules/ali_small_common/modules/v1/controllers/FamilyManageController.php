<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/21
 * Time: 14:12
 */

namespace app\modules\ali_small_common\modules\v1\controllers;


use app\modules\ali_small_common\controllers\UserBaseController;
use app\small\services\FamilyManageService;
use common\core\F;
use common\core\PsCommon;

class FamilyManageController extends UserBaseController
{
    //获取住户信息
    public function actionResidentList()
    {
        $data['app_user_id'] = PsCommon::get($this->params, 'app_user_id');
        $data['room_id'] = PsCommon::get($this->params, 'room_id');

        $result = FamilyManageService::service()->getResidentList($data);
        return F::apiSuccess($result);
    }

    //住户删除
    public function actionResidentDel()
    {
        $data['app_user_id'] = PsCommon::get($this->params, 'app_user_id');
        $data['resident_id'] = PsCommon::get($this->params, 'resident_id');
        $data['rid'] = PsCommon::get($this->params, 'rid');

        $result = FamilyManageService::service()->delResidentList($data);
        return F::apiSuccess($result);
    }

    //住户详情
    public function actionResidentDetail()
    {
        $data['app_user_id'] = PsCommon::get($this->params, 'app_user_id');
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
        $result = FamilyManageService::service()->addResident($this->params);
        return F::apiSuccess($result);
    }

    //住户新增
    public function actionEdit()
    {
        $result = FamilyManageService::service()->addResident($this->params);
        return F::apiSuccess($result);
    }
}
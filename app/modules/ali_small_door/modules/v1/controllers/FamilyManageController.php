<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/21
 * Time: 14:03
 */

namespace app\modules\ali_small_door\modules\v1\controllers;

use app\small\services\FamilyManageService;
use common\core\F;
use common\core\PsCommon;

class FamilyManageController extends BaseController
{
    //获取住户信息
    public function actionResidentList()
    {
        $data['app_user_id'] = PsCommon::get($this->request_params, 'app_user_id');
        $data['room_id'] = PsCommon::get($this->request_params, 'room_id');

        $result = FamilyManageService::service()->getResidentList($data);
        return F::apiSuccess($result);
    }

    //住户删除
    public function actionResidentDel()
    {
        $data['app_user_id'] = PsCommon::get($this->request_params, 'app_user_id');
        $data['resident_id'] = PsCommon::get($this->request_params, 'resident_id');
        $data['rid'] = PsCommon::get($this->request_params, 'rid');

        $result = FamilyManageService::service()->delResidentList($data);
        return F::apiSuccess($result);
    }

    //住户详情
    public function actionResidentDetail()
    {
        $data['app_user_id'] = PsCommon::get($this->request_params, 'app_user_id');
        $data['resident_id'] = PsCommon::get($this->request_params, 'resident_id');
        $data['rid'] = PsCommon::get($this->request_params, 'rid');
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
        $result = FamilyManageService::service()->addResident($this->request_params);
        return F::apiSuccess($result);
    }

    //住户新增
    public function actionEdit()
    {
        $result = FamilyManageService::service()->addResident($this->request_params);
        return F::apiSuccess($result);
    }
}
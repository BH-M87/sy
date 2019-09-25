<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/21
 * Time: 16:13
 */

namespace app\modules\ali_small_common\modules\v1\controllers;


use app\modules\ali_small_common\controllers\UserBaseController;
use common\core\F;
use common\core\PsCommon;

use service\room\RoomService;
use service\small\MemberService;
use service\small\RoomUserService;

class RoomController extends UserBaseController
{
    //已认证和未认证的房屋信息
    public function actionList()
    {
        $result = RoomUserService::service()->getList($this->params);
        return F::apiSuccess($result);
    }

    // 住户房屋新增
    public function actionAdd()
    {
        $result = RoomUserService::service()->add($this->params);
        return F::apiSuccess($result);
    }

    // 住户房屋编辑
    public function actionEdit()
    {
        $result = RoomUserService::service()->update($this->params);
        return F::apiSuccess($result);
    }

    // 住户房屋详情
    public function actionView()
    {
        $result = RoomUserService::service()->view($this->params);
        return F::apiSuccess($result);
    }

    //标记已选择房屋
    public function actionSmallList()
    {
        $r = $this->params;
        $r['app_user_id'] = $this->appUserId;
        $result = MemberService::service()->smallSelcet($r);
        if($result['code']){
            return F::apiSuccess($result['data']);
        }else{
            return F::apiFailed($result['msg']);
        }

    }

    // 获取房屋详情信息(只包含房屋的信息)
    public function actionOwnView()
    {
        $room_id = PsCommon::get($this->params, 'room_id');
        if (empty($room_id)){
            return F::apiFailed('房屋编号不能为空');
        }
        $result = RoomService::service()->getOwnView($room_id);
        return self::dealReturnResult($result);
    }
}
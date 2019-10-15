<?php
/**
 * User: ZQ
 * Date: 2019/10/14
 * Time: 10:19
 * For: ****
 */

namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\controllers\UserBaseController;
use common\core\F;
use common\core\PsCommon;
use common\MyException;
use service\door\VisitorService;

class VisitorController extends UserBaseController
{
    //访客列表
    public function actionList()
    {
        $data = $this->request_params;
        $result = VisitorService::service()->getListForDing($data, $this->page, $this->pageSize,$this->userMobile);
        if ($result["code"]) {
            return F::apiSuccess($result["data"]);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    //新增访客
    public function actionAdd()
    {
        $data = $this->request_params;
        $member_id = PsCommon::get($this->params, 'member_id');
        $room_id = PsCommon::get($this->params, 'room_id');
        $start_time = PsCommon::get($this->params, 'start_time');
        $end_time = PsCommon::get($this->params, 'end_time');
        $vistor_mobile = PsCommon::get($this->params, 'vistor_mobile');
        $vistor_name = PsCommon::get($this->params, 'vistor_name');
        $content = PsCommon::get($this->params, 'content');
        $people_num = PsCommon::get($this->params, 'people_num',0);
        $reason_type = PsCommon::get($this->params, 'reason_type');

        if (mb_strlen($content) > 100) {
            throw new MyException("备注限制100字");
        }

        if (empty($room_id)) {
            throw new MyException("房屋ID不能为空");
        }

        if (empty($member_id)) {
            throw new MyException("业主信息不能为空");
        }

        if (empty($vistor_mobile)) {
            throw new MyException("访客手机不能为空");
        }

        $vistor_mobile = preg_replace("/\D/", '',$vistor_mobile);
        if (!preg_match("/^1\d{10}$/", $vistor_mobile)) {
            throw new MyException("访客手机格式错误");
        }

        if (mb_strlen($vistor_name) > 20) {
            throw new MyException("访客姓名限制20字");
        }

        if (empty($vistor_name)) {
            throw new MyException("访客姓名不能为空");
        }

        if (empty($start_time)) {
            throw new MyException("开始时间不能为空");
        }

        if (empty($end_time)) {
            throw new MyException("结束时间不能为空");
        }

        if ($start_time >= $end_time) {
            throw new MyException("结束时间只能大于开始时间");
        }

        if (empty($people_num)) {
            throw new MyException("来访人数不能为空");
        }

        if (empty($reason_type)) {
            throw new MyException("来访是由不能为空");
        }

        $result = VisitorService::service()->addForDing($data);
        if ($result["code"]) {
            return F::apiSuccess($result["data"]);
        } else {
            return F::apiFailed($result["msg"]);
        }

    }

    //获取公共参数
    public function actionCommon()
    {
        $result = VisitorService::service()->getCommonDing();
        return F::apiSuccess($result);
    }

    //获取业主列表
    public function actionUserList()
    {
        $room_id = PsCommon::get($this->request_params,'room_id');
        if (empty($room_id)) {
            throw new MyException("房屋id不能为空");
        }
        $result = VisitorService::service()->getUserListDing($this->request_params);
        return F::apiSuccess($result);
    }

}
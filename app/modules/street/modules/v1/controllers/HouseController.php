<?php

namespace app\modules\street\modules\v1\controllers;

use common\core\F;
use common\MyException;
use service\door\DoorRecordService;
use service\door\VisitorService;
use service\parking\CarService;
use service\resident\RoomUserService;
use service\room\HouseService;
use common\core\PsCommon;
use service\street\LabelsService;
use service\street\UserService;

class HouseController extends BaseController
{

    /**
     * 获取苑期区
     * @author yjh
     * @return string
     * @throws MyException
     */
    public function actionGetGroupsUnits()
    {
        if (empty($this->request_params['community_id'])) throw new MyException('请选择小区');
        $result = HouseService::service()->getGroupsUnits($this->request_params['community_id']);
        return PsCommon::responseSuccess($result);
    }



    /**
     * 获取房屋
     * @author yjh
     * @return string
     * @throws MyException
     */
    public function actionGetRooms()
    {
        if (empty($this->request_params['unit_id'])) throw new MyException('单元ID不能为空');
        $result = HouseService::service()->getRoomList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 房屋详情
     * @author yjh
     * @return string
     * @throws MyException
     */
    public function actionGetRoomDetail()
    {
        if (empty($this->request_params['id'])) throw new MyException('房屋ID不能为空');
        $result = HouseService::service()->getRoomDetail($this->request_params['id']);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 关联人员
     * @author yjh
     * @return string
     * @throws MyException
     */
    public function actionGetRoomUser()
    {
        if (empty($this->request_params['id'])) throw new MyException('ID不能为空');
        if (!in_array($this->request_params['type'],[1,2])) throw new MyException('type错误');
        if ($this->request_params['type'] == 2) {
            $this->request_params['room_id'] = $this->request_params['id'];
        } else {
            $this->request_params['room_id'] = RoomUserService::service()->getRoomIdList($this->request_params['id'],$this->user_info['community_id']);
            if (empty($this->request_params['room_id'])) {
                return PsCommon::responseSuccess([]);
            }
        }
        $result = RoomUserService::service()->getRoomUserList($this->request_params);
        \Yii::info(json_encode(['request' => $_REQUEST,'response'=>$result]),'room_user');
        return PsCommon::responseSuccess($result);
    }


    /**
     * 关联车辆
     * @author yjh
     * @return string
     * @throws MyException
     */
    public function actionGetCarList()
    {
        if (empty($this->request_params['id'])) throw new MyException('ID不能为空');
        if (!in_array($this->request_params['type'],[1,2])) throw new MyException('type错误');
        if ($this->request_params['type'] == 2) {
            $this->request_params['room_id'] = $this->request_params['id'];
        } else {
            $this->request_params['member_id'] = $this->request_params['id'];
            $this->request_params['community_id'] = $this->user_info['community_id'];
        }
        $result = CarService::service()->getList($this->request_params);
        //当前用户所拥有街道权限的所有标签
        $organization_type = 1;
        $organization_id = UserService::service()->geyStreetCodeByUserInfo($this->user_info);
        foreach ($result['list'] as &$v) {
            $v['car_id'] = $v['id'];
            $v['car_images'] = F::getOssImagePath($v['images'][0]);
            $v['label'] = LabelsService::service()->getLabelInfoByCarId($v['id'],$organization_type,$organization_id);
            unset($v['id']);
            unset($v['images']);
        }
        return PsCommon::responseSuccess($result);
    }

    /**
     * 关联访客
     * @author yjh
     * @return string
     * @throws MyException
     */
    public function actionGetVisitorList()
    {
        if (empty($this->request_params['id'])) throw new MyException('ID不能为空');
        if (!in_array($this->request_params['type'],[1,2])) throw new MyException('type错误');
        if ($this->request_params['type'] == 2) {
            $param['room_id'] = $this->request_params['id'];
        } else {
            $param['member_id'] = $this->request_params['id'];
            $param['community_id'] = $this->user_info['community_id'];
        }
        $result = VisitorService::service()->getList($param);
        foreach ($result['list'] as &$v) {
            $v['car_num'] = $v['car_number'];
            $v['visitor_status'] = $v['status'] == 2 ? '1' : '2';
            $v['start_time'] = !empty($v['start_time']) ? date('Y-m-d',$v['start_time']) : '';
            $v['end_time'] = !empty($v['end_time']) ? date('Y-m-d',$v['end_time']) : '';
            $v['door_record_list'] = DoorRecordService::service()->getVisitorRecord($v['id']);
            $v['visitor_id'] = $v['id'];
            unset($v['car_number']);
        }
        return PsCommon::responseSuccess($result);
    }
}

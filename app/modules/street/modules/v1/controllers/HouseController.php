<?php

namespace app\modules\street\modules\v1\controllers;

use common\MyException;
use service\basic_data\CommunityBuildingService;
use service\basic_data\CommunityGroupService;
use service\room\HouseService;
use common\core\PsCommon;

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
        if (empty($this->request_params['community_id'])) throw new MyException('小区ID不能为空');
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

    public function actionGetRoomDetail()
    {

    }

}

<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/21
 * Time: 16:06
 */

namespace app\modules\ali_small_common\modules\v1\controllers;


use app\modules\ali_small_common\controllers\UserBaseController;
use app\small\services\CommunityRoomService;
use common\core\F;
use common\core\PsCommon;
use common\MyException;

class CommunityController extends UserBaseController
{
    /**
     * @api 获取小区列表-包含定位信息
     * @author wyf
     * @date 2019-05-22
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionList()
    {
        $name = PsCommon::get($this->params, 'name');
        $lat = PsCommon::get($this->params, 'lat', '');
        $lon = PsCommon::get($this->params, 'lon', '');
        //无定位信息直接响应空数据
        if (($lat && $lon) || !empty($name)){
            $data = CommunityRoomService::getCommunityList($name, $lon, $lat);
        }else{
            $data = [];
        }
        $result = CommunityRoomService::service()->transFormInfo($data, $lon, $lat);
        return F::apiSuccess($result);
    }

    /**
     * @api 获取苑期区-楼幢格式信息
     * @author wyf
     * @date 2019-05-22
     * @return array
     * @throws MyException
     * @throws \yii\db\Exception
     */
    public function actionHouseList()
    {
        $community_id = PsCommon::get($this->params, 'community_id');
        if (empty($community_id)){
            throw new MyException('小区编号不能为空');
        }
        $data = CommunityRoomService::houseList($community_id);
        $result = CommunityRoomService::service()->transFormHouse($data);
        return F::apiSuccess($result);
    }

    /**
     * @api 获取单元-室格式信息
     * @author wyf
     * @date 2019-05-22
     * @return array
     * @throws MyException
     * @throws \yii\db\Exception
     */
    public function actionRoomList()
    {
        $building_id = PsCommon::get($this->params, 'building_id');
        if (empty($building_id)){
            throw new MyException('楼幢编号不能为空');
        }
        $data = CommunityRoomService::RoomList($building_id);
        $result = CommunityRoomService::service()->transFormRoomInfo($data);
        return F::apiSuccess($result);
    }
}
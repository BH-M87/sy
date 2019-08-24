<?php
/**
 * 车场相关服务
 * User: fengwenchao
 * Date: 2019/8/21
 * Time: 18:30
 */
namespace service\parking;

use app\models\ParkingCarport;
use app\models\ParkingLot;
use app\models\ParkingReportDaily;
use common\core\PsCommon;
use service\BaseService;
use service\basic_data\IotParkingService;
use service\basic_data\SupplierService;
use service\rbac\OperateService;

class LotService extends BaseService
{
    /**
     * 保存收费统计
     * @param $day
     * @param $type
     * @param $yes
     * @param $wee
     * @param $mon
     * @param $year
     * @return bool
     */
    public function saveReport($day, $type, $yes, $wee, $mon, $year)
    {
        $model = ParkingReportDaily::find()->where(['day'=>$day, 'type'=>$type])->one();
        if(!$model) {
            $model = new ParkingReportDaily();
        }
        $model->day = $day;
        $model->yesterday = intval($yes);
        $model->week = intval($wee);
        $model->month = intval($mon);
        $model->year = intval($year);
        $model->type = $type;
        $model->create_at = time();
        return $model->save();
    }

    public function getList($params)
    {
        $model = ParkingLot::find()
            ->select('id, name, type, created_at')
            ->where("1=1");
        if (!empty($params['community_id'])) {
            $model->andWhere(['community_id' => $params['community_id']]);
        }
        if (!empty($params['name'])) {
            $model->andWhere(['like', 'name', $params['name']]);
        }
        $lots = $model->orderBy('id desc')
            ->asArray()
            ->all();
        $re['totals'] = count($lots);
        $re['list'] = $lots;
        return $re;
    }

    //车场新增
    public function add($params, $userInfo = [])
    {
        $model = ParkingLot::find()->where(['community_id' => $params['community_id'],'name' => $params['name']])->one();
        if ($model) {
            return $this->failed('车场名重复');
        }
        $model = $this->checkParkCode($params['parkCode']);
        if ($model) {
            return $this->failed('该车场已经被绑定');
        }
        //新增车场
        $model = new ParkingLot();
        $model->supplier_id = $params['supplier_id'];
        $model->community_id = $params['community_id'];
        $model->name = $params['name'];
        $model->park_code = $params['parkCode'];
        $model->parkId = $params['parkId'];
        $model->created_at = time();
        if($model->save()){
            //TODO 数据推送
            $operate = [
                "community_id" => $params['community_id'],
                "operate_menu" => "车场管理",
                "operate_type" => "新增车场",
                "operate_content" => '名称:' . $params['name'],
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success('车场名重复');
        }
        return $this->failed(PsCommon::getModelError($model));
    }

    //车场编辑
    public function edit($params, $userInfo = [])
    {
        $model = ParkingLot::findOne($params['id']);
        if (!$model) {
            return $this->failed('车场不存在！');
        }
        $reParkInfo = ParkingLot::find()
            ->where(['community_id' => $params['community_id'],'name' => $params['name']])
            ->andWhere(['!=', 'id', $params['id']])
            ->one();
        if ($reParkInfo) {
            return $this->failed('车场名重复');
        }
        $model->name = $params['name'];
        if ($model->save()) {
            //TODO 数据推送
            $operate = [
                "community_id" => $params['community_id'],
                "operate_menu" => "车场管理",
                "operate_type" => "编辑车场",
                "operate_content" => '名称:' . $params['name'],
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success();
        }
        return $this->failed(PsCommon::getModelError($model));
    }

    //车场删除
    public function delete($params, $userInfo = [])
    {
        $model = ParkingLot::findOne($params['id']);
        if (!$model) {
            return $this->failed('车场不存在！');
        }
        //查看车场是否绑定了车位，如果有就不能删除
        $res = ParkingCarport::find()->where(['lot_id' => $params['id']])->orderBy('id desc')->limit(1)->asArray()->one();
        if($res){
            return $this->failed('车场已绑定车位信息，请先删除车位相关信息');
        }
        if ($model->delete()) {
            //TODO 数据推送
            $operate = [
                "community_id" => $params['community_id'],
                "operate_menu" => "车场管理",
                "operate_type" => "删除车场",
                "operate_content" => '名称:' . $model->name,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success();
        }
        return $this->failed(PsCommon::getModelError($model));
    }

    public function view($params)
    {
        $model = ParkingLot::findOne($params['id']);
        if (!$model) {
            return $this->failed('车场不存在！');
        }
        $lotInfo = $model->toArray();
        return $this->success($lotInfo);
    }

    //供应商列表
    public function getSupplierList($params)
    {
        return SupplierService::service()->getSupplierList($params['community_id'], 1);
    }

    //iot车场列表
    public function getIotLostList($supplier_id,$type)
    {
        $paramData['productSn'] = $this->getSupplierProductSn($supplier_id);
        if($type == 1) {
            $paramData['isUsed'] = 1;
        }
        $res = IotParkingService::service()->getParkInfo($paramData);
        $list = [];
        if($res['code'] == 1){
            if($res['data']){
                foreach($res['data'] as $key=>$value){
                    $data['productSn'] = $value['parkInfo']['brand'];
                    $data['parkName'] = $value['parkInfo']['parkName'];
                    $data['parkCode'] = $value['parkInfo']['parkCode'];
                    $data['parkId'] = $value['parkInfo']['parkId'];
                    $list[] = $data;
                }
            }
            return $this->success($list);
        }
        return $res;
    }

    //获取停车区列表
    public function getAreaListAll($requestArr){
        $communityId = $requestArr['community_id'];
        $query = ParkingLot::find()->select(['id','name'])->where(['community_id'=>$communityId,'status'=>1]);
        $query->andWhere(['!=', 'parent_id', 0]);
        if ($requestArr['lot_id']) {
            $query->andWhere(['parent_id' => $requestArr['lot_id']]);
        }
        return $query->asArray()->all();
    }

    private function checkParkCode($parkCode, $id = '')
    {
        if ($id) {
            $model = ParkingLot::find()->where(['park_code'=>$parkCode])->andWhere(['!=','id',$id])->one();
        } else {
            $model = ParkingLot::find()->where(['park_code'=>$parkCode])->one();
        }
        if ($model) {
            return $model;
        } else {
            return false;
        }
    }
}
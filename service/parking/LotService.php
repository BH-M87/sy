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
use common\core\F;
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
            ->alias('lot')
            ->select('lot.id, lot.name,lot.lon, lot.lat, lot.location,lot.iot_park_name, lot.created_at,s.name supplier_name')
            ->leftJoin('iot_suppliers s','s.id = lot.supplier_id')
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
        foreach ($lots as $k => $v) {
            $lots[$k]['created_at'] = $v['created_at']  ? date("Y-m-d H:i", $v['created_at']) : '';
            //查询车位数量
            $lots[$k]['carport_num'] = ParkingCarport::find()->select('count(id)')->where(['lot_id' => $v['id']])->scalar();
        }
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
        if (!empty($params['parkCode'])) {
            $model = $this->checkParkCode($params['parkCode']);
            if ($model) {
                return $this->failed('该车场已经被绑定');
            }
        }

        //新增车场
        $model = new ParkingLot();
        $model->supplier_id = F::value($params, 'supplier_id', 0);
        $model->community_id = $params['community_id'];
        $model->name = F::value($params,'name', '');
        $model->lon = F::value($params,'lon', 0);
        $model->lat = F::value($params,'lat', 0);
        $model->location = F::value($params,'location', '');
        $model->park_code = F::value($params,'parkCode', '');
        $model->iot_park_name = F::value($params,'parkName', '');
        $model->parkId = F::value($params,'parkId', '');
        $model->created_at = time();
        if($model->save()){
            return $this->success();
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

        if (!empty($params['supplier_id']) && $model->supplier_id != $params['supplier_id']) {
            return $this->failed('供应商不可编辑');
        }
        if (!empty($params['parkCode']) && $model->park_code != $params['parkCode']) {
            return $this->failed('设备对应车场不可编辑');
        }

        $model->name = F::value($params,'name', '');
        $model->lon = F::value($params,'lon', 0);
        $model->lat = F::value($params,'lat', 0);
        $model->location = F::value($params,'location', '');
        if ($model->save()) {
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
        if ($model->supplier_id) {
            return $this->failed('车场已关联设备厂商，不可删除');
        }

        //查看车场是否绑定了车位，如果有就不能删除
        $res = ParkingCarport::find()->where(['lot_id' => $params['id']])->orderBy('id desc')->limit(1)->asArray()->one();
        if($res){
            return $this->failed('车场已绑定车位信息，请先删除车位相关信息');
        }
        if ($model->delete()) {
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
        $lotInfo['created_at'] = $lotInfo['created_at'] ? date("Y-m-d H:i", $lotInfo['created_at']) : '';
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
        $list = [
            0 => [
                'productSn' => 'dnk',
                'parkName' => '春江花园地面停车场',
                'parkCode' => 'PK0001',
                'parkId' => 1
            ],
            1 => [
                'productSn' => 'dnk',
                'parkName' => '华庭地面停车场',
                'parkCode' => 'PK0002',
                'parkId' => 2
            ],
        ];
        return $this->success($list);
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
<?php
namespace service\alipay;

use common\core\PsCommon;
use app\models\PsElectricMeter;
use app\models\PsMeterCycle;
use app\models\PsWaterMeter;
use app\models\PsWaterRecord;
use service\BaseService;
use Yii;
use yii\base\Model;
use service\rbac\OperateService;
use yii\db\Exception;

class MeterReadingService extends BaseService
{

    /**
     * 添加账期
     * @author Yjh
     * @param $data
     * @param $user
     * @return array
     */
    public function add($data,$user)
    {
        $trans = Yii::$app->db->beginTransaction();
        try{
            $callback = $this->addRelationMeter($user);
            $cycle_model = new PsMeterCycle();
            $valid = PsCommon::validParamArr($cycle_model, $data, 'add');
            if (!$valid["status"]) {
                return $this->failed($valid["errorMsg"]);
            }
            $cycle_model->period = strtotime($cycle_model->period);
            $cycle_model->meter_time = strtotime($cycle_model->meter_time);
            $cycle_model->created_at = time();
            $cycle_model->save();
            $result = $callback($cycle_model);
            if ($result['code']) {
                $operate = [
                    "community_id" =>$data['community_id'],
                    "operate_menu" => "抄表管理",
                    "operate_type" => "新增抄表",
                    "operate_content" => "抄表周期：".$data['period']
                ];
                OperateService::addComm($user, $operate);
                $trans->commit();
                return $this->success();
            } else {
                return $this->failed($result['msg']);
            }
        }catch (Exception $e){
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /**
     * 添加关联账单
     * @author Yjh
     * @param $user
     * @return \Closure
     */
    public function addRelationMeter($user)
    {
        return function (PsMeterCycle $cycle) use($user)
        {
            $callback = WaterRecordService::service()->addMeterRecord($cycle,$user);
            switch ($cycle->type) {
                //水表
                case 1:
                    $result = WaterMeterService::service()->getWaterData(['community_id'=>$cycle->community_id,'meter_status'=>1],'room_id,group_id,building_id,unit_id,address,community_id,latest_record_time,start_ton,meter_no,meter_status');
                    if (!empty($result['list'])) {
                        $callback($result['list']);
                    } else {
                        return $this->failed('请新增小区房屋的水表');
                    }
                    break;
                //电表
                case 2:
                    $result = ElectrictMeterService::service()->getElectrictData(['community_id'=>$cycle->community_id,'meter_status'=>1],'room_id,group_id,building_id,unit_id,address,community_id,latest_record_time,start_ton,meter_no,meter_status');
                    if (!empty($result['list'])) {
                        $callback($result['list']);
                    } else {
                        return $this->failed('请新增小区房屋的电表');
                    }
                    break;
                default:
                    return $this->failed('参数错误');
            }
            return $this->success();
        };
    }

    /**
     * 删除周期
     * @author Yjh
     * @param $param
     * @return array
     */
    public function delete($param,$userinfo='')
    {
        $cycle_model = new PsMeterCycle();
        $valid = PsCommon::validParamArr($cycle_model, $param, 'delete');
        if (!$valid["status"]) {
            return $this->failed($valid["errorMsg"]);
        }
        $result = WaterRecordService::service()->delete($param['id'],$userinfo);
        if ($result['code']) {
            return $this->success($result['data']);
        } else {
            return $this->failed($result['msg']);
        }
    }

    /**
     * 获取周期列表
     * @author Yjh
     * @param $param
     * @return array
     */
    public function getList($param)
    {
        unset($param['token'],$param['create_id'],$param['create_name'],$param['corp_id']);

        $cycle_model = new PsMeterCycle();
        $valid = PsCommon::validParamArr($cycle_model, $param, 'list');
        if (!$valid["status"]) {
            return $this->failed($valid["errorMsg"]);
        }
        $where['page'] = $param['page'] ?? 1;
        $where['row'] = $param['rows'] ?? 10;
        unset($param['page']);
        unset($param['rows']);
        $communityList = [];
        if(!empty($param['communityList'])){
            $communityList = $param['communityList'];
            unset($param['communityList']);
        }
        $where['where'] = $param;
        $result = PsMeterCycle::getList($where,true,$communityList);
        return $this->success($result);
    }

    /**
     * 获取抄表列表
     * @author yjh
     * @param $param
     * @return array
     */
    public function getListRecord($param)
    {
        if (empty($param['bill_type']) || empty($param['cycle_id'])) {
            return $this->failed('参数错误');
        }
        $result = WaterRecordService::service()->getData($param);
        if ($result['code']) {
            return $this->success($result['data']);
        } else {
            return $this->failed($result['msg']);
        }
    }

    /**
     * 生成账期
     * @author yjh
     * @param $param
     * @return array
     */
    public function generateBill($param,$userinfo)
    {
        //生成账单
        $result = AlipayCostService::service()->createBillByRecord($param,$userinfo);
        //修改读数和时间
        if ($result['code']) {
            //$result['data']['success_list'] = [3448,3449];
            if (!empty($result['data']['success_list'])) {
                $room_id = PsWaterRecord::find()->where(['in','id',$result['data']['success_list']])->asArray()->all();
                if (!empty($room_id)) {
                    $this->chooseMeterData($param,$room_id);
                }
            }
            return $this->success($result['data']);
        } else {
            return $this->failed($result['msg']);
        }
    }

    /**
     * 选择修改表类型
     * @author yjh
     * @param $param
     * @param array $room_id
     * @return void
     */
    public function chooseMeterData($param,array $room_id)
    {
        $this->editMeterData($room_id);
        $cycleInfo = PsMeterCycle::find()->where(['id' => $param['cycle_id'], 'community_id' => $param['community_id']])->one();
        $cycleInfo->status = 2;
        $cycleInfo->save();
        return;
    }

    /**
     * 修改上次读数和上次抄表时间
     * @author yjh
     * @param $room_id
     * @return void
     */
    public function editMeterData($room_id)
    {
        foreach ($room_id as $k => $v) {
            $model= $v['bill_type']==1 ? PsWaterMeter::findOne(['room_id'=>$v['room_id']]) : PsElectricMeter::findOne(['room_id'=>$v['room_id']]);
            $model->latest_record_time = $v['period_end'];
            $model->start_ton = $v['current_ton'];
            $model->save();
        }
    }
}
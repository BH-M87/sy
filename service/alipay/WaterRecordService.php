<?php
namespace service\alipay;

use common\core\PsCommon;
use app\models\PsMeterCycle;
use app\models\PsPhaseFormula;
use app\models\PsWaterRecord;
use app\models\PsWaterFormula;
use common\core\F;
use service\BaseService;
use service\common\ExcelService;
use service\rbac\OperateService;
use Yii;

class WaterRecordService extends BaseService
{
    /**
     * 添加相关水电账单数据
     * @author Yjh
     * @param PsMeterCycle $cycle
     * @param $user
     * @return \Closure
     */
    public function addMeterRecord(PsMeterCycle $cycle,$user)
    {
        return function ($data) use($cycle,$user)
        {
            $insert = [];
            $formulal = PsWaterFormula::getFormula(['community_id' => $cycle->community_id, 'rule_type' => $cycle->type]);
            if (!empty($data)) {
                foreach ($data as $k => $v) {
                    $insert[$k]['cycle_id'] = $cycle->id;
                    $insert[$k]['room_id'] = $v['room_id'];
                    $insert[$k]['group_id'] = $v['group_id'];
                    $insert[$k]['building_id'] = $v['building_id'];
                    $insert[$k]['unit_id'] = $v['unit_id'];
                    $insert[$k]['address'] = $v['address'];
                    $insert[$k]['community_id'] = $v['community_id'];
                    $insert[$k]['status'] = $v['meter_status'];
                    $insert[$k]['latest_ton'] = $v['start_ton'];
                    $insert[$k]['use_ton'] = 0;
                    $insert[$k]['current_ton'] = 0;
                    $insert[$k]['period_start'] = strtotime($v['latest_record_time']);
                    $insert[$k]['period_end'] = $cycle->meter_time;
                    $insert[$k]['price'] = 0;
                    $insert[$k]['meter_no'] = $v['meter_no'];
                    $insert[$k]['create_time'] = $cycle->meter_time;
                    $insert[$k]['operator_id'] = $user['id'];
                    $insert[$k]['operator_name'] = $user['truename'];
                    $insert[$k]['bill_type'] = $cycle->type;
                    $insert[$k]['formula'] = $formulal[0];
                    $insert[$k]['formula_price'] = $formulal[1];
                    $insert[$k]['has_reading'] = 2;
                    $insert[$k]['created_at'] = time();
                }
                PsWaterRecord::batchInserts($insert);
            }
        };
    }

    /**
     * 删除周期和账单
     * @author Yjh
     * @param $id
     * @return array
     * @throws \yii\db\Exception
     */
    public function delete($id,$user='')
    {
        $trans = Yii::$app->getDb()->beginTransaction();
        $cycle = PsMeterCycle::find()->where(['status'=>1,'id'=>$id])->one();;
        if ($cycle != null) {
            try {
                $operate = [
                    "community_id" =>$cycle['community_id'],
                    "operate_menu" => "抄表管理",
                    "operate_type" => "删除抄表",
                    "operate_content" => "抄表周期：".$cycle['period']
                ];
                OperateService::addComm($user, $operate);
                $cycle->delete();
                PsWaterRecord::deleteAll(['cycle_id' => $id]);
                $trans->commit();
                return $this->success();
            } catch (\Exception $e) {
                $trans->rollBack();
                return $this->failed($e->getMessage());
            }
        }
        return $this->failed('周期不存在');
    }

    /**
     * 导出数据
     * @author yjh
     */
    public function export($where)
    {
        $result = $this->getData($where,false);
        $config = $this->exportConfig();
        $url = ExcelService::service()->export($result['data']['list'], $config);
        return $this->success($url);
    }

    /**
     * 导出配置
     * @author yjh
     * @return array
     */
    public function exportConfig()
    {
        $config["sheet_config"] = [
            'address' => ['title' => '房屋地址', 'width' => 30],
            'bill_type' => ['title' => '表具类型', 'width' => 16],
            'meter_no' => ['title' => '表具编号', 'width' => 16],
            'formula' => ['title' => '单价', 'width' => 16],
            'period_start' => ['title' => '上次抄表时间', 'width' => 18],
            'period_end' => ['title' => '本次抄表时间', 'width' => 18],
            'latest_ton' => ['title' => '上次抄表读数', 'width' => 16],
            'current_ton' => ['title' => '本次抄表读数', 'width' => 16],
            'use_ton' => ['title' => '抄表用量', 'width' => 16],
            'price' => ['title' => '抄表费用', 'width' => 16],

        ];
        $config["save"] = true;
        $config['path'] = 'temp/'.date('Y-m-d');
        $config['file_name'] = ExcelService::service()->generateFileName('record');
        return $config;
    }


    /**
     * 获取数据
     * @author yjh
     * @param $param
     * @param bool $page
     * @return array
     */
    public function getData($param,$page = true)
    {
        //条件处理
        $data['page'] = $param['page'] ?? 1;
        $data['row'] = $param['rows'] ?? 10;
        unset($param['page']);
        unset($param['rows']);
        $where['community_id'] = !empty($param['community_id']) ? $param['community_id'] : null ;
        $where['room_id'] = !empty($param['room_id']) ? $param['room_id'] : null ;
        $where['group_id'] = !empty($param['group_id']) ? $param['group_id'] : null ;
        $where['building_id'] = !empty($param['building_id']) ? $param['building_id'] : null ;
        $where['unit_id'] = !empty($param['unit_id']) ? $param['unit_id'] : null ;
        $where['cycle_id'] = !empty($param['cycle_id']) ? $param['cycle_id'] : null ;
        $where['bill_type'] = !empty($param['bill_type']) ? $param['bill_type'] : null ;

        $where = F::searchFilter($where);
        $like = !empty($param['meter_no']) ? ['like' , 'meter_no' , $param['meter_no']] : '1=1' ;
        //查询
        $data['where'] = $where;
        $data['like'] = $like;
        $field = 'id,room_id,bill_type,current_ton,formula,address,has_reading,latest_ton,meter_no,period_end,period_start,price,use_ton';
        $result = PsWaterRecord::getData($data,$field,$page,$param['communityList']);
        return $this->success($result);
    }

    /**
     * 修改读数
     * @author yjh
     * @param $param
     * @return array
     */
    public function updateMeterNun($param)
    {
        $model = new PsWaterRecord();
        $valid = PsCommon::validParamArr($model, $param, 'edit_meter_num');
        if (!$valid["status"]) {
            return $this->failed($valid["errorMsg"]);
        }
        if ($param['current_ton'] < $param['latest_ton']) {
            return $this->failed('本期读数不能小于上期读数');
        }
        if (empty($param['community_id'])) {
            return $this->failed('小区ID不能为空');
        }
        $model = $model->findOne($param['id']);
        if (!empty($model)) {
            if ($model->has_reading == 3) {
                return $this->failed('已经发布的账单不能修改');
            }
            $result = $this->editPrice($model, $param);
            return $this->success($result);
        } else {
            return $this->failed('未查到数据');
        }
    }

    /**
     * 修改价格
     * @author yjh
     * @param PsWaterRecord $model
     * @param $param
     * @return PsWaterRecord|array
     */
    public function editPrice(PsWaterRecord $model,$param)
    {
        $ton = $param['use_ton'] = $this->getTon($param);
        $price = AlipayCostService::service()->taskAmount($param['community_id'],$model->bill_type,$ton);
        if ($price['code']) {
            $param['price'] = $price['data'];
        } else {
            return $this->failed($price['msg']);
        }
        $model = PsWaterRecord::editMeterNum($param);
        return $model;
    }

    /**
     * 计算使用量
     * @author yjh
     * @param $param
     * @return mixed
     */
    public function getTon($param)
    {
        return $param['current_ton'] - $param['latest_ton'];
    }


    /**
     * 获取水电单价
     * @author Yjh
     * @param $json
     * @return string
     */
    public function getFormula($json)
    {
        $formula = json_decode($json['formula'],true);
        if (count($formula) > 1) {
           return $formula[1]['price'].'元(阶梯价)';;
        } else {
            return $formula[0]['price'].'元(固定价)';
        }
    }

}
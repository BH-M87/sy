<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/8/23
 * Time: 16:29
 */

namespace service\parking;


use app\models\ParkingAcrossRecord;
use app\models\ParkingDevices;
use common\core\F;
use service\BaseService;

class CarAcrossService extends BaseService
{
    public $carTypes = [
        0 => ['id' => 1, 'name' => '会员'],
        1 => ['id' => 2, 'name' => '访客'],
    ];

    //搜索出库记录
    private function _searchOut($params)
    {
        $community_id = F::value($params, 'community_id');
        $outTimeStart = !empty($params['out_time_start']) ? strtotime($params['out_time_start']) : 0;
        $outTimeEnd = !empty($params['out_time_end']) ? strtotime($params['out_time_end'] . ' 23:59:59') : 0;
        return ParkingAcrossRecord::find()
            ->filterWhere([
                'car_type' => F::value($params, 'car_type'),
                'community_id' =>$community_id
            ])
            ->andFilterWhere(['like', 'car_num', F::value($params, 'car_num')])
            ->andFilterWhere(['>=', 'amount', F::value($params, 'amount_min')])
            ->andFilterWhere(['<=', 'amount', F::value($params, 'amount_max')])
            ->andFilterWhere(['>=', 'out_time', $outTimeStart])
            ->andFilterWhere(['<=', 'out_time', $outTimeEnd])
            ->andWhere(['>', 'out_time', 0]);
    }

    public function outList($params, $page, $pageSize){
        $data = $this->_searchOut($params)
            ->orderBy('out_time desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->asArray()->all();
        $result = [];
        $total = $this->outListCount($params);
        $i = $total - ($page-1)*$pageSize;
        foreach ($data as $v) {
            $v['car_type'] = F::value($this->carTypes, $v['car_type']-1, []);
            $v['in_time'] = date('Y-m-d H:i:s', $v['in_time']);
            $v['out_time'] = date('Y-m-d H:i:s', $v['out_time']);
            $v['park_time'] = $this->parkTimeFormat($v['park_time']);
            $v['tid'] = $i--;//编号
            $v['discount_amount'] = !empty($v['discount_amount']) ? $v['discount_amount'] : "0.00";
            $v['pay_amount'] = !empty($v['pay_amount']) ? $v['pay_amount'] : "0.00";
            $result[] = $v;
        }
        return $result;
    }

    public function outListCount($params){
        return $this->_searchOut($params)->count();
    }

    /**
     * 后台在库车辆查询
     * @param $params
     */
    private function _searchIn($params)
    {
        $community_id = F::value($params, 'community_id');
        $inTimeStart = !empty($params['in_time_start']) ? strtotime($params['in_time_start']) : null;
        $inTimeEnd = !empty($params['in_time_end']) ? strtotime($params['in_time_end'] . ' 23:59:59') : null;
        return ParkingAcrossRecord::find()
            ->where([
                'community_id' => $community_id,
                'out_time' => 0
            ])->andFilterWhere(['car_type' => F::value($params, 'car_type')])
            ->andFilterWhere(['like', 'car_num', F::value($params, 'car_num')])
            ->andFilterWhere(['>=', 'in_time', $inTimeStart])
            ->andFilterWhere(['<=', 'in_time', $inTimeEnd]);
    }

    public function inList($params, $page, $pageSize){
        $data = $this->_searchIn($params)
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->groupBy('car_num')
            ->orderBy(['id'=>SORT_DESC])
            ->asArray()->all();
        $result = [];
        $total = $this->inListCount($params);
        $i = $total - ($page-1)*$pageSize;
        foreach ($data as $v) {
            $v['car_type'] = F::value($this->carTypes, $v['car_type']-1, []);
            $time = $v['in_time'];
            $parkTime = intval((time() - $time) / 60);//分钟
            $v['park_time'] = $this->parkTimeFormat($parkTime);
            $v['in_time'] = date('Y-m-d H:i:s', $time);
            $v['id'] = $i--;//编号
            $result[] = $v;
        }
        return $result;
    }

    public function inListCount($params){

        return $this->_searchIn($params)->groupBy('car_num')->count();

    }

    /**
     * 停车时长，分钟数转化为: xx天xx小时xx分钟
     * @param $minutes
     */
    public function parkTimeFormat($minutes)
    {
        $str = '';
        $day = intval($minutes / (60 * 24));
        if ($day) {
            $str .= $day . '天';
        }
        $left = $minutes % (60 * 24);
        $hour = intval($left / 60);
        if ($hour) {
            $str .= $hour . '小时';
        }
        $m = $left % 60;
        $str .= $m . '分钟';
        return $str;
    }


    public function getDeviceAddress($community_id,$supplier_id,$device_id){
        $res = ParkingDevices::find()->where(['community_id'=>$community_id,'supplier_id'=>$supplier_id,'device_id'=>$device_id])->one();
        if($res){
            return $res;
        }else{
            return '';
        }
    }

    public function checkData($requestArr){
        if($requestArr['id']){

        }
    }

    /**
     * 车辆入库
     * @param $requestArr
     * @return bool
     */
    public function addRecordData($requestArr)
    {
        $community = new ParkingAcrossRecord();
        $community->scenario = 'enter';
        $requestArr['created_at'] = time();
        $requestArr['in_time'] = $requestArr['in_time'] ? strtotime($requestArr['in_time']) : '0';
        $requestArr['in_address'] = $requestArr['in_address'] ?
            $requestArr['in_address'] : self::getDeviceAddress($requestArr['community_id'],$requestArr['supplier_id'],$requestArr['in_gate_id']);
        if($requestArr['in_time'] > time()){
            return "入库时间不能大于当前时间";
        }
        $requestArr['out_time'] = $requestArr['out_time'] ? strtotime($requestArr['out_time']) : '0';
        $community->load($requestArr, '');
        if ($community->validate()) {
            if ($community->save()) {
                return true;
            } else {
                return false;
            }
        } else {
            $re = array_values($community->getErrors());
            return $re[0][0];
        }
    }

    /**
     * 车辆出库
     * @param $requestArr
     * @return bool
     */
    public function editRecordData($requestArr)
    {
        $requestArr['in_time'] = strtotime($requestArr['in_time']);
        $community = ParkingAcrossRecord::find()->where(['in_time'=>$requestArr['in_time'],'car_num'=>$requestArr['car_num'],'out_time'=>0])->one();
        if(!$community){
            return false;
        }
        $res = $community->toArray();
        if($res['out_time'] > 0){
            return "该车辆已经出库";//防止多次插入数据
        }
        $community->scenario = 'exit';
        $requestArr['update_at'] = time();
        $requestArr['out_address'] = $requestArr['out_address'] ? $requestArr['out_address'] : self::getDeviceAddress($requestArr['community_id'],$requestArr['supplier_id'],$requestArr['out_gate_id']);
        $requestArr['out_time'] = $requestArr['out_time'] ? strtotime($requestArr['out_time']) : '0';
        if ($requestArr['car_type'] == 2) {
            if (empty($requestArr['amount'])) {
                return "缺少停车费用";
            }
            if (empty($requestArr['park_time'])) {
                return "缺少停车时长";
            }
        }

        $community->load($requestArr, '');
        if ($community->validate()) {
            if ($community->save()) {
                return true;
            } else {
                return false;
            }
        } else {
            $re = array_values($community->getErrors());
            return $re[0][0];
        }
    }
}
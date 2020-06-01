<?php

namespace service\alipay;

use app\models\PsChannelDayReport;
use app\models\PsChannelMonthReport;
use service\BaseService;
use Yii;
use common\core\F;
use common\core\PsCommon;
use app\models\BillReportMonthly;
use service\common\ExcelService;
use app\models\PsBillCost;
use app\models\PsCommunityModel;
use app\models\BillReportYearly;
use app\models\BillReportRoom;
use app\models\PsBillYearly;
use app\models\PsBillIncome;
use service\manage\CommunityService;

class BillReportService extends BaseService
{
    protected static $service = [

    ];

    /**
     * 获取月报表
     * @author yjh
     * @param array $params 搜索参数
     * @return array
     */
    public function getMonthList($params)
    {
        if (empty($params['community_id']) || empty($params['start_time'])) {
            return $this->failed("参数错误");
        }
        $date = F::getYearMonth(strtotime($params['start_time']));
        $result = BillReportMonthly::getMonthList($date,$params['cost_id'] ?? null,$params['community_id']);
        if (!empty($result)) {
            //总计列
            $total_data['community_name'] = '总计';
            $total_data['charge_discount'] = 0;
            $total_data['charge_advance'] = 0;
            $total_data['charge_history'] = 0;
            $total_data['charge_last'] = 0;
            $total_data['charge_amount'] = 0;
            $total_data['year_charge_advanced'] = 0;
            $total_data['year_charge_discount'] = 0;
            $total_data['year_charge_history'] = 0;
            $total_data['year_charge_last'] = 0;
            $total_data['year_charge_amount'] = 0;
            $total_data['total_charge'] = 0;
            foreach ($result as $k => $v) {
                $result[$k]['community_name'] = CommunityService::service()->getShowCommunityInfo($v["community_id"])['name'];
                $result[$k]['cost_name'] = BillCostService::service()->getCostName($v["cost_id"])['data']['name'];
                $result[$k]['year_total_charge'] = sprintf("%.2f",$v['year_charge_last'] + $v['year_charge_history'] + $v['year_charge_advanced'] + $v['year_charge_amount']);
                $total_data['charge_discount'] += $v['charge_discount'];
                $total_data['charge_advance'] += $v['charge_advance'];
                $total_data['charge_history'] += $v['charge_history'];
                $total_data['charge_last'] += $v['charge_last'];
                $total_data['charge_amount'] += $v['charge_amount'];
                $total_data['year_charge_advanced'] += $v['year_charge_advanced'];
                $total_data['year_charge_discount'] += $v['year_charge_discount'];
                $total_data['year_charge_history'] += $v['year_charge_history'];
                $total_data['year_charge_last'] += $v['year_charge_last'];
                $total_data['year_charge_amount'] += $v['year_charge_amount'];
            }
            $total_data['total_charge'] = $total_data['charge_amount'] + $total_data['charge_last'] + $total_data['charge_history'] + $total_data['charge_advance'];
            $total_data['year_total_charge'] = $total_data['year_charge_amount'] + $total_data['year_charge_last'] + $total_data['year_charge_history']  + $total_data['year_charge_advanced'];
            $total_data = $this->roundData($total_data);
        }
        $arr = ["total_data" => $total_data ?? null, "list" => $result,'total'=>count($result)];
        return $this->success($arr);
    }

    /**
     * 获取渠道列表
     * @author yjh
     * @param array $data 搜索参数
     * @return array
     */
    public function getChannelList($data)
    {

        if (empty($data['community_id'])) {
            return $this->failed('参数错误');
        }
        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            $date =  [
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
            ];
        } else {
            $date = null;
        }
        $arr = [
            'community_id' => $data['community_id'],
            'cost_id' => $data['cost_id'] ?? null,
        ];
        //数据查询处理
        $result = PsChannelDayReport::getChannelList($arr,$date);
        $community = $this->countChannelCommunity($result);
        //数据合并处理
        $data = $this->channelDataMerger($result,$community);
        //计算横向总计
        $data = $this->countHChannelData($data);
        if (!empty($data)) {
            //计算纵向总计
            $total_data = $this->countZChannelData($data);
            //格式化小数
        }
        $arr = ["total_data" => $total_data ?? null ,"list" => $data,'total'=>count($data)];
        return $this->success($arr);
    }
    
    /**
     * 计算渠道横向总计数据
     * @author yjh
     * @param array $data 渠道数据
     * @return array
     */
    public function countHChannelData($data)
    {
        foreach ($data as $k => $v) {
            $data[$k]['count'] = sprintf("%.2f",$v['money']+$v['alipay']+$v['wechat']+$v['card']+$v['public']+$v['cheque']+$v['line_charge']);
            $data[$k]['community_name'] = CommunityService::service()->getShowCommunityInfo($v["community_id"])['name'];
            $data[$k]['cost_name'] = BillCostService::service()->getCostName($v["cost_id"])['data']['name'];
        }
        return $data;
    }

    /**
     * 计算渠道纵向总计数据
     * @author yjh
     * @param array $result 渠道总计数据
     * @return array
     */
    public function countZChannelData($result)
    {
        $total_data['community_name'] = '总计';
        $total_data['alipay'] = 0;
        $total_data['card'] = 0;
        $total_data['cheque'] = 0;
        $total_data['count'] = 0;
        $total_data['line_charge'] = 0;
        $total_data['money'] = 0;
        $total_data['public'] = 0;
        $total_data['wechat'] = 0;
        foreach ($result as $k => $v) {
            $total_data['alipay'] += $v['alipay'];
            $total_data['card'] += $v['card'];
            $total_data['cheque'] += $v['cheque'];
            $total_data['count'] += $v['count'];
            $total_data['line_charge'] += $v['line_charge'];
            $total_data['money'] += $v['money'];
            $total_data['public'] += $v['public'];
            $total_data['wechat'] += $v['wechat'];
        }
        return $this->roundData($total_data);
    }

    /**
     * 格式化小数数据
     * @author yjh
     * @param array $data 需要格式化的数组
     * @return array
     */
    public function roundData($data)
    {
        return array_map(function($v){
            if (is_numeric($v)) {
                return sprintf("%.2f",$v);
            } else {
                return $v;
            }
        },$data);
    }

    /**
     * 合并渠道列表xinx
     * @author yjh
     * @param array $result 渠道数据
     * @param array $community 小区集合
     * @return array
     */
    public function channelDataMerger($result,$community)
    {
        foreach ($result as $kk => $vv) {
            switch ($vv['type']) {
                case 1:
                    foreach ($community as $kkk => &$vvv) {
                        if ($vvv['cost_id'] == $vv['cost_id'] && $vvv['community_id'] == $vv['community_id']) {
                            $vvv['money'] = sprintf("%.2f",$vvv['money']+$vv['c_amount']);
                        }
                    }
                    break;
                case 2:
                    foreach ($community as $kkk => &$vvv) {
                        if ($vvv['cost_id'] == $vv['cost_id'] && $vvv['community_id'] == $vv['community_id']) {
                            $vvv['alipay'] = sprintf("%.2f",$vvv['alipay'] + $vv['c_amount']);
                        }
                    }
                    break;
                case 3:
                    foreach ($community as $kkk => &$vvv) {
                        if ($vvv['cost_id'] == $vv['cost_id'] && $vvv['community_id'] == $vv['community_id']) {
                            $vvv['wechat'] = sprintf("%.2f",$vvv['wechat']+$vv['c_amount']);
                        }
                    }
                    break;
                case 4:
                    foreach ($community as $kkk => &$vvv) {
                        if ($vvv['cost_id'] == $vv['cost_id'] && $vvv['community_id'] == $vv['community_id']) {
                            $vvv['card'] = sprintf("%.2f",$vvv['card']+$vv['c_amount']);
                        }
                    }
                    break;
                case 5:
                    foreach ($community as $kkk => &$vvv) {
                        if ($vvv['cost_id'] == $vv['cost_id'] && $vvv['community_id'] == $vv['community_id']) {
                            $vvv['public'] = sprintf("%.2f",$vvv['public']+$vv['c_amount']);
                        }
                    }
                    break;
                case 6:
                    foreach ($community as $kkk => &$vvv) {
                        if ($vvv['cost_id'] == $vv['cost_id'] && $vvv['community_id'] == $vv['community_id']) {
                            $vvv['cheque'] = sprintf("%.2f",$vvv['cheque']+$vv['c_amount']);
                        }
                    }
                    break;
                case 9:
                    foreach ($community as $kkk => &$vvv) {
                        if ($vvv['cost_id'] == $vv['cost_id'] && $vvv['community_id'] == $vv['community_id']) {
                            $vvv['line_charge'] = sprintf("%.2f",$vvv['line_charge']+$vv['c_amount']);
                        }
                    }
                    break;
            }
        }
        return $community;
    }

    /**
     * 合并统计渠道小区和缴费项
     * @author yjh
     * @param array $result 渠道数据
     * @return array
     */
    public function countChannelCommunity($result)
    {
        $community = [];
        foreach ($result as $k => $v) {
            if (!empty($community)) {
                $flag[1] = array_column($community,'cost_id');
                $flag[2] = array_column($community,'community_id');
                foreach ($flag as $kk => $vv) {
                    if (!in_array($v['cost_id'],$flag[1])) {
                        $community[$k] = [
                            'cost_id' => $v['cost_id'],
                            'community_id' => $v['community_id'],
                            'money' => '0.00',
                            'alipay' => '0.00',
                            'wechat' => '0.00',
                            'card' => '0.00',
                            'public' => '0.00',
                            'cheque' => '0.00',
                            'line_charge' => '0.00',
                        ];
                    }
                }
            } else {
                $community[] = [
                    'cost_id' => $v['cost_id'],
                    'community_id' => $v['community_id'],
                    'money' => '0.00',
                    'alipay' => '0.00',
                    'wechat' => '0.00',
                    'card' => '0.00',
                    'public' => '0.00',
                    'cheque' => '0.00',
                    'line_charge' => '0.00',
                ];
            }
        }
        return array_values($community);
    }

    /**
     * 导出渠道表
     * @author yjh
     * @param array $data 渠道数据
     * @return array
     */
    public function ExportChannel($data)
    {
        $result = BillReportService::service()->getChannelList($data);
        array_push($result['data']['list'],$result['data']['total_data']);
        $config = $this->exportChannelConfig();
        $url = ExcelService::service()->export($result['data']['list'], $config);
        $fileName = pathinfo($url, PATHINFO_BASENAME);
        return $fileName;
    }

    /**
     * 导出渠道表配置
     * @author yjh
     * @return array
     */
    public function exportChannelConfig()
    {
        $config["sheet_config"] = [
        'community_name' => ['title' => '小区名称', 'width' => 16],
        'cost_name' => ['title' => '收费项目', 'width' => 16],
        'line_charge' => ['title' => '线上收款', 'width' => 16],
        'money' => ['title' => '现金', 'width' => 16],
        'alipay' => ['title' => '支付宝', 'width' => 16],
        'wechat' => ['title' => '微信', 'width' => 18],
        'card' => ['title' => '刷卡', 'width' => 16],
        'public' => ['title' => '对公', 'width' => 16],
        'cheque' => ['title' => '支票', 'width' => 16],
        'count' => ['title' => '合计', 'width' => 16],
        ];
        $config["save"] = true;
        $config['path'] = 'temp/'.date('Y-m-d');
        $config['file_name'] = ExcelService::service()->generateFileName('month_bill');
        return $config;
    }

    /**
     * 导出月报表
     * @author yjh
     * @param $data 月表数据
     * @return array
     */
    public function exportMonth($data)
    {
        $result = BillReportService::service()->getMonthList($data);
        if (!$result['code']) {
            return $this->failed($result["msg"]);
        }
        array_push($result['data']['list'],$result['data']['total_data']);
        $config = $this->exportMonthConfig();
        $url = ExcelService::service()->export($result['data']['list'], $config,$config['diy'],$config['start']);
        $fileName = pathinfo($url, PATHINFO_BASENAME);
        return $this->success($fileName);
    }

    /**
     * 导出月报表配置
     * @author yjh
     * @return array
     */
    public function exportMonthConfig()
    {
        $config["sheet_config"] = [
            'community_name' => ['title' => '小区名称', 'width' => 16],
            'cost_name' => ['title' => '收费项目', 'width' => 16],
            'charge_amount' => ['title' => '收当年费用', 'width' => 16],
            'charge_last' => ['title' => '收上年费用', 'width' => 16],
            'charge_history' => ['title' => '收历年费用', 'width' => 16],
            'charge_advance' => ['title' => '预收下年', 'width' => 16],
            'charge_discount' => ['title' => '优惠金额总计', 'width' => 16],
            'total_charge' => ['title' => '本月收费总计', 'width' => 16],
            '' => ['title' => '', 'width' => 16],
            'year_charge_amount' => ['title' => '收当年费用', 'width' => 16],
            'year_charge_last' => ['title' => '收上年欠费', 'width' => 18],
            'year_charge_history' => ['title' => '收历年欠费', 'width' => 18],
            'year_charge_advanced' => ['title' => '预收下年', 'width' => 16],
            'year_charge_discount' => ['title' => '优惠金额合计', 'width' => 18],
            'year_total_charge' => ['title' => '本年收费合计', 'width' => 16],
        ];
        //设置diy头
        $config['diy']['A1:H1']= [
            'start' => 'A1' ,
            'info' => '本月累积收费' ,
        ];
        $config['diy']['I1:O1']= [
            'start' => 'I1' ,
            'info' => '本年累积收费' ,
        ];
        $config['start'] = 2;
        $config["save"] = true;
        $config['path'] = 'temp/'.date('Y-m-d');
        $config['file_name'] = ExcelService::service()->generateFileName('month_bill');
        return $config;
    }

    // 年收费总况 搜索
    private function _yearSearch($params)
    {
        $model = BillReportYearly::find()
            ->andFilterWhere(['=', 'community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['in', 'cost_id', PsCommon::get($params, 'cost_id')])
            ->andFilterWhere(['=', 'year', PsCommon::get($params, 'start_time')]);
        return $model;
    }

    private function _totalList($model, $k)
    {
        $k += 1;

        $model[$k]['community_name'] = '合计';
        $model[$k]['cost_name'] = '';

        $model[$k]['charge_advanced'] = number_format(array_sum(array_map(function($val){return $val['charge_advanced'];}, $model)), 2);
        $model[$k]['charge_advanced_discount'] = number_format(array_sum(array_map(function($val){return $val['charge_advanced_discount'];}, $model)), 2);
        $model[$k]['charge_amount'] = number_format(array_sum(array_map(function($val){return $val['charge_amount'];}, $model)), 2);
        $model[$k]['charge_discount'] = number_format(array_sum(array_map(function($val){return $val['charge_discount'];}, $model)), 2);
        $model[$k]['charge_history'] = number_format(array_sum(array_map(function($val){return $val['charge_history'];}, $model)), 2);
        $model[$k]['charge_history_discount'] = number_format(array_sum(array_map(function($val){return $val['charge_history_discount'];}, $model)), 2);
        $model[$k]['charge_last'] = number_format(array_sum(array_map(function($val){return $val['charge_last'];}, $model)), 2);
        $model[$k]['charge_last_discount'] = number_format(array_sum(array_map(function($val){return $val['charge_last_discount'];}, $model)), 2);
        $model[$k]['total_charge'] = sprintf("%.2f", array_sum(array_map(function($val){return $val['total_charge'];}, $model)));
        $model[$k]['total_charge_discount'] = sprintf("%.2f", array_sum(array_map(function($val){return $val['total_charge_discount'];}, $model)));
        // 当年未收
        $model[$k]['nocharge_amount'] = number_format(array_sum(array_map(function($val){return $val['nocharge_amount'];}, $model)), 2);
        $model[$k]['nocharge_history'] = number_format(array_sum(array_map(function($val){return $val['nocharge_history'];}, $model)), 2);
        $model[$k]['nocharge_last'] = number_format(array_sum(array_map(function($val){return $val['nocharge_last'];}, $model)), 2);
        $model[$k]['total_nocharge'] = number_format(array_sum(array_map(function($val){return $val['total_nocharge'];}, $model)), 2);
        // 当年应收费
        $model[$k]['bill_advanced'] = number_format(array_sum(array_map(function($val){return $val['bill_advanced'];}, $model)), 2);
        $model[$k]['bill_amount'] = number_format(array_sum(array_map(function($val){return $val['bill_amount'];}, $model)), 2);
        $model[$k]['bill_history'] = number_format(array_sum(array_map(function($val){return $val['bill_history'];}, $model)), 2);
        $model[$k]['bill_last'] = number_format(array_sum(array_map(function($val){return $val['bill_last'];}, $model)), 2);
        $model[$k]['total_bill'] = sprintf("%.2f", array_sum(array_map(function($val){return $val['total_bill'];}, $model)));

        
        if ($model[$k]['total_bill'] != 0) {
            $model[$k]['rate'] = 100 * number_format(($model[$k]['total_charge'] + $model[$k]['total_charge_discount'] - $model[$k]['charge_advanced'] - $model[$k]['charge_advanced_discount']) / $model[$k]['total_bill'], 4) . '%';
        } else {
            $model[$k]['rate'] = number_format(0, 2) . '%';
        }
      
        return $model;
    }

    // 年收费总况 列表
    public function yearList($params)
    {
        $model = $this->_yearSearch($params)->select('*')->orderBy('cost_id asc')->asArray()->all();
        if (!empty($model)) {
            $community_name = PsCommunityModel::findOne(PsCommon::get($params, 'community_id'))->name;
            foreach ($model as $k => $v) {
                $model[$k]['community_name'] = $community_name;
                $model[$k]['cost_name'] = PsBillCost::findOne($v['cost_id'])->name;

                // 已收合计(不含优惠) = 收当年费用 + 收上年欠费 + 收历年欠费 + 预收下年
                $model[$k]['total_charge'] = sprintf("%.2f", $v['charge_amount'] + $v['charge_last'] + $v['charge_history'] + $v['charge_advanced']);
                // 已收优惠合计 = 收当年费用优惠 + 收上年欠费优惠 + 收历年欠费优惠 + 预收下年优惠
                $model[$k]['total_charge_discount'] = sprintf("%.2f", $v['charge_discount'] + $v['charge_last_discount'] + $v['charge_history_discount'] + $v['charge_advanced_discount']);
                // 当年应收合计 = 当年应收 + 上年欠费应收 + 历年欠费应收
                $model[$k]['total_bill'] = sprintf("%.2f", $v['bill_amount'] + $v['bill_last'] + $v['bill_history']);
                // 未收合计 = 当年实际未收 + 上年实际未收 + 历年实际未收
                $model[$k]['total_nocharge'] = sprintf("%.2f", $v['nocharge_amount'] + $v['nocharge_last'] + $v['nocharge_history']);
                // 收缴率
                if ($model[$k]['total_bill'] != 0) {
                    $model[$k]['rate'] = 100 * number_format(($model[$k]['total_charge'] + $model[$k]['total_charge_discount'] - $v['charge_advanced'] - $v['charge_advanced_discount']) / $model[$k]['total_bill'], 4) . '%';
                } else {
                    $model[$k]['rate'] = number_format(0, 2) . '%';
                }
            }

            $arr['list'] = self::_totalList($model, $k);
        }

        return $arr;
    }

    // 年收费总况 总数
    public function yearCount($params)
    {
        return $this->_yearSearch($params)->count();
    }

    // 收费项目明细 搜索
    private function _roomSearch($params)
    {
        $model = BillReportRoom::find()->alias("A")
            ->leftJoin("ps_community_roominfo B", "A.room_id = B.id")
            ->andFilterWhere(['=', 'A.community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'A.cost_id', PsCommon::get($params, 'cost_id')])
            ->andFilterWhere(['=', 'B.group', PsCommon::get($params, 'group')])
            ->andFilterWhere(['=', 'B.building', PsCommon::get($params, 'building')])
            ->andFilterWhere(['=', 'B.unit', PsCommon::get($params, 'unit')])
            ->andFilterWhere(['=', 'B.room', PsCommon::get($params, 'room')])
            ->andFilterWhere(['=', 'A.year', PsCommon::get($params, 'start_time')]);

        return $model;
    }

    // 收费项目明细 列表
    public function roomList($params)
    {
        $model = $this->_roomSearch($params)
            ->select('A.*, B.group, B.building, B.unit, B.room, B.charge_area')->orderBy('A.room_id desc')->asArray()->all();

        if (!empty($model)) {
            $community_name = PsCommunityModel::findOne(PsCommon::get($params, 'community_id'))->name;
            foreach ($model as $k => $v) {
                if (!empty($v['group'])) {
                    $model[$k]['community_name'] = $community_name;
           
                    // 已收合计(不含优惠) = 收当年费用 + 收上年欠费 + 收历年欠费 + 预收下年
                    $model[$k]['total_charge'] = sprintf("%.2f", $v['charge_amount'] + $v['charge_last'] + $v['charge_history'] + $v['charge_advanced']);
                    // 已收优惠合计 = 收当年费用优惠 + 收上年欠费优惠 + 收历年欠费优惠 + 预收下年优惠
                    $model[$k]['total_charge_discount'] = sprintf("%.2f", $v['charge_discount'] + $v['charge_last_discount'] + $v['charge_history_discount'] + $v['charge_advanced_discount']);
                    // 当年应收合计 = 当年应收 + 上年欠费应收 + 历年欠费应收
                    $model[$k]['total_bill'] = sprintf("%.2f", $v['bill_amount'] + $v['bill_last'] + $v['bill_history']);
                    // 未收合计 = 当年实际未收 + 上年实际未收 + 历年实际未收
                    $model[$k]['total_nocharge'] = sprintf("%.2f", $v['nocharge_amount'] + $v['nocharge_last'] + $v['nocharge_history']);
                } else {
                    unset($model[$k]);
                } 
            }

            $arr['list'] = array_values(self::_totalList($model, $k));
        } else {
            $arr['list'] = [];
        }
        
        return $arr;
    }

    // 收费项目明细 总数
    public function roomCount($params)
    {
        return $this->_roomSearch($params)->count();
    }

    // 统计分析
    public function analysis($p)
    {
        $year = !empty($p['year']) ? $p['year'] : date('Y', time());
        $yearStart = strtotime($year.'-1-1');
        $yearEnd = strtotime($year.'-12-31 23:59:59');

        $income = PsBillIncome::find()->select('sum(pay_money)')
            ->where(['>=', 'income_time', $yearStart])
            ->andWhere(['<=', 'income_time', $yearEnd])->andWhere(['trade_type' => 1])
            ->andFilterWhere(['=', 'community_id', $p['community_id']])->scalar();

        $list = PsBillYearly::find()->select('sum(pay_amount) count, pay_month item')
            ->andFilterWhere(['=', 'community_id', $p['community_id']])
            ->andFilterWhere(['=', 'pay_year', $year])
            ->andFilterWhere(['=', 'is_del', 1])
            ->groupBy('pay_month')->orderBy('pay_month asc')->asArray()->all();
        $arr = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $count = $v['count'];
                switch ($v['item']) {
                    case '01':
                        $arr[0] = $count;
                        break;
                    case '02':
                        $arr[1] = $count;
                        break;
                    case '03':
                        $arr[2] = $count;
                        break;
                    case '04':
                        $arr[3] = $count;
                        break;
                    case '05':
                        $arr[4] = $count;
                        break;
                    case '06':
                        $arr[5] = $count;
                        break;
                    case '07':
                        $arr[6] = $count;
                        break;
                    case '08':
                        $arr[7] = $count;
                        break;
                    case '09':
                        $arr[8] = $count;
                        break;
                    case '10':
                        $arr[9] = $count;
                        break;
                    case '11':
                        $arr[10] = $count;
                        break;
                    case '12':
                        $arr[11] = $count;
                        break;
                }
            }
        }
        
        $m['list'] = $arr;
        $m['total'] = round($income, 2);

        // 缴费记录分析
        $bill = PsBillYearly::find()->select('sum(discount_amount) discount, sum(pay_amount) pay')
            ->where(['=', 'pay_status', 1])
            ->andFilterWhere(['=', 'community_id', $p['community_id']])
            ->andFilterWhere(['=', 'pay_year', $year])
            ->andFilterWhere(['=', 'is_del', 1])
            ->asArray()->one();

        $discount = !empty($bill['discount']) ? $bill['discount'] : 0;
        $pay = !empty($bill['pay']) ? $bill['pay'] : 0;

        $total = $discount + $pay;
        $m['bill'] = [];
        if ($total > 0) {
            $m['bill'] = [
                ['count' => $discount, 'item' => '优惠', 'percent' => round($discount / $total, 2)],
                ['count' => $pay, 'item' => '已缴', 'percent' => round($pay / $total, 2)]
            ];
        }

        
        
        // 缴费记录项目分析
        $cost = PsBillYearly::find()->select('sum(pay_amount) count, cost_id item')
            //->andFilterWhere(['in', 'cost_id', [1,2,11]])
            ->andFilterWhere(['=', 'community_id', $p['community_id']])
            ->andFilterWhere(['=', 'pay_year', $year])
            ->andFilterWhere(['=', 'is_del', 1])
            ->groupBy('cost_id')->asArray()->all();

        if (!empty($cost)) {
            $total = array_sum(array_column($cost, 'count'));
            foreach ($cost as $k => &$v) {
                $v['percent'] = $total > 0 ? round($v['count'] / $total, 2) : 0;
                $v['item'] = PsBillCost::findOne($v['item'])->name;
            }
        }

        // 未缴账单渠道分析
        $costNo = PsBillYearly::find()->select('sum(bill_amount) count, cost_id item')
            ->where(['=', 'pay_status', 0])
            //->andFilterWhere(['in', 'cost_id', [1,2,11]])
            ->andFilterWhere(['=', 'community_id', $p['community_id']])
            ->andFilterWhere(['=', 'acct_year', $year])
            ->andFilterWhere(['=', 'is_del', 1])
            ->groupBy('cost_id')->asArray()->all();

        if (!empty($costNo)) {
            $total = array_sum(array_column($costNo, 'count'));
            foreach ($costNo as $k => &$v) {
                $v['percent'] = $total > 0 ? round($v['count'] / $total, 2) : 0;
                $v['item'] = PsBillCost::findOne($v['item'])->name;
            }
        }
        
        // 缴费记录渠道分析
        $channel = PsBillYearly::find()->alias('A')->select('sum(A.pay_amount) count, B.pay_channel item')
            ->leftJoin('ps_order B', 'A.order_id = B.id')
            ->where(['=', 'A.pay_status', 1])
            //->andFilterWhere(['in', 'B.pay_channel', [1,2,3]])
            ->andFilterWhere(['=', 'A.community_id', $p['community_id']])
            ->andFilterWhere(['=', 'A.pay_year', $year])
            ->andFilterWhere(['=', 'A.is_del', 1])
            ->groupBy('B.pay_channel')->asArray()->all();

        if (!empty($channel)) {
            $total = array_sum(array_column($channel, 'count'));
            foreach ($channel as $k => &$v) {
                $v['percent'] = $total > 0 ? round($v['count'] / $total, 2) : 0;
                $v['item'] = PsCommon::getPayChannel($v['item']);
            }
        }

        // 未缴账单分析
        $billNo = PsBillYearly::find()->select('sum(bill_amount) count, pay_status item')
            ->andFilterWhere(['=', 'community_id', $p['community_id']])
            ->andFilterWhere(['=', 'acct_year', $year])
            ->andFilterWhere(['=', 'is_del', 1])
            ->groupBy('pay_status')->orderBy('pay_status asc')
            ->asArray()->all();

        if (!empty($billNo)) {
            $total = array_sum(array_column($billNo, 'count'));
            foreach ($billNo as $k => &$v) {
                $v['percent'] = $total > 0 ? round($v['count'] / $total, 2) : 0;
                switch ($v['item']) {
                    case '0':
                        $v['item'] = '未缴';
                        break;
                    case '1':
                        $v['item'] = '已缴';
                        break;
                }
            }
        }

        $m['cost'] = $cost;
        $m['costNo'] = $costNo;
        $m['channel'] = $channel;
        $m['billNo'] = $billNo;
        
        return $m; 
    }
}
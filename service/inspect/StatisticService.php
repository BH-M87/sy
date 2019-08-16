<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/12
 * Time: 10:42
 */
namespace service\inspect;

use app\models\PsDevice;
use app\models\PsInspectRecord;
use app\models\PsInspectRecordPoint;
use common\core\PsCommon;
use service\BaseService;

class StatisticService extends BaseService
{
    /**  物业后台接口 start */

    // 巡检数据统计 列表
    public function userList($params)
    {
        $page = PsCommon::get($params, 'page');
        $rows = PsCommon::get($params, 'rows');

        $model = self::userSearchFilter($params)
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();

        if (!empty($model)) {
            // 计算全部数据
            $modelAll = self::userSearchFilter($params)->asArray()->all();

            foreach ($modelAll as $k => $v) {
                $params['user_id'] = $v['user_id'];
                $status = self::arrResult(self::recordSearchFilter($params));

                $modelAll[$k]['task_count']     = (string)array_sum($status);
                $modelAll[$k]['finish_count']   = $status['count3'];
                $modelAll[$k]['part_count']     = $status['count2'];
                $modelAll[$k]['unfinish_count'] = $status['count1'];
                $modelAll[$k]['finish_rate']    = self::rateResult($modelAll[$k]['finish_count'], $modelAll[$k]['task_count']);
            }

            $arr['task_count']     = array_sum(array_map(function($val){return $val['task_count'];}, $modelAll));
            $arr['finish_count']   = array_sum(array_map(function($val){return $val['finish_count'];}, $modelAll));
            $arr['part_count']     = array_sum(array_map(function($val){return $val['part_count'];}, $modelAll));
            $arr['unfinish_count'] = array_sum(array_map(function($val){return $val['unfinish_count'];}, $modelAll));

            // 计算一页数据
            foreach ($model as $k => $v) {
                $params['user_id'] = $v['user_id'];
                $status = self::arrResult(self::recordSearchFilter($params));

                $model[$k]['task_count']     = (string)array_sum($status);
                $model[$k]['finish_count']   = $status['count3'];
                $model[$k]['part_count']     = $status['count2'];
                $model[$k]['unfinish_count'] = $status['count1'];
                $model[$k]['finish_rate']    = self::rateResult($model[$k]['finish_count'], $model[$k]['task_count']).'%';
            }
        } else {
            $arr['task_count']     = 0;
            $arr['finish_count']   = 0;
            $arr['part_count']     = 0;
            $arr['unfinish_count'] = 0;
        }

        $arr['fact_count']  = $arr['finish_count'] + $arr['part_count'];
        $arr['finish_rate'] = self::rateResult($arr['finish_count'], $arr['task_count']);
        $arr['list']        = $model;

        return $arr;
    }

    // 巡检记录根据状态分组查询
    private static function recordSearchFilter($params)
    {
        $start_at = !empty($params['start_at']) ? strtotime(PsCommon::get($params, 'start_at').' 0:0:0') : '';
        $end_at = !empty($params['end_at']) ? strtotime(PsCommon::get($params, 'end_at').'23:59:59') : '';

        $model = PsInspectRecord::find()
            ->select("status, count(status) as c")
            ->filterWhere(['=', 'community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'user_id', PsCommon::get($params, 'user_id')])
            ->andFilterWhere(['=', 'status', PsCommon::get($params, 'status')])
            ->andFilterWhere(['>=', 'plan_start_at', $start_at])
            ->andFilterWhere(['<=', 'plan_start_at', $end_at])
            ->andFilterWhere(['>=', 'plan_end_at', $start_at])
            ->andFilterWhere(['<=', 'plan_end_at', $end_at])
            ->andFilterWhere(['<=', 'plan_end_at', time()])
            ->groupBy("status")->asArray()->all();

        return $model;
    }

    // 巡检数据统计 搜索
    private function userSearchFilter($params)
    {
        $start_at = !empty($params['start_at']) ? strtotime(PsCommon::get($params, 'start_at').' 0:0:0') : '';
        $end_at = !empty($params['end_at']) ? strtotime(PsCommon::get($params, 'end_at').'23:59:59') : '';

        $model = PsInspectRecord::find()->alias("A")->distinct("A.user_id")
            ->select('A.user_id, B.truename as user_name, B.mobile')
            ->leftJoin("ps_user B", "A.user_id = B.id")
            ->filterWhere(['=', 'A.community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['>=', 'A.plan_start_at', $start_at])
            ->andFilterWhere(['<=', 'A.plan_start_at', $end_at])
            ->andFilterWhere(['>=', 'A.plan_end_at', $start_at])
            ->andFilterWhere(['<=', 'A.plan_end_at', $end_at])
            ->andFilterWhere(['<=', 'A.plan_end_at', time()]);

        return $model;
    }

    // 异常设备统计 列表
    public function issueList($params)
    {
        $page = PsCommon::get($params, 'page');
        $rows = PsCommon::get($params, 'rows');

        $model = $this->issueSearchFilter($params)
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();

        if (!empty($model)) {
            $modelAll = self::issueSearchFilter($params)->asArray()->all();

            foreach ($modelAll as $k => $v) {
                $params['device_id'] = $v['device_id'];
                $status = self::arrResult(self::deviceSearchFilter($params));

                $modelAll[$k]['inspect_count'] = (string)array_sum($status);
                $modelAll[$k]['normal_count']  = $status['count1'];
                $modelAll[$k]['issue_count']   = $status['count2'];
                $modelAll[$k]['issue_rate']    = self::rateResult($modelAll[$k]['issue_count'], $modelAll[$k]['inspect_count']);
            }

            $arr['inspect_count'] = array_sum(array_map(function($val){return $val['inspect_count'];}, $modelAll));
            $arr['normal_count']  = array_sum(array_map(function($val){return $val['normal_count'];}, $modelAll));
            $arr['issue_count']   = array_sum(array_map(function($val){return $val['issue_count'];}, $modelAll));

            foreach ($model as $k => $v) {
                $params['device_id'] = $v['device_id'];
                $status = self::arrResult(self::deviceSearchFilter($params));

                $model[$k]['inspect_count'] = (string)array_sum($status);
                $model[$k]['normal_count']  = $status['count1'];
                $model[$k]['issue_count']   = $status['count2'];
                $model[$k]['issue_rate']    = self::rateResult($model[$k]['issue_count'], $model[$k]['inspect_count']).'%';
            }
        } else {
            $arr['inspect_count'] = 0;
            $arr['normal_count']  = 0;
            $arr['issue_count']   = 0;
        }

        $arr['issue_rate'] = self::rateResult($arr['issue_count'], $arr['inspect_count']);
        $arr['list']       = $model;

        return $arr;
    }

    // 异常设备统计 搜索
    private function issueSearchFilter($params)
    {
        $start_at = !empty($params['start_at']) ? strtotime(PsCommon::get($params, 'start_at').' 0:0:0') : '';
        $end_at = !empty($params['end_at']) ? strtotime(PsCommon::get($params, 'end_at').'23:59:59') : '';

        $model = PsInspectRecordPoint::find()->alias("A")->distinct("B.device_id")
            ->select('B.device_id, B.device_name, B.device_no')
            ->leftJoin("ps_inspect_point B", "A.point_id = B.id")
            ->filterWhere(['=', 'A.community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'A.device_status', 2])
            ->andFilterWhere(['>=', 'A.finish_at', $start_at])
            ->andFilterWhere(['<=', 'A.finish_at', $end_at])
            ->andFilterWhere(['<=', 'A.finish_at', time()]);

        return $model;
    }

    // 设备概况
    public function deviceList($params)
    {
        $model = PsDevice::find()
            ->select("inspect_status as status, count(inspect_status) as c")
            ->filterWhere(['=', 'community_id', PsCommon::get($params, 'community_id')])
            ->groupBy("inspect_status")->asArray()->all();

        $device = PsDevice::find()
            ->select("status, count(status) as c")
            ->filterWhere(['=', 'community_id', PsCommon::get($params, 'community_id')])
            ->groupBy("status")->asArray()->all();

        $status_model  = self::arrResult($model);
        $status_device = self::arrResult($device);

        $arr['normal'] = $status_model['count1'];
        $arr['issue']  = $status_model['count2'];
        $arr['scrap']  = $status_device['count2'];
        $arr['totals'] = array_sum($status_device);
        $arr['rate']   = self::rateResult($arr['normal'], $arr['totals']);

        return $arr;
    }

    // 根据设备状态分组查询
    private static function deviceSearchFilter($params)
    {
        $start_at = !empty($params['start_at']) ? strtotime(PsCommon::get($params, 'start_at').' 0:0:0') : '';
        $end_at = !empty($params['end_at']) ? strtotime(PsCommon::get($params, 'end_at').'23:59:59') : '';

        $model = PsInspectRecordPoint::find()->alias("A")
            ->leftJoin("ps_inspect_point B", "A.point_id = B.id")
            ->select("A.device_status as status, count(A.device_status) as c")
            ->filterWhere(['=', 'A.community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'B.device_id', PsCommon::get($params, 'device_id')])
            ->andFilterWhere(['>=', 'A.finish_at', $start_at])
            ->andFilterWhere(['<=', 'A.finish_at', $end_at])
            ->andFilterWhere(['<=', 'A.finish_at', time()])
            ->groupBy("A.device_status")->asArray()->all();

        return $model;
    }

    /**  物业后台接口 end */
    /**  公共接口 start */
    // 根据status状态 取出对应status的总条数
    public static function arrResult($params)
    {
        $returnArr['count0'] = '0';
        $returnArr['count1'] = '0';
        $returnArr['count2'] = '0';
        $returnArr['count3'] = '0';

        if (!empty($params)) {
            foreach ($params as $k => $v) {
                switch ($v['status']) {
                    case '3':
                        $returnArr['count3'] = $v['c'];
                        break;
                    case '2':
                        $returnArr['count2'] = $v['c'];
                        break;
                    case '1':
                        $returnArr['count1'] = $v['c'];
                        break;
                    default:
                        $returnArr['count0'] = $v['c'];
                        break;
                }
            }
        }

        return $returnArr;
    }

    // 计算百分比
    public static function rateResult($up, $down)
    {
        if ($down != 0) {
            $rate = round($up / $down, 2) * 100;
            return (string)$rate;
        } else {
            return '0';
        }
    }
    /**  公共接口 end */
}
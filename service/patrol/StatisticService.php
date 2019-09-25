<?php
/**
 * Created by PhpStorm.
 * User: zhangqiang
 * Date: 2019-08-12
 * Time: 16:13
 */

namespace service\patrol;


use app\models\PsPatrolStatistic;
use app\models\PsUser;
use service\BaseService;

class StatisticService extends BaseService
{
    /**
     * 获取巡更记录
     * @param $type
     * @param $start_time
     * @param $end_time
     * @return mixed
     */
    public function getReport($community_id,$type,$start_time,$end_time){
        $now = time();
        if($type == '1'){
            $start_time = date('Y-m-d', strtotime('-1 week'));
            $end_time = date("Y-m-d",$now - 86400);//数据截至到昨天
        }
        if($type == '2'){
            $start_time = date('Y-m-d', strtotime('-1 month'));
            $end_time = date("Y-m-d",$now - 86400);//数据截至到昨天
        }
        if($type == '3'){
            $start_time = date('Y-m-d', strtotime('-1 year'));
            $end_time = date("Y-m-d",$now - 86400);//数据截至到昨天
        }
        $report = PsPatrolStatistic::find()
            ->alias('s')
            ->leftJoin(['u'=>PsUser::tableName()],'u.id=s.user_id')
            ->select(['s.*','u.mobile','u.truename as name'])
            ->where(['>=','day',$start_time])
            ->andFilterWhere(['community_id'=>$community_id])
            ->andFilterWhere(['<=','day',$end_time])
            ->asArray()->all();

        $totals = [];
        $new_report = [];
        if($report){
            $new_report = [];
            foreach ($report as $key =>$value){
                $user_id = $value['user_id'];
                if(array_key_exists($user_id,$new_report)){
                    $new_report[$user_id]['actual_num'] += $value['actual_num'];
                    $new_report[$user_id]['error_num'] += $value['error_num'];
                    $new_report[$user_id]['normal_num'] += $value['normal_num'];
                    $new_report[$user_id]['task_num'] += $value['task_num'];
                    $new_report[$user_id]['late_num'] += $value['late_num'];

                }else{
                    $new_report[$user_id]['user_id'] = $user_id;
                    $new_report[$user_id]['mobile'] = $value['mobile'];
                    $new_report[$user_id]['name'] = $value['name'];
                    $new_report[$user_id]['late_num'] = $value['late_num'];
                    $new_report[$user_id]['actual_num'] = $value['actual_num'];
                    $new_report[$user_id]['error_num'] = $value['error_num'];
                    $new_report[$user_id]['normal_num'] = $value['normal_num'];
                    $new_report[$user_id]['task_num'] = $value['task_num'];
                }
            }
            sort($new_report);
            $totals['actual_num'] = array_sum(array_column($new_report,'actual_num'));
            $totals['error_num'] = array_sum(array_column($new_report,'error_num'));
            $totals['normal_num'] = array_sum(array_column($new_report,'normal_num'));
            $totals['task_num'] = array_sum(array_column($new_report,'task_num'));
        }
        $result['users'] = $new_report;
        $result['totals'] = $totals;
        return $result;
    }

    /**
     * 生成统计报表
     * @param $type
     * @param $start_time
     * @param $end_time
     * @return mixed
     */
    public function getReportRank($community_id,$type,$start_time,$end_time){
        $res = self::getReport($community_id,$type,$start_time,$end_time);
        $list = [];
        $totals = $lists = [];
        if($res['users']){
            foreach ($res['users'] as $key =>$value){
                $list[$key] = $value;
                $error_num[] = $value['error_num'];//旷巡数量。排序用
                //$actual_num[] = $value['actual_num'];//实际出巡数量。排序用
                $task_num[] = $value['task_num'];//应巡数量。排序用
            }
            array_multisort($error_num, SORT_DESC, $task_num, SORT_DESC, $list);//对新生成的数组进行2个参数的排序
            $users = array_slice($list,0,15);//取整个数组的前15个人
            $totals['actual_num'] = array_sum(array_column($users,'actual_num'));
            $totals['error_num'] = array_sum(array_column($users,'error_num'));
            $totals['normal_num'] = array_sum(array_column($users,'normal_num'));
            $totals['task_num'] = array_sum(array_column($users,'task_num'));
            foreach ($users as $k =>$v){
                $lists[$k] = $v;
                $lists[$k]['rank'] = $k+1;
                //是否需要重新排百分比
                $lists[$k]['percent_actual_num'] = self::deal_percent($v['actual_num'],$totals['actual_num']);
                $lists[$k]['percent_error_num'] = self::deal_percent($v['error_num'],$totals['error_num']);
                $lists[$k]['percent_normal_num'] = self::deal_percent($v['normal_num'],$totals['normal_num']);
                $lists[$k]['percent_task_num'] = self::deal_percent($v['task_num'],$totals['task_num']);
            }

        }
        $result['list'] = $lists;
        return $result;
    }
    //计算百分比
    private function deal_percent($a,$b,$r = 2){
        return $b > 0 ? round($a/$b*100,$r)."%" : "0%";
    }
}
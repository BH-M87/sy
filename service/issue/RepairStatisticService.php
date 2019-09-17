<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/8/15
 * Time: 17:02
 */

namespace service\issue;

use app\models\PsRepair;
use app\models\PsRepairAppraise;
use app\models\PsRepairType;
use common\core\PsCommon;
use service\BaseService;
use yii\db\Query;

class RepairStatisticService extends BaseService
{
    //按照数量统计
    public function status($params)
    {
        $result = [];
        $community_id = $params['community_id'];
        $result['week'] = self::getOrderStatistic($community_id, 'week', 'order');
        $result['month'] = self::getOrderStatistic($community_id, 'month', 'order');
        $result['year'] = self::getOrderStatistic($community_id, 'year', 'order');
        return $result;
    }

    //按照渠道统计
    public function channels($params)
    {
        $result = [];
        $community_id = $params['community_id'];
        $result['week'] = self::getOrderStatistic($community_id, 'week', 'channel');
        $result['month'] = self::getOrderStatistic($community_id, 'month', 'channel');
        $result['year'] = self::getOrderStatistic($community_id, 'year', 'channel');
        return $result;
    }

    //按照类型统计
    public function types($params)
    {
        $start_time = PsCommon::get($params, 'start', '');
        $start = $start_time ? strtotime($start_time) : mktime(0, 0, 0, date('m'), 1, date('Y'));//本月第一天
        $end_time = PsCommon::get($params, 'end', '');
        $end = $end_time ? strtotime($end_time) : mktime(0, 0, 0, date('m'), date('d'), date('Y'));//今天凌晨0点
        $community_id = $params['community_id'];
        $model = PsRepair::find()->alias('t')
            ->leftJoin(['u' => PsRepairType::tableName()], 'u.id=t.repair_type_id')
            ->where(['t.community_id' => $community_id]);
        if ($start && $end) {
            $model->andFilterWhere(['between', 't.create_at', $start, $end]);
        }
        $list = $model->select("t.id,t.repair_type_id,u.name,u.id as uid")->asArray()->all();
        $return = $result = [];
        if ($list) {
            $key_list = [];
            $total = 0;
            $name_list = [];
            foreach ($list as $key => $value) {
                $repair_type = $value['repair_type_id'];
                if (!in_array($repair_type, $key_list)) {
                    $name_list[$repair_type]['name'] = $value['name'];
                    $name_list[$repair_type]['num'] = 1;
                    array_push($key_list, $repair_type);
                    $total += 1;
                } else {
                    $name_list[$repair_type]['num'] += 1;
                    $total += 1;
                }
            }
            foreach ($name_list as $key => $value) {
                $result[] = self::dealPercent($value['num'], $total, $value['name']);
            }
        }
        $return['list'] = $result;
        $return['total_num'] = $model->count();
        return $return;
    }

    //按照评分统计
    public function score($params)
    {
        $start_time = PsCommon::get($params, 'start', '');
        $start = $start_time ? strtotime($start_time) : mktime(0, 0, 0, date('m'), 1, date('Y'));//本月第一天
        $end_time = PsCommon::get($params, 'end', '');
        $end = $end_time ? strtotime($end_time) : mktime(0, 0, 0, date('m'), date('d'), date('Y'));//今天凌晨0点

        $community_id = $params['community_id'];
        $model = PsRepair::find()->alias('t')
            ->leftJoin(['u' => PsRepairAppraise::tableName()], 'u.repair_id=t.id')
            ->where(['t.community_id' => $community_id]);
        if ($start && $end) {
            $model->andFilterWhere(['between', 'create_at', $start, $end]);
        }
        $list = $model->select("t.id,u.start_num")->asArray()->all();
        $star1 = $star2 = $star3 = $star4 = $star5 = 0;
        $star_total = 0;
        $result = [];
        if ($list) {
            foreach ($list as $key => $value) {
                switch ($value['start_num']) {
                    case "1":
                        $star1 += 1;
                        $star_total += 1;
                        break;
                    case "2":
                        $star2 += 1;
                        $star_total += 1;
                        break;
                    case "3":
                        $star3 += 1;
                        $star_total += 1;
                        break;
                    case "4":
                        $star4 += 1;
                        $star_total += 1;
                        break;
                    case "5":
                        $star5 += 1;
                        $star_total += 1;
                        break;
                }
            }
        }
        $return['total_num'] = $model->count();
        $result[] = self::dealPercent($star1, $star_total, "一星");
        $result[] = self::dealPercent($star2, $star_total, "二星");
        $result[] = self::dealPercent($star3, $star_total, "三星");
        $result[] = self::dealPercent($star4, $star_total, "四星");
        $result[] = self::dealPercent($star5, $star_total, "五星");
        $return['list'] = $result;
        return $return;
    }

    private function getOrderStatistic($community_id, $day, $type)
    {
        $query = new Query();
        $query->from('ps_repair')->where(["community_id" => $community_id]);
        $start = strtotime(date('Y-m-d', strtotime('-1 ' . $day)) . " 00:00:00");
        $end = strtotime(date('Y-m-d', time()) . " 23:59:59");
        if ($start) {
            $query->andWhere(['>=', 'create_at', $start]);
        }
        if ($end) {
            $query->andWhere(['<=', 'create_at', $end]);
        }
        $command = $query->createCommand();
        $list = $command->queryAll();

        $process = 0;   //待处理
        $confirm = 0;   //待确定
        $reject = 0;    //驳回
        $pending = 0;   //待完成
        $completed = 0; //已完成
        $end = 0;       //已结束
        $review = 0;    //已复核
        $nullify = 0;   //作废
        $recheck = 0;   //复核不通过

        $life = 0;      //小区生活号报修
        $property = 0;  //物业内部报修
        $dingding = 0;  //钉钉报修
        $front = 0;     //前台报修
        $phone = 0;     //电话报修
        $reviews = 0;   //复查工单

        if ($list) {
            foreach ($list as $key => $value) {
                if ($type == 'order') {
                    switch ($value['status']) {
                        case "1":
                            $process += 1;
                            break;
                        case "2":
                            $pending += 1;
                            break;
                        case "3":
                            $completed += 1;
                            break;
                        case "4":
                            $end += 1;
                            break;
                        case "5":
                            $review += 1;
                            break;
                        case "6":
                            $nullify += 1;
                            break;
                        case "7";
                            $confirm += 1;
                            break;
                        case "8":
                            $reject += 1;
                            break;
                        case "9":
                            $recheck += 1;
                            break;
                    }
                }
                if ($type == 'channel') {
                    switch ($value['repair_from']) {
                        case "1":
                            $life += 1;
                            break;
                        case "2":
                            $property += 1;
                            break;
                        case "3":
                            $dingding += 1;
                            break;
                        case "4":
                            $front += 1;
                            break;
                        case "5":
                            $phone += 1;
                            break;
                        case "6":
                            $reviews += 1;
                            break;
                    }
                }
            }
        }
        $return = [];
        if ($type == 'order') {
            $return = compact('process', 'confirm', 'reject', 'pending', 'completed', 'end', 'review', 'nullify', 'recheck');
        }
        if ($type == 'channel') {
            $return = compact('life', 'property', 'dingding', 'front', 'phone', 'reviews');
        }
        $return['total_num'] = count($list);
        return $return;
    }

    private function dealPercent($num, $total, $name = '')
    {
        $percent = 0;
        if ($total) {
            $percent = $num / $total * 100;
        }
        if ($name) {
            $return['name'] = $name;
        }
        $return['num'] = $num;
        $return['percent'] = sprintf("%.2f", $percent);
        return $return;
    }
}
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
    // 按照数量统计
    public function status($p)
    {
        $r = self::getOrderStatistic($p, 'order');

        return $r ?? [];
    }

    // 按照渠道统计
    public function channels($p)
    {
        $r = self::getOrderStatistic($p, 'channel');

        return $r ?? [];
    }

    //按照类型统计
    public function types($params)
    {
        $start_time = PsCommon::get($params, 'start', '');
        $start = $start_time ? strtotime($start_time. " 00:00:00") : 0;//本月第一天
        $end_time = PsCommon::get($params, 'end', '');
        $end = $end_time ? strtotime($end_time." 23:59:59") : 0;//今天凌晨0点
        $community_id = $params['community_id'];
        $model = PsRepair::find()->alias('t')
            ->leftJoin(['u' => PsRepairType::tableName()], 'u.id=t.repair_type_id')
            ->where(['t.community_id' => $community_id]);
        if ($start) {
            $model->andWhere(['>=', 't.create_at',$start]);
        }
        if ($end) {
            $model->andWhere(['<=', 't.create_at',$end]);
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
        $start = $start_time ? strtotime($start_time. " 00:00:00") : 0;//本月第一天
        $end_time = PsCommon::get($params, 'end', '');
        $end = $end_time ? strtotime($end_time." 23:59:59") : 0; //今天凌晨0点

        $community_id = $params['community_id'];
        $model = PsRepair::find()->alias('t')
            ->leftJoin(['u' => PsRepairAppraise::tableName()], 'u.repair_id=t.id')
            ->where(['t.community_id' => $community_id]);
        if ($start) {
            $model->andWhere(['>=', 'create_at',$start]);
        }
        if ($end) {
            $model->andWhere(['<=', 'create_at',$end]);
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

    private function getOrderStatistic($p, $type)
    {
        $start = !empty($p['start']) ? strtotime($p['start']) : '';
        $end = !empty($p['end']) ? strtotime($p['end'] . " 23:59:59") : '';

        $query = new Query();
        $query->from('ps_repair')->where(["community_id" => $p['community_id']]);
        $hardModel = PsRepair::find()->where(['=','community_id',$p['community_id']])->andWhere(['=','hard_type',2]);

        if (!empty($start)) {
            $query->andWhere(['>=', 'create_at', $start]);
            $hardModel->andWhere(['>=', 'create_at', $start]);
        }

        if (!empty($end)) {
            $query->andWhere(['<=', 'create_at', $end]);
            $hardModel->andWhere(['<=', 'create_at', $end]);
        }

        $list = $query->createCommand()->queryAll();

        $confirm = 0;   // 待处理 = 7
        $process = 0;   // 处理中 = 1
        $completed = 0; // 已完成 = 3
        $nullify = 0;   // 已关闭 = 6

        $life = 0; // 支付宝小程序
        $dingding = 0; // 钉钉报修
        $front = 0; // 物业前台报修
        $phone = 0; // 电话报修
        $other = 0; // 其他

        $hard = $hardModel->count('id');    //疑难报事保修

        if ($list) {
            foreach ($list as $v) {
                if ($type == 'order') {
                    switch ($v['status']) {
                        case "1":
                            $process += 1;
                            break;
                        case "2":
                            $process += 1;
                            break;
                        case "3":
                            $completed += 1;
                            break;
                        case "6":
                            $nullify += 1;
                            break;
                        case "7";
                            $confirm += 1;
                            break;
                    }
                }

                if ($type == 'channel') {
                    switch ($v['repair_from']) {
                        case "1": // 支付宝小程序
                            $life += 1;
                            break;
                        case "3": // 钉钉报修
                            $dingding += 1;
                            break;
                        case "4": // 物业前台报修
                            $front += 1;
                            break;
                        case "5": // 电话报修
                            $phone += 1;
                            break;
                        default : // 其他
                            $other += 1;
                            break;
                    }
                }
            }
        }

        $r = [];
        if ($type == 'order') {
            $r = compact('process', 'confirm', 'completed', 'nullify', 'other','hard');
        }

        if ($type == 'channel') {
            $r = compact('life', 'dingding', 'front', 'phone', 'other');
        }

        $r['total_num'] = count($list);
        
        return $r;
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
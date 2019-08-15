<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/12
 * Time: 10:42
 */

namespace service\inspect;

use app\models\PsInspectPoint;
use app\models\PsInspectRecord;
use app\models\PsInspectRecordPoint;
use app\models\User;
use common\core\F;
use common\core\PsCommon;
use common\MyException;
use service\BaseService;
use service\common\CsvService;
use service\rbac\OperateService;

class TaskService extends BaseService
{
    public static $status = [
        "1" => "未完成",
        "2" => "部分完成",
        "3" => "已完成"
    ];
    public static $point_status = [
        "1" => "未巡检",
        "2" => "已巡检",
        "3" => "漏巡检"
    ];

    public static $device_status = [
        "1" => "正常",
        "2" => "异常",
        "0" => "-"
    ];

    /**  物业后台接口 start */

    //详情接口
    public function view($params)
    {
        $id = PsCommon::get($params, 'id');
        if (empty($id)) {
            throw new MyException('ID不能为空！');
        }
        $model = PsInspectRecord::find()->where(['=', 'id', $id])->asArray()->one();
        if (empty($model)) {
            throw new MyException('数据不存在');
        }
        if ($model['community_id'] != $params['community_id']) {
            throw new MyException('您没有权限');
        }
        $plan_start = !empty($model['plan_start_at']) ? date('Y-m-d H:i', $model['plan_start_at']) : '';
        $plan_end = !empty($model['plan_end_at']) ? date('Y-m-d H:i', $model['plan_end_at']) : '';
        $check_start = !empty($model['check_start_at']) ? date('Y-m-d H:i', $model['check_start_at']) : '???';
        $check_end = !empty($model['check_end_at']) ? date('Y-m-d H:i', $model['check_end_at']) : '???';

        $model['check_at'] = $check_start . '-' . $check_end; // 巡检时间
        $model['plan_at'] = $plan_start . '-' . $plan_end;   // 计划时间
        $model['status'] = !empty($model['status']) ? self::$status[$model['status']] : "未知";
        $model['plan_name'] = $model['task_name'];
        $model['user_name'] = User::find()->select('truename')->where(['id' => $model['user_id']])->scalar();;
        $model['finish_rate'] = round($model['finish_count'] / $model['point_count'], 2) * 100;

        return $model;
    }

    //详情里的列表数据
    public function showLists($params)
    {
        $page = PsCommon::get($params, 'page');
        $rows = PsCommon::get($params, 'rows');
        $query = self::showSearchFilter($params);
        $totals = $query->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => $totals];
        }
        $list = $query
            ->select('A.point_name, B.device_name, B.device_no, A.finish_at, A.status, A.location_name, A.record_note, A.imgs as image_list, A.device_status')
            ->orderBy('A.id desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['status'] = self::$device_status[$v['device_status']];
                $list[$k]['finish_at'] = !empty($v['finish_at']) ? date('Y-m-d H:i', $v['finish_at']) : '';
                $list[$k]['image_list'] = !empty($v['image_list']) ? explode(',', $v['image_list']) : '';
            }
        }
        return ['list' => $list, 'totals' => $totals];
    }

    // 巡检记录 详情里的列表 搜索
    private static function showSearchFilter($params)
    {
        $model = PsInspectRecordPoint::find()->alias("A")
            ->leftJoin("ps_inspect_point B", "A.point_id = B.id")
            ->filterWhere(['=', 'A.record_id', $params['id']]);
        return $model;
    }

    public function getQrcodeInfo($params)
    {
        if (empty($params['point_id'])) {
            throw new MyException('巡检点id不能为空');
        }
        $task_date = date('Y-m-d', time());
        $task_time = strtotime(date('Y-m-d', time()));
        //巡检点的任务
        $pointAll = PsInspectRecordPoint::find()->alias("task_point")
            ->where(['task_point.point_id' => $params['point_id'], 'task_point.status' => 1])
            ->select(['comm.name as community_name', 'record.id as record_id', 'task_point.id', 'record.plan_start_at', 'record.plan_end_at', 'record.status', 'record.plan_id', 'record.line_id', 'record.line_name', 'record.task_name'])
            ->leftJoin("ps_inspect_record record", "record.id=task_point.record_id")
            ->leftJoin("ps_community comm", "comm.id=task_point.community_id")
            ->andWhere(['record.user_id' => $params['user_id']])
            ->andWhere(['or',
                ['and', ['>=', 'record.plan_start_at', $task_time], ['=', "FROM_UNIXTIME(record.plan_start_at,'%Y-%m-%d')", $task_date]],
                ['and', ['<=', 'record.plan_start_at', $task_time], ['>=', 'record.plan_end_at', $task_time]]
            ])
            ->andWhere(['!=', 'record.status', 3])//只查没有完成的数据
            ->asArray()->all();
        if (!empty($pointAll)) {
            foreach ($pointAll as $point) {
                $arr['id'] = $point['id'];
                $arr['plan_name'] = $point['task_name'];
                $arr['community_name'] = $point['community_name'];
                $arr['status'] = $point['status'];
                $arr['status_msg'] = !empty($point['status']) ? self::$status[$point['status']] : '未知';
                $arr['line_name'] = $point['line_name'];
                $arr['plan_start_at'] = !empty($point['plan_start_at']) ? date('Y-m-d H:i', $point['plan_start_at']) : '';
                $arr['plan_end_at'] = !empty($point['plan_end_at']) ? date('Y-m-d H:i', $point['plan_end_at']) : '';
                $arr['plan_time'] = date("Y-m-d H:i", $point['plan_start_at']) . '至' . date("Y-m-d H:i", $point['plan_end_at']);
                $arrList[] = $arr;
            }
            return ['list' => $arrList ?? []];
        }
        throw new MyException('当前巡检点暂无任务！');
    }

    //列表
    public function taskList($params)
    {
        $page = PsCommon::get($params, 'page');
        $rows = PsCommon::get($params, 'rows');
        $query = self::searchFilter($params);
        $totals = $query->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }
        $list = $query
            ->select('A.*, D.truename as user_name')
            ->orderBy('A.id desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['status'] = !empty($v['status']) ? self::$status[$v['status']] : "未知";
                $list[$k]['start_at'] = !empty($v['plan_start_at']) ? date('Y-m-d H:i', $v['plan_start_at']) : '';
                $list[$k]['end_at'] = !empty($v['plan_end_at']) ? date('Y-m-d H:i', $v['plan_end_at']) : '';
                $check_time_start = !empty($v['check_start_at']) ? date('Y-m-d H:i', $v['check_start_at']) : '???';
                $check_time_end = !empty($v['check_end_at']) ? date('Y-m-d H:i', $v['check_end_at']) : '???';
                $list[$k]['finish_at'] = $check_time_start . '-' . $check_time_end; // 巡检时间
                $list[$k]['finish_rate'] = (round($v['finish_count'] / $v['point_count'], 2) * 100) . '%';
            }
        }
        return ['list' => $list, 'totals' => $totals];
    }

    // 巡检记录 搜索
    private static function searchFilter($params)
    {
        $start_at = !empty($params['start_at']) ? strtotime(PsCommon::get($params, 'start_at') . ' 0:0:0') : '';
        $end_at = !empty($params['end_at']) ? strtotime(PsCommon::get($params, 'end_at') . '23:59:59') : '';

        $model = PsInspectRecord::find()->alias("A")
            ->leftJoin("ps_user D", "A.user_id = D.id")
            ->filterWhere(['=', 'A.plan_id', PsCommon::get($params, 'plan_id')])
            ->andFilterWhere(['=', 'A.user_id', PsCommon::get($params, 'user_id')])
            ->andFilterWhere(['=', 'A.community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'A.line_id', PsCommon::get($params, 'line_id')])
            ->andFilterWhere(['like', 'A.task_name', PsCommon::get($params, 'plan_name')])
            ->andFilterWhere(['like', 'A.line_name', PsCommon::get($params, 'line_name')])
            ->andFilterWhere(['=', 'A.status', PsCommon::get($params, 'status')])
            ->andFilterWhere(['>=', 'A.plan_start_at', $start_at])
            ->andFilterWhere(['<=', 'A.plan_start_at', $end_at])
            ->andFilterWhere(['>=', 'A.plan_end_at', $start_at])
            ->andFilterWhere(['<=', 'A.plan_end_at', $end_at])
            ->andFilterWhere(['<=', 'A.plan_end_at', time()]);
        return $model;
    }

    //导出
    public function export($params, $systemType, $userInfo = [])
    {
        $config = [
            ['title' => '计划名称', 'field' => 'task_name'],
            ['title' => '对应线路', 'field' => 'line_name'],
            ['title' => '执行人员', 'field' => 'user_name'],
            ['title' => '规定开始时间', 'field' => 'start_at'],
            ['title' => '规定结束时间', 'field' => 'end_at'],
            ['title' => '完成时间', 'field' => 'finish_at'],
            ['title' => '状态', 'field' => 'status'],
            ['title' => '巡检点数量', 'field' => 'point_count'],
            ['title' => '完成数量', 'field' => 'finish_count'],
            ['title' => '漏检数量', 'field' => 'miss_count'],
            ['title' => '异常数量', 'field' => 'issue_count'],
            ['title' => '完成率', 'field' => 'finish_rate']
        ];
        $list = $this->taskList($params);
        $result = $list['list'] ?? [];
        if (empty($userInfo)) {
            $operate = [
                "community_id" => $params,
                "operate_menu" => "巡检记录",
                "operate_type" => "导出",
                "operate_content" => "",
            ];
            OperateService::addComm($userInfo, $operate);
        }
        $filename = CsvService::service()->saveTempFile(1, $config, $result, 'xunjianjilu');
        $downUrl = F::downloadUrl($systemType, $filename, 'temp', 'xunjianjilu.csv');
        return ['down_url' => $downUrl];
    }

    //异常数据汇总列表
    public function issueLists($params)
    {
        $page = PsCommon::get($params, 'page');
        $rows = PsCommon::get($params, 'rows');

        $query = self::issueSearchFilter($params);
        $totals = $query->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => $totals];
        }
        $list = $query
            ->select('A.id, E.task_name as plan_name, E.line_name, A.point_name, F.device_name, A.device_status, D.truename as user_name, A.finish_at')
            ->orderBy('A.id desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['device_status'] = "异常";
                $list[$k]['finish_at'] = !empty($v['finish_at']) ? date('Y-m-d H:i', $v['finish_at']) : '';
            }
        }
        return ['list' => $list, 'totals' => $totals];
    }

    // 异常数据汇总 搜索
    private static function issueSearchFilter($params)
    {
        $start_at = !empty($params['start_at']) ? strtotime(PsCommon::get($params, 'start_at') . ' 0:0:0') : '';
        $end_at = !empty($params['end_at']) ? strtotime(PsCommon::get($params, 'end_at') . '23:59:59') : '';
        $model = PsInspectRecordPoint::find()->alias("A")
            ->leftJoin("ps_inspect_record E", "A.record_id = E.id")
            ->leftJoin("ps_user D", "E.user_id = D.id")
            ->leftJoin("ps_inspect_point F", "A.point_id = F.id")
            ->filterWhere(['=', 'E.plan_id', PsCommon::get($params, 'plan_id')])
            ->andFilterWhere(['=', 'E.user_id', PsCommon::get($params, 'user_id')])
            ->andFilterWhere(['=', 'E.community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'E.line_id', PsCommon::get($params, 'line_id')])
            ->andFilterWhere(['like', 'E.task_name', PsCommon::get($params, 'plan_name')])
            ->andFilterWhere(['like', 'E.line_name', PsCommon::get($params, 'line_name')])
            ->andFilterWhere(['=', 'A.device_status', 2])
            ->andFilterWhere(['>=', 'E.plan_start_at', $start_at])
            ->andFilterWhere(['<=', 'E.plan_start_at', $end_at])
            ->andFilterWhere(['>=', 'E.plan_end_at', $start_at])
            ->andFilterWhere(['<=', 'E.plan_end_at', $end_at]);
        return $model;
    }

    // 异常数据汇总 详情
    public function issueShow($params)
    {
        $id = PsCommon::get($params, 'id');
        if (empty($id)) {
            throw new MyException('ID不能为空！');
        }
        $model = PsInspectRecordPoint::find()->alias("A")
            ->leftJoin("ps_inspect_record B", "A.record_id = B.id")
            ->select("A.id, A.point_name, B.task_name as plan_name, B.line_name, B.user_id, A.point_id, A.record_note, A.location_name, A.imgs as image_list, A.community_id, A.device_status, A.finish_at")
            ->where(['=', 'A.id', $id])->asArray()->one();

        if (empty($model)) {
            throw new MyException('数据不存在');
        }

        if ($model['community_id'] != $params['community_id']) {
            throw new MyException('您没有权限');
        }

        $point = PsInspectPoint::find()->select('name, device_name')->where(['id' => $model['point_id']])->one();
        $user_name = User::find()->select('truename')->where(['id' => $model['user_id']])->scalar();

        $model['device_name'] = $point['device_name'];
        $model['device_status'] = $model['device_status'] == 1 ? '正常' : '异常';
        $model['user_name'] = !empty($user_name) ? $user_name : '';
        $model['image_list'] = !empty($model['image_list']) ? explode(',', $model['image_list']) : '';
        $model['finish_at'] = !empty($model['finish_at']) ? date('Y-m-d H:i', $model['finish_at']) : '';

        return $model;
    }

    public function issueExport($params, $systemType, $userInfo = [])
    {
        $config = [
            ['title' => '计划名称', 'field' => 'plan_name'],
            ['title' => '对应线路', 'field' => 'line_name'],
            ['title' => '对应巡检点', 'field' => 'point_name'],
            ['title' => '对应设备', 'field' => 'device_name'],
            ['title' => '设备状态', 'field' => 'device_status'],
            ['title' => '巡检人员', 'field' => 'user_name'],
            ['title' => '完成时间', 'field' => 'finish_at']
        ];
        $list = $this->issueLists($params);
        $result = $list['list'] ?? [];
        if (!empty($userInfo)) {
            $operate = [
                "community_id" => $params["community_id"],
                "operate_menu" => "异常数据汇总",
                "operate_type" => "导出",
                "operate_content" => "",
            ];
            OperateService::addComm($userInfo, $operate);
        }

        $filename = CsvService::service()->saveTempFile(1, $config, $result, 'yichangshuju');
        $downUrl = F::downloadUrl($systemType, $filename, 'temp', 'yichangshuju.csv');

        return ['down_url' => $downUrl];
    }
    /**  物业后台接口 end */

    /**  钉钉接口 start */

    /**  钉钉接口 end */

    /**  公共接口 start */

    /**  公共接口 end */
}
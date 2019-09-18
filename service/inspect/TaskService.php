<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/12
 * Time: 10:42
 */

namespace service\inspect;

use app\models\PsDevice;
use app\models\PsInspectPoint;
use app\models\PsInspectRecord;
use app\models\PsInspectRecordPoint;
use app\models\User;
use common\core\F;
use common\core\PsCommon;
use common\MyException;
use service\BaseService;
use service\common\CsvService;
use service\qiniu\UploadService;
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

    public function issueExport($params, $userInfo = [])
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
        $downUrl = F::downloadUrl($filename, 'temp', 'yichangshuju.csv');

        return ['down_url' => $downUrl];
    }
    /**  物业后台接口 end */

    /**  钉钉接口 start */

    //列表
    public function getList($params)
    {
        $task_time = !empty($params['task_time']) ? strtotime($params['task_time']) : strtotime(date('Y-m-d', time()));
        $task_date = !empty($params['task_time']) ? $params['task_time'] : date('Y-m-d', time());
        $page = !empty($params['page']) ? $params['page'] : 1;
        $rows = !empty($params['rows']) ? $params['rows'] : 5;
        $resultAll = PsInspectRecord::find()->alias("record")
            ->where(['record.user_id' => $params['user_id']])
            ->select(['record.id', 'comm.name as community_name', 'record.task_name', 'record.status', 'record.line_name', 'record.head_name', 'record.head_mobile',
                'record.plan_start_at', 'record.plan_end_at', 'record.check_start_at', 'record.check_end_at', 'record.point_count', 'record.finish_count'
            ])
            ->leftJoin("ps_community comm", "comm.id=record.community_id")
            ->andWhere(['or',
                ['and', ['>=', 'record.plan_start_at', $task_time], ['=', "FROM_UNIXTIME(record.plan_start_at,'%Y-%m-%d')", $task_date]],
                ['and', ['<=', 'record.plan_start_at', $task_time], ['>=', 'record.plan_end_at', $task_time]],
            ])
            ->orderBy('record.id desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();
        $dataList = [];
        if (!empty($resultAll)) {
            foreach ($resultAll as $result) {
                $arr = $result;
                $plan_time_start = !empty($result['plan_start_at']) ? date('Y-m-d H:i', $result['plan_start_at']) : '';
                $plan_time_end = !empty($result['plan_end_at']) ? date('Y-m-d H:i', $result['plan_end_at']) : '';
                $check_time_start = !empty($result['check_start_at']) ? date('Y-m-d H:i', $result['check_start_at']) : '???';
                $check_time_end = !empty($result['check_end_at']) ? date('Y-m-d H:i', $result['check_end_at']) : '???';
                $arr['check_time'] = $check_time_start . '至' . $check_time_end;  //巡检时间
                $arr['check_start_at'] = !empty($result['check_start_at']) ? date('Y-m-d H:i', $result['check_start_at']) : '???';
                $arr['check_end_at'] = !empty($result['check_end_at']) ? date('Y-m-d H:i', $result['check_end_at']) : '???';
                $arr['plan_time'] = $plan_time_start . '至' . $plan_time_end;     //计划时间
                $arr['plan_start_at'] = !empty($result['plan_start_at']) ? date('Y-m-d H:i', $result['plan_start_at']) : '';
                $arr['plan_end_at'] = !empty($result['plan_end_at']) ? date('Y-m-d H:i', $result['plan_end_at']) : '';
                $arr['status'] = !empty($result['status']) ? self::$status[$result['status']] : "未知";
                $arr['unfinish_count'] = $result['point_count'] - $result['finish_count'];
                $dataList[] = $arr;
            }
        }
        return ['list' => $dataList];
    }

    //详情
    public function getInfo($params)
    {
        if (empty($params['id'])) {
            throw new MyException('id不能为空');
        }
        $result = PsInspectRecord::find()->alias("record")
            ->where(['record.id' => $params['id'], 'record.user_id' => $params['user_id']])
            ->select(['record.id', 'comm.name as community_name', 'record.task_name', 'record.status', 'record.line_name', 'record.head_name', 'record.head_mobile',
                'record.plan_start_at', 'record.plan_end_at', 'record.check_start_at', 'record.check_end_at', 'record.point_count', 'record.finish_count'
            ])
            ->leftJoin("ps_community comm", "comm.id=record.community_id")
            ->asArray()->one();
        if (!empty($result)) {
            $plan_time_start = !empty($result['plan_start_at']) ? date('Y-m-d H:i', $result['plan_start_at']) : '';
            $plan_time_end = !empty($result['plan_end_at']) ? date('Y-m-d H:i', $result['plan_end_at']) : '';
            $check_time_start = !empty($result['check_start_at']) ? date('Y-m-d H:i', $result['check_start_at']) : '???';
            $check_time_end = !empty($result['check_end_at']) ? date('Y-m-d H:i', $result['check_end_at']) : '???';
            $result['check_time'] = $check_time_start . '至' . $check_time_end;  //巡检时间
            $result['check_start_at'] = !empty($result['check_start_at']) ? date('Y-m-d H:i', $result['check_start_at']) : '???';
            $result['check_end_at'] = !empty($result['check_end_at']) ? date('Y-m-d H:i', $result['check_end_at']) : '???';
            $result['plan_time'] = $plan_time_start . '至' . $plan_time_end;     //计划时间
            $result['plan_start_at'] = !empty($result['plan_start_at']) ? date('Y-m-d H:i', $result['plan_start_at']) : '';
            $result['plan_end_at'] = !empty($result['plan_end_at']) ? date('Y-m-d H:i', $result['plan_end_at']) : '';
            $result['status'] = !empty($result['status']) ? self::$status[$result['status']] : "未知";
            $result['unfinish_count'] = $result['finish_count'] > 0 ? ($result['point_count'] - $result['finish_count']) : $result['point_count'];
            //获取任务下的巡检点
            $pointList = PsInspectRecordPoint::find()
                ->where(['record_id' => $params['id']])
                ->select(['id', 'finish_at', 'device_status', 'status', 'point_name', 'point_location_name', 'need_location', 'point_id'])
                ->asArray()->all();
            $pointData = [];
            if (!empty($pointList)) {
                foreach ($pointList as $point) {
                    $pointInfo = PsInspectPoint::findOne($point['point_id']);
                    $point['device_name'] = $pointInfo->device_name;
                    $point['finish_at'] = !empty($point['finish_at']) ? date("Y-m-d H:i", $point['finish_at']) : '';
                    $point['status_lable'] = self::$point_status[$point['status']];
                    $pointData[] = $point;
                }
            }
            $result['point_list'] = $pointData;
            return $result;
        }
        throw new MyException('任务不存在');
    }

    //巡检点-详情
    public function getPointInfo($params)
    {
        if (empty($params['id'])) {
            throw new MyException('id不能为空');
        }
        //巡检点详情
        $pointInfo = PsInspectRecordPoint::find()->alias("task_point")
            ->where(['task_point.id' => $params['id']])
            ->select(['task_point.id', 'task_point.device_status', 'task_point.point_name', 'task_point.need_location', 'point.device_name', 'task_point.need_photo', 'task_point.status', 'task_point.imgs', 'task_point.record_note', 'task_point.finish_at', 'task_point.location_name', 'task_point.location_name', 'task_point.point_lat', 'task_point.point_lon', 'point.device_name'])
            ->leftJoin("ps_inspect_point point", "point.id=task_point.point_id")
            ->asArray()->one();
        $pointInfo['error_msg'] = '';
        if (!empty($pointInfo)) {
            $pointInfo['finish_at'] = !empty($pointInfo['finish_at']) ? date("Y-m-d H:i", $pointInfo['finish_at']) : '';
            $pointInfo['imgs'] = !empty($pointInfo['imgs']) ? explode(",", $pointInfo['imgs']) : '';
            $pointInfo['error_msg'] = '';
            if ($pointInfo['status'] == 1) {
                $info = PsInspectRecordPoint::find()->alias("task_point")
                    ->where(['task_point.id' => $params['id']])
                    ->select(['task_point.id', 'task_point.device_status', 'task_point.point_name', 'task_point.need_location', 'task_point.need_photo', 'task_point.status'])
                    ->andWhere(['<=', 'record.plan_start_at', time()])
                    ->andWhere(['>=', 'record.plan_end_at', time()])
                    ->leftJoin("ps_inspect_record record", "record.id=task_point.record_id")
                    ->asArray()->one();
                if (empty($info)) {
                    $pointInfo['error_msg'] = "当前时间不可执行任务！";
                }
                //如果需要定位的话判断距离误差
                if ($pointInfo['need_location'] == 1) {
                    $distance = F::getDistance($params['lat'], $params['lon'], $pointInfo['point_lat'], $pointInfo['point_lon']);
                    if ($distance > \Yii::$app->getModule('property')->params['distance']) {
                        $pointInfo['error_msg'] = "当前位置不可巡检！";
                    }
                }
            }
            return ['point_info' => $pointInfo];
        }
        throw new MyException('巡检点不存在');
    }

    //提交巡检点
    public function add($reqArr)
    {
        if (empty($reqArr['id'])) {
            throw new MyException('id不能为空');
        }
        $trans = \Yii::$app->getDb()->beginTransaction();
        try {
            $model = PsInspectRecordPoint::findOne($reqArr['id']);
            if (empty($model)) {
                throw new MyException('任务不存在!');
            }
            if ($model['status'] != 1) {
                throw new MyException('任务已巡检!');
            }
            //得到对应的巡检点信息
            $point = PsInspectPoint::findOne($model['point_id']);
            if ($point->need_location == 1 && (empty($reqArr['lat']) || empty($reqArr['lon']) || empty($reqArr['location_name']))) {
                throw new MyException('该任务需定位,经纬度不能为空!');
            }
            if ($point->need_photo == 1 && empty($reqArr['img'])) {
                throw new MyException('该任务需拍照,图片不能为空!');
            }
            $reqArr['imgs'] = $reqArr['img'];
            $reqArr['status'] = 2;
            $reqArr['finish_at'] = time();
            $info = PsInspectRecordPoint::find()->alias("task_point")
                ->where(['task_point.id' => $reqArr['id']])
                ->select(['task_point.id', 'task_point.device_status', 'task_point.point_name', 'task_point.need_location', 'task_point.need_photo', 'task_point.status', 'task_point.point_lat', 'task_point.point_lon'])
                ->andWhere(['<=', 'record.plan_start_at', time()])
                ->andWhere(['>=', 'record.plan_end_at', time()])
                ->leftJoin("ps_inspect_record record", "record.id=task_point.record_id")
                ->asArray()->one();
            if (empty($info)) {
                throw new MyException('当前时间不可执行任务!');
            }
            //如果需要定位的话判断距离误差
            if ($info['need_location'] == 1) {
                $distance = F::getDistance($reqArr['lat'], $reqArr['lon'], $info['point_lat'], $info['point_lon']);
                if ($distance > \Yii::$app->getModule('property')->params['distance']) {
                    throw new MyException('当前位置不可巡检！');
                }
            }
            $model->scenario = 'edit';  # 设置数据验证场景为 新增
            $model->load($reqArr, '');   # 加载数据
            if ($model->validate()) {  # 验证数据
                if ($model->save()) {  # 保存新增数据
                    //更新任务完成数,完成率
                    $record = PsInspectRecord::findOne($model->record_id);              //任务详情
                    $pointInfo = PsInspectPoint::findOne(['id' => $model->point_id]);    //巡检点详情
                    $flag = PsDevice::updateAll(['inspect_status' => $reqArr['device_status']], ['id' => $pointInfo->device_id]);   //修改设备状态为正常
                    if ($record->check_start_at == 0) {
                        PsInspectRecord::updateAll(['status' => 2, 'check_start_at' => time()], ['id' => $model->record_id]);
                    }
                    if ($reqArr['device_status'] == 2) {//设备异常
                        PsInspectRecord::updateAll(['issue_count' => $record->issue_count + 1], ['id' => $model->record_id]);
                    }
                    $finish_count = $record->finish_count + 1;
                    $finish_rate = ($record->finish_count / $record->point_count) * 100;
                    PsInspectRecord::updateAll(['finish_count' => $finish_count, 'finish_rate' => $finish_rate], ['id' => $model->record_id]);
                    //查询是否还有未完成的巡检点,没有则任务是完成状态
                    $modelInfo = PsInspectRecordPoint::find()
                        ->where(['record_id' => $model->record_id, 'status' => 1])
                        ->andWhere(['!=', 'id', $model->id])->one();
                    if (empty($modelInfo)) {
                        PsInspectRecord::updateAll(['status' => 3, 'check_end_at' => time()], ['id' => $model->record_id]);
                    }
                    //提交事务
                    $trans->commit();

                    //TODO 是否需要数据监控
                    /*if ($flag) {//(事务提交后数据库才能查到)设备状态更新后，推送到监控页面 @shenyang v4.4数据监控版本
                        WebSocketClient::getInstance()->send(MonitorService::MONITOR_DEVICE, $model->community_id);
                    }*/
                    ////将钉钉图片转化为七牛图片
                    UploadService::service()->pushDing($model->id, 'inspect', $model['imgs']);
                    return $this->success([]);
                }
                throw new MyException($model->getErrors());
            } else {
                throw new MyException($model->getErrors());
            }
        } catch (\Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
    }
    /**  钉钉接口 end */
}

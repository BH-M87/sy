<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/12
 * Time: 10:42
 */

namespace service\inspect;

use app\models\PsInspectLine;
use app\models\PsInspectPlanContab;
use app\models\PsUser;
use app\models\PsInspectPlan;
use common\core\PsCommon;
use common\MyException;
use service\BaseService;
use service\rbac\OperateService;
use service\rbac\UserService;
use Yii;

class PlanService extends BaseService
{
    public static $exec_type = [
        '1' => '按天',
        '2' => '按周',
        '3' => '按月',
        '4' => '按年'
    ];
    public static $week_type = [
        '1' => '星期一',
        '2' => '星期二',
        '3' => '星期三',
        '4' => '星期四',
        '5' => '星期五',
        '6' => '星期六',
        '7' => '星期日'
    ];

    /**  物业后台接口 start */
    public function add($params, $userInfo = [])
    {
        self::checkCommon($params, $userInfo, 'add');
    }

    public function edit($params, $userInfo = [])
    {
        self::checkCommon($params, $userInfo, 'update');
    }

    protected static function checkCommon($params, $userInfo = [], $scenario)
    {
        $model = new PsInspectPlan();
        $params = $model->validParamArr($params, $scenario);
        if ($scenario == 'update') {
            $model = self::planOne($params['id'], '', '', 'id');
            if (empty($model)) {
                throw new MyException('巡检计划不存在!');
            }
        } else {
            unset($params['id']);
        }
        $user_list = !empty($params['user_list']) ? json_decode($params['user_list'], true) : '';
        if (!empty($user_list)) {
            foreach ($user_list as $user_id) {
                $checkUserId = PsUser::findOne($user_id);
                if (empty($checkUserId)) {
                    throw new MyException('选择的人员不存在!!');
                }
            }
        }
        //查看巡检计划名称是否重复
        $Plan = self::planOne('', $params['name'], $params['community_id'], 'id');
        if (!empty($Plan)) {
            throw new MyException('巡检计划已存在!');
        }
        //查看巡检线路是否存在
        $line = LineService::lineOne($params['line_id'], 'line.id');
        if (empty($line)) {
            throw new MyException('巡检线路不存在!');
        }
        if (count($params['time_list']) == 0 || count($params['time_list']) > 12) {
            throw new MyException('执行时间为1-12!');
        }
        foreach ($params['time_list'] as $timeData) {
            switch ($params['exec_type']) {
                case 1://按天
                    if (empty($timeData['hours_start']) || empty($timeData['hours_end'])) {
                        throw new MyException('执行时间参数错误!');
                    }
                    break;
                case 2://按周
                    if (empty($timeData['week_start']) || empty($timeData['week_end']) || empty($timeData['hours_start']) || empty($timeData['hours_end'])) {
                        throw new MyException('执行时间参数错误!');
                    }
                    break;
                case 3://按月
                    if (empty($timeData['day_start']) || empty($timeData['day_end']) || empty($timeData['hours_start']) || empty($timeData['hours_end'])) {
                        throw new MyException('执行时间参数错误!');
                    }
                    break;
                case 4://按年
                    if (empty($timeData['month_start']) || empty($timeData['month_end']) || empty($timeData['day_start']) || empty($timeData['day_end'])) {
                        throw new MyException('执行时间参数错误!');
                    }
                    break;
            }
        }
        $params['status'] = 1;
        $model->setAttributes($params);
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            if ($model->save()) {  # 保存新增数据
                //先清空老数据
                if ($scenario == 'update') {
                    PsInspectPlanContab::deleteAll(['plan_id' => $params['id']]);
                }
                foreach ($params['time_list'] as $timeData) {
                    $pointArr['month_start'] = $timeData['month_start'] ?? "";
                    $pointArr['week_start'] = $timeData['week_start'] ?? "";
                    $pointArr['day_start'] = $timeData['day_start'] ?? "";
                    $pointArr['hours_start'] = $timeData['hours_start'] ?? "";
                    $pointArr['month_end'] = $timeData['month_end'] ?? "";
                    $pointArr['week_end'] = $timeData['week_end'] ?? "";
                    $pointArr['day_end'] = $timeData['day_end'] ?? "";
                    $pointArr['hours_end'] = $timeData['hours_end'] ?? "";
                    $pointArr['plan_id'] = $model->id;
                    $pointArr['create_at'] = time();
                    $pointArr['update_at'] = time();
                    Yii::$app->db->createCommand()->insert('ps_inspect_plan_contab', $pointArr)->execute();
                }
            } else {
                throw new MyException('操作失败');
            }
            //提交事务
            $trans->commit();
            if (!empty($userInfo)) {
                //self::addLog($userInfo, $params['name'], $params['community_id'], $scenario);
            }
        } catch (\Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
        return true;
    }

    //统一日志新增
    private static function addLog($userInfo, $name, $community_id, $operate_type = "")
    {
        switch ($operate_type) {
            case 'add':
                $operate_name = '新增';
                break;
            case 'update':
                $operate_name = '编辑';
                break;
            case 'del':
                $operate_name = '删除';
                break;
            default:
                return;
        }
        $content = "计划名称:" . $name;
        $operate = [
            "community_id" => $community_id,
            "operate_menu" => "设备巡检",
            "operate_type" => "巡检计划" . $operate_name,
            "operate_content" => $content,
        ];
        OperateService::addComm($userInfo, $operate);
    }

    public function view($params)
    {
        if (empty($params['id'])) {
            throw new MyException('ID不能为空！');
        }

        $model = self::planOne($params['id'], '', $params['community_id'], '*');
        $model = $model->toArray();
        if (empty($model)) {
            throw new MyException('数据不存在');
        }
        $user_list = json_decode($model['user_list'], true);
        if (!empty($user_list)) {
            $username = '';
            $arr = [];
            foreach ($user_list as $key => $user_id) {
                $userInfo = PsUser::findOne($user_id);

                $arr[$key]['user_id'] = $user_id;
                $arr[$key]['user_name'] = $userInfo->truename;

                $username .= $userInfo->truename . ' ';
            }

            $model['user_list'] = $arr;
            $model['user_lists'] = $username;
        }

        $time = self::getTimeData($model['exec_type'], $model['id']);

        if (!empty($time)) {
            foreach ($time as $k => $v) {
                if (!empty($v['month_start']) && $v['month_start'] < 10) {
                    $time[$k]['month_start'] = '0' . $v['month_start'];
                }

                if (!empty($v['month_end']) && $v['month_end'] < 10) {
                    $time[$k]['month_end'] = '0' . $v['month_end'];
                }

                if (!empty($v['day_start']) && $v['day_start'] < 10) {
                    $time[$k]['day_start'] = '0' . $v['day_start'];
                }
                if (!empty($v['day_end']) && $v['day_end'] < 10) {
                    $time[$k]['day_end'] = '0' . $v['day_end'];
                }

                if ($v['hours_start'] < 10) {
                    $time[$k]['hours_start'] = '0' . $v['hours_start'];
                }

                if ($v['hours_end'] < 10) {
                    $time[$k]['hours_end'] = '0' . $v['hours_end'];
                }
            }
        }

        $model['time_list'] = $time;
        $model['line_name'] = PsInspectLine::find()->select('name')->where(['id' => $model['line_id']])->scalar();
        $model['exec_name'] = self::$exec_type[$model['exec_type']];
        $model['time_lists'] = self::getDDTimeData($model['exec_type'], $model['id']); // 获取执行时间

        return $model;
    }

    public function del($params, $userInfo = [])
    {
        if (empty($params['id'])) {
            throw new MyException('巡检计划id不能为空');
        }
        $model = self::planOne($params['id'], '', '', 'name,status');
        if (empty($model)) {
            throw new MyException('巡检计划不存在!');
        }
        if ($model->status == 1) {
            throw new MyException('巡检计划已启用不可删除!');
        }
        $result = PsInspectPlan::deleteAll(['id' => $params['id']]);
        if (!empty($result)) {
            if (!empty($userInfo)) {
                //self::addLog($userInfo,$model->name,$params['community_id'],'del');
            }
            return true;
        }
        throw new MyException('删除失败，巡检计划不存在');
    }

    public function planList($params)
    {
        $page = PsCommon::get($params, 'page');
        $rows = PsCommon::get($params, 'rows');
        $query = self::searchList($params);
        $totals = $query->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }
        $list = $query
            ->select('A.id, A.community_id, A.name, B.name as line_name, A.exec_type, A.status, A.user_list')
            ->orderBy('A.id desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['status'] = $v['status'] == 1 ? '已启用' : '已停用';
                $list[$k]['exec_type'] = self::$exec_type[$v['exec_type']];
                $user_list = json_decode($v['user_list'], true);
                if (!empty($user_list)) {
                    $arr = [];
                    foreach ($user_list as $key => $user_id) {
                        $userInfo = PsUser::findOne($user_id);
                        $arr[$key]['user_id'] = $user_id;
                        $arr[$key]['user_name'] = $userInfo->truename;
                    }

                    $list[$k]['user_list'] = $arr;
                }
            }
        }
        return ['list' => $list, 'totals' => $totals];
    }

    // 巡检计划 搜索
    private static function searchList($params)
    {
        $model = PsInspectPlan::find()->alias("A")
            ->leftJoin("ps_inspect_line B", "A.line_id = B.id")
            ->filterWhere(['like', 'A.user_list', PsCommon::get($params, 'user_id')])
            ->andFilterWhere(['=', 'A.exec_type', PsCommon::get($params, 'exec_type')])
            ->andFilterWhere(['=', 'A.community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'A.line_id', PsCommon::get($params, 'line_id')])
            ->andFilterWhere(['=', 'A.status', PsCommon::get($params, 'status')])
            ->andFilterWhere(['=', 'A.id', PsCommon::get($params, 'plan_id')]);

        return $model;
    }

    public function getPlanList($params)
    {
        $arr = PsInspectPlan::find()
            ->filterWhere(['=', 'community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'status', PsCommon::get($params, 'status')])
            ->select(['id', 'name'])->asArray()->all();
        return $this->success(['list' => $arr]);
    }

    public function editStatus($params)
    {
        if (empty($params['id'])) {
            throw new MyException('巡检计划id不能为空');
        }
        if (empty($params['status'])) {
            throw new MyException('巡检计划状态不能为空');
        }
        if (!in_array($params['status'], [1, 2])) {
            throw new MyException('巡检计划状态取值范围错误');
        }
        $model = self::planOne($params['id'],'','','id');
        if (empty($model)) {
           throw new MyException('巡检计划不存在!');
        }
        PsInspectPlan::updateAll(['status' => $params['status']], ['id' => $params['id']]);
        return $this->success();
    }

    public function getPlanUserList($params)
    {
        $data = UserService::service()->getUserByCommunityId($params['community_id']);
        return PsCommon::responseSuccess($data);
    }

    public static function planOne($id = '', $name = '', $community_id = '', $select = "*", $line_id = '')
    {
        $select = $select ?? "*";
        return PsInspectPlan::find()
            ->select($select)
            ->andFilterWhere(['name' => $name, 'community_id' => $community_id, 'id' => $id, 'line_id' => $line_id])
            ->one();
    }

    //获取执行时间-编辑页面专用
    public function getTimeData($exec_type, $plan_id)
    {
        switch ($exec_type) {
            case 1://按天
                $timeData = PsInspectPlancontab::find()->where(['plan_id' => $plan_id])->select(['hours_start', 'hours_end'])->asArray()->all();
                break;
            case 2://按周
                $timeData = PsInspectPlancontab::find()->where(['plan_id' => $plan_id])->select(['week_start', 'hours_start', 'week_end', 'hours_end'])->asArray()->all();
                break;
            case 3://按月
                $timeData = PsInspectPlancontab::find()->where(['plan_id' => $plan_id])->select(['day_start', 'hours_start', 'day_end', 'hours_end'])->asArray()->all();
                break;
            case 4://按年
                $timeData = PsInspectPlancontab::find()->where(['plan_id' => $plan_id])->select(['month_start', 'day_start', 'hours_start', 'month_end', 'day_end', 'hours_end'])->asArray()->all();
                break;
        }
        return $timeData;
    }

    //获取执行时间-钉钉详情页面专用
    public function getDDTimeData($exec_type, $plan_id)
    {
        $timeData = [];
        $time_all = PsInspectPlancontab::find()->where(['plan_id' => $plan_id])->asArray()->all();
        foreach ($time_all as $times) {
            switch ($exec_type) {
                case 1://按天
                    $timeData[] = $times['hours_start'] . ':00' . '~' . $times['hours_end'] . ':00';
                    break;
                case 2://按周
                    $timeData[] = self::$week_type[$times['week_start']] . $times['hours_start'] . ':00' . '~' . self::$week_type[$times['week_end']] . $times['hours_end'] . ':00';
                    break;
                case 3://按月
                    $timeData[] = $times['day_start'] . '号' . $times['hours_start'] . ':00' . '~' . $times['day_end'] . '号' . $times['hours_end'] . ':00';
                    break;
                case 4://按年
                    $timeData[] = $times['month_start'] . '月' . $times['day_start'] . '号' . $times['hours_start'] . ':00' . '~' . $times['month_end'] . '月' . $times['day_end'] . '号' . $times['hours_end'] . ':00';
                    break;
            }
        }
        return $timeData;
    }
    /**  物业后台接口 end */

    /**  钉钉接口 start */

    /**  钉钉接口 end */

    /**  公共接口 start */

    /**  公共接口 end */
}
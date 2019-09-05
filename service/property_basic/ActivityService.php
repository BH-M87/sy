<?php
namespace service\property_basic;

use common\core\PsCommon;
use common\MyException;

use service\BaseService;
use service\rbac\OperateService;

use app\models\PsActivity;
use app\models\PsActivityEnroll;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsAppUser;
use app\models\PsAppMember;
use app\models\PsRoomUser;

class ActivityService extends BaseService
{
    // 活动 新增
    public function add($p, $scenario = 'add')
    {
        return $this->_saveActivity($p, $scenario);
    }

    // 活动 编辑
    public function edit($p, $scenario = 'edit')
    {
        return $this->_saveActivity($p, $scenario);
    }

    // 新增编辑 活动
    public function _saveActivity($p, $scenario)
    {
        $m = new PsActivity();

        if (!empty($p['id'])) {
            $m = PsActivity::getOne($p);
        }

        $p['activity_number'] = $p['activity_number'] == '不限' ? 0 : $p['activity_number'];
        $p['start_time'] = !empty($p['start_time']) ? strtotime($p['start_time']) : '';
        $p['end_time'] = !empty($p['end_time']) ? strtotime($p['end_time']) : '';
        $p['join_end'] = !empty($p['join_end']) ? strtotime($p['join_end']) : '';
        
        $data = PsCommon::validParamArr($m, $p, $scenario);

        if (empty($data['status'])) {
            return $this->failed($data['errorMsg']);
        }

        if ($p['is_top'] == 2) {
            $m->top_time = time();
        }

        if ($m->save()) {
            return $this->success();
        }
    }

    // 获取活动列表
    public function list($p)
    {
        $data = PsActivity::getList($p);

        return $this->success($data);
    }

    // 活动删除
    public function delete($p)
    {
        $m = PsActivity::getOne($p);

        $m->is_del = 2;
        $m->operator_id = $p['operator_id'];

        if (!$m->save()) {
            throw new MyException($m->getErrors());
        }
    }

    // 获取活动详情
    public function detail($p)
    {
        $m = PsActivity::getOne($p)->toArray();

        $m['join_end'] = date('Y-m-d H:i', $m['join_end']);
        $m['start_time'] = date('Y-m-d H:i', $m['start_time']);
        $m['end_time'] = date('Y-m-d H:i', $m['end_time']);
        $m['status_desc'] = PsActivity::$status[$m['status']];
        $m['type_desc'] = PsActivity::$type[$m['type']];
        $m['activity_type_desc'] = PsActivity::$activity_type[$m['activity_type']];

        return $m;
    }

    // 获取报名列表
    public function joinList($p)
    {
        $page = $p['page'] ?? 1;
        $rows = $p['rows'] ?? 10;

        $m = PsActivity::getOne($p);

        $enroll = PsActivityEnroll::find()->where(['a_id' => $m->id]);
        $totals = $enroll->count();
        if ($totals > 0) {
            $list = $enroll->orderBy('id desc')->offset(($page - 1) * $rows)->limit($rows)->asArray()->all();
            foreach ($list as &$v) {
                $v['created_at'] = $v['created_at'] ? date('Y-m-d H:i', $v['created_at']) : '';
                $v['address'] = PsCommunityRoominfo::findOne($v['room_id'])->address;
                $v['community_name'] = PsCommunityModel::findOne($v['community_id'])->name;
            }
        }

        return ['totals' => $totals, 'list' => $list ?? []];
    }

    // 置顶 活动
    public function top($p)
    {
        $m = PsActivity::getOne($p);

        if ($m->is_top == 1) {
            $m->is_top = 2; // 置顶
            $m->top_time = time();
        } else {
            $m->is_top = 1; // 不置顶
            $m->top_time = 0;
        }

        $m->operator_id = $p['operator_id'];

        $m->save();
    }

    // 活动 取消
    public function activityCancel($param)
    {
        $model = PsActivity::find()->where(['id' => $param['id'], 'is_del' => 1])->asArray()->one();

        if (empty($model)) {
            return $this->failed('活动不存在！');
        }

        if (empty($param['user_id'])) {
            return $this->failed('住户ID必填！');
        }

        if ($model['operator_id'] != $param['user_id']) {
            return $this->failed('只能取消自己发布的活动！');
        }

        if ($model['status'] == 2 || $model['end_time'] < time()) {
            return $this->failed('活动已结束不能取消！');
        }

        PsActivity::updateAll(['status' => 3], ['id' => $param['id']]);

        return $this->success();
    }

    // 报名 取消
    public function activityJoinCancel($param)
    {
        $modelInfo = PsActivity::find()->where(['id' => $param['id'], 'is_del' => 1])->asArray()->one();

        if (empty($modelInfo)) {
            return $this->failed('活动不存在！');
        }

        if ($modelInfo['status'] == 2 || $modelInfo['end_time'] < time()) {
            return $this->failed('活动已结束不能取消！');
        }

        $trans = Yii::$app->getDb()->beginTransaction();

        try {
            $model = PsActivityEnroll::deleteAll(['a_id' => $param['id'], 'room_id' => $param['room_id'], 'user_id' => $param['user_id']]);

            if (!empty($model)) {
                PsActivity::updateAllCounters(['join_number' => -1], ['id' => $param['id']]);
            }

            $trans->commit();
            return $this->success();
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    // 活动 报名
    public function join($p)
    {
        $m = PsActivity::find()->where(['id' => $p['id'], 'is_del' => 1])->asArray()->one();

        if (empty($m)) {
            return $this->failed('活动不存在！');
        }

        if ($m['status'] == 2 || $m['end_time'] < time()) {
            return $this->failed('活动已结束！');
        }

        if ($m['join_end'] < time()) {
            return $this->failed('报名已截止！');
        }

        if ($m['status'] == 3) {
            return $this->failed('活动已取消！');
        }

        if ($m['operator_id'] == $p['user_id']) {
            return $this->failed('不能报名自己发布的活动哦！');
        }

        if ($m['activity_number'] != 0 && $m['join_number'] >= $m['activity_number']) {
            return $this->failed('活动报名人数已满！');
        }

        // 查询业主
        $member_id = PsAppMember::find()->alias('A')->leftJoin('ps_member member', 'member.id = A.member_id')
            ->select(['A.member_id'])
            ->where(['A.app_user_id' => $p['user_id']])->scalar();
        if (!$member_id) {
            return $this->failed('业主不存在！');
        }

        $roomInfo = PsCommunityRoominfo::find()->alias('A')
            ->leftJoin('ps_community B', 'B.id = A.community_id')->select(['A.id'])
            ->where(['A.id' => $p['room_id']])->asArray()->one();
        if (!$roomInfo) {
            return $this->failed('房屋不存在！');
        }

        $appUser = PsAppUser::find()->select('avatar, phone, true_name')->where(['id' => $p['user_id']])->asArray()->one();
        
        $params['a_id'] = $p['id'];
        $params['user_id'] = $p['user_id'];
        $params['room_id'] = $p['room_id'];
        $params['avatar'] = !empty($appUser['avatar']) ? $appUser['avatar'] : 'http://static.zje.com/2019041819483665978.png';
        $params['name'] = $appUser['true_name'];
        $params['mobile'] = $appUser['phone'];

        $trans = Yii::$app->getDb()->beginTransaction();

        try {
            $model = new PsActivityEnroll(['scenario' => 'add']);

            if (!$model->load($params, '') || !$model->validate()) {
                return $this->failed($this->getError($model));
            }

            if (!$model->save()) {
                return $this->failed($this->getError($model));
            }

            PsActivity::updateAllCounters(['join_number' => 1], ['id' => $p['id']]);

            $trans->commit();

            return $this->success();
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    // 小程序 活动 详情
    public function show($p)
    {
        $m = PsActivity::find()->where(['id' => $p['id'], 'is_del' => 1])->asArray()->one();

        if (empty($m)) {
            return $this->failed('活动不存在！');
        }

        if (empty($p['user_id'])) {
            return $this->failed('用户ID必填！');
        }
        
        $m['type_msg'] = PsActivity::$type[$m['type']];
        $m['status'] = $m['end_time'] < time() ? 2 : $m['status'];
        $m['status_msg'] = PsActivity::$status[$m['status']];
        $m['join_status'] = $m['join_end'] < time() ? 2 : 1;
        $m['join_end'] = !empty($m['join_end']) ? date('Y-m-d H:i', $m['join_end']) : '';

        $start_date = !empty($m['start_time']) ? date('Y-m-d', $m['start_time']) : '';
        $start_time = !empty($m['start_time']) ? date('H:i', $m['start_time']) : '';
        $m['start_time'] = [];
        $m['start_time']['date'] = $start_date;
        $m['start_time']['time'] = $start_time;

        $end_date = !empty($m['end_time']) ? date('Y-m-d', $m['end_time']) : '';
        $end_time = !empty($m['end_time']) ? date('H:i', $m['end_time']) : '';
        $m['end_time'] = [];
        $m['end_time']['date'] = $end_date;
        $m['end_time']['time'] = $end_time;

        $enroll = PsActivityEnroll::find()->select('avatar')->where(['a_id' => $m['id']])->orderBy('id')->asArray()->all();
        if (!empty($enroll)) {
            $avatar_arr = [];
            foreach ($enroll as $k => $v) {
                $avatar_arr[] = $v['avatar'];
            }
        }
        $m['join_info'] = $avatar_arr;
        $m['is_me'] = $p['user_id'] == $m['operator_id'] ? 1 : 2;

        $activityEnroll = PsActivityEnroll::find()->select('id')->where(['a_id' => $m['id'], 'user_id' => $p['user_id']])->asArray()->one();
        $m['is_join'] = !empty($activityEnroll) ? 1 : 2;

        $appUser = PsAppUser::find()->select('avatar, true_name')->where(['id' => $m['operator_id']])->asArray()->one();
        if ($m['type'] == 2) {
            $m['operator_name'] = $appUser['true_name'];
            if ($p['user_id'] != $m['operator_id']) { // 不是活动发布人 隐藏姓名
                $lenth = strlen($appUser['true_name']);
                if ($lenth <= 6) {
                    $m['operator_name'] = substr($appUser['true_name'], 0, 3) . '*';
                } else {
                    $m['operator_name'] = substr($appUser['true_name'], 0, 3) . '*' . substr($appUser['true_name'], -3);
                }
            }
            $m['operator_head'] = !empty($appUser['avatar']) ? $appUser['avatar'] : 'http://static.zje.com/2019041819483665978.png';
        }

        return $this->success($m);
    }
}
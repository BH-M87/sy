<?php
namespace service\small;

use Yii;
use yii\db\Query;
use yii\db\Exception;

use common\core\F;
use common\core\PsCommon;

use service\BaseService;
use service\message\MessageService;

use app\models\PsAppUser;
use app\models\PsAppMember;
use app\models\PsCommunityRoominfo;
use app\models\PsActivity;
use app\models\PsActivityEnroll;
use app\models\PsResidentAudit;
use app\models\PsRoomUser;

Class ActivityService extends BaseService
{
    public function numberDropDown($param)
    {
        $arr = [];
        $key = 1;
        for ($i = 0; $i <= 100; $i = $i + 1) { 
            if ($i == 0) {
                $arr[0] = '不限';
            } else {
                $arr[$key++] = (string)$i;
            }
        }

        return $arr;
    }

    // 活动 搜索
    private function _searchActivity($param)
    {
        $is_del = !empty($param['is_del']) ? $param['is_del'] : '1';

        $model = PsActivity::find()
            ->filterWhere(['=', 'is_del', $is_del])
            ->andFilterWhere(['=', 'operator_id', $param['operator_id']])
            ->andFilterWhere(['=', 'community_id', $param['community_id']])
            ->andFilterWhere(['=', 'room_id', $param['room_id']])
            ->andFilterWhere(['!=', 'status', $param['status']])
            ->andFilterWhere(['in', 'id', $param['ids']]);   

        return $model;
    }

    // 活动 我的活动列表
    public function listMe($param)
    {
        $page = !empty($param['page']) ? $param['page'] : 1;
        $pageSize = !empty($param['rows']) ? $param['rows'] : 5;

        if (empty($param['user_id'])) {
            return $this->failed('用户ID必填！');
        }

        if ($param['type'] == 1) { // 我参与的
            $a_id = PsActivityEnroll::find()->select('a_id')->where(['user_id' => $param['user_id'], 'room_id' => $param['room_id']])->asArray()->all();
            $activity_arr = [];
            if (!empty($a_id)) {
                foreach ($a_id as $val) {
                    $activity_arr[] = $val['a_id'];
                }
            }
            $params['ids'] = !empty($activity_arr) ? $activity_arr : 'null';
        } else { // 我发布的
            $params['operator_id'] = !empty($param['user_id']) ? $param['user_id'] : 0;
            $params['room_id'] = $param['room_id'];
        }

        $roomInfo = PsCommunityRoominfo::find()->alias('A')
            ->leftJoin('ps_community B', 'B.id = A.community_id')->select('A.address, B.name')
            ->where(['A.id' => $param['room_id']])->asArray()->one();
        if (!$roomInfo) {
            return $this->failed('房屋不存在！');
        }

        $model = $this->_searchActivity($params)
            ->orderBy('is_top desc, top_time desc, created_at desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)->asArray()->all();

        $totals = $this->_searchActivity($params)->count();
        
        if (!empty($model)) {
            foreach ($model as $k => &$v) {
                $v['picture'] = F::ossImagePath($v['picture']);
                $v['start_time'] = !empty($v['start_time']) ? date('Y-m-d', $v['start_time']) : '';
                $v['join_end'] = !empty($v['join_end']) ? date('Y-m-d H:i', $v['join_end']) : '';
                $v['type_msg'] = PsActivity::$type[$v['type']];
                $v['status'] = $v['end_time'] < time() ? 2 : $v['status'];
                $v['status_msg'] = PsActivity::$status[$v['status']];

                $enroll = PsActivityEnroll::find()->select('avatar')->where(['a_id' => $v['id']])->limit(3)->orderBy('id')->asArray()->all();
                $avatar_arr = [];
                if (!empty($enroll)) {
                    foreach ($enroll as $val) {
                        $avatar_arr[] = !empty($val['avatar']) ? $val['avatar'] : 'http://static.zje.com/2019041819483665978.png';
                    }
                }
                $v['join_info'] = $avatar_arr;
            }
        }

        // 查询业主
        $member_id = PsAppMember::find()->alias('A')->leftJoin('ps_member B', 'B.id = A.member_id')->select('B.id')
            ->where(['A.app_user_id' => $param['user_id']])->scalar();

        $identity_type = PsRoomUser::find()->select('identity_type')
            ->where(['member_id' => $member_id, 'room_id' => $param['room_id'], 'status' => 2])->scalar();
        
        $identity_type = !empty($identity_type) ? $identity_type : 0;

        return $this->success(['list' => $model, 'totals' => $totals, 'community_name' => $roomInfo['name'], 'room_info' => $roomInfo['address'], 'identity_type' => $identity_type]);
    }

    // 活动 详情（我参与的）
    public function showMe($param)
    {
    	$model = PsActivity::find()->where(['id' => $param['id'], 'is_del' => 1])->asArray()->one();

    	if (empty($model)) {
    		return $this->failed('活动不存在！');
    	}

    	$roomInfo = PsCommunityRoominfo::find()->alias('A')
            ->leftJoin('ps_community B', 'B.id = A.community_id')
            ->select('B.name, A.address')
            ->where(['A.id' => $param['room_id']])->asArray()->one();
        if (!$roomInfo) {
            return $this->failed('房屋不存在！');
        }
        
        $model['type_msg'] = PsActivity::$type[$model['type']];
        $model['status'] = $model['end_time'] < time() ? 2 : $model['status'];
        $model['status_msg'] = PsActivity::$status[$model['status']];
    	$model['join_end'] = !empty($model['join_end']) ? date('Y-m-d H:i', $model['join_end']) : '';

    	$start_date = !empty($model['start_time']) ? date('Y-m-d', $model['start_time']) : '';
    	$start_time = !empty($model['start_time']) ? date('H:i', $model['start_time']) : '';
    	$model['start_time'] = [];
    	$model['start_time']['date'] = $start_date;
    	$model['start_time']['time'] = $start_time;

    	$end_date = !empty($model['end_time']) ? date('Y-m-d', $model['end_time']) : '';
    	$end_time = !empty($model['end_time']) ? date('H:i', $model['end_time']) : '';
    	$model['end_time'] = [];
    	$model['end_time']['date'] = $end_date;
    	$model['end_time']['time'] = $end_time;

        $enroll = PsActivityEnroll::find()->where(['a_id' => $model['id'], 'room_id' => $param['room_id'], 'user_id' => $param['user_id']])->asArray()->one();
        $model['name'] = !empty($enroll['name']) ? $enroll['name'] : '';
        $model['mobile'] = !empty($enroll['mobile']) ? $enroll['mobile'] : '';
        $model['created_at'] = !empty($enroll['created_at']) ? date('Y-m-d H:i', $enroll['created_at']) : '';
        $model['room_info'] = $roomInfo['name'].$roomInfo['address'];
        $model['picture'] = F::ossImagePath($model['picture']);
      
        return $this->success($model);
    }

    // 活动 报名列表
    public function joinList($param)
    {
    	$page = !empty($param['page']) ? $param['page'] : 1;
        $pageSize = !empty($param['rows']) ? $param['rows'] : 10000;

        $activity = PsActivity::find()->where(['id' => $param['id'], 'is_del' => 1])->asArray()->one();

        if (empty($activity)) {
            return $this->failed('活动不存在！');
        }

        $model = PsActivityEnroll::find()
            ->filterWhere(['=', 'a_id', $param['id']])
            ->select('avatar as head, name, created_at')
            ->orderBy('created_at desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->asArray()->all();
        
        if (!empty($model)) {
            foreach ($model as $k => $v) {
                $model[$k]['created_at'] = date('Y-m-d H:i:s', $v['created_at']);
                
                if ($param['user_id'] != $activity['operator_id']) { // 不是活动发布人 隐藏姓名
                    $lenth = strlen($v['name']);
                    if ($lenth <= 6) {
                        $model[$k]['name'] = substr($v['name'], 0, 3) . '*';
                    } else {
                        $model[$k]['name'] = substr($v['name'], 0, 3) . '*' . substr($v['name'], -3);
                    }
                }
            }
        }

        $totals = PsActivityEnroll::find()->filterWhere(['=', 'a_id', $param['id']])->count();

        return $this->success(['list' => $model, 'totals' => $totals]);
    }

    // 活动 取消
    public function cancel($param)
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
    public function joinCancel($param)
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
        $member = PsAppMember::find()->alias('A')->leftJoin('ps_member B', 'B.id = A.member_id')
            ->select('B.*')->where(['A.app_user_id' => $p['user_id']])->asArray()->one();
        if (!$member) {
            return $this->failed('业主不存在！');
        }

        $roomInfo = PsCommunityRoominfo::find()->alias('A')
            ->leftJoin('ps_community B', 'B.id = A.community_id')->select('A.id, A.community_id')
            ->where(['A.id' => $p['room_id']])->asArray()->one();
        if (!$roomInfo) {
            return $this->failed('房屋不存在！');
        }

        $params['a_id'] = $p['id'];
        $params['user_id'] = $p['user_id'];
        $params['room_id'] = $p['room_id'];
        $params['avatar'] = !empty($member['face_url']) ? $member['face_url'] : 'http://static.zje.com/2019041819483665978.png';
        $params['name'] = $member['name'];
        $params['mobile'] = $member['mobile'];
        $params['community_id'] = $roomInfo ['community_id'];

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
        
        $m['picture'] = F::ossImagePath($m['picture']);
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
<?php
namespace service\small;

use Yii;
use yii\db\Query;
use yii\db\Exception;

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
	public static $activity_type = ['1' => '小区活动', '2' => '邻里活动', '3' => '官方活动'];
	public static $activity_status = ['1' => '进行中', '2' => '已结束', '3' => '已取消'];

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

	// 活动 列表
    public function activityList($param)
    {
    	$page = !empty($param['page']) ? $param['page'] : 1;
        $pageSize = !empty($param['rows']) ? $param['rows'] : 5;

        $params['community_id'] = !empty($param['community_id']) ? $param['community_id'] : 0;
        $params['status'] = 3;
        $model = $this->_searchActivity($params)
            ->orderBy('is_top desc, top_time desc, created_at desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)->asArray()->all();

        $totals = $this->_searchActivity($params)->count();
        
        if (!empty($model)) {
            foreach ($model as $k => $v) {
                $model[$k]['start_time'] = !empty($v['start_time']) ? date('Y-m-d', $v['start_time']) : '';
                $model[$k]['join_end'] = !empty($v['join_end']) ? date('Y-m-d H:i', $v['join_end']) : '';
                $model[$k]['type_msg'] = self::$activity_type[$v['type']];
                $model[$k]['status'] = $v['end_time'] < time() ? 2 : $v['status'];
                $model[$k]['status_msg'] = self::$activity_status[$model[$k]['status']];

                $enroll = PsActivityEnroll::find()->select('avatar')->where(['a_id' => $v['id']])->limit(3)->orderBy('id')->asArray()->all();
		        $avatar_arr = [];
		        if (!empty($enroll)) {
		        	foreach ($enroll as $val) {
		        		$avatar_arr[] = !empty($val['avatar']) ? $val['avatar'] : 'http://static.zje.com/2019041819483665978.png';
		        	}
		        }
		    	$model[$k]['join_info'] = $avatar_arr;
            }
        }

        // 查询业主
        $member_id = PsAppMember::find()->alias('A')->leftJoin('ps_member B', 'B.id = A.member_id')->select('B.id')
            ->where(['A.app_user_id' => $param['user_id']])->scalar();

        $identity_type = PsRoomUser::find()->select('identity_type')
            ->where(['member_id' => $member_id, 'room_id' => $param['room_id'], 'status' => 2])->scalar();

        return ['list' => $model, 'totals' => $totals, 'identity_type' => !empty($identity_type) ? $identity_type : 0];
    }

    // 活动 详情
    public function activityShow($param)
    {
    	$model = PsActivity::find()->where(['id' => $param['id'], 'is_del' => 1])->asArray()->one();

    	if (empty($model)) {
    		return $this->failed('活动不存在！');
    	}

        if (empty($param['user_id'])) {
            return $this->failed('用户ID必填！');
        }
        
        $model['type_msg'] = self::$activity_type[$model['type']];
        $model['status'] = $model['end_time'] < time() ? 2 : $model['status'];
        $model['status_msg'] = self::$activity_status[$model['status']];
        $model['join_status'] = $model['join_end'] < time() ? 2 : 1;
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

        $enroll = PsActivityEnroll::find()->select('avatar')->where(['a_id' => $model['id']])->orderBy('id')->asArray()->all();
        if (!empty($enroll)) {
        	$avatar_arr = [];
        	foreach ($enroll as $k => $v) {
        		$avatar_arr[] = $v['avatar'];
        	}
        }
    	$model['join_info'] = $avatar_arr;
    	$model['is_me'] = $param['user_id'] == $model['operator_id'] ? 1 : 2;

        $activityEnroll = PsActivityEnroll::find()->select('id')->where(['a_id' => $model['id'], 'user_id' => $param['user_id']])->asArray()->one();
        $model['is_join'] = !empty($activityEnroll) ? 1 : 2;

    	$appUser = PsAppUser::find()->select('avatar, true_name')->where(['id' => $model['operator_id']])->asArray()->one();
        if ($model['type'] == 2) {
            $model['operator_name'] = $appUser['true_name'];
            if ($param['user_id'] != $model['operator_id']) { // 不是活动发布人 隐藏姓名
                $lenth = strlen($appUser['true_name']);
                if ($lenth <= 6) {
                    $model['operator_name'] = substr($appUser['true_name'], 0, 3) . '*';
                } else {
                    $model['operator_name'] = substr($appUser['true_name'], 0, 3) . '*' . substr($appUser['true_name'], -3);
                }
            }
            $model['operator_head'] = !empty($appUser['avatar']) ? $appUser['avatar'] : 'http://static.zje.com/2019041819483665978.png';
        }

        return $this->success($model);
    }

    // 活动 报名
    public function activityJoin($param)
    {
    	$activity = PsActivity::find()->where(['id' => $param['id'], 'is_del' => 1])->asArray()->one();

    	if (empty($activity)) {
    		return $this->failed('活动不存在！');
    	}

    	if ($activity['status'] == 2 || $activity['end_time'] < time()) {
    		return $this->failed('活动已结束！');
    	}

        if ($activity['join_end'] < time()) {
            return $this->failed('报名已截止！');
        }

    	if ($activity['status'] == 3) {
    		return $this->failed('活动已取消！');
    	}

        if ($activity['operator_id'] == $param['user_id']) {
            return $this->failed('不能报名自己发布的活动哦！');
        }

        if ($activity['activity_number'] != 0 && $activity['join_number'] >= $activity['activity_number']) {
            return $this->failed('活动报名人数已满！');
        }

    	// 查询业主
        $member_id = PsAppMember::find()->alias('A')->leftJoin('ps_member member', 'member.id = A.member_id')
            ->select(['A.member_id'])
            ->where(['A.app_user_id' => $param['user_id']])->scalar();
        if (!$member_id) {
            return $this->failed('业主不存在！');
        }

        $roomInfo = PsCommunityRoominfo::find()->alias('A')
            ->leftJoin('ps_community B', 'B.id = A.community_id')->select(['A.id'])
            ->where(['A.id' => $param['room_id']])->asArray()->one();
        if (!$roomInfo) {
            return $this->failed('房屋不存在！');
        }

    	$appUser = PsAppUser::find()->select('avatar, phone, true_name')->where(['id' => $param['user_id']])->asArray()->one();
        
        $params['a_id'] = $param['id'];
        $params['user_id'] = $param['user_id'];
        $params['room_id'] = $param['room_id'];
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

            PsActivity::updateAllCounters(['join_number' => 1], ['id' => $param['id']]);

            //发送消息
            if ($activity['type'] == 1) {//只有物业端的消息才推送
                //获取业主id
                $member_name = $this->getMemberNameByUser($member_id);
                $room_info = CommunityRoomService::getCommunityRoominfo($param['room_id']);
                $data = [
                    'community_id' => $activity['community_id'],
                    'id' => $activity['id'],
                    'member_id' => $member_id,
                    'user_name' => $member_name,
                    'create_user_type' => 2,

                    'remind_tmpId' => 10,
                    'remind_target_type' => 10,
                    'remind_auth_type' => 10,
                    'msg_type' => 3,

                    'msg_tmpId' => 10,
                    'msg_target_type' => 10,
                    'msg_auth_type' => 10,
                    'remind' => [
                        0 => $member_name,
                        1 => "报名"
                    ],
                    'msg' => [
                        0 => $member_name,
                        1 => $activity['title'],
                        2 => $activity['title'],
                        3 => $room_info['group'] . '' . $room_info['building'] . '' . $room_info['unit'] . $room_info['room'],
                        4 => $member_name,
                        5 => date("Y-m-d H:i:s", time())
                    ]
                ];
                MessageService::service()->addMessageTemplate($data);
            }
	        $trans->commit();

            return $this->success();
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    // 活动 新增 编辑
    private function _saveActivity($param, $scenario)
    {
        if (!empty($param['id'])) {
            $activity = PsActivity::getOne($param);
            if (!$activity || $activity['is_del'] == 2) {
                return $this->failed('数据不存在！');
            }
        }

        // 查询业主
        $member_id = PsAppMember::find()->alias('A')->leftJoin('ps_member member', 'member.id = A.member_id')
            ->select(['A.member_id'])
            ->where(['A.app_user_id' => $param['user_id']])->scalar();
        if (!$member_id) {
            return $this->failed('业主不存在！');
        }

        $roomInfo = PsCommunityRoominfo::find()->alias('A')
            ->leftJoin('ps_community B', 'B.id = A.community_id')->select(['A.id'])
            ->where(['A.id' => $param['room_id']])->asArray()->one();
        if (!$roomInfo) {
            return $this->failed('房屋不存在！');
        }

        if ($param['start_time'] >= $param['end_time']) {
            return $this->failed('活动结束时间要大于开始时间！');
        }

        if ($param['join_end'] >= $param['end_time']) {
            return $this->failed('报名截止时间要小于结束时间！');
        }
        
        $param['operator_id'] = $param['user_id'];
        $param['type'] = 2; // 业主端
        $param['is_top'] = 1;
        $param['activity_number'] = $param['activity_number'] == '不限' ? 0 : $param['activity_number'];
        $param['start_time'] = !empty($param['start_time']) ? strtotime($param['start_time']) : '';
        $param['end_time'] = !empty($param['end_time']) ? strtotime($param['end_time']) : '';
        $param['join_end'] = !empty($param['join_end']) ? strtotime($param['join_end']) : '';

        $model = new PsActivity(['scenario' => $scenario]);

        if (!$model->load($param, '') || !$model->validate()) {
            return $this->failed($this->getError($model));
        }

        if (!$model->saveData($scenario, $param)) {
            return $this->failed($this->getError($model));
        }

        return $this->success(['id' => $model->attributes['id']]);
    }

    // 活动 新增
    public function activityAdd($param)
    {
        return $this->_saveActivity($param, 'add');
    }

    // 活动 编辑
    public function activityEdit($param)
    {
        return $this->_saveActivity($param, 'edit');
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
    public function activityListMe($param)
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
            foreach ($model as $k => $v) {
                $model[$k]['start_time'] = !empty($v['start_time']) ? date('Y-m-d', $v['start_time']) : '';
                $model[$k]['join_end'] = !empty($v['join_end']) ? date('Y-m-d H:i', $v['join_end']) : '';
                $model[$k]['type_msg'] = self::$activity_type[$v['type']];
                $model[$k]['status'] = $v['end_time'] < time() ? 2 : $v['status'];
                $model[$k]['status_msg'] = self::$activity_status[$model[$k]['status']];

                $enroll = PsActivityEnroll::find()->select('avatar')->where(['a_id' => $v['id']])->limit(3)->orderBy('id')->asArray()->all();
                $avatar_arr = [];
                if (!empty($enroll)) {
                    foreach ($enroll as $val) {
                        $avatar_arr[] = !empty($val['avatar']) ? $val['avatar'] : 'http://static.zje.com/2019041819483665978.png';
                    }
                }
                $model[$k]['join_info'] = $avatar_arr;
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
    public function activityShowMe($param)
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
        
        $model['type_msg'] = self::$activity_type[$model['type']];
        $model['status'] = $model['end_time'] < time() ? 2 : $model['status'];
        $model['status_msg'] = self::$activity_status[$model['status']];
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
      
        return $this->success($model);
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

    // 活动 报名列表
    public function activityJoinList($param)
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
            //发送消息
            if ($modelInfo['type'] == 1) {//只有物业的活动才推送
                //获取业主id
                $member_id = $this->getMemberByUser($param['user_id']);
                $member_name = $this->getMemberNameByUser($member_id);
                $room_info = CommunityRoomService::getCommunityRoominfo($param['room_id']);
                $data = [
                    'community_id' => $modelInfo['community_id'],
                    'id' => $modelInfo['id'],
                    'member_id' => $member_id,
                    'user_name' => $member_name,
                    'create_user_type' => 2,

                    'remind_tmpId' => 10,
                    'remind_target_type' => 10,
                    'remind_auth_type' => 10,
                    'msg_type' => 3,

                    'msg_tmpId' => 18,
                    'msg_target_type' => 10,
                    'msg_auth_type' => 10,
                    'remind' => [
                        0 => $member_name,
                        1 => "取消"
                    ],
                    'msg' => [
                        0 => $member_name,
                        1 => $modelInfo['title'],
                        2 => $modelInfo['title'],
                        3 => $room_info['group'] . '' . $room_info['building'] . '' . $room_info['unit'] . $room_info['room'],
                        4 => $member_name,
                        5 => date("Y-m-d H:i:s", time())
                    ]
                ];
                MessageService::service()->addMessageTemplate($data);
            }
            $trans->commit();
            return $this->success();
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }
}
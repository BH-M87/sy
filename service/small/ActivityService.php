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
            foreach ($model as $k => $v) {
                $model[$k]['start_time'] = !empty($v['start_time']) ? date('Y-m-d', $v['start_time']) : '';
                $model[$k]['join_end'] = !empty($v['join_end']) ? date('Y-m-d H:i', $v['join_end']) : '';
                $model[$k]['type_msg'] = PsActivity::$type[$v['type']];
                $model[$k]['status'] = $v['end_time'] < time() ? 2 : $v['status'];
                $model[$k]['status_msg'] = PsActivity::$status[$model[$k]['status']];

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
      
        return $this->success($model);
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
}
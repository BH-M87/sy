<?php
namespace service\property_basic;

use common\core\PsCommon;
use common\MyException;

use service\BaseService;
use service\rbac\OperateService;

use app\models\PsActivity;
use app\models\PsActivityEnroll;
use app\models\PsCommunityRoominfo;

class ActivityService extends BaseService
{
    // 获取活动列表
    public function backendActivityList($params)
    {
        $this->checkListParams($params);
        $data = PsActivity::getList($params,['id','title','start_time','end_time','join_end','status','address','link_name','link_mobile','join_number','is_top','activity_number']);
        return $data;
    }

    // 新增活动
    public function addBackendActivity($params, $user_info)
    {
        $activity = new PsActivity();
        $params['join_end'] = isset($params['join_end']) ? strtotime($params['join_end']) : null;
        $params['start_time'] = isset($params['start_time']) ? strtotime($params['start_time']) : null;
        $params['end_time'] = isset($params['end_time']) ? strtotime($params['end_time']) : null;
        $data = PsCommon::validParamArr($activity,$params,'backend_add');
        if (!$data['status']) {
            throw new MyException($data['errorMsg']);
        }

        if ($params['end_time'] < $params['start_time']) {
            throw new MyException('活动结束时间必须大于活动开始时间');
        }
        if ($params['end_time'] < $params['join_end']) {
            throw new MyException('活动结束必须大于报名截止时间');
        }
        if ($params['is_top'] == 2) {
            $activity->top_time = time();
        } else {
            $activity->is_top = 1;
        }
        $activity->type = 1;
        $activity->status = 1;
        $activity->operator_id = $user_info['id'];
        $activity->save();
        if (!empty($user_info)){
            $content = "活动主题名称:".$params['title']."联系人:".$params['link_name'].'活动地点:'.$params['address'];
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "社区运营",
                "operate_type" => "小区活动新增",
                "operate_content" => $content,
            ];
            OperateService::addComm($user_info, $operate);
        }
    }

    // 修改活动
    public function editBackendActivity($params, $user_info)
    {
        $this->checkActivityId($params);
        /** @var  PsActivity $activity */
        $activity = $this->getActivityOne(['community_id' => $params['community_id'],'id' => $params['id']]);
        $params['join_end'] = isset($params['join_end']) ? strtotime($params['join_end']) : null;
        $params['start_time'] = isset($params['start_time']) ? strtotime($params['start_time']) : null;
        $params['end_time'] = isset($params['end_time']) ? strtotime($params['end_time']) : null;
        $data = PsCommon::validParamArr($activity,$params,'backend_edit');
        if (!$data['status']) {
            throw new MyException($data['errorMsg']);
        }

        if ($params['end_time'] < $params['start_time']) {
            throw new MyException('活动结束时间必须大于活动开始时间');
        }
        if ($params['end_time'] < $params['join_end']) {
            throw new MyException('活动结束必须大于报名截止时间');
        }
        if ($params['is_top'] == 2) {
            $activity->top_time = time();
        } else {
            $activity->is_top = 1;
        }
        $activity->operator_id = $user_info['id'];
        $activity->save();
        if (!empty($user_info)){
            $content = "活动主题名称:".$params['title']."联系人:".$params['link_name'].'活动地点:'.$params['address'];
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "社区运营",
                "operate_type" => "小区活动编辑",
                "operate_content" => $content,
            ];
            OperateService::addComm($user_info, $operate);
        }
    }

    // 活动删除
    public function deleteBackendActivity($params, $user_info)
    {
        $this->checkActivityId($params);
        /** @var  PsActivity $activity */
        $activity = $this->getActivityOne(['community_id' => $params['community_id'],'id' => $params['id']]);
        if (empty($activity) || $activity->is_del == 2) {
            throw new MyException('数据不存在');
        }
        $activity->is_del = 2;
        $activity->operator_id = $user_info['id'];
        if (!$activity->save()) {
            $activity->getErrors();
            throw new MyException($activity->getErrors());
        }
        if (!empty($user_info)){
            $content = "活动主题名称:".$activity->title;
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "社区运营",
                "operate_type" => "小区活动删除",
                "operate_content" => $content,
            ];
            OperateService::addComm($user_info, $operate);
        }
    }

    // 获取活动详情
    public function getBackendActivityOne($params)
    {
        $this->checkActivityId($params);
        $activity = $this->getActivityOne(['community_id' => $params['community_id'],'id' => $params['id']])->toArray();
        $activity['join_end'] = date('Y-m-d H:i',$activity['join_end']);
        $activity['start_time'] = date('Y-m-d H:i',$activity['start_time']);
        $activity['end_time'] = date('Y-m-d H:i',$activity['end_time']);
        $activity['status_desc'] = PsActivity::$status_desc[$activity['status']];
        unset($activity['operator_id']);
        unset($activity['is_del']);
        unset($activity['room_id']);
        unset($activity['community_id']);
        unset($activity['top_time']);
        unset($activity['type']);
        return $activity;
    }

    // 获取报名列表
    public function getBackendActivityJoinList($params)
    {
        $this->checkActivityId($params);
        /** @var  PsActivity $activity */
        $activity = $this->getActivityOne(['community_id' => $params['community_id'],'id' => $params['id']]);
        $enroll = PsActivityEnroll::find()->select(['name','mobile','room_id','created_at'])->where(['a_id' => $activity->id]);
        $count = $enroll->count();
        if ($count > 0) {
            $list = $enroll->orderBy('id desc')->offset((($params['page'] ?? 1) - 1) * ($params['rows'] ?? 10))->limit($params['rows'] ?? 10)->asArray()->all();
            foreach ($list as &$v) {
                $v['created_at'] = date('Y-m-d H:i',$v['created_at']);
                $v['address'] = PsCommunityRoominfo::find()->select('address')->where(['id' => $v['room_id']])->one()->address;
            }
        }
        return ['totals'=>$count,'list'=>$list ?? []];
    }

    // 获取单条数据
    public function getActivityOne($where)
    {
        $activity = PsActivity::getBackendOne($where);
        if (empty($activity)) {
            throw new MyException('数据不存在');
        }
        return $activity;
    }

    // 置顶活动
    public function topActivity($params, $user_info)
    {
        $this->checkActivityId($params);
        /** @var  PsActivity $activity */
        $activity = $this->getActivityOne(['community_id' => $params['community_id'],'id' => $params['id']]);
        if (empty($activity) || $activity->is_del == 2) {
            throw new MyException('数据不存在');
        }
        if ($activity->is_top == 1) {
            $activity->is_top = 2;
            $activity->top_time = time();
        } else {
            $activity->is_top = 1;
            $activity->top_time = 0;
        }
        $activity->operator_id = $user_info['id'];
        $activity->save();
    }

    // 检查活动ID和小区ID参数
    public function checkActivityId($params)
    {
        if (empty($params['id']) || empty($params['community_id'])) {
            throw new MyException('活动ID或小区ID不能为空');
        }
    }

    // 时间必须大于当前时间
    public function checkTime($time,$name)
    {
        if ($time < time()) {
            throw new MyException($name.'必须大于当前时间');
        }
    }

    // 列表搜索参数整理
    public function checkListParams(&$params)
    {
        if (empty($params['community_id'])) {
            throw new MyException('小区ID不能为空!');
        }
        if (!empty($params['status']) && in_array($params['status'],[1,2,3])) {
            $params['status'] = $params['status'] == 3 ? null : $params['status'];
        } else {
            $params['status'] = null;
        }
        if (!empty($params['join_start'])) {
            $params['join_start'] = strtotime($params['join_start'].' 00:00');
            if (!$params['join_start']) {
                $params['join_start'] = null;
            }
        }
        if (!empty($params['join_end'])) {
            $params['join_end'] = strtotime($params['join_end'].' 23:59');
            if (!$params['join_end']) {
                $params['join_end'] = null;
            }
        }

        if (!empty($params['activity_start'])) {
            $params['activity_start'] = strtotime($params['activity_start'].' 00:00');
            if (!$params['activity_start']) {
                $params['activity_start'] = null;
            }
        }
        if (!empty($params['activity_end'])) {
            $params['activity_end'] = strtotime($params['activity_end'].' 23:59');
            if (!$params['activity_end']) {
                $params['activity_end'] = null;
            }
        }
    }
}
<?php
namespace service\property_basic;

use common\core\F;
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
use app\models\Department;
use app\models\DepartmentCommunity;

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
        if (strstr($p['picture'], 'http')) {
            unset($p['picture']);
        }

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
            return $this->success(['id' => $m->id]);
        }
    }

    // 获取活动列表
    public function list($p)
    {
        $m = PsActivity::getList($p);
        return $this->success($m);
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
        
        $m['picture'] = F::ossImagePath($m['picture']);
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

}
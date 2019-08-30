<?php
namespace service\resident;

use Yii;
use yii\db\Exception;

use common\core\F;
use common\core\PsCommon;
use common\MyException;

use app\models\PsAppMember;
use app\models\PsAppUser;
use app\models\PsCommunityRoominfo;
use app\models\PsLabels;
use app\models\PsLabelsRela;
use app\models\PsMember;
use app\models\PsNation;
use app\models\PsResidentAudit;
use app\models\PsResidentHistory;
use app\models\PsRoomUser;
use app\models\PsRoomUserLabel;
use app\models\ParkingUsers;
use app\models\PsCommunityModel;

use service\basic_data\DoorPushService;
use service\basic_data\RoomMqService;
use service\common\AreaService;
use service\common\SmsService;
use service\label\LabelsService;
use service\manage\CommunityService;
use service\rbac\OperateService;
use service\room\RoomService;

class ResidentService extends BaseService
{
    public $sexes = [
        0 => ['id' => 0, 'name' => '未设置'],
        1 => ['id' => 1, 'name' => '男'],
        2 => ['id' => 2, 'name' => '女'],
    ];

    public $change_detail = [
        '1' => '迁入',
        '2' => '迁出',
        '3' => '死亡',
        '4' => '失联',
        '5' => '购房入住',
        '6' => '出生',
        '7' => '其他',
    ];

    public $face = [
        '1' => '党员',
        '2' => '团员',
        '3' => '群众',
    ];

    public $household_type = [
        '1' => '非农业户口',
        '2' => '农业户口',
    ];

    public $identity_type = [
        '1' => '业主',
        '2' => '家人',
        '3' => '租客',
    ];

    public $live_detail = [
        '1' => '空巢老人',
        '2' => '独居',
        '3' => '孤寡',
        '4' => '其他',
    ];

    public $live_type = [
        '1' => '户在人在',
        '2' => '户在人不在',
        '3' => '常住(已购房，户籍不在)',
        '4' => '承租',
        '5' => '空房',
        '6' => '借住',
        '7' => '其他',
        '8' => '人在户不在',
    ];
    
    public $marry_status = [
        '1' => '已婚',
        '2' => '未婚',
        '3' => '离异',
        '4' => '分居',
        '5' => '丧偶',
    ];

    // 住户列表
    public static function lists($params, $page, $rows)
    {
        // 标签处理
        if (!empty($params['user_label_id']) && is_array($params['user_label_id'])) {
            $labels = PsLabelsRela::find()->where(['in', 'labels_id', $params['user_label_id']])
                ->andWhere(['data_type' => 2])->asArray()->all();
            $room_user_id = array_unique(array_column($labels, 'data_id'));
            if (empty($room_user_id)) {
                return ["list" => 0, 'totals' => 0];
            }
            $params['id'] = $room_user_id;
        }

        $count = PsRoomUser::model()->get($params)->count();
        if (!$count) {
            return ['totals' => 0, 'list' => []];
        }

        $models = PsRoomUser::model()->get($params)
            ->select('id, name, mobile, sex, card_no, group, building, unit, room, identity_type, status, auth_time, time_end, out_time')
            ->orderBy('id desc')
            ->offset(($page - 1) * $rows)->limit($rows)
            ->asArray()->all();
        foreach ($models as $key => $model) {
            $models[$key]['card_no'] = F::processIdCard($model['card_no']);
            $models[$key]['mobile'] = PsCommon::isVirtualPhone($model['mobile']) ? '' : PsCommon::hideMobile($model['mobile']);
            $models[$key]['time_end'] = $model['time_end'] ? date('Y-m-d', $model['time_end']) : '长期';
            $models[$key]['out_time'] = $model['out_time'] > 0 ? date("Y-m-d H:i:s", $model['out_time']) : "-";
            $models[$key]['auth_time'] = $model['auth_time'] > 0 ? date("Y-m-d H:i:s", $model['auth_time']) : "-";
            $models[$key]['identity_type_desc'] = $model['identity_type'] ? PsCommon::getIdentityType($model['identity_type'], 'key') : "-";
            $models[$key]['status_desc'] = $model['status'] ? PsCommon::getIdentityStatus($model['status']) : "-";
        }

        return ["list" => $models, 'totals' => $count];
    }

    // 查看用户详情
    public function show($id)
    {
        $model = PsRoomUser::find()->where(['community_id' => $this->communityId, 'id' => $id])->asArray()->one();
        $nations = $this->getNation();
        $nationNames = [];
        foreach ($nations as $n) {
            $nationNames[$n['id']] = $n['name'];
        }
        $areaCodes = [$model['household_province'], $model['household_city'], $model['household_area']];
        $psAreas = AreaService::service()->getNamesByCodes($areaCodes);
        if ($model) {
            $label = PsLabelsRela::find()->select('labels_id')->where(['data_id' => $model['id'], 'data_type' => 2])->asArray()->all();//标签id
            if (!empty($label)) {
                foreach ($label as $k => $v) {
                    $model['user_label'][$k]['id'] = $v['labels_id'];
                    $model['user_label'][$k]['name'] = PsLabels::findOne($v['labels_id'])->name;
                }
            }
            //edit by wenchao.feng 虚拟手机号处理
            $model['mobile'] = PsCommon::isVirtualPhone($model['mobile']) ? "" : $model['mobile'];
            $model['enter_time'] = $model['enter_time'] ? date('Y-m-d', $model['enter_time']) : '';
            $model['time_end'] = $model['time_end'] ? date('Y-m-d', $model['time_end']) : '0';
            $model['auth_time'] = $model['auth_time'] > 0 ? date("Y-m-d H:i:s", $model['auth_time']) : "-";
            $model['out_time'] = $model['out_time'] > 0 ? date("Y-m-d H:i:s", $model['out_time']) : "-";
            $model['create_at'] = $model['create_at'] > 0 ? date("Y-m-d", $model['create_at']) : "-";
            $model['update_at'] = $model['update_at'] > 0 ? date("Y-m-d", $model['update_at']) : "-";
            $model['marry_status_desc'] = PsCommon::get($this->marry_status, $model['marry_status']);
            $model['live_type_desc'] = PsCommon::get($this->live_type, $model['live_type']);
            $model['live_detail_desc'] = PsCommon::get($this->live_detail, $model['live_detail']);
            $model['household_type_desc'] = PsCommon::get($this->household_type, $model['household_type']);
            $model['face_desc'] = PsCommon::get($this->face, $model['face']);
            $model['change_detail_desc'] = PsCommon::get($this->change_detail, $model['change_detail']);
            $model['nation_desc'] = PsCommon::get($nationNames, $model['nation']);
            $model['household_province_desc'] = PsCommon::get($psAreas, $model['household_province']);
            $model['household_city_desc'] = PsCommon::get($psAreas, $model['household_city']);
            $model['household_area_desc'] = PsCommon::get($psAreas, $model['household_area']);
            $model['identity_type_desc'] = $model['identity_type'] ? PsCommon::getIdentityType($model['identity_type'], 'key') : "-";
            $face = PsMember::find()->select('face_url')->where(['id' => $model['member_id']])->scalar();
            $model['face_url'] = $face ?? "";
        }
        
        return $model;
    }

    public function add($request, $operatorInfo)
    {
        $transaction = Yii::$app->getDb()->beginTransaction();
        try {
            $r = $this->_saveRoomUser($request, $operatorInfo);
            if (!$r['code']) {
                throw new Exception($r['msg']);
            }

            // 保存日志
            $operator = [
                "community_id" => $this->communityId,
                "operate_menu" => "住户管理",
                "operate_type" => "新增住户",
                "operate_content" => $request["name"] . " " . (PsCommon::isVirtualPhone($request["mobile"]) ? '' : $request["mobile"]),
            ];
            OperateService::addComm($operatorInfo, $operator);
            $transaction->commit();
            return $this->success(['id' => $r['data']['id']]);
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    // 编辑住户
    public function edit($data, $user_info)
    {
        $id = PsCommon::get($data, 'id');
        if (!$id) {
            return $this->failed('ID不能为空');
        }
        $roomUser = PsRoomUser::findOne(['id' => $id, 'community_id' => $this->communityId]);
        if (!$roomUser) {
            return $this->failed('住户不存在');
        }

        $communityId = $roomUser['community_id'];
        $roomId = PsCommon::get($data, 'room_id');
        $mobile = trim(PsCommon::get($data, 'mobile'));
        $name = PsCommon::get($data, 'name');
        $label_id = PsCommon::get($data, 'user_label_id');
        $isEditMobile = 0;
        $oldRoomUser = PsRoomUser::find()->where(['id' => $id, 'community_id' => $this->communityId])->asArray()->one();

        //edit by wenchao.feng 虚拟手机号
        if ($roomUser['status'] != 2) {
            //已认证的不可编辑手机号，不做处理
            if (PsCommon::isVirtualPhone($roomUser['mobile']) && empty($mobile)) {
                //虚拟手机号继续编辑为空的情况，保留原虚拟手机号
                $mobile = $roomUser['mobile'];
                $data['mobile'] = $roomUser['mobile'];
            } elseif (!PsCommon::isVirtualPhone($roomUser['mobile']) && empty($mobile)) {
                return $this->failed('手机号不能为空');
            }
        }
        $r = $this->_addCheck($communityId, $roomId, $mobile, $id, $name);
        if (!$r['code']) {
            return $this->failed($r['msg']);
        }

        // edit by wenchao.feng -v phone_empty 20190805 增加验证，如果之前有上传人脸，那么人脸不能为空
        $memberModel = PsMember::findOne($roomUser['member_id']);
        if ($memberModel->face_url && empty($data['face_url'])) {
            return $this->failed('人脸照片不能为空');
        }
        // 验证标签
        if (!empty($label_id)) {
            $relation = LabelsService::service()->addRelation($roomUser->id, $label_id, 2);
            if (!$relation) {
                return $this->failed('标签错误');
            }
        } else if (empty($label_id)) {
            PsLabelsRela::deleteAll(['data_type' => 2, 'data_id' => $roomUser->id]);
        }

        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            // 房屋信息无法编辑
            unset(
                $data['room_id'],
                $data['group'],
                $data['building'],
                $data['unit'],
                $data['room']
            );

            if ($roomUser['mobile'] != $mobile) {
                // 新增新用户
                $member = MemberService::service()->saveMember([
                    'name' => PsCommon::get($data, 'name'),
                    'mobile' => PsCommon::get($data, 'mobile'),
                    'card_no' => PsCommon::get($data, 'card_no'),
                    'sex' => !empty($data['sex']) ? $data['sex'] : 1,
                    'face_url' => PsCommon::get($data, 'face_url', '')
                ]);
                if (!$member['code']) {
                    throw new Exception($member['msg']);
                }
                $isEditMobile = 1;
                $memberId = $member["data"];
                $roomUser->member_id = $memberId;
            }

            //todo 待完成，业主修改身份证以后，ps_member表没做操作
            $et = PsCommon::get($data, 'enter_time');
            $data['enter_time'] = $et ? strtotime($et) : 0;
            $data['sex'] = !empty($data['sex']) ? $data['sex'] : 1;
            $data['operator_id'] = PsCommon::get($user_info, 'id');
            $data['operator_name'] = PsCommon::get($user_info, 'truename');
            if ($data['identity_type'] == 3) {
                $time_end = strtotime($data['time_end'] . " 23:59:59") ?: 0;
                if ($time_end != 0 && $time_end < time()) {
                    return $this->failed('有效期不能小于当前时间');
                }
            } else {
                $time_end = 0;
            }
            $data['time_end'] = $time_end;
            if ($roomUser['status'] == PsRoomUser::AUTH) {
                if ($roomUser['identity_type'] != $data['identity_type']) {
                    unset($data['time_end']);
                }
                unset(
                    $data['name'],
                    $data['mobile'],
                    $data['identity_type']
                );
            }

            $isAuth = $this->isAuthByNameMobile($communityId, $name, $mobile);
            if ($isAuth && $roomUser->status == PsRoomUser::UN_AUTH) {//如果新的名字+手机号已经认证过，则自动变更为已认证状态
                $roomUser->auth_time = time();
                $roomUser->status = PsRoomUser::AUTH;
                $roomUser->auth_time = time();
            }

            // 同步人脸
            $member = PsMember::findOne($roomUser->member_id);
            if (!empty($member) && !empty($data['face_url'])) {
                $member->face_url = $data['face_url'];
                $member->save();
            }

            $roomUser->load($data, '');
            if (!$roomUser->validate() || !$roomUser->save()) {
                throw new Exception("住户编辑失败:" . $this->getError($roomUser));
            }

            //日志
            $operate = [
                "community_id" => $data["community_id"],
                "operate_menu" => "住户管理",
                "operate_type" => "编辑住户",
                "operate_content" => $name . " " . (PsCommon::isVirtualPhone($mobile) ? '' : $mobile),
            ];
            OperateService::addComm($user_info, $operate);
            $trans->commit();
            return $this->success();
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    // 删除未认证住户
    public function delete($id, $user_info)
    {
        if (!$id) {
            return $this->failed('ID不能为空');
        }

        $roomUser = PsRoomUser::findOne(['id' => $id]);

        if (!$roomUser) {
            return $this->failed('数据不存在');
        }

        if ($roomUser["status"] == PsRoomUser::AUTH) {
            return $this->failed("已认证住户无法删除");
        }

        $operate = [
            "community_id" => $roomUser["community_id"],
            "operate_menu" => "住户管理",
            "operate_type" => "删除住户",
            "operate_content" => $roomUser["name"] . " " . (PsCommon::isVirtualPhone($roomUser["mobile"]) ? '' : $roomUser["mobile"]),
        ];
        OperateService::addComm($user_info, $operate);

        $roomUser->delete();
        return $this->success();
    }

    // 关联房屋
    public function relatedHouse($id, $page = 0, $rows = 0)
    {
        $query = PsRoomUser::model()->get(['community_id' => $this->communityId, 'member_id' => $id, 'move_status' => 1]);
        $total = $query->count();
        if (!$total) {
            return ['list' => [], 'total' => $total];
        }

        if ($page && $rows) {
            $query->offset(($page - 1) * $rows)->limit($rows);
        }

        $data = $query->with('roomInfo')->orderBy('id desc')
            ->select('id, community_id, room_id, member_id, group, building, unit, room')
            ->asArray()->all();
        foreach ($data as &$v) {
            $v['community_name'] = PsCommunityModel::findOne($v['community_id'])->name;
            if (!empty($v['roomInfo'])) {
                $v['roomInfo']['property_type_desc'] = PsCommon::propertyType($v['roomInfo']['property_type']);
                $v['roomInfo']['status_desc'] = PsCommon::houseStatus($v['roomInfo']['status']);
            }
            $label = PsLabelsRela::find()->select('labels_id')->where(['data_id' => $v['roomInfo']['id'], 'data_type' => 1])->asArray()->all();//标签id
            if (!empty($label)) {
                foreach ($label as $k => $val) {
                    $v['label'][$k]['id'] = $val['labels_id'];
                    $v['label'][$k]['name'] = PsLabels::findOne($val['labels_id'])->name;
                }
            }
        }

        return ['list' => $data, 'total' => $total];
    }

    /*
    * 关联房屋编辑
    */
    public function relatedHouseEdit($id, $identity_type, $end_time)
    {
        $model = PsRoomUser::findOne(['id' => $id, 'community_id' => $this->communityId]);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        if ($model->status == PsRoomUser::AUTH) {
            return $this->failed('房屋已认证，不可编辑');
        }
        $model->identity_type = $identity_type;
        if ($identity_type == 3) {
            $model->time_end = strtotime($end_time . " 23:59:59") ?: 0;
            if ($model->time_end != 0 && $model->time_end < time()) {
                return $this->failed('有效期不能小于当前时间');
            }
        } else {
            $model->time_end = 0;
        }
        if ($model->identity_type == 3) {
            $model->setScenario('renter');
        }
        if (!$model->save()) {
            return $this->failed($model->errors);
        }
        return $this->success();
    }

    // 关联住户
    public function relatedResident($id, $page = 0, $rows = 0)
    {
        $houses = $this->relatedHouse($id);
        $housesIds = array_column($houses['list'], 'room_id');

        $query = PsRoomUser::find()->select('id, name, mobile, card_no, room_id, member_id, identity_type, status, group, building, unit, room, time_end, create_at, auth_time')
            ->where(['room_id' => $housesIds, 'status' => [PsRoomUser::UN_AUTH, PsRoomUser::AUTH]]);
        $total = $query->count();
        if ($page && $rows) {
            $query->offset(($page - 1) * $rows)->limit($rows);
        }

        $data = $query->orderBy('id desc')->asArray()->all();
        foreach ($data as &$model) {
            $model['card_no'] = F::processIdCard($model['card_no']);
            $model['time_end'] = !empty($model['time_end']) ? date('Y-m-d', $model['time_end']) : '长期';
            $model['create_at'] = !empty($model['create_at']) ? date('Y-m-d', $model['create_at']) : '';
            $model['identity_type_des'] = PsCommon::getIdentityType($model['identity_type'], 'key');
            $model['status_desc'] = PsCommon::getIdentityStatus($model['status']);
            $model['auth_time'] = $model['auth_time'] ? date('Y-m-d H:i:s', $model['auth_time']) : '-';
            $model['mobile'] = PsCommon::isVirtualPhone($model['mobile']) ? '' : $model['mobile'];
        }

        return ['list' => $data, 'total' => $total];
    }


    // 关联车辆
    public function relatedCar($param, $page = 0, $rows = 0)
    {
        $roomUser = PsRoomUser::findOne($param['id']);

        $query = ParkingUsers::find()->alias('A')
            ->select('C.id, C.community_id, B.room_address, D.car_port_num, A.user_name, A.user_mobile, C.car_num, C.car_model')
            ->leftJoin('parking_user_carport B', 'A.id = B.user_id')
            ->leftJoin('parking_cars C', 'C.id = B.car_id')
            ->leftJoin('parking_carport D', 'D.id = B.carport_id')
            ->where(['=', 'user_mobile', $roomUser->mobile]);

        $total = $query->count();
        if ($page && $rows) {
            $query->offset(($page - 1) * $rows)->limit($rows);
        }

        $model = $query->orderBy('id desc')->asArray()->all();

        foreach ($model as &$v) {
            $v['community_name'] = PsCommunityModel::findOne($v['community_id'])->name;
        }

        return ['list' => $model, 'total' => $total];
    }

    // 住户列表 审核 待审核
    public function auditLists($params, $page, $rows)
    {
        $count = PsResidentAudit::model()->get($params)->count();
        if (!$count) {
            return ['totals' => 0, 'list' => []];
        }

        $models = PsResidentAudit::model()->get($params)
            ->orderBy('id desc')
            ->offset(($page - 1) * $rows)->limit($rows)
            ->asArray()->all();

        foreach ($models as &$model) {
            $model['card_no'] = F::processIdCard($model['card_no']);
            $model['sex'] = $model['sex'] == 1 ? '男' : '女';
            $model['mobile'] = PsCommon::isVirtualPhone($model['mobile']) ? '' : PsCommon::hideMobile($model['mobile']);
            $model['time_end'] = !empty($model['time_end']) ? date('Y-m-d', $model['time_end']) : '长期';
            $model['create_at'] = !empty($model['create_at']) ? date('Y-m-d H:i:s', $model['create_at']) : '';
            $model['unaccept_at'] = !empty($model['unaccept_at']) ? date('Y-m-d H:i:s', $model['unaccept_at']) : '';
            $model['identity_type_desc'] = PsCommon::getIdentityType($model['identity_type'], 'key');
            $model['images'] = explode(',', $model['images']);
        }

        return ["list" => $models, 'totals' => $count];
    }

    // 住户审核详情
    public function auditShow($id, $communityId)
    {
        $data = PsResidentAudit::find()->alias('t')
            ->select('t.*, r.group, r.building, r.unit, r.room')
            ->leftJoin(['r' => PsCommunityRoominfo::tableName()], 't.room_id=r.id')
            ->where(['t.id' => $id, 't.community_id' => $communityId])->asArray()->one();
        if (!$data) {
            return null;
        }

        $data['create_at'] = $data['create_at'] ? date('Y-m-d', $data['create_at']) : 0;
        $data['update_at'] = $data['update_at'] ? date('Y-m-d', $data['update_at']) : 0;
        $data['time_end'] = $data['time_end'] ? date('Y-m-d', $data['time_end']) : '0';
        $data['accept_at'] = $data['accept_at'] ? date('Y-m-d', $data['accept_at']) : '';
        $data['identity_type_des'] = PsCommon::getIdentityType($data['identity_type'], 'key');
        $data['images'] = $data['images'] ? explode(',', $data['images']) : [];
        $data['mobile'] = PsCommon::isVirtualPhone($data['mobile']) ? '' : $data['mobile'];

        return $data;
    }

    // 审核不通过
    public function nopass($id, $message, $operator)
    {
        $model = PsResidentAudit::findOne(['id' => $id, 'community_id' => $this->communityId]);
        if (!$model) {
            return $this->failed('数据不存在');
        }

        $message = trim($message);
        if (!$message) {
            return $this->failed('不通过原因不能为空');
        }

        $model->status = PsResidentAudit::AUDIT_NO_PASS;
        $model->reason = $message;
        $model->operator = $operator['id'];
        $model->operator_name = $operator['username'];
        $model->unaccept_at = time();

        if (!$model->save()) {
            return $this->failed(array_values($model->errors)[0][0]);
        }

        PsResidentHistory::model()->addHistory($model, ['id' => $operator['id'], 'name' => $operator['username']]);

        // 保存日志
        $log = [
            "community_id" => $model['community_id'],
            "operate_menu" => "住户管理",
            "operate_type" => "审核不通过",
            "operate_content" => $model->name . " " . (PsCommon::isVirtualPhone($model->mobile) === true ? '' : $model->mobile),
        ];
        OperateService::addComm($operator, $log);

        return $this->success();
    }

    // 审核bu通过删除
    public function auditDel($id, $userinfo = '')
    {
        $model = PsResidentAudit::findOne(['id' => $id, 'community_id' => $this->communityId]);
        if (empty($model)) {
            return $this->failed('删除失败，数据不存在');
        }

        $name = $model->name;
        $mobile = $model->mobile;
        if (!$model || !$model->delete()) {
            return $this->failed('删除失败');
        }
        // 保存日志
        $log = [
            "community_id" => $model['community_id'],
            "operate_menu" => "住户管理",
            "operate_type" => "删除住户",
            "operate_content" => $name . " " . (PsCommon::isVirtualPhone($mobile) === true ? '' : $mobile),
        ];
        OperateService::addComm($userinfo, $log);

        return $this->success();
    }

    // 审核通过迁入
    public function pass($id, $param, $operator)
    {
        $psResidentAudit = PsResidentAudit::find()->with('room')->where(['id' => $id, 'community_id' => $this->communityId])->one();
        if (!$psResidentAudit) {
            return $this->failed('记录不存在');
        }
        $roomId = PsCommon::get($param, 'room_id');
        $r = $this->_addCheck($psResidentAudit->community_id, $roomId, $psResidentAudit->mobile, null, $psResidentAudit->name);
        if (!$r['code']) {
            return $this->failed($r['msg']);
        }

        // wyf 20190515 新增 同一个人只能新增一套房间，审核失败（不能进行新增房屋），待审核（只能先审核）
        if (!empty($psResidentAudit->member_id)) {
            RoomUserService::checkRoomExist($roomId, $psResidentAudit->member_id, 1);
        }

        $psResidentAudit->status = PsResidentAudit::AUDIT_PASS;
        $psResidentAudit->room_id = $param['room_id'];
        $psResidentAudit->identity_type = $param['identity_type'];
        if ($param['identity_type'] == 3) {
            $psResidentAudit->time_end = strtotime($param['end_time'] . " 23:59:59") ?: 0;
            if ($psResidentAudit->time_end != 0 && $psResidentAudit->time_end < time()) {
                return $this->failed('有效期不能小于当前时间');
            }
        } else {
            $psResidentAudit->time_end = 0;
        }
        $psResidentAudit->operator = $operator['id'];
        $psResidentAudit->operator_name = $operator['username'];
        $psResidentAudit->accept_at = time();
        $psResidentAudit->save();

        $psRoomUser = new PsRoomUser;
        $room = PsCommunityRoominfo::findOne($roomId);
        $auth_status = RoomUserService::getMemberStatus($psResidentAudit->member_id, $psResidentAudit->name, $psResidentAudit->mobile);
        $value = [
            'community_id' => $this->communityId,
            'room_id' => $psResidentAudit->room_id,
            'member_id' => $psResidentAudit->member_id,
            'name' => $psResidentAudit->name,
            'mobile' => $psResidentAudit->mobile,
            'card_no' => $psResidentAudit->card_no,
            'sex' => $psResidentAudit->sex,
            'group' => $room->group,
            'building' => $room->building,
            'room' => $room->room,
            'unit' => $room->unit,
            'identity_type' => $psResidentAudit->identity_type,
            'status' => $auth_status,
            'auth_time' => $auth_status == 2 ? time() : '0',
            'time_end' => $psResidentAudit->time_end,
            'operator_id' => $operator['id'],
            'operator_name' => $operator['username'],
        ];
        $psRoomUser->attributes = $value;
        if (!$psRoomUser->save()) {
            return $this->failed($psRoomUser->getErrors());
        }
        //推送到供应商

        MemberService::service()->turnReal($psResidentAudit->member_id);

        PsResidentHistory::model()->addHistory($psRoomUser, ['id' => $operator['id'], 'name' => $operator['username']], true);
        //保存日志
        $log = [
            "community_id" => $this->communityId,
            "operate_menu" => "住户管理",
            "operate_type" => "住户迁入",
            "operate_content" => $psResidentAudit->name . " " . (PsCommon::isVirtualPhone($psResidentAudit->mobile) ? '' : $psResidentAudit->mobile)
        ];
        OperateService::addComm($operator, $log);
        
        return $this->success();
    }

    // 迁出后迁入
    public function moveIn($id, $param, $operator)
    {
        $model = PsRoomUser::findOne(['id' => $id, 'community_id' => $this->communityId]);
        if (!$model) {
            return $this->failed('数据不存在');
        }

        if ($this->repeatCheck($this->communityId, $model['room_id'], $model['mobile'], $id)) {
            return $this->failed('同一个房屋下手机号无法重复');
        }

        if ($param['identity_type'] == 3) {
            $time_end = !empty($param['end_time']) ? strtotime($param['end_time'] . " 23:59:59") : 0;
            if ($time_end != 0 && $time_end <= strtotime(date('Y-m-d 23:59:59'))) {
                return $this->failed('有效期必须大于当天');
            }
        } else {
            $time_end = 0;
        }

        // wyf 20190515 新增 同一个人只能新增一套房间，审核失败（不能进行新增房屋），待审核（只能先审核）
        if (!empty($model->member_id)) {
            $checkRoomUserInfo = RoomUserService::checkRoomUserExist($param['room_id'], $model->member_id);
            if ($checkRoomUserInfo) {
                if ($checkRoomUserInfo['id'] != $id) {
                    throw new MyException('房屋信息已存在');
                } else {
                    // 查询审核表中的信息是否存在
                    RoomUserService::checkRoomExist($param['room_id'], $model->member_id, 2);
                }
            }
            if ($model->room_id != $param['room_id']) {
                RoomUserService::checkRoomExist($param['room_id'], $model->member_id, 2);
            }
        }

        // 查看此用户是否已经认证过房屋，已认证过，直接改为认证
        $isAuth = $this->isAuthByNameMobile($model->community_id, $model->name, $model->mobile);
        $value = [
            'room_id' => $param['room_id'] ?? $model['room_id'],
            'group' => $param['group'] ?? $model['group'],
            'building' => $param['building'] ?? $model['building'],
            'room' => $param['room'] ?? $model['room'],
            'unit' => $param['unit'] ?? $model['unit'],
            'identity_type' => $param['identity_type'] ?? $model['identity_type'],
            // 状态验证
            'status' => $isAuth ? 2 : 1,
            'auth_time' => $isAuth ? time() : 0,
            'out_time' => 0,
            'time_end' => $time_end,
            'operator_id' => $operator['id'],
            'operator_name' => $operator['truename'],
        ];

        $model->attributes = $value;
        if (!$model->save()) {
            return $this->failed();
        }

        MemberService::service()->turnReal($model['member_id']);
        //保存日志
        $log = [
            "community_id" => $this->communityId,
            "operate_menu" => "住户管理",
            "operate_type" => "住户迁入",
            "operate_content" => $model->name . " " . (PsCommon::isVirtualPhone($model->mobile) === true ? '' : $model->mobile)
        ];
        OperateService::addComm($operator, $log);

        return $this->success();
    }

    // 迁出
    public function moveOut($id, $userInfo, $communityId = '')
    {
        $communityId = $this->communityId ? $this->communityId : $communityId;
        $model = PsRoomUser::findOne(['id' => $id, 'community_id' => $communityId]);
        if (!$model) {
            return $this->failed('数据不存在');
        }

        $model->status = PsRoomUser::UNAUTH_OUT;
        $model->out_time = time();

        if (!$model->save()) {
            return $this->failed($this->getError($model));
        }

        // 业主迁出，则对应该房屋下所有的家人，租客都迁出，重新添加
        if ($model->identity_type == 1) {
            PsRoomUser::updateAll(['status' => PsRoomUser::UNAUTH_OUT, 'out_time' => time()], ['room_id' => $model['room_id'], 'identity_type' => [2, 3], 'status' => PsRoomUser::UN_AUTH]);
            PsRoomUser::updateAll(['status' => PsRoomUser::AUTH_OUT, 'out_time' => time()], ['room_id' => $model['room_id'], 'identity_type' => [2, 3], 'status' => PsRoomUser::AUTH]);
        }
        // 添加变更历史
        PsResidentHistory::model()->addHistory($model, ['id' => $userInfo['id'], 'name' => $userInfo['username']], true);

        // 保存日志
        $log = [
            "community_id" => $this->communityId,
            "operate_menu" => "住户管理",
            "operate_type" => "住户迁出",
            "operate_content" => $model->name . " " . (PsCommon::isVirtualPhone($model->mobile) === true ? '' : $model->mobile)
        ];
        OperateService::addComm($userInfo, $log);

        return $this->success();
    }

    // 投票 - 业主列表
    public function getList($reqArr, $page, $pageSize)
    {
        $totals = PsRoomUser::model()->get($reqArr, true)->count();
        if (!$totals) {
            return ['list' => [], 'totals' => []];
        }
        $data = PsRoomUser::model()->get($reqArr, true)
            ->select('id, member_id, room_id, name, identity_type, status, sex, mobile, group, building, unit, room')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->asArray()->all();

        foreach ($data as $key => $v) {
            $data[$key]['address'] = $v['group'] . $v['building'] . $v['unit'] . $v['room'];
            $data[$key]['identity_type_desc'] = $v['identity_type'] ? PsCommon::getIdentityType($v['identity_type'], 'key') : "-";
            $data[$key]['status_desc'] = $v['status'] ? PsCommon::getIdentityStatus($v['status']) : "-";
            $data[$key]['mobile'] = PsCommon::isVirtualPhone($v['mobile']) ? '' : $v['mobile'];
        }
        return ["totals" => $totals, 'list' => $data];
    }

    // 导出数据
    public function exportList($params)
    {
        // 标签处理
        if (!empty($params['user_label_id']) && is_array($params['user_label_id'])) {
            $labelRela = PsLabelsRela::find()->where(['in', 'labels_id', $params['user_label_id']])
                ->andWhere(['data_type' => 2])->asArray()->all();
            $room_user_id = array_unique(array_column($labelRela, 'data_id'));
            if (empty($room_user_id)) {
                return [];
            }
            $params['id'] = $room_user_id;
        }

        $models = PsRoomUser::model()->get($params)
            ->select('id, name, enter_time, face, marry_status, household_type, nation, live_type, mobile,
            sex, status, card_no, group, building, unit, room, identity_type, auth_time, time_end')
            ->orderBy('id desc')->asArray()->all();
        $arr = [];
        foreach ($models as $k => $v) {
            $label_user = PsLabelsRela::find()->alias('A')->select('name')
                ->innerJoin('ps_labels B', 'B.id = A.labels_id')
                ->where(['data_id' => $v['id'], 'data_type' => 2])->asArray()->all();
            if (!empty($label_user)) {
                $label_name = implode(array_unique(array_column($label_user, 'name')), ',');
                $v['label_name'] = $label_name;
            } else {
                $v['label_name'] = '';
            }
            $v['enter_time'] = !empty($v['enter_time']) ? date('Y-m-d', $v['enter_time']) : '';
            $v['auth_time'] = !empty($v['auth_time']) ? date('Y-m-d H:i:s', $v['auth_time']) : '';
            $v['time_end'] = $v['time_end'] ? date('Y-m-d', $v['time_end']) : '长期';
            if (!empty($v['sex'])) {
                $v['sex'] = $v['sex'] == 1 ? '男' : '女';
            }
            $v['face'] = !empty($v['face']) ? $this->face[$v['face']] : '';
            $v['marry_status'] = !empty($v['marry_status']) ? $this->marry_status[$v['marry_status']] : '';
            $v['household_type'] = !empty($v['household_type']) ? $this->household_type[$v['household_type']] : '';
            $v['live_type'] = !empty($v['live_type']) ? $this->live_type[$v['live_type']] : '';
            $v['nation'] = !empty($v['nation']) ? PsNation::find()->where(['id' => $v['nation']])->one()->name : '';
            $v['mobile'] = PsCommon::isVirtualPhone($v['mobile']) ? '' : $v['mobile'];
            $arr[] = $v;
        }

        return $arr;
    }

    // 住户重复验证，一个房屋下的迁入住户手机号无法重复
    private function repeatCheck($communityId, $roomId, $mobile, $id = false)
    {
        return PsRoomUser::find()
            ->where(['community_id' => $communityId, 'room_id' => $roomId, 'mobile' => $mobile, 'status' => [1, 2]])
            ->andFilterWhere(['<>', 'id', $id])
            ->exists();
    }

    // 住户重复验证，一个房屋下的迁入住户姓名无法重复
    private function repeatCheckName($communityId, $roomId, $name, $id = false)
    {
        return PsRoomUser::find()
            ->where(['community_id' => $communityId, 'room_id' => $roomId, 'name' => $name, 'status' => [1, 2]])
            ->andFilterWhere(['<>', 'id', $id])
            ->exists();
    }

    // 新增(编辑)用户检查规则(已失效不在检查范围)
    private function _addCheck($communityId, $roomId, $mobile, $id = null, $name = null)
    {
        if (!$communityId) {
            return $this->failed('小区ID不能为空');
        }

        if (!$roomId) {
            return $this->failed('房屋不能为空');
        }

        if (!$mobile) {
            return $this->failed('手机号不能为空');
        }

        if (!PsCommunityRoominfo::find()->where(['id' => $roomId])->exists()) {
            return $this->failed('房屋不存在');
        }

        if ($this->repeatCheck($communityId, $roomId, $mobile, $id)) {
            return $this->failed('同一个房屋下手机号无法重复');
        }

        if ($this->repeatCheckName($communityId, $roomId, $name, $id)) {
            //return $this->failed('同一个房屋下姓名无法重复');
        }

        return $this->success();
    }

    // 新增到ps_room_user表
    private function _saveRoomUser($data, $userInfo)
    {
        $communityId = PsCommon::get($data, 'community_id');
        $roomId = (integer)PsCommon::get($data, 'room_id');
        $mobile = trim(PsCommon::get($data, 'mobile'));
        $name = trim(PsCommon::get($data, 'name'));
        $label_id = PsCommon::get($data, 'user_label_id');

        $r = $this->_addCheck($communityId, $roomId, $mobile, null, $name);
        if (!$r['code']) {
            return $this->failed($r['msg']);
        }

        $r = MemberService::service()->saveMember([
            'name' => $name,
            'mobile' => $mobile,
            'card_no' => PsCommon::get($data, 'card_no'),
            'sex' => !empty($data['sex']) ? $data['sex'] : 1,
            'face_url' => PsCommon::get($data, 'face_url', '')
        ]);

        if (!$r['code']) {
            return $this->failed($r['msg']);
        }
        
        $memberId = (integer)$r['data'];
        // wyf 20190515 新增 同一个人只能新增一套房间，审核失败（不能进行新增房屋），待审核（只能先审核）
        if (!empty($memberId)) {
            RoomUserService::checkRoomExist($roomId, $memberId, 3);
        }

        $isAuth = $this->isAuthByNameMobile($communityId, $name, $mobile);

        $model = new PsRoomUser();
        $et = PsCommon::get($data, 'enter_time');
        $data['enter_time'] = $et ? strtotime($et) : 0;
        $data['sex'] = !empty($data['sex']) ? $data['sex'] : 1;
        $data['member_id'] = $memberId;
        $data['operator_id'] = PsCommon::get($userInfo, 'id');
        $data['operator_name'] = PsCommon::get($userInfo, 'truename');
        $data['status'] = $isAuth ? 2 : 1; // 新增默认未认证状态
        $data['auth_time'] = $isAuth ? time() : 0;
        if (!empty($data['identity_type']) && ($data['identity_type'] == 1 || $data['identity_type'] == 2)) {
            $data['time_end'] = 0;
        } else {
            $time_end = PsCommon::get($data, 'time_end');
            $time_end = $time_end ? strtotime($time_end . ' 23:59:59') : 0;
            $data['time_end'] = (integer)$time_end;
        }

        $model->load($data, '');

        if ($model->identity_type == 3) {
            $model->setScenario('renter');
        } elseif ($model->identity_type == 2) {
            $model->setScenario('family');
        }
        if ($model->validate() && $model->save()) {
            // 验证标签
            if (!empty($label_id)) {
                $relation = LabelsService::service()->addRelation($model->id, $label_id, 2);
                if (!$relation) {
                    return $this->failed('标签错误');
                }
            }

            return $this->success($model->toArray());
        }
        return $this->failed($this->getError($model));
    }

    //搜索认证的业主(发放优惠券选择业主)
    public function searchAuthUsers($params, $page, $pageSize)
    {
        $data = PsRoomUser::find()
            ->select('id, name, mobile, sex, community_id, member_id')
            ->with('community')
            ->where(['status' => PsRoomUser::AUTH])
            ->andFilterWhere(['community_id' => PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['like', 'name', PsCommon::get($params, 'name')])
            ->andFilterWhere(['like', 'mobile', PsCommon::get($params, 'mobile')])
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->orderBy('member_id asc')->groupBy('member_id')
            ->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $v['sex'] = PsCommon::get($this->sexes, $v['sex']);
            $v['community'] = $v['community'] ? $v['community'] : [];
            $result[] = $v;
        }
        return $result;
    }

    //搜索认证业主数量
    public function searchAuthUsersCount($params)
    {
        return PsRoomUser::find()
            ->where(['status' => PsRoomUser::AUTH])
            ->andFilterWhere(['community_id' => PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['like', 'name', PsCommon::get($params, 'name')])
            ->andFilterWhere(['like', 'mobile', PsCommon::get($params, 'mobile')])
            ->groupBy('member_id')
            ->count();
    }

    //业主卡号
    public function getUserCard()
    {
        $redis = Yii::$app->redis;
        $key = 'user_cards2';
        $no = $redis->incr($key);
        return str_pad($no, 8, '0', STR_PAD_LEFT);
    }

    //获取小区住户，名称，房屋ID(批量导入住户的时候判断是否重复)
    public function getMobileNames($communityId)
    {
        $data = PsRoomUser::find()
            ->select('mobile, room_id, name')
            ->where(['community_id' => $communityId, 'status' => [PsRoomUser::AUTH, PsRoomUser::UN_AUTH]])
            ->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $result[$v['room_id']][$v['mobile']][$v['name']] = 1;
        }
        return $result;
    }
    
    // 获取基本下拉信息
    public function getOption()
    {
        $data['change_detail'] = $this->getForeach($this->change_detail);
        $data['face'] = $this->getForeach($this->face);
        $data['household_type'] = $this->getForeach($this->household_type);
        $data['identity_type'] = $this->getForeach($this->identity_type);
        $data['live_detail'] = $this->getForeach($this->live_detail);
        $data['live_type'] = $this->getForeach($this->live_type);
        $data['marry_status'] = $this->getForeach($this->marry_status);

        return $data;
    }

    public function getForeach($data)
    {
        $arr = [];
        foreach ($data as $k => $v) {
            $arr[] = ['id' => $k, 'name' => $v];
        }
        return $arr;
    }

    // 获取民族
    public function getNation()
    {
        return PsNation::find()->asArray()->all();
    }

    //获取认证状态码，判断跳转逻辑, 1 已认证, 2 填手机号页面, 3 业主管理页面
    public function getAuthStatus($memberId, $communityId)
    {
        if (!$memberId) return 2;
        $data = PsRoomUser::find()->select('count(1) as c, status')
            ->where(['community_id' => $communityId, 'member_id' => $memberId])
            ->groupBy('status')->indexBy('status')
            ->asArray()->all();
        if (!empty($data[PsRoomUser::AUTH])) {//有认证的房屋，正常流程
            return 1;
        }
        //是否有审核信息
        $audits = PsResidentAudit::find()->where([
            'community_id' => $communityId,
            'member_id' => $memberId,
            'status' => [PsResidentAudit::AUDIT_NO_PASS, PsResidentAudit::AUDIT_WAIT]
        ])->count();
        if (!$audits && empty($data[PsRoomUser::UNAUTH_OUT]) && empty($data[PsRoomUser::AUTH_OUT])) {// 没有审核信息，且没有迁出信息
            return 2;
        }
        return 3;
    }

    //是否认证
    public function isAuth($memberId, $communityId = 0)
    {
        if (!$memberId) return false;
        $model = PsRoomUser::find()
            ->where(['member_id' => $memberId, 'status' => PsRoomUser::AUTH]);
        if ($communityId) {
            $model->andWhere(['community_id' => $communityId]);
        }
        $re = $model->andWhere(['OR', ['time_end' => 0], ['>', 'time_end', time()]])
            ->exists();
        return $re;
    }

    //当前小区下是否有已认证的房屋
    public function isAuthByNameMobile($communityId, $name, $mobile)
    {
        //5.1 edit by wenchao.feng 认证状态不区分小区，只有有认证过的房屋，默认为认证状态
        $memberModel = PsRoomUser::find()
            ->where(['name' => $name, 'mobile' => $mobile, 'status' => PsRoomUser::AUTH])
            ->asArray()
            ->one();
        //$member = PsMember::find()->where(['name' => $name,'mobile'=>$mobile])->asArray()->one();
        //有一套房认证，或者业主是实名认证的
        //if ($memberModel || $member['is_real']==1) {
        if ($memberModel) {
            return true;
        }

        return PsRoomUser::find()
            ->where([
                //'community_id' => $communityId,
                'name' => $name,
                'mobile' => $mobile,
                'status' => PsRoomUser::AUTH,
            ])->andWhere(['OR', ['time_end' => 0], ['<=', 'time_end', time()]])
            ->exists() || PsResidentAudit::find()->where([
            'community_id' => $communityId,
            'name' => $name,
            'mobile' => $mobile,
            'status' => PsResidentAudit::AUDIT_WAIT,
        ])->exists();
    }

    // 导入检查
    public function importCheck($residentData, $allRooms, $nationNames, $labelNames)
    {
        if (empty($allRooms[$residentData['group']][$residentData['building']][$residentData['unit']][$residentData['room']])) {
            return $this->failed('未找到系统内对应得小区的房屋信息');
        }
        $result['room_id'] = $allRooms[$residentData['group']][$residentData['building']][$residentData['unit']][$residentData['room']];

        $result['identity_type'] = PsCommon::getIdentityType($residentData['identity_type'], 'value');
        if (!$result['identity_type']) {
            return $this->failed('身份错误');
        }
        $timeEnd = PsCommon::get($residentData, 'time_end');
        $result['time_end'] = 0;
        if ($result['identity_type'] == 3) {//租客才有有效期
            if ($timeEnd != '长期') {
                if (strtotime($timeEnd . ' 23:59:59') <= strtotime(date('Y-m-d') . ' 23:59:59')) {
                    return $this->failed('有效期必须大于当天');
                }
                $result['time_end'] = strtotime($timeEnd . ' 23:59:59');
            }
        }
        if (!empty($residentData["nation"])) {//民族处理
            $result['nation'] = PsCommon::get($nationNames, $residentData['nation']);
            if (!$result['nation']) {
                return $this->failed('民族信息错误');
            }
        }
        if (!empty($residentData["face"])) {//政治面貌
            $result['face'] = PsCommon::getFlipKey(ResidentService::service()->face, $residentData["face"]);
            if (!$result['face']) {
                return $this->failed('政治面貌错误');
            }
        }
        if (!empty($residentData["household_type"])) {//户口类型
            $result['household_type'] = PsCommon::getFlipKey(ResidentService::service()->household_type, $residentData['household_type']);
            if (!$result['household_type']) {
                return $this->failed('户口类型错误');
            }
        }
        if (!empty($residentData["live_type"])) {//居住类型
            $result['live_type'] = PsCommon::getFlipKey(ResidentService::service()->live_type, $residentData['live_type']);
            if (!$result['live_type']) {
                return $this->failed('居住类型错误');
            }
        }
        if (!empty($residentData['marry_status'])) {//婚姻情况
            $result['marry_status'] = PsCommon::getFlipKey(ResidentService::service()->marry_status, $residentData['marry_status']);
            if (!$result['marry_status']) {
                return $this->failed('婚姻情况错误');
            }
        }
        if (!empty($residentData["enter_time"])) {//入住时间
            $result['enter_time'] = strtotime($residentData["enter_time"]);
            if (!$result['enter_time']) {
                return $this->failed('入住时间错误');
            }
        }
        //标签处理
        $result['label_id'] = [];
        if (!empty($residentData['label_name'])) {
            $label_name = explode(',', F::sbcDbc($residentData['label_name'], 1));
            if (!$label_name) {
                return $this->failed('标签错误');
            }
            $label_error = false;
            foreach ($label_name as $ln) {
                if (!isset($labelNames[$ln])) {
                    $label_error = true;
                    break;
                }
                $result['label_id'][] = $labelNames[$ln];
            }
            if ($label_error) {//任何一个标签错误
                return $this->failed('标签错误');
            }
        }
        return $this->success($result);
    }

    /**
     * 业主变更处理结果发送生活号模板消息
     * @param string $communityId 小区id
     * @param string $appUserId 支付宝用户id
     * @param string $result 处理结果 已变更|已驳回
     * @param string $reason 驳回原因 请尽快完成业主认证|驳回原因
     * @return bool|string
     */
    public function sendAlipayTempMsg($communityId, $memberId, $result, $reason)
    {
        $appUserId = MemberService::service()->getAppUserId($memberId);
        if (!$appUserId) {
            return false;
        }
        $lifeService = PsLifeServices::find()
            ->select(['id', 'add_type', 'ali_status', 'has_online_apply', 'mechart_private_key', 'alipay_public_key'])
            ->where(['community_id' => $communityId, 'status' => 2])
            ->asArray()
            ->one();
        if (!$lifeService) {
            return false;
        }
        if ($lifeService['add_type'] == 1) {
            if (!$lifeService['mechart_private_key'] || !$lifeService['alipay_public_key']) {
                return false;
            }
        } elseif ($lifeService['add_type'] == 2) {
            if ($lifeService['has_online_apply'] != 1) {
                return false;
            }
        }

        //查询生活号有没有领取业主变更模板
        $tmpl = PsLifeServicesTemplate::find()
            ->where(['life_id' => $lifeService['id']])
            ->andWhere(['template_id' => Yii::$app->params['temp_msg_member_room_change']])
            ->asArray()
            ->one();
        if (!$tmpl) {
            return false;
        }

        $appUser = PsAppUser::find()
            ->select(['channel_user_id'])
            ->where(['id' => $appUserId])
            ->asArray()
            ->one();
        if (!$appUser) {
            return false;
        }

        $reqArr['life_id'] = $lifeService['id'];
        $reqArr['to_user_id'] = $appUser['channel_user_id'];
        $reqArr['template'] = [
            'template_id' => $tmpl['msg_template_id'],
            'context' => [
                'head_color' => '#0200d9',
                'keyword1' => [
                    'color' => '#0200d9',
                    'value' => $result
                ],
                'keyword2' => [
                    'color' => '#0200d9',
                    'value' => $reason
                ],
                'first' => [
                    'color' => '#0200d9',
                    'value' => '亲爱的业主：您提交的业主变更信息已处理。'
                ],
                'remark' => [
                    'color' => '#000000',
                    'value' => '感谢您的支持'
                ],
            ]
        ];
        $sendRe = LifeNoService::service()->sendTemplateMessage($reqArr);
        return $sendRe;
    }

    // ==================================== 生活号 ================================

    //住户信息
    public function showOne($id, $communityId)
    {
        $data = PsRoomUser::find()
            ->select('id, room_id, group, building, unit, room, name, mobile, card_no, identity_type, time_end, status')
            ->where(['id' => $id, 'community_id' => $communityId])->asArray()->one();
        if (!$data) return null;
        $data['time_end'] = $data['time_end'] ? date('Y-m-d', $data['time_end']) : '0';
        $data['identity_type_des'] = PsCommon::getIdentityType($data['identity_type'], 'key');
        return $data;
    }

    //业主审核数据（待审核，不通过）
    public function getAudits($memberId, $communityId)
    {
        $data = PsResidentAudit::find()->alias('t')
            ->leftJoin(['r' => PsCommunityRoominfo::tableName()], 't.room_id=r.id')
            ->select('t.id, t.room_id, r.group, r.building, r.unit, r.room, t.identity_type, t.time_end, t.status')
            ->where([
                't.community_id' => $communityId,
                't.member_id' => $memberId,
                't.status' => [PsResidentAudit::AUDIT_WAIT, PsResidentAudit::AUDIT_NO_PASS]
            ])->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $v['time_end_des'] = $v['time_end'] ? date('Y-m-d', $v['time_end']) : '长期';
            $v['identity_type_des'] = PsCommon::getIdentityType($v['identity_type'], 'key');
            $result[] = $v;
        }
        return $result;
    }

    //业主住户房屋数据
    public function getRooms($memberId, $communityId)
    {
        $data = PsRoomUser::find()
            ->select('id, room_id, group, building, unit, room, time_end, identity_type, status')
            ->where([
                'community_id' => $communityId,
                'member_id' => $memberId,
            ])->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $v['time_end_des'] = $v['time_end'] ? date('Y-m-d', $v['time_end']) : '长期';
            $v['identity_type_des'] = PsCommon::getIdentityType($v['identity_type'], 'key');
            $result[] = $v;
        }
        return $result;
    }

    //审核唯一性判断: 同一个房屋，同一个人不可以有两条待审核的数据
    protected function auditUnique($memberId, $roomId)
    {
        $flag = PsResidentAudit::find()->where([
            'member_id' => $memberId,
            'room_id' => $roomId,
            'status' => PsResidentAudit::AUDIT_WAIT
        ])->exists();
        return $flag ? false : true;
    }

    //生活号 房屋认证重新提交
    public function recommit($appUserId, $communityId, $params, $id, $rid)
    {
        $member = MemberService::service()->getInfoByAppUserId($appUserId);
        if (!$member) {
            return $this->failed('帐号不存在', 50009);
        }
        //var_dump($rid);var_dump($member);die;
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            if ($rid) {//迁出数据重新审核，需要删除原先的迁出数据
                $r = $this->delete($rid, ['id' => $member['id'], 'mobile' => $member['mobile'], 'truename' => $member['name']]);
                if (!$r['code']) {
                    throw new Exception($r['msg']);
                }
            }
            $r = $this->saveAudit($appUserId, $communityId, $params, $id);
            if (!$r['code']) {
                throw new Exception($r['msg']);
            }
            $trans->commit();
            return $this->success();
        } catch (Exception $e) {
            $trans->rollback();
            return $this->failed($e->getMessage());
        }
    }

    //新增/编辑 房屋认证审核信息
    public function saveAudit($appUserId, $communityId, $params, $id = false)
    {
        $member = MemberService::service()->getInfoByAppUserId($appUserId);
        if (!$member) {
            return $this->failed('帐号不存在', 50009);
        }
        if ($id) {//审核失败的才可以编辑
            $model = PsResidentAudit::find()
                ->where(['id' => $id, 'community_id' => $communityId, 'member_id' => $member['id'], 'status' => PsResidentAudit::AUDIT_NO_PASS])->one();
            if (!$model) {
                return $this->failed('数据不存在');
            }
            $model->status = PsResidentAudit::AUDIT_WAIT;
        } else {//
            $roomId = PsCommon::get($params, 'room_id');
            if (!$roomId) {
                return $this->failed('房屋ID不能为空');
            }
            if (!$this->auditUnique($member['id'], $roomId)) {
                return $this->failed('已提交过审核，审核结果将以短信形式通知，请耐心等待');
            }
            $model = new PsResidentAudit();
            $model->member_id = $member['id'];
            $model->create_at = time();
        }

        $time_end = 0;
        //租客的时候才会必填有效期，其余默认填0，即永久
        if ($params['identity_type'] == 3) {
            $time_end = !empty($params['time_end']) ? strtotime($params['time_end'] . ' 23:59:59') : 0;
        }
        $params['time_end'] = $time_end;
        $model->operator = $member['id'];
        $model->operator_name = $member['name'];
        $model->sex = $member['sex'];
        $model->update_at = time();
        $images = array_filter(explode(',', $params['images']));
        unset($params['images']);
        if (count($images) > 5) {
            return $this->failed('证件图片最多5张');
        }
        $model->images = implode(',', $images);
        $model->load($params, '');
        if (!$model->validate() || !$model->save()) {
            return $this->failed($this->getError($model));
        }
        //设置默认选择的房屋
        PsMember::updateAll(['room_id' => $params['room_id']], ['id' => $member['id'], 'room_id' => '0']);
        //添加历史提交记录
        PsResidentHistory::model()->addHistory($model, $member);
        //新增消息
        $room_info = \app\services\CommunityService::getCommunityRoominfo($params['room_id']);
        $data = [
            'community_id' => $communityId,
            'id' => 0,
            'member_id' => $member['id'],
            'user_name' => $member['name'],
            'create_user_type' => 2,

            'remind_tmpId' => 6,
            'remind_target_type' => 6,
            'remind_auth_type' => 6,
            'msg_type' => 2,

            'msg_tmpId' => 6,
            'msg_target_type' => 6,
            'msg_auth_type' => 6,
            'remind' => [
                0 => '123456'
            ],
            'msg' => [
                0 => CommunityService::service()->getCommunityName($communityId)['name'],
                1 => $room_info['group'] . '' . $room_info['building'] . '' . $room_info['unit'] . $room_info['room'],
                2 => $member['name'],
                3 => PsCommon::getIdentityType($params['identity_type'], 'key'),
                4 => date("Y-m-d H:i:s", time())
            ]
        ];

        MessageService::service()->addMessageTemplate($data);

        return $this->success();
    }

    //认证的住户信息
    public function getAuthRoom($roomId, $appUserId, $communityId)
    {
        $memberId = MemberService::service()->getMemberId($appUserId);
        if (!$memberId) return null;
        $result = PsRoomUser::find()->select('id, identity_type, status, name, mobile, time_end, group, building, unit, room')
            ->where(['community_id' => $communityId, 'room_id' => $roomId, 'member_id' => $memberId, 'status' => PsRoomUser::AUTH])
            ->asArray()->one();
        if ($result) {
            $result['identity_type_desc'] = PsCommon::getIdentityType($result['identity_type'], 'key');
            $result['status_desc'] = PsCommon::getIdentityStatus($result['status']);
            $result['time_end'] = $result['time_end'] ? date('Y-m-d', $result['time_end']) : '长期';
        }
        return $result;
    }

    //认证并且是业主的房屋
    public function getAuthRooms($appUserId, $communityId)
    {
        $memberId = MemberService::service()->getMemberId($appUserId);
        if (!$memberId) return [];
        return PsRoomUser::find()->select('room_id as id, group, building, unit, room')
            ->where(['community_id' => $communityId, 'member_id' => $memberId])
            ->andWhere(['identity_type' => 1, 'status' => PsRoomUser::AUTH])
            ->asArray()->all();
    }

    //子住户列表(家人，租客)
    public function getChildResidents($roomId)
    {
        $data = PsRoomUser::find()
            ->select('id, identity_type, status, name, mobile, time_end')
            ->where(['room_id' => $roomId, 'identity_type' => [2, 3]])
            ->orderBy('identity_type asc, status asc, id desc')
            ->asArray()->all();

        $result = [];
        foreach ($data as $v) {
            $v['identity_type_desc'] = PsCommon::getIdentityType($v['identity_type'], 'key');
            $v['status_desc'] = PsCommon::getIdentityStatus($v['status']);
            $v['time_end'] = $v['time_end'] ? date('Y-m-d', $v['time_end']) : '长期';
            $result[] = $v;
        }
        return $this->success($result);
    }

    //创建住户(生活号)
    public function createResident($appUserId, $communityId, $roomId, $params)
    {
        $parent = $this->getAuthRoom($roomId, $appUserId, $communityId);
        if (!$parent || ($parent['identity_type'] != 1)) {
            return $this->failed('您不是该房屋下的业主，没有操作权限');
        }
        $par = [
            'community_id' => $communityId,
            'room_id' => $roomId,
            'group' => $parent['group'],
            'building' => $parent['building'],
            'unit' => $parent['unit'],
            'room' => $parent['room'],
        ];
        $rp = array_merge($par, $params);
        $r = $this->_saveRoomUser($rp, ['id' => $parent['id'], 'truename' => $parent['name']]);
        if ($r['code']) {
            //发送短信
            if ($params['identity_type'] == 2) {
                $identityTypeLabel = '家人';
            } elseif ($params['identity_type'] == 3) {
                $identityTypeLabel = '租客';
            } else {
                $identityTypeLabel = '';
            }
            $communityName = CommunityService::service()->getCommunityName($communityId);
            if (!PsCommon::isVirtualPhone($params['mobile'])) {
                SmsService::service()->init(32, $params['mobile'])->send([$params['name'], $communityName['name'], $parent['name'], $identityTypeLabel]);
            }
            return $this->success();
        }
        return $this->failed($r['msg']);
    }

    //编辑住户(生活号)
    public function editResident($id, $appUserId, $communityId, $roomId, $params)
    {
        $parent = $this->getAuthRoom($roomId, $appUserId, $communityId);
        if (!$parent || ($parent['identity_type'] != 1)) {
            return $this->failed('您不是该房屋下的业主，没有操作权限');
        }
        $mobile = PsCommon::get($params, 'mobile');
        //
        $model = PsRoomUser::find()->where([
            'id' => $id,
            'identity_type' => [2, 3],
            'status' => [PsRoomUser::UN_AUTH, PsRoomUser::UNAUTH_OUT, PsRoomUser::AUTH_OUT]])->one();
        $name = PsCommon::get($params, 'name');
        $identity_type = $model['identity_type'];
        if (!$model) return $this->failed('住户不存在');
        $old_mobile = $model->mobile;
        $checkMobile = PsCommon::isVirtualPhone($old_mobile);
        if (empty($mobile)) {

            if (!$checkMobile) {
                return $this->failed("手机号不能为空");
            } else {
                $mobile = $old_mobile;
            }
        } else {
            if (!preg_match(Regular::phone(), $mobile)) {
                return $this->failed("手机号格式错误");
            }
            $need_del = 1;
            if ($checkMobile) {
                /** 20190805 wyf start 要删除的旧手机号的member用户信息 */
                $oldModel = [
                    'community_id' => $communityId,
                    'identity_type' => $model->identity_type,
                    'member_id' => $model->member_id,
                    'time_end' => $model->time_end,
                    'room_id' => $model->room_id,
                    'name' => $model->name,
                    'mobile' => $old_mobile,
                    'sex' => $model->sex,
                ];
                /** 20190805 wyf end 要删除的旧手机号的member用户信息 */
            }
        }
        $r = $this->_addCheck($communityId, $roomId, $mobile, $id, $name);
        if (!$r['code']) {
            return $this->failed($r['msg']);
        }
        //save
        $m = MemberService::service()->saveMember([
            'name' => $name,
            'mobile' => $mobile,
            'card_no' => PsCommon::get($params, 'card_no'),
        ]);
        if (!$m['code']) {
            return $this->failed($m['msg']);
        }
        $oldMobile = $model->mobile;
        $model->member_id = $m['data'];
        $model->card_no = PsCommon::get($params, 'card_no');
        $model->mobile = $mobile;
        $model->name = PsCommon::get($params, 'name');
        $model->sex = PsCommon::get($params, 'sex');
        $model->identity_type = PsCommon::get($params, 'identity_type');
        if ($model->identity_type == 1 || $model->identity_type == 2) {//业主或者家人，有效期变更为长期
            $model->time_end = 0;
        } else {
            $timeEnd = PsCommon::get($params, 'time_end');
            $timeEnd = $timeEnd ? strtotime($timeEnd . " 23:59:59") : 0;
            $model->time_end = (integer)$timeEnd;
        }
        if ($model->identity_type == 3) {
            $model->setScenario('renter');
        } elseif ($model->identity_type == 2) {
            $model->setScenario('family');
        }
        $model->update_at = time();
        $isAuth = $this->isAuthByNameMobile($communityId, $name, $mobile);
        if ($isAuth) {//如果新的名字+手机号已经认证过，则自动变更为已认证状态
            $model->auth_time = time();
            $model->status = PsRoomUser::AUTH;
            $model->auth_time = time();
        }
        //切换房屋
        if ($model->room_id != $roomId) {
            $model->room_id = $roomId;
            $roomInfo = RoomService::service()->getInfo($roomId);
            $model->group = $roomInfo['group'];
            $model->building = $roomInfo['building'];
            $model->unit = $roomInfo['unit'];
            $model->room = $roomInfo['room'];
        }
        if ($model->validate() && $model->save()) {
            if ($oldMobile != $mobile) {//手机号有变更，给新的手机号发送短信
                if ($params['identity_type'] == 2) {
                    $identityTypeLabel = '家人';
                } elseif ($params['identity_type'] == 3) {
                    $identityTypeLabel = '租客';
                } else {
                    $identityTypeLabel = '';
                }
                $communityName = CommunityService::service()->getCommunityName($communityId);
                if (!PsCommon::isVirtualPhone($mobile)) {
                    SmsService::service()->init(32, $mobile)->send([$params['name'], $communityName['name'], $parent['name'], $identityTypeLabel]);
                }
            }

            /** 20190805 wyf end 要删除的旧手机号的member用户信息 */
            return $this->success();
        }
        return $this->failed($this->getError($model));
    }

    //删除子用户
    public function removeChild($id, $app_user_id, $communityId = 0)
    {
        $memberId = MemberService::service()->getMemberId($app_user_id);
        if (!$memberId) {
            return $this->failed('用户不存在');
        }
        $roomUser = PsRoomUser::find()->where(['id' => $id])->one();
        if (!$roomUser) {
            return $this->failed('数据不存在');
        }
        $roomId = $roomUser['room_id'];
        $flag = PsRoomUser::find()
            ->where(['room_id' => $roomId, 'member_id' => $memberId,
                'identity_type' => 1, 'status' => PsRoomUser::AUTH])
            ->exists();
        if (!$flag) {//当前用户不是该房屋的认证业主
            return $this->failed('没有权限无法删除');
        }
        if ($roomUser->status == PsRoomUser::AUTH) {
            return $this->failed('已认证状态无法删除');
        }

        if ($roomUser->delete()) {
            return $this->success();
        }
        return $this->failed();
    }

    // 获取房屋下所有的已迁入住户
    public function residentsByRoom($roomId)
    {
        if (!$roomId) {
            return $this->failed('房屋ID不能为空');
        }
        $data = PsRoomUser::find()->select('id, mobile, name')
            ->where(['room_id' => $roomId, 'status' => [PsRoomUser::AUTH, PsRoomUser::UN_AUTH]])->asArray()->all();
        return $this->success($data);
    }

    //===========================================判断小区是否需要查询审核表的家人与租客=================================
    public function getCommunityConfig($community_id)
    {
        $config = Yii::$app->db->createCommand("SELECT * FROM ps_community_config where community_id = :community_id")->bindValue(':community_id', $community_id)->queryOne();
        return !empty($config) ? $config['is_family'] : 1;
    }

    //审核表的子住户列表(家人，租客)
    public function getResidentsFamily($roomId)
    {
        $data = PsResidentAudit::find()
            ->select('id, identity_type, status, name, mobile, time_end')
            ->where(['room_id' => $roomId, 'identity_type' => [2, 3], 'status' => [0, 2]])
            ->orderBy('identity_type asc, status asc, id desc')
            ->asArray()->all();

        $result = [];
        foreach ($data as $v) {
            $v['identity_type_desc'] = PsCommon::getIdentityType($v['identity_type'], 'key');
            $v['status'] = $v['status'] == 0 ? 5 : 6;
            $v['status_desc'] = $v['status'] == 5 ? '待审核' : '审核不通过';
            $v['time_end'] = $v['time_end'] ? date('Y-m-d', $v['time_end']) : '长期';
            $result[] = $v;
        }
        return $this->success($result);
    }

    //删除子用户
    public function removeResiden($id, $app_user_id, $communityId = 0)
    {
        $memberId = MemberService::service()->getMemberId($app_user_id);
        if (!$memberId) {
            return $this->failed('用户不存在');
        }
        $roomUser = PsResidentAudit::find()->where(['id' => $id])->one();
        if (!$roomUser) {
            return $this->failed('数据不存在');
        }
        $roomId = $roomUser['room_id'];
        $flag = PsRoomUser::find()
            ->where(['room_id' => $roomId, 'member_id' => $memberId,
                'identity_type' => 1, 'status' => PsRoomUser::AUTH])
            ->exists();
        if (!$flag) {//当前用户不是该房屋的认证业主
            return $this->failed('没有权限无法删除');
        }
        if ($roomUser->delete()) {
            return $this->success();
        }
        return $this->failed();
    }


    //创建审核表数据
    public function createFamilyResident($appUserId, $communityId, $roomId, $params)
    {
        $parent = $this->getAuthRoom($roomId, $appUserId, $communityId);
        if (!$parent || ($parent['identity_type'] != 1)) {
            return $this->failed('您不是该房屋下的业主，没有操作权限');
        }
        $par = [
            'community_id' => $communityId,
            'room_id' => $roomId
        ];
        $rp = array_merge($par, $params);
        $r = $this->_saveAuditRoomUser($rp, ['id' => $parent['id'], 'truename' => $parent['name']]);
        if ($r['code']) {
            return $this->success();
        }
        return $this->failed($r['msg']);
    }

    //编辑住户(生活号)
    public function editFamilyResident($id, $appUserId, $communityId, $roomId, $params)
    {
        $parent = $this->getAuthRoom($roomId, $appUserId, $communityId);
        if (!$parent || ($parent['identity_type'] != 1)) {
            return $this->failed('您不是该房屋下的业主，没有操作权限');
        }
        $mobile = PsCommon::get($params, 'mobile');
        //
        $model = PsRoomUser::find()->where([
            'id' => $id,
            'identity_type' => [2, 3],
            'status' => [PsRoomUser::UN_AUTH, PsRoomUser::UNAUTH_OUT, PsRoomUser::AUTH_OUT]])->one();
        $name = PsCommon::get($params, 'name');
        if (!$model) return $this->failed('住户不存在');
        $r = $this->_addCheck($communityId, $roomId, $mobile, $id, $name);
        if (!$r['code']) {
            return $this->failed($r['msg']);
        }
        //save
        $m = MemberService::service()->saveMember([
            'name' => $name,
            'mobile' => $mobile,
            'card_no' => PsCommon::get($params, 'card_no'),
        ]);
        if (!$m['code']) {
            return $this->failed($m['msg']);
        }
        $oldMobile = $model->mobile;
        $model->member_id = $m['data'];
        $model->card_no = PsCommon::get($params, 'card_no');
        $model->mobile = PsCommon::get($params, 'mobile');
        $model->name = PsCommon::get($params, 'name');
        $model->identity_type = PsCommon::get($params, 'identity_type');
        if ($model->identity_type == 1 || $model->identity_type == 2) {//业主或者家人，有效期变更为长期
            $model->time_end = 0;
        } else {
            $timeEnd = PsCommon::get($params, 'time_end');
            $timeEnd = $timeEnd ? strtotime($timeEnd . " 23:59:59") : 0;
            $model->time_end = (integer)$timeEnd;
        }
        if ($model->identity_type == 3) {
            $model->setScenario('renter');
        } elseif ($model->identity_type == 2) {
            $model->setScenario('family');
        }
        $model->update_at = time();
        $isAuth = $this->isAuthByNameMobile($communityId, $name, $mobile);
        if ($isAuth) {//如果新的名字+手机号已经认证过，则自动变更为已认证状态
            $model->auth_time = time();
            $model->status = PsRoomUser::AUTH;
            $model->auth_time = time();
        }
        //切换房屋
        if ($model->room_id != $roomId) {
            $model->room_id = $roomId;
            $roomInfo = RoomService::service()->getInfo($roomId);
            $model->group = $roomInfo['group'];
            $model->building = $roomInfo['building'];
            $model->unit = $roomInfo['unit'];
            $model->room = $roomInfo['room'];
        }
        if ($model->validate() && $model->save()) {
            if ($oldMobile != $mobile) {//手机号有变更，给新的手机号发送短信
                if ($params['identity_type'] == 2) {
                    $identityTypeLabel = '家人';
                } elseif ($params['identity_type'] == 3) {
                    $identityTypeLabel = '租客';
                } else {
                    $identityTypeLabel = '';
                }
                $communityName = CommunityService::service()->getCommunityName($communityId);
                SmsService::service()->init(32, $params['mobile'])->send([$params['name'], $communityName['name'], $parent['name'], $identityTypeLabel]);
            }

            return $this->success();
        }
        return $this->failed($this->getError($model));
    }


    /**
     * 新增到ps_resident_audit表
     */
    private function _saveAuditRoomUser($data, $userInfo)
    {
        $communityId = PsCommon::get($data, 'community_id');
        $roomId = (integer)PsCommon::get($data, 'room_id');
        $mobile = trim(PsCommon::get($data, 'mobile'));
        $name = trim(PsCommon::get($data, 'name'));
        $id = trim(PsCommon::get($data, 'rid'));

        $r = $this->_addCheck($communityId, $roomId, $mobile, null, $name);
        if (!$r['code']) {
            return $this->failed($r['msg']);
        }

        $r = MemberService::service()->saveMember([
            'name' => $name,
            'mobile' => $mobile,
            'card_no' => PsCommon::get($data, 'card_no'),
            'sex' => !empty($data['sex']) ? $data['sex'] : 1,
        ]);
        if (!$r['code']) {
            return $this->failed($r['msg']);
        }
        //认证状态:
        $memberId = (integer)$r['data'];
        $member['id'] = $memberId;
        $member['name'] = $name;

        if (!empty($id)) {//审核失败的才可以编辑
            $model = PsResidentAudit::find()->where(['id' => $id, 'community_id' => $communityId, 'member_id' => $member['id']])->one();
            if (empty($model)) {
                // wyf 20190515 新增 同一个人只能新增一套房间，审核失败（不能进行新增房屋），待审核（只能先审核）
                if (!empty($memberId)) {
                    RoomUserService::checkRoomExist($roomId, $memberId, 3);
                }
                $model = PsResidentAudit::find()->where(['id' => $id, 'community_id' => $communityId])->one();
            }
            if ($model['status'] == 0) {
                return $this->failed('住户房屋状态待审核');
            }
            $model->member_id = $member['id'];
            $model->status = PsResidentAudit::AUDIT_WAIT;
        } else {//
            if (!$roomId) {
                return $this->failed('房屋ID不能为空');
            }
            if (!$this->auditUnique($member['id'], $roomId)) {
                return $this->failed('已提交过审核，审核结果将以短信形式通知，请耐心等待');
            }
            // wyf 20190515 新增 同一个人只能新增一套房间，审核失败（不能进行新增房屋），待审核（只能先审核）
            if (!empty($memberId)) {
                RoomUserService::checkRoomExist($roomId, $memberId, 3);
            }
            $model = new PsResidentAudit();
            $model->member_id = $member['id'];
            $model->create_at = time();
        }

        $time_end = 0;
        //租客的时候才会必填有效期，其余默认填0，即永久
        if ($data['identity_type'] == 3) {
            $time_end = !empty($data['time_end']) ? strtotime($data['time_end'] . ' 23:59:59') : 0;
        }
        $data['time_end'] = $time_end;
        $model->operator = $member['id'];
        $model->operator_name = $member['name'];
        $model->update_at = time();
        $model->status = PsResidentAudit::AUDIT_WAIT;
        $model->load($data, '');
        if (!$model->validate() || !$model->save()) {
            return $this->failed($this->getError($model));
        }
        //添加历史提交记录
        PsResidentHistory::model()->addHistory($model, $member);
        return $this->success();
    }

    //同步房屋数据到open-api
    public function syncRoomUserListToOpenApi($communityId, $community_no, $memberId, $identity_type, $time_end, $room_id, $user_name, $user_phone, $sex, $card_no, $label_name)
    {
        //计算有效期
        $timeEnd = time() + 100 * 365 * 86400;
        if ($identity_type == 3 && $time_end) {//租客
            $timeEnd = $time_end;
        }
        //查询住户人脸头像
        $faceUrl = PsMember::find()
            ->select(['face_url'])
            ->where(['id' => $memberId])
            ->scalar();
        $faceUrl = $faceUrl ? $faceUrl : '';

        $userPushData = [
            'community_id' => $communityId,
            'room_id' => $room_id,
            'user_name' => $user_name,
            'user_phone' => (string)$user_phone,
            'user_type' => $identity_type,
            'community_no' => $community_no,
            'user_sex' => $sex,
            'user_id' => $memberId,
            'user_expired' => $timeEnd,
            'card_no' => $card_no,
            'time_end' => $time_end,
            'face_url' => $faceUrl,
            'label' => !empty($label_name) ? $label_name : [],//住户标签，add bu zq 2019-5-29
        ];
        $cacheName = YII_ENV . 'RoomUserList';
        Yii::$app->redis->rpush($cacheName, json_encode($userPushData));
    }
}

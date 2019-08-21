<?php
/**
 * 会员服务
 * @author shenyang
 * @date 2017/08/24
 */
namespace services\resident;

use app\models\DoorLastVisit;
use app\models\PsAppMember;
use app\models\PsAppUser;
use app\models\PsCommunityRoominfo;
use app\models\PsMember;
use app\models\PsResidentAudit;
use app\models\PsRoomUser;
use common\core\PsCommon;
use service\BaseService;
use service\basic_data\DoorPushService;
use Yii;
use yii\db\Query;

Class MemberService extends BaseService
{

    //根据app_user_id获取member
    public function getMemberId($appUserId)
    {
        return PsAppMember::find()->select('member_id')->where(['app_user_id' => $appUserId])->scalar();
    }

    //根据member_id获取最新的绑定app_user_id
    public function getAppUserId($memberId)
    {
        return PsAppMember::find()->select('app_user_id')->where(['member_id' => $memberId])->orderBy('id desc')->scalar();
    }

    //获取认证的memberId
    public function getAuthMemberId($appUserId, $communityId)
    {
        $memberId = $this->getMemberId($appUserId);
        if (!$memberId) {
            return false;
        }
        $flag = ResidentService::service()->isAuth($memberId, $communityId);
        return $flag ? $memberId : false;
    }

    //保存会员
    public function saveMember($params)
    {
        if(empty($params['mobile'])) {
            return $this->failed('手机号不能为空');
        }
        $params['mobile'] = trim($params['mobile']);

        $one = PsMember::find()->where(['mobile'=>$params['mobile']])->one();
        if($one) {
            //已经验证过，手机号和姓名不能更改
            if ($one['is_real']) {
                $params['name'] = $one['name'];
                $params['mobile'] = $one['mobile'];
            }
            if (empty($params['face_url'])) {
                $params['face_url']  = $one['face_url'];
            }
            $one->load($params, '');
            $one->save();
            return $this->success($one['id']);//已保存过，直接返回会员ID
        }
        $model = new PsMember();
        $params['create_at'] = time();
        $params['member_card'] = ResidentService::service()->getUserCard();
        $model->load($params, '');
        if(!$model->validate() || !$model->save()) {
            $errors = array_values($model->getErrors());
            return $this->failed($errors[0][0]);
        }
        return $this->success($model->id);
    }

    //会员信息 确认真实(有已认证信息)
    public function turnReal($id)
    {
        if (!$id) return false;
        return PsMember::updateAll(['is_real' => 1], ['id' => $id]);
    }

    //获取 房屋下所有用户级期身份
    public function getRoomUser($room_id, $emptyFilter = false) {
        $query = PsRoomUser::find()
            ->select('id, member_id, name, mobile, identity_type')
            ->where(['room_id' => $room_id, 'status' => [1, 2]]);
        if ($emptyFilter) {
            $query->andWhere("mobile not like '120%'");
        }
        $query->groupBy('member_id');
        $users = $query
            ->asArray()
            ->all();
        return $users;
    }

    //判断房屋下用户是否有房
    public function hasRoom($room_id, $member_id){
        return PsRoomUser::find()
            ->where(['room_id' => $room_id, 'member_id' => $member_id, 'status' => [1, 2]])
            ->exists();
    }

    /**
     * 查询会员某小区下的所有房屋ID
     * @param $memberId 会员id
     * @param $communityId 小区id
     * @author wenchao.feng
     * @return array
     */
    public function getRommIdsByMemberId($memberId, $communityId)
    {
        return PsRoomUser::find()->select('room_id')
            ->where(['member_id' => $memberId, 'community_id' => $communityId])
            ->andWhere(['<>', 'status', 3])
            ->column();
    }

    /**
     * 根据会员手机号查询会员认证的房屋(测试临时调用清理认证关系)
     * @param $phone
     * @param $communityName
     */
    public function getMembersRoomByPhone($phone, $communityName)
    {
        $query = PsRoomUser::find()->alias('t')
            ->select(['t.id','t.name as member_name','t.member_id', 't.mobile',
                't.identity_type', 't.room_id', 'comm.name as comm_name',
                't.group', 't.building', 't.unit', 't.room'])
            ->leftJoin('ps_community comm', 'comm.id = t.community_id')
            ->where(['t.status' => 2])
            ->andWhere(['t.mobile' => $phone]);
        if ($communityName) {
            $query->andWhere(['comm.name' => $communityName]);
        }

        $rooms = $query->asArray()->all();
        return $rooms;
	}

    //获取AppUser信息，仅用于小程序 --勿删！！！ 小程序的认证无房屋信息
    public function getAppUser($appUserId)
    {
        return PsAppUser::find()->select('id, nick_name as true_name,  phone, avatar')->where(['id' => $appUserId])->asArray()->one();
    }

    /**
     * 查询所有拥有会员信息的房屋ID组合
     * @param int $communityId 小区id
     * @return array
     */
    public function getHasMemberRooms($communityId)
    {
        return PsRoomUser::find()
            ->select(['room_id'])
            ->groupBy('room_id')
            ->andWhere(['community_id' => $communityId])
            ->column();
    }

    /**
     * 获取支付宝ID
     * @param $mobile
     */
    public function getAliIdByMobile($mobile)
    {
        $memberId = PsMember::find()->select('id')->where(['mobile' => $mobile])->scalar();
        if (!$memberId) return [];
        return PsAppUser::find()->alias('t')->select('t.channel_user_id')
            ->leftJoin(['am' => PsAppMember::tableName()], 't.id=am.app_user_id')
            ->where(['am.member_id' => $memberId])
            ->column();
    }

    /**
     * 获取住户(已认证+未认证)及其门禁配置
     * @param $communityId
     */
    public function getMemberDoors($params)
    {
        if (!$params) return [];
        $data = PsRoomUser::find()
            ->select('id, identity_type, name, mobile, room_id, status, member_id, time_end')
            ->andFilterWhere([
                'community_id' => PsCommon::get($params, 'community_id'),
                'member_id' => PsCommon::get($params, 'member_id'),
                'room_id' => PsCommon::get($params, 'room_id'),
            ])
            ->andWhere(['<>', 'status', 3])
            ->orderBy('room_id asc, identity_type asc')
            ->asArray()->all();
        //按房间分组
        $result = [];
        foreach ($data as $v) {
            $result[$v['room_id']][] = $v;
        }
        return $result;
    }

    /**
     * 获取用户基本信息
     * @param $memberId
     */
    public function getInfo($memberId, $withCard=false)
    {
        $columns = $withCard ? ['id', 'name', 'sex', 'mobile', 'member_card', 'face_url'] : ['id', 'name', 'sex', 'mobile', 'face_url'];
        return PsMember::find()->select($columns)->where(['id' => $memberId])->asArray()->one();
    }

    /**
     * 根据手机号获取用户
     * @param $mobile
     */
    public function getInfoByMobile($mobile)
    {
        return PsMember::find()->select('id, name, mobile')
            ->where(['mobile' => $mobile])
            ->asArray()->one();
    }

    /**
     * 根据appUserId获取会员信息
     * @param $appUserId
     */
    public function getInfoByAppUserId($appUserId)
    {
        $memberId = $this->getMemberId($appUserId);
        if (!$memberId) return [];
        return $this->getInfo($memberId);
    }

    /**
     * 保存用户的人脸照片
     * @param $memberId
     * @param $faceUrl
     * @return array
     */
    public function saveMemberFace($memberId, $faceUrl, $communityId, $roomId = 0)
    {
        $member = PsMember::findOne($memberId);
        if (!$member) {
            return $this->failed('用户信息不存在');
        }

        //查询用户在此小区下的一套房屋
        $roomInfo = PsRoomUser::find()
            ->select(['room_user.name', 'room_user.mobile', 'room_user.identity_type', 'room_user.time_end','room_user.face',
                'room_user.sex', 'unit.unit_no', 'room.out_room_id'])
            ->alias('room_user')
            ->leftJoin('ps_community_roominfo room', 'room.id = room_user.room_id')
            ->leftJoin('ps_community_units unit', 'room.unit_id=unit.id')
            ->where(['room_user.member_id' => $memberId, 'room_user.community_id' => $communityId])
            ->andFilterWhere(['room_user.room_id' => $roomId])
            ->asArray()
            ->one();
        if (!$roomInfo) {
            return $this->failed('用户房屋信息不存在');
        }

        $member->face_url = $faceUrl;
        if ($member->save()) {
            //数据推送
            $identityType = !empty($roomInfo['identity_type']) ? $roomInfo['identity_type'] : 1;
            $timeEnd = time() + 100 * 365 * 86400;
            if ($roomInfo['identity_type'] == 3 && $roomInfo['time_end']) {//租客
                $timeEnd = $roomInfo['time_end'];
            }
            DoorPushService::service()->userEdit($communityId, $roomInfo['unit_no'], $roomInfo['out_room_id'],
                $roomInfo['name'], $roomInfo['mobile'], $identityType, $roomInfo['sex'], $memberId, $faceUrl, $timeEnd,$roomInfo['face'],
                '','','','','');
            return $this->success();
        } else {
            $errors = array_values($member->getErrors());
            return $this->failed($errors[0][0]);
        }
    }

    /**
     * 查询最近访问的房屋数据
     * @param $appUserId
     * @param $communityId
     * @return int|mixed
     */
    public function getLastVisitorRoom($appUserId, $communityId)
    {
        // 查询业主
        $memberInfo = PsAppMember::find()->alias('A')
            ->leftJoin('ps_member B', 'B.id = A.member_id')
            ->select(['B.face_url', 'B.id as member_id', 'B.name', 'B.mobile', 'B.room_id'])
            ->where(['A.app_user_id' => $appUserId])->asArray()->one();

        // 查询访问的房屋
        $roomInfo = PsCommunityRoominfo::find()->alias('roominfo')
            ->leftJoin('ps_community comm', 'comm.id=roominfo.community_id')
            ->select(['comm.id as community_id', 'comm.name as community_name',
                'roominfo.id as room_id', 'roominfo.address as room_info'])
            ->where(['roominfo.id' => $memberInfo['room_id']])->asArray()->one();

        // 当前房屋是否认证
        $roomUser = PsRoomUser::find()->select('status')
            ->where(['room_id' => $memberInfo['room_id'], 'member_id' => $memberInfo['member_id']])
            ->asArray()->one();

        // 查询是否有认证的房屋
        $is_house = PsRoomUser::find()->select('id')
            ->where(['member_id' => $memberInfo['member_id'], 'status' => 2])
            ->scalar();

        // 查询已审核的房屋数量
        $roomCount = PsRoomUser::find()->select(['count(id)'])
            ->where(['member_id' => $memberInfo['member_id']])->scalar();
        // 查询待审核 审核失败的房屋数量
        $auditCount = PsResidentAudit::find()->select(['count(id)'])
            ->where(['member_id' => $memberInfo['member_id']])
            ->andWhere(['!=', 'status', '1'])->scalar();

        $result = [];
        $count = $roomCount + $auditCount; // 业主全部房屋数量

        $result['is_house'] = !empty($is_house) ? 1 : 2; // 至少有一个已认证的房屋 1有 2没有
        $result['type'] = $count > 1 ? 2 : $count;
        $result['is_auth'] = $roomUser['status'] == 2 ? 1 : 2; // 当前房屋是否认证 1已认证 2未认证
        // 用$memberInfo['mobile']判断是因为 出现过ps_app_member表有关联数据 ps_member表对应数据被删了 如果是这种情况就是业主未认证 重新去走业主认证操作 更新ps_app_member表
        $result['is_auth_member'] = !empty($memberInfo['mobile']) ? 1 : 2; //是否业主认证 1已认证 2未认证
        return $result;
    }

    //根据appUserId 查询用户的基本信息
    public function getUserDataByAppUserId($appUserId)
    {
        $responseData = [];
        $responseData['is_auth'] = 2;
        $responseData['face_url'] = '';
        $responseData['has_room'] = 0;
        $responseData['last_view_room_id'] = 0;
        $responseData['mobile'] = "";
        $responseData['name'] = "";
        $responseData['roomCount'] = 0;
        $responseData['roomList'] = [];

        $memberId = PsAppMember::find()
            ->select(['member_id'])
            ->where(['app_user_id' => $appUserId])
            ->orderBy('id desc')
            ->limit(1)
            ->asArray()
            ->scalar();
        if ($memberId) {
            $isAuth = ResidentService::service()->isAuth($memberId);
            if ($isAuth) {
                $responseData['is_auth'] = 1;
            }
            $memberInfo = PsMember::find()
                ->select(['name', 'mobile', 'face_url'])
                ->where(['id' => $memberId])
                ->asArray()
                ->one();
            if ($memberInfo) {
                $responseData['face_url'] = $memberInfo['face_url'];
                $responseData['name'] = $memberInfo['name'];
                $responseData['mobile'] = $memberInfo['mobile'];
            }

            //查询已认证的房屋列表
            $authRooms = PsRoomUser::find()
                ->select(['ru.community_id', 'ru.room_id', 'ru.identity_type', 'community.name as community_name',
                    'roominfo.group', 'roominfo.building', 'roominfo.unit', 'roominfo.room'])
                ->alias('ru')
                ->leftJoin('ps_community community', 'community.id = ru.community_id')
                ->leftJoin('ps_community_roominfo roominfo', 'roominfo.id=ru.room_id')
                ->where(['ru.status' => 2])
                ->andWhere(['ru.member_id' => $memberId])
                ->orderBy('ru.id asc, ru.community_id asc')
                ->asArray()
                ->all();
            $roomIds = [];
            foreach ($authRooms as $k=>$v) {
                $identityLabel = !empty(ResidentService::service()->identity_type[$v['identity_type']]) ? ResidentService::service()->identity_type[$v['identity_type']] : '';
                $authRooms[$k]['identity_label'] = $identityLabel;
                array_push($roomIds, $v['room_id']);
            }
            $responseData['roomList'] = $authRooms;
            $responseData['roomCount'] = count($authRooms);

            //查询是否有申请认证的房屋
            $residentAudit = PsResidentAudit::find()
                ->where(['member_id' => $memberId, 'status' => [0,2]])
                ->exists();
            if ($residentAudit) {
                $responseData['has_room'] = 1;
            }

            //查询最近一次的房屋id
            $lastVisitRoomId = DoorLastVisit::find()
                ->select(['room_id'])
                ->where(['member_id' => $memberId])
                ->asArray()
                ->scalar();
            if ($lastVisitRoomId && in_array($lastVisitRoomId, $roomIds)) {
                $responseData['last_view_room_id'] = $lastVisitRoomId;
            }
        }
        return $responseData;
    }

    /**
     * 查询小程序的房屋id
     * @param $appUserId
     * @param $communityId
     * @return int|mixed
     */
    public function getSmallRoom($appUserId, $communityId)
    {
        //获取生活号其中一个认证过后的房屋
        $rooms = PsAppMember::find()
            ->alias('am')
            ->leftJoin(['ru' => PsRoomUser::tableName()], 'am.member_id = ru.member_id')
            ->select(['ru.room_id','am.member_id'])
            ->where(['am.app_user_id' => $appUserId,'ru.status' => 2])
            ->asArray()
            ->one();
        $room_id = !empty($rooms['room_id'])?$rooms['room_id']:'';
        //获取小程序最后选择的房屋
        $memberInfo = PsMember::find()->select(['room_id'])->where(['id'=>$rooms['member_id']])->asArray()->one();
        $room_id = !empty($memberInfo['room_id'])?$memberInfo['room_id']:$room_id;
        
        return $room_id;
    }
    //小程序业主认证
    public function authTo($params)
    {
        $m = MemberService::service()->saveMember(['mobile' => $params['mobile'], 'name' => $params['user_name']]);
        $memberId = $m['data'];
        //只要走过业主认证的(支付宝实名+支付宝未实名但修改后提交的)，姓名和手机号就不允许再更改
        MemberService::service()->turnReal($memberId);

        //绑定ps_app_user和ps_member
        $one = PsAppMember::find()->where(['app_user_id' => $params['app_user_id']])->one();
        if (!$one) {
            $one = new PsAppMember();
            $one->app_user_id = $params['app_user_id'];
        }
        $one->member_id = $memberId;
        if (!$one->save()) {
            return $this->failed('绑定手机号失败');
        }
        //更新授权的用户名称
        PsAppUser::updateAll(['nick_name'=>$params['user_name'],'true_name'=>$params['user_name']],['id'=>$params['app_user_id']]);
        //查询有无房屋
        $hasRoomCommunitys = PsRoomUser::find()
            ->select(['community_id'])
            ->where(['name' => $params['user_name'], 'mobile' => $params['mobile']])
            ->asArray()
            ->column();
        if ($hasRoomCommunitys) {
            //将未认证的房屋都认证掉
            $flag = PsRoomUser::updateAll(['status' => 2, 'auth_time' => time()],
                ['status' => 1, 'name' => $params['user_name'], 'mobile' => $params['mobile']]);
        } else {
            //无房屋，判断是否有在申请中的房屋
            $auditRoom = PsResidentAudit::find()
                ->select(['id'])
                ->where(['member_id' => $memberId])
                ->asArray()
                ->all();
            if ($auditRoom) {
                return $this->failed('房屋还在认证中', 50002);
            } else {
                return $this->failed('没有房屋', 50003);
            }
        }
        $hasRoomCommunitys = array_unique($hasRoomCommunitys);
        //认证成功后的，推送到监控页面，变更业主身份数据 @shenyang v4.4数据监控版本
        foreach ($hasRoomCommunitys as $val) {
            //todo
            //WebSocketClient::getInstance()->send(MonitorService::MONITOR_RESIDENT, $val);
        }
        return $this->success();
    }

    //获取首页数据
    public function homeData($params)
    {
        $roomInfo = PsCommunityRoominfo::find()
            ->alias('roominfo')
            ->leftJoin('ps_community comm', 'comm.id=roominfo.community_id')
            ->select(['comm.id as community_id', 'comm.name as community_name',
                'roominfo.out_room_id', 'roominfo.id as room_id', 'roominfo.address as room_address','roominfo.unit_id'])
            ->where(['roominfo.id' => $params['room_id']])
            ->asArray()
            ->one();
        if (!$roomInfo) {
            return $this->failed('房屋不存在');
        }

        //查询业主
        $memberInfo = PsAppMember::find()
            ->alias('a')
            ->leftJoin('ps_member member', 'member.id=a.member_id')
            ->select(['member.face_url', 'a.member_id'])
            ->where(['a.app_user_id' => $params['app_user_id']])
            ->asArray()
            ->one();
        if (!$memberInfo) {
            return $this->failed('业主不存在');
        }
        $responseData = array_merge($roomInfo, $memberInfo);
        unset($responseData['member_id']);
        // 根据供应商判断这个用户是否有扫码、访客密码、住户密码、反扫码的权限
        $responseData = $this->_suppliers($roomInfo, $responseData);
        //保存最近一次访问的房屋
        $visitModel = DoorLastVisit::find()
            ->where(['member_id' => $memberInfo['member_id']])
            ->one();
        if (!$visitModel) {
            $visitModel = new DoorLastVisit();
            $visitModel->member_id = $memberInfo['member_id'];
        }
        $visitModel->community_id = $roomInfo['community_id'];
        $visitModel->community_name = $roomInfo['community_name'];
        $visitModel->room_id = $roomInfo['room_id'];
        $visitModel->out_room_id = $roomInfo['out_room_id'];
        $visitModel->room_address = $roomInfo['room_address'];
        $visitModel->update_at = time();
        $visitModel->save();

        //查看常用钥匙
        $responseData['keys'] = [];
        $keyRe = KeyService::service()->get_keys($params['app_user_id'], $memberInfo['member_id'],1);
        if ($keyRe['code'] == 1) {
            $responseData['keys'] = $keyRe['data'];
        }
        $responseData['bluetooth'] = [];
        $keyRe = KeyService::service()->get_keys($params['app_user_id'], $memberInfo['member_id'],2);
        if ($keyRe['code'] == 1) {
            $responseData['bluetooth'] = $keyRe['data'];
        }
        //查看住户密码
        $query = new Query();
        $roomPassword = $query->select(['code as password','expired_time'])
            ->from('door_room_password')
            ->where(['room_id' => $params['room_id'], 'member_id' => $memberInfo['member_id']])
            ->andWhere(['!=', 'code', ''])
            ->andWhere(['>', 'expired_time', time()])
            ->orderBy('id desc')
            ->limit(1)
            ->one();
        if (!empty($roomPassword)) {
            $roomPassword['expired_time'] = date("Y-m-d H:i:s", $roomPassword['expired_time']);
        } else {
            if ($responseData['is_residents_pwd']) {
                $re = KeyService::service()->visitor_password($params['app_user_id'],$params['room_id'],2);
                if ($re['code'] == 1) {
                    $keyData = $re['data'];
                    $roomPassword['password'] = $keyData['password'];
                    $roomPassword['expired_time'] = $keyData['expired_time'];
                }
            }
        }
        $responseData['room_password'] = !empty($roomPassword) ? $roomPassword : [];
        return $this->success($responseData);
    }

    // 获取小程序首页默认房屋
    //TODO 优化！！！！！一个方法查询了十几条sql，不合理！！
    public function doorIndexData($appUserId, $communityId, $roomId)
    {
        $result = [
            'member_id' => 0,
            'is_auth' => 2,
            'has_room' => 0,
            'room_id' => 0,
            'community_name' => 0,
            'room_address' => '',
            'is_qrcode' => false,
            'is_key' => false,
            'is_password' => false,
            'is_visitor' => false,
            'name' => '',
            'mobile' => '',
            'is_register' => false,//是否注册ps_member，用于判断跳转到业主认证页面，还是新增房屋
            'identity_type' => 0,
        ];
        $memberId = $this->getMemberId($appUserId);
        $userInfo = $this->getInfo($memberId);
        $result['name'] = $userInfo['name'];
        $result['mobile'] = $userInfo['mobile'];
        if (!$memberId) {
            return $this->success($result);
        }
        $result['is_register'] = true;
        $result['member_id'] = $memberId;
        $isAuth = ResidentService::service()->isAuth($result['member_id']);
        $unitId = null;
        if ($isAuth) {//认证过
            $result['is_auth'] = 1;
            if (!$roomId) {//如果没有传入roomId，判断是否有小区id
                if ($communityId) {//有小区，则选择该小区下认证的第一个房屋
                    $roomId = PsRoomUser::find()->select('room_id')
                        ->where(['community_id' => $communityId, 'member_id' => $result['member_id'], 'status' => PsRoomUser::AUTH])
                        ->scalar();
                } else {//没有传小区id，取最后访问历史表中的数据
                    $roomId = DoorLastVisit::find()->select('room_id')
                        ->where(['member_id' => $memberId])
                        ->scalar();
                }
            }
            if ($roomId) {//如果前面有取到room_id，也要验证一次房屋是否已认证
                $roomUser = PsRoomUser::find()
                    ->where(['member_id' => $memberId, 'room_id' => $roomId, 'status' => PsRoomUser::AUTH])
                    ->select('room_id, identity_type')
                    ->one();
                if ($roomUser) {
                    $roomId = $roomUser['room_id'];
                    $result['identity_type'] = $roomUser['identity_type'];
                } else {
                    $roomId = null;
                }
            }
            //
            if (!$roomId) {//如果经过前面的步骤，还是没有roomId，则取他认证的第一条数据
                $roomUser = PsRoomUser::find()->select('room_id, identity_type')
                    ->where(['member_id' => $result['member_id'], 'status' => PsRoomUser::AUTH])
                    ->one();
                if ($roomUser) {
                    $roomId = $roomUser['room_id'];
                    $result['identity_type'] = $roomUser['identity_type'];
                }
            }
            $roomInfo = PsCommunityRoominfo::find()->alias('t')
                ->leftJoin(['c' => PsCommunityModel::tableName()], 't.community_id=c.id')
                ->select('t.out_room_id, t.community_id, c.name as community_name, t.id as room_id, t.address as room_address, t.unit_id')
                ->where(['t.id' => $roomId])->asArray()->one();

            $result['room_id'] = $roomInfo['room_id'];
            $result['community_name'] = $roomInfo['community_name'];
            $result['room_address'] = $roomInfo['room_address'];
            $unitId = $roomInfo['unit_id'];
            //写入door_last_visit表
            $lastVisit = DoorLastVisit::find()->where(['member_id' => $memberId])->one();
            if (empty($lastVisit)) {
                $lastVisit = new DoorLastVisit();
            }
            $lastVisit->community_id =$roomInfo['community_id'];
            $lastVisit->community_name = $roomInfo['community_name'];
            $lastVisit->room_id = $roomInfo['room_id'];
            $lastVisit->out_room_id = $roomInfo['out_room_id'];
            $lastVisit->room_address = $roomInfo['room_address'];
            $lastVisit->member_id = $memberId;
            $lastVisit->update_at = time();
            $lastVisit->save();
        }
        //查询是否有申请认证的房屋
        $residentAudit = PsResidentAudit::find()
            ->where(['member_id' => $result['member_id'], 'status' => [0,2]])
            ->exists();
        if ($residentAudit) {
            $result['has_room'] = 1;
        }
        $supplierRights = $this->_suppliers($unitId);
        $result = array_merge($result, $supplierRights);
        return $this->success($result);
    }

    // 根据供应商判断这个用户是否有扫码、访客密码、住户密码、反扫码的权限
    public function _suppliers($unitId,$responseData = [])
    {
        if (!$unitId) return [];
        // 查看小区已接入的供应商
        $suppliers = (new Query())
            ->select(['supplier.supplier_name'])
            ->distinct()
            ->from('door_device_unit du')
            ->rightJoin('door_devices d','d.id = du.devices_id')
            ->rightJoin('parking_suppliers supplier','supplier.id = d.supplier_id')
            ->where(['du.unit_id' => $unitId])
            ->column();
        // 根据供应商判断这个用户是否有扫码、访客密码、住户密码、反扫码的权限
        $responseData['is_qrcode'] = false; // 扫码
        $responseData['is_key'] = false; //钥匙开门(远程开门)
        $responseData['is_visitor'] = false; // 访客密码
        $responseData['is_residents_pwd'] = false; // 住户密码
        $responseData['is_bluetooth'] = false;//蓝牙开门
        
        $qrcode = ['iot']; // 二维码开门
        $isKey = ['zhiguo', 'ximo', 'iot']; // 钥匙开门
        $visitor_pwd = ['zhiguo','ximo','iot']; // 访客密码
        $residents_pwd = ['zhiguo']; // 住户密码
        $bluetooth = ['iot'];//蓝牙开门

        if ($suppliers) {
            foreach ($suppliers as $key) {
                if (in_array($key, $qrcode)) {
                    $responseData['is_qrcode'] = true;
                }

                if (in_array($key, $isKey)) {
                    $responseData['is_key'] = true;
                }

                if (in_array($key, $visitor_pwd)) {
                    $responseData['is_visitor'] = true;
                }

                if (in_array($key, $residents_pwd)  && $responseData['community_id'] != 570  && $responseData['community_id'] != 493 ) {
                    $responseData['is_residents_pwd'] = false;//java端暂时不展示住户密码模块，edit by zq 20119-2-26
                }
                //add by zq 2019-3-26 狄耐克的其中一种设别显示蓝牙开门，演示用，顾做了时间限制，到3月30
                if(in_array($key,$bluetooth) && time() <= '1553961599'){
                    $responseData['is_bluetooth'] = true;
                }
            }
        }

        return $responseData;
    }

    //人脸列表
    public function getFaceList($appUserId, $roomId)
    {
        $memberId = $this->getMemberId($appUserId);
        if (!$memberId) {
            return $this->failed('用户不存在');
        }
        $current = PsRoomUser::find()->select('identity_type, name')
            ->where(['room_id' => $roomId, 'member_id' => $memberId, 'status' => PsRoomUser::AUTH])
            ->asArray()->one();
        if (!$current) {
            return $this->failed('服务不可用');
        }
        //家人和租客，只可以看到自己的头像
        $myself = [['member_id' => $memberId, 'identity_type' => $current['identity_type'], 'name' => $current['name']]];
        if ($current['identity_type'] != 1) {
            $data = [];
        } else {
            $data = PsRoomUser::find()->select('member_id, name, identity_type')
                ->where(['room_id' => $roomId, 'status' => [1, 2]])
                ->andWhere(['!=', 'member_id', $memberId])
                ->orderBy('identity_type asc')
                ->asArray()->all();
        }
        $data = array_merge($myself, $data);
        $memberIds = array_column($data, 'member_id');
        $members = PsMember::find()->select('id ,face_url')
            ->where(['id' => $memberIds])
            ->indexBy('id')->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $v['face_url'] = !empty($members[$v['member_id']]['face_url']) ? $members[$v['member_id']]['face_url'] : '';
            $v['identity_type_label'] = ResidentService::service()->identity_type[$v['identity_type']];
            $result[] = $v;
        }
        return $this->success($result);
    }

    //获取住户信息
    public function getResidentList($params)
    {
        $validate = $this->validateUser($params['app_user_id'], $params['room_id']);
        if (!$validate['code']) {
            return $this->failed($validate['msg']);
        }
        $roomUser = $validate['data'];
        $data = [];
        $roomInfo = PsCommunityRoominfo::find()
            ->alias('roominfo')
            ->leftJoin('ps_community comm', 'comm.id=roominfo.community_id')
            ->select(['comm.id as community_id', 'comm.name as community_name',
                'roominfo.group', 'roominfo.building', 'roominfo.unit', 'roominfo.room',
                'roominfo.id as house_id', 'roominfo.address as house_address'])
            ->where(['roominfo.id' => $params['room_id']])
            ->asArray()
            ->one();
        if (!$roomInfo) {
            return $this->failed('房屋不存在');
        }
        $data['community_id'] = $roomInfo['community_id'];
        $data['community_name'] = $roomInfo['community_name'];
        $data['houseInfo'] = [
            'group' => $roomInfo['group'],
            'building' => $roomInfo['building'],
            'unit' => $roomInfo['unit'],
            'room' => $roomInfo['room'],
            'house_address' => $roomInfo['house_address'],
            'house_id' => $roomInfo['house_id'],
        ];

        //查询住户列表
        $data['resident_list'] = [];
        //如果不是业主身份，只返回自己的信息
        $data['resident_list'][] = [
            'id' => $roomUser['id'],
            'identity_type' => $roomUser['identity_type'],
            'identity_label' => PsCommon::getIdentityType($roomUser['identity_type'], 'key'),
            'mobile' => $roomUser['mobile'],
            'name' => $roomUser['name'],
            'auth_status' => $roomUser['status'],
            'auth_status_label' => PsCommon::getIdentityStatus($roomUser['status']),
            'expired_time' => $roomUser['time_end'] ? date('Y-m-d', $roomUser['time_end'])  : '永久'
        ];

        if ($roomUser['identity_type'] == 1) {
            //业主查询所有人
            $residentListRe = ResidentService::service()->getChildResidents($params['room_id']);
            if ($residentListRe['code']) {
                $residentList = $residentListRe['data'];
                foreach ($residentList as $k => $v) {
                    $data['resident_list'][] = [
                        'id' => $v['id'],
                        'identity_type' => $v['identity_type'],
                        'identity_label' => $v['identity_type_desc'],
                        'mobile' => $v['mobile'],
                        'name' => $v['name'],
                        'auth_status' => $v['status'],
                        'auth_status_label' => $v['status_desc'],
                        'expired_time' => $v['time_end'] ? $v['time_end'] : '永久'
                    ];
                }
            }
        }
        return $this->success($data);
    }

    //删除住户
    public function delResidentList($params)
    {
        return ResidentService::service()->removeChild($params['resident_id'], $params['app_user_id']);
    }

    //查看住户详情
    public function getResidentDetail($params)
    {
        $data = [];
        $memberId = MemberService::service()->getMemberId($params['app_user_id']);
        if (!$memberId) {
            return $this->failed('用户不存在');
        }
        $roomUser = PsRoomUser::find()->where(['id' => $params['resident_id']])->one();
        if (!$roomUser) {
            return $this->failed('住户数据不存在');
        }
        $roomId = $roomUser['room_id'];
        $flag = PsRoomUser::find()
            ->where(['room_id' => $roomId, 'member_id' => $memberId,
                'identity_type' => 1, 'status' => PsRoomUser::AUTH])
            ->exists();
        if (!$flag) {//当前用户不是该房屋的认证业主
            return $this->failed('没有权限查看');
        }

        $data['auth_status'] = $roomUser['status'];
        $data['auth_status_label'] = PsCommon::getIdentityStatus($roomUser['status']);
        $data['card_no'] = $roomUser['card_no'];
        $data['expired_time'] = $roomUser['time_end'] ? date('Y-m-d', $roomUser['time_end']) : '永久';
        $data['identity_type'] = $roomUser['identity_type'];
        $data['identity_label'] = PsCommon::getIdentityType($roomUser['identity_type'], 'key');
        $data['mobile'] = PsCommon::isVirtualPhone($roomUser['mobile']) ? '' : $roomUser['mobile'];
        $data['name'] = $roomUser['name'];
        return $this->success($data);
    }

    //添加住户
    public function addResident($params)
    {
        $otherParams['card_no']       = $params['card_no'];
        $otherParams['identity_type'] = $params['identity_type'];
        $otherParams['mobile']        = $params['mobile'];
        $otherParams['name']          = $params['name'];
        $otherParams['time_end']  = $params['expired_time'];
        if ($otherParams['identity_type'] == 1 || $otherParams['identity_type'] == 2) {
            $otherParams['time_end'] = 0;
        }
        if ($params['resident_id']) {
            $re = ResidentService::service()->editResident($params['resident_id'], $params['app_user_id'], $params['community_id'], $params['room_id'], $otherParams);
        } else {
            $re = ResidentService::service()->createResident($params['app_user_id'], $params['community_id'], $params['room_id'], $otherParams);
        }
        return $re;
    }

    //验证当前用户状态
    private function validateUser($appUserId, $roomId)
    {
        //查询业主
        $memberInfo = PsAppMember::find()
            ->alias('a')
            ->leftJoin('ps_member member', 'member.id=a.member_id')
            ->select(['member.face_url', 'a.member_id'])
            ->where(['a.app_user_id' => $appUserId])
            ->asArray()
            ->one();
        if (!$memberInfo) {
            return $this->failed('服务不可用');
        }

        //一个member_id在一个房屋下，可能有多条数据，所以只判断是否有认证的数据即可
        $roomUser = PsRoomUser::find()
            ->select('id, identity_type, status, name, mobile, time_end')
            ->where(['member_id' => $memberInfo['member_id'], 'room_id' => $roomId, 'status' => 2])
            ->asArray()
            ->one();
        if (!$roomUser) {
            return $this->failed('服务不可用');
        }

        return $this->success($roomUser);
    }
}
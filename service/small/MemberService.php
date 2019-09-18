<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/20
 * Time: 13:35
 */

namespace service\small;


use app\models\DoorLastVisit;
use app\models\PsAlipayCardRecord;
use app\models\PsAliToken;
use app\models\PsAppMember;
use app\models\PsAppUser;
use app\models\PsAreaAli;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsLifeBroadcastRecord;
use app\models\PsOrder;
use app\models\PsProclaim;
use app\models\PsPropertyIsvToken;
use app\models\PsMember;
use app\models\PsResidentAudit;
use app\models\PsRoomUser;
use app\models\StCommunist;
use app\models\StCommunistAppUser;
use app\modules\small\services\BillSmallService;
use common\core\PsCommon;
use common\MyException;
use service\alipay\AlipayBillService;
use service\alipay\BillCostService;
use service\alipay\MemberCardService;
use service\BaseService;
use service\door\KeyService;
use service\property_basic\ActivityService;
use service\room\RoomService;
use yii\db\Query;

class MemberService extends BaseService
{
    //根据app_user_id获取member
    public function getMemberId($appUserId)
    {
        return PsAppMember::find()->select('member_id')->where(['app_user_id' => $appUserId])->scalar();
    }

    //根据member_id获取最新的绑定app_user_id
    public function getAppUserId($memberId)
    {
        return PsAppMember::find()->select('app_user_id')->where(['member_id' => $memberId])->scalar();
    }

    //获取认证的memberId
    public function getAuthMemberId($appUserId, $communityId)
    {
        $memberId = $this->getMemberId($appUserId);
        if (!$memberId) {
            return false;
        }
        $flag = $this->isAuth($memberId, $communityId);
        return $flag ? $memberId : false;
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


    // 停车缴费 判断小区状态 添加ps_order表记录 创建支付宝订单 更新ps_order表相关信息 配置前端唤起支付成功异步回调地址
    public function pay($params)
    {
        $data['pay_type'] = 'park'; // 标记为 停车缴费
        $data['amount'] = !empty($params['amount']) ? $params['amount'] : 0;
        $data['remark'] = !empty($params['remark']) ? $params['remark'] : '';
        $data['community_id'] = !empty($params['community_id']) ? $params['community_id'] : 0;
        $data['room_id'] = !empty($params['room_id']) ? $params['room_id'] : 0; // 临停 可以没有房屋信息
        $data['app_user_id'] = !empty($params['app_user_id']) ? $params['app_user_id'] : 0;
        $data['car_across_id'] = !empty($params['car_across_id']) ? $params['car_across_id'] : 0; //查询停车费时会返回相关的车辆入场记录id
        $data['out_id'] = !empty($params['out_id']) ? $params['out_id'] : 0; //停车扫动态二维码支付，需要在 parking_lk_pay_code 表中回填order_id
        $data['buyer_id'] = !empty($params['buyer_id']) ? $params['buyer_id'] : '';

        if (empty($data['app_user_id']) && empty($data['buyer_id'])) {
            return $this->failed('用户ID不能为空');
        }

        if (empty($data['community_id'])) {
            return $this->failed('小区ID不能为空');
        }

        if (empty($data['amount']) || $data['amount'] <= 0) {
            return $this->failed('金额不能为空');
        }

        $this->payCheck($data['community_id']); // 判断小区状态

        // 添加ps_order表记录 返回['order_no' => '','cost_type' => '','cost_name' => '','amount' => '',];
        $result = BillSmallService::generalBill($data);
        if (is_array($result)) { // 成功去支付
            //判断金额，创建支付宝订单
            if ($data['amount'] > 0) {
                $r = $this->alipay($result, $data);
                if (is_array($r)) {
                    return $this->success($r);
                } else {
                    return $this->failed($r);
                }
            }
            $r['out_trade_no'] = '';
            $r['trade_no'] = '';
            return $this->success($r);
        } else {
            return $this->failed($result);
        }
    }

    // 临时停车小区状态判断
    public function payCheck($community_id)
    {
        $model = $this->getCommInfo($community_id); // 查询小区
        if (empty($model)) { // 小区不存在
            return $this->failed('小区不存在');
        }

        if ($model['status'] != 1) { // 小区被禁用
            return $this->failed('小区被禁用');
        }

        if (empty($model['token'])) { // 未获取到小区授权
            return $this->failed('小区对应的物业公司未授权给我们');
        }

        return $model;
    }

    /**
     * 查询小区的基本信息
     * @param $communityId
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getCommInfo($communityId)
    {
        $info = PsCommunityModel::find()
            ->select(['ps_community.community_no', 'ps_community.name', 'ps_community.status',
                'company.property_name as company_name','company.id as company_id',
                'company.user_id','company.alipay_account','company.seller_id', 'company.has_sign_qrcode'])
            ->leftJoin('ps_property_company company','ps_community.pro_company_id = company.id')
            ->where(['ps_community.id' => $communityId])
            ->asArray()
            ->one();

        $info['token'] = '';
        //查询此小区的用户token
        if (YII_ENV == "master") {
            if ($info['has_sign_qrcode']) {
                $tokenInfo = PsPropertyIsvToken::find()->select(['token'])
                    ->where(['type' => 1, 'type_id' => $info['company_id']])
                    ->asArray()
                    ->one();
            } else {
                $tokenInfo = PsAliToken::find()->select(['token'])
                    ->where(['type' => 1, 'type_id' => $info['company_id']])
                    ->asArray()
                    ->one();
            }
            if ($tokenInfo) {
                $info['token'] = $tokenInfo['token'];
            }
        } else {
            $info['token'] = \Yii::$app->params['test_auth_token'];
        }

        if ($info) {
            $req['property_company_id'] = $info['company_id'];
            $result = BillCostService::service()->getAllByPay($req);
            if ($result['code']) {
                $info['services'] = $result['data'];
            }
        }
        return $info;
    }

    // 创建支付宝订单 更新ps_order表相关信息 配置前端唤起支付成功异步回调地址
    public function alipay($return, $params)
    {
        $amount = $return['amount']; // 支付金额
        $order_no = $return['order_no']; // ps_order表订单号

        $app_user_id = !empty($params['app_user_id']) ? $params['app_user_id'] : 0; // 用户ID
        $buyer_id = !empty($params['buyer_id']) ? $params['buyer_id'] : ''; // 用户ID

        if (!$amount || !$order_no) {
            return '请求参数不完整！';
        }

        // 支付宝小区编号
        $community_no = PsCommunityModel::find()->select('community_no')
            ->where(['id' => $params['community_id']])->scalar();

        // 获取支付宝id
        if (!$buyer_id && $app_user_id) {
            $buyer_id = $this->getBuyerIdr($app_user_id);
        }

        $data = [
            "community_id" => $community_no,
            "out_trade_no" => $this->_generateBatchId(),
            "total_amount" => $amount,
            "subject" => '临时停车',
            "buyer_id" => $buyer_id,
            "timeout_express" => "30m"
        ];

        $small_url = \Yii::$app->params['external_invoke_small_address_park']; // 支付成功回调地址
        $result = AlipayBillService::service($community_no)->tradeCreate($data, $small_url); // 调用接口
        if ($result['code'] == 10000) { // 创建成功 更新ps_order表信息
            $out_trade_no = !empty($result['out_trade_no']) ? $result['out_trade_no'] : '';
            $trade_no = !empty($result['trade_no']) ? $result['trade_no'] : '';
            $order = PsOrder::find()->where(['order_no' => $order_no])->one();

            if (empty($order)) {
                return '订单不存在';
            }

            $order->status = 8; // 线下扫码
            $order->trade_no = $trade_no; // 支付宝交易流水号

            if (!$order->save()) {
                return 'order信息更新失败';
            }

            return ['out_trade_no' => $out_trade_no, 'trade_no' => $trade_no];
        } else if ($result['code'] == 0) { // 物业公司未授权
            return $result['msg'];
        } else {
            return $result['sub_msg'];
        }
    }

    // 获取不重复batch_id
    private function _generateBatchId()
    {
        $incr = \Yii::$app->redis->incr('ps_bill_batch_id');
        return date("YmdHis") . '2' . rand(100, 999) . str_pad(substr($incr, -3), 3, '0', STR_PAD_LEFT);
    }

    /**
     * 获取用户基本信息
     * @param $memberId;
     */
    public function getInfo($memberId, $withCard=false)
    {
        $columns = $withCard ? ['id', 'name', 'sex', 'mobile', 'member_card', 'face_url'] : ['id', 'name', 'sex', 'mobile', 'face_url', 'is_real'];
        return PsMember::find()->select($columns)->where(['id' => $memberId])->asArray()->one();
    }

    //获取首页数据
    public function homeData($params)
    {
        $roomInfo = PsCommunityRoominfo::find()
            ->alias('roominfo')
            ->leftJoin('ps_community comm', 'comm.id=roominfo.community_id')
            ->select(['comm.id as community_id', 'comm.name as community_name', 'roominfo.out_room_id', 'roominfo.id as room_id', 'roominfo.address as room_address','roominfo.unit_id','roominfo.unit_id'])
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
        $unitId = $roomInfo['unit_id'];
        $responseData = \service\door\MemberService::service()->_suppliers($unitId);
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
        $keyRe = KeyService::service()->get_keys($params['app_user_id'], $memberInfo['member_id']);
        if ($keyRe['code'] == 1) {
            $responseData['keys'] = $keyRe['data'];
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
            if ($responseData['link_pwd_name']) {
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

    // 获取首页数据
    public function getHomeData($params)
    {
        //查询业主
        $memberInfo = PsAppMember::find()->alias('A')
            ->leftJoin('ps_member B', 'B.id = A.member_id')
            ->select(['B.face_url', 'B.id as member_id', 'B.name', 'B.mobile', 'B.room_id as sel_room_id', 'B.is_real'])
            ->where(['A.app_user_id' => $params['app_user_id']])->asArray()->one();
        $appUser = PsAppUser::find()->select('id, nick_name, avatar, phone, true_name, is_certified, biz_card_no, channel_user_id')->where(['id' => $params['app_user_id']])->asArray()->one();
        if(empty($memberInfo)){
            $resident['is_house'] =  2; // 至少有一个已认证的房屋 1有 2没有
            $resident['type'] = 0;
            $resident['is_auth'] =  2; // 当前房屋是否认证 1已认证 2未认证
            $resident['is_auth_member'] =  2; //是否业主认证 1已认证 2未认证
            $resident['community_id'] =  '';
            $resident['community_name'] = '';
            $resident['steward'] = '';
            $resident['name'] =  !empty($memberInfo['name']) ? $memberInfo['name'] : $appUser['nick_name'];
            $resident['face_url'] = $appUser['avatar'];
            $resident['mobile'] = $memberInfo['mobile'];
            $resident['hideMobile'] = PsCommon::hideMobile($resident['mobile']);
            $resident['is_certified'] = $appUser['is_certified'];
            return $this->success($resident);
        }
        $roomIds = [];
        // 查询已审核的房屋数量
        $roomAll = PsRoomUser::find()->select(['room_id'])
            ->where(['member_id' => $memberInfo['member_id'], 'name' => $memberInfo['name']])->all();
        if(!empty($roomAll)){
            foreach ($roomAll as $k=>$v) {
                array_push($roomIds, $v['room_id']);
            }
        }
        // 查询待审核 审核失败的房屋数量
        $roomAuditAll = PsResidentAudit::find()->select(['room_id'])
            ->where(['member_id' => $memberInfo['member_id'], 'name' => $memberInfo['name']])
            ->andWhere(['!=', 'status', '1'])->all();
        if(!empty($roomAuditAll)){
            foreach ($roomAuditAll as $k=>$m) {
                array_push($roomIds, $m['room_id']);
            }
        }
        $count = count($roomAuditAll) + count($roomAll); // 业主全部房屋数量
        if(!empty($memberInfo['sel_room_id']) && in_array($memberInfo['sel_room_id'], $roomIds)) {//说明业主选择了房屋并且业主选择的房屋也是在当前房屋中
            $sel_room = $memberInfo['sel_room_id'];
        }else{//没有选择房屋的情况根据用户id查询一条已认证的房屋
            $sel_room = PsRoomUser::find()->select('room_id')->where(['member_id' => $memberInfo['member_id'], 'name' => $memberInfo['name'],'status'=>'2'])->scalar();
        }

        if (empty($sel_room)) { // 都没有 迁入未认证的 2019-6-20说要改的
            $sel_room = PsRoomUser::find()->select('room_id')->where(['member_id' => $memberInfo['member_id'], 'name' => $memberInfo['name']])->andWhere(['!=', 'status', '2'])->scalar();
        }

        if (empty($sel_room)) { // 都没有 就看待审核 审核失败的房屋 2019-6-20说要改的
            $sel_room = PsResidentAudit::find()->select('room_id')->where(['member_id' => $memberInfo['member_id'], 'name' => $memberInfo['name']])->andWhere(['!=', 'status', '1'])->scalar();
        }

        // 当前房屋是否认证
        $roomUser = PsRoomUser::find()->select('status')->where(['room_id' => $sel_room, 'member_id' => $memberInfo['member_id']])->orderBy('status asc')->asArray()->one();
        //选择的房屋在后台有住户信息
        $roomInfo= ["community_id"=>'',"community_name"=>'',"room_id"=>'','room_info'=>''];
        if(!empty($sel_room)){
            // 查询访问的房屋
            $roomInfo = PsCommunityRoominfo::find()->alias('roominfo')
                ->leftJoin('ps_community comm', 'comm.id=roominfo.community_id')
                ->select(['comm.id as community_id', 'comm.name as community_name','comm.pro_company_id','roominfo.id as room_id', 'roominfo.address as room_info','comm.longitude as lon','comm.latitude as lat'])
                ->where(['roominfo.id' => $sel_room])->asArray()->one();
        }

        // 查询是否有认证的房屋
        $is_house = PsRoomUser::find()->select('id')->where(['member_id' => $memberInfo['member_id'], 'status' => 2, 'name' => $memberInfo['name']])->scalar();
        PsRoomUser::updateAll(['status' => PsRoomUser::AUTH, 'auth_time' => time()], ['member_id' => $memberInfo['member_id'], 'name' => $memberInfo['name'], 'status' => PsRoomUser::UN_AUTH]);

        $result = !empty($roomInfo) ? array_merge($memberInfo, $roomInfo) : $memberInfo;
        $result['is_house'] = !empty($is_house) ? 1 : 2; // 至少有一个已认证的房屋 1有 2没有
        $result['type'] = $count > 1 ? 2 : $count;
        $result['is_auth'] = $roomUser['status']==2 ? 1 : 2; // 当前房屋是否认证 1已认证 2未认证
        // 用$memberInfo['mobile']判断是因为 出现过ps_app_member表有关联数据 ps_member表对应数据被删了 如果是这种情况就是业主未认证 重新去走业主认证操作 更新ps_app_member表
        $result['is_auth_member'] = !empty($memberInfo['mobile']) && $memberInfo['is_real'] ? 1 : 2; //是否业主认证 1已认证 2未认证
        $result['community_id'] = !empty($result['community_id']) ? $result['community_id'] : '0';
        $result['community_name'] = !empty($result['community_name']) ? $result['community_name'] : '';
        //获取物业公司的电话
        $result['property_mobile'] = !empty($roomInfo['pro_company_id'])?PsCommunityModel::find()->select('phone')->where(['id' => $result['community_id']])->scalar():'';
        //添加经纬度，用于首页的天气查找， add by zq 2019-3-25
        $result['lon'] = !empty($result['lon']) ? $result['lon'] : '';
        $result['lat'] = !empty($result['lat']) ? $result['lat'] : '';
        //$result['weather'] = MojiService::service()->getWeather($result['community_id'],$result['lon'],$result['lat']);
        //$result['suggest'] = MojiService::service()->getSuggest($result['community_id'],$result['lon'],$result['lat']);
        $result['name'] = !empty($memberInfo['name']) ? $memberInfo['name'] : $appUser['nick_name'];
        $result['face_url'] = $appUser['avatar'];
        $result['mobile'] = $memberInfo['mobile'];
        $result['is_certified'] = $appUser['is_certified'];
        //获取管家数据
        $steward_params['community_id'] = $result['community_id'];
        $steward_params['room_id'] = $result['room_id'];
        $result['steward'] = ComplaintService::service()->stewardIndexInfo($steward_params);
        // 滚动消息需要滚动展示系统信息和物业公告的前十条
        $proclaim = PsProclaim::find()->select('title, show_at')->where(['community_id' => $result['community_id'], 'is_show' => 2])->limit(10)->orderBy('show_at DESC')->asArray()->all();
        $broadcast = PsLifeBroadcastRecord::find()->alias('A')
            ->leftJoin('ps_life_broadcast B', 'A.broadcast_id = B.id')
            ->select(['B.title', 'A.send_at as show_at'])
            ->where(['A.community_id' => $result['community_id'], 'A.status' => 1])
            ->andWhere(['=', 'B.push_type', 2])
            ->limit(10)
            ->orderBy('A.send_at desc')
            ->asArray()->all();

        $news = array_merge($broadcast, $proclaim);
        // 根据显示时间倒序排序
        $arr1 = array_map(create_function('$n', 'return $n["show_at"];'), $news);
        array_multisort($arr1, SORT_DESC, $news);
        // 业主卡
        $pass_id = MemberCardService::service()->cardQuery($appUser)['data']['pass_id'];
        $result['card'] = [
            'pass_id' => !empty($pass_id) ? $pass_id : '',
            'is_card' => !empty($pass_id) ? 1 : 2,
            'apply_card_url' => !empty($pass_id) ? '' : MemberCardService::service()->cardActivateurlApply(['type' => $params['system_type']])['apply_card_url']
        ];

        if (empty($pass_id) && !empty($appUser['biz_card_no'])) { // 删卡记录
            $record = new PsAlipayCardRecord();
            $record->app_user_id = $appUser['id'];
            $record->biz_card_no = $appUser['biz_card_no'];
            $record->create_at = time();
            $record->save();
        }

        // 小区活动 显示进行中和已结束的数据
        $activity = ActivityService::service()->list(['community_id' => $result['community_id'], 'status' => [1,2], 'small' => 1]);
        $result['activity'] = !empty($activity['code']) ? $activity['data']['list'] : [];
        // 社区曝光台
        $exposure = CommunityService::service()->exposureList(['community_id' => $result['community_id'], 'homePage' => 1]);
        $exposure = $exposure['data'];
        $result['exposure'] = $exposure['list'];
        $result['exposure_total'] = $exposure['total'];
        $result['exposure_avatar'] = $exposure['avatar'];
        $result['hideMobile'] = PsCommon::hideMobile($result['mobile']);

        if (!empty($news)) {
            foreach ($news as $k => $v) {
                if ($k <= 9) { // 前十条
                    $news[$k]['show_at'] = !empty($v['show_at']) ? date('Y-m-d', $v['show_at']) : '';
                } else {
                    unset($news[$k]);
                }
            }

            $new_count = count($news);
            if ($new_count % 2 == 1) { // 前端两条两条滚动 所以取双数
                unset($news[$new_count - 1]);
            }

            $result['news'] = $news;
        }

        unset($result['member_id']);

        return $this->success($result);
    }

    // 标记已选择房屋 提交房屋认证的时候也会调用
    public function smallSelcet($params)
    {
        $app_user_id = $params['app_user_id'];

        //查询业主
        $member_id = PsAppMember::find()->alias('a')->leftJoin('ps_member member', 'member.id = a.member_id')
            ->select(['a.member_id'])
            ->where(['a.app_user_id' => $app_user_id])->scalar();
        if (!$member_id) {
            return $this->failed('业主不存在');
        }

        if (!empty($params['is_submit'])) { // 标记为提交房屋认证
            $roomUser = PsRoomUser::find()->select(['id'])->where(['member_id' => $member_id])->asArray()->one();
            $residentAudit = PsResidentAudit::find()->select(['id'])->where(['member_id' => $member_id])->asArray()->one();
            $room_id = RoomService::service()->findRoom($params['community_id'], $params['group'], $params['building'], $params['unit'], $params['room'])['id'];

            if (empty($roomUser) && empty($residentAudit)) { // 如果之前没有添加过房屋认证 获取room_id
                $params['room_id'] = $room_id;
            } else { // 新增的时候 如果之前添加过房屋 判断房屋不能有重复
                $audit_record_id = !empty($params['audit_record_id']) ? $params['audit_record_id'] : '0';
                $rid = !empty($params['rid']) ? $params['rid'] : '0';

                $rUser = PsRoomUser::find()->select(['id'])->where(['member_id' => $member_id, 'room_id' => $room_id])
                    ->andWhere(['!=', 'id', $rid])
                    ->andWhere(['!=', 'status', '4'])
                    ->asArray()->one();
                $rAudit = PsResidentAudit::find()->select(['id'])->where(['member_id' => $member_id, 'room_id' => $room_id])
                    ->andWhere(['!=', 'id', $audit_record_id])
                    ->andWhere(['!=', 'status', '1'])
                    ->asArray()->one();

                if (!empty($rUser) || !empty($rAudit)) { // 只要有一个存在 就不能提交房屋认证
                    return $this->failed('房屋已存在！');
                }
                return $this->success();
            }
        }

        if (!empty($params['room_id'])) {
            $model = PsMember::updateAll(['room_id' => $params['room_id']], ['id' => $member_id]);
            return $this->success();
        }
    }

    //获取天气详情接口
    public function getWeatherInfo($data)
    {
        //默认信息
        $type = -1;
        $id = '-1';
        $lon = '120.005176';
        $lat = '30.281448';
        $city = '杭州市';
        //用户授权以后没房屋并且前端传了经纬度
        if(!empty($data['app_user_id'])){
            $type = 2;
            $id = $data['app_user_id'];
            $lon = $data['lon'];
            $lat = $data['lat'];
            $city = $data['city'];
        }
        //用户有房屋，切换小区
        if(!empty($data['community_id'])){
            $type = 1;
            $id = $data['community_id'];
            $res = PsCommunityModel::find()->alias('c')
                ->leftJoin(['a'=>PsAreaAli::tableName()],'a.areaCode = c.city_id')
                ->select(['a.areaName','c.longitude as lon','c.latitude as lat'])
                ->where(['c.id'=>$id])->asArray()->one();
            $city = PsCommon::get($res,'areaName');
            $lon = PsCommon::get($res,'lon');
            $lat = PsCommon::get($res,'lat');
        }
        $result['city'] = $city;
        $result['weather'] = MojiService::service()->getWeather($id,$lon,$lat,$type);
        $result['suggest'] = MojiService::service()->getSuggest($id,$lon,$lat,$type);
        $result['limt'] = MojiService::service()->getLimit($id,$lon,$lat,$type);
        return $this->success($result);

    }

    /**
     * 保存member表与ps_app_user表的关系
     * @param $memberId
     * @param $appUserId
     * @param $systemType
     * @param $mobile
     * @return bool
     * @throws MyException
     */
    public function saveMemberAppUser($memberId, $appUserId, $systemType, $mobile)
    {
        if ($systemType == "djyl") {
            $model = PsAppMember::find()
                ->where(['app_user_id' => $appUserId, 'member_id' => $memberId])
                ->one();
            if (!$model) {
                $model = new PsAppMember();
                $model->app_user_id = $appUserId;
                $model->member_id = $memberId;
                $model->save();
            }

            //根据手机号查找党员
            $communistInfo = StCommunist::find()
                ->where(['mobile' => $mobile, 'is_del' => 1])
                ->asArray()
                ->one();
            if ($communistInfo) {
                //查找党员
                $commAppUser = StCommunistAppUser::find()
                    ->where(['communist_id' => $communistInfo['id']])
                    ->andWhere(['app_user_id' => $appUserId])
                    ->asArray()
                    ->one();
                if ($commAppUser) {
                    return true;
                }
                $commAppUser = new StCommunistAppUser();
                $commAppUser->communist_id = $communistInfo['id'];
                $commAppUser->app_user_id = $appUserId;
                if ($commAppUser->save()) {
                    return true;
                }
                throw new MyException("用户保存失败");
            }
        } else {
            $model = PsAppMember::find()
                ->where(['app_user_id' => $appUserId, 'member_id' => $memberId])
                ->one();
            if ($model) {
                return true;
            }
            $model = new PsAppMember();
            $model->app_user_id = $appUserId;
            $model->member_id = $memberId;
            if ($model->save()) {
                return true;
            }
            throw new MyException("用户保存失败");
        }
    }
}


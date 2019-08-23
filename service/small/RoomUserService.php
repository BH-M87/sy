<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/5/24
 * Time: 17:36
 */

namespace service\small;

use app\models\PsCommunityBuilding;
use app\models\PsCommunityGroups;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsCommunityUnits;
use app\models\PsMember;
use app\models\PsResidentAudit;
use app\models\PsResidentHistory;
use app\models\PsRoomUser;
use common\core\F;
use common\core\PsCommon;
use common\core\TagLibrary;
use common\MyException;
use service\BaseService;
use service\message\MessageService;
use yii\db\Query;

class RoomUserService extends BaseService
{
    /**
     * @param $params
     * @return array
     * @throws MyException
     * @throws \yii\db\Exception
     * @api 住户房屋新增
     * @author wyf
     * @date 2019/5/24
     */
    public function add($params)
    {
        //统一验证
        $result = static::commonCheck($params);
        $memberInfo = $result['memberInfo'];
        $member_id = $result['member_id'];
        $roomInfo = $result['roomInfo'];
        //验证待房屋信息是否存在
        static::checkResidentAudit($member_id, $params['room_id'], $memberInfo['name']);
        //验证房屋信息是否存在
        static::checkRoomUser($member_id, $params['room_id']);

        //进行数据比对,验证
        $checkRoomUserExist = static::checkRooUserExist($memberInfo['mobile'], $memberInfo['name'], $params['room_id'], $member_id, $params['identity_type']);
        if ($checkRoomUserExist) {
            $model = PsRoomUser::find()->where(['id' => $checkRoomUserExist])->one();
            $model->status = 2;
            $model->update_at = time();
            $model->auth_time = time();
        } else {
            $images = static::_checkImage($params);
            $data['community_id'] = $roomInfo['community_id'];
            $data['member_id'] = $member_id;
            $data['room_id'] = $params['room_id'];
            $data['name'] = $memberInfo['name'];
            $data['sex'] = $memberInfo['sex'];
            $data['mobile'] = $memberInfo['mobile'];
            $data['images'] = $images;
            $data['identity_type'] = $params['identity_type'];
            $data['time_end'] = empty($params['expired_time']) ? 0 : strtotime(date('Y-m-d 23:59:59', strtotime($params['expired_time'])));
            $data['status'] = 0;
            $data['create_at'] = time();
            $data['update_at'] = time();
            $model = new PsResidentAudit();
            $model->setAttributes($data);
        }
        //新增到待审核表中
        $trans = \Yii::$app->getDb()->beginTransaction();
        try {
            //设置默认选择的房屋
            PsMember::updateAll(['room_id' => $params['room_id']], ['id' => $member_id, 'room_id' => '0']);
            $model->save();
            //添加历史提交记录
            $historyParams = [
                'community_id' => $roomInfo['community_id'],
                'member_id' => $member_id,
                'room_id' => $params['room_id'],
                'id' => $model->id,
                'status' => 0,
            ];
            $historyInfo = [
                'name' => $memberInfo['name'],
                'id' => $member_id,
            ];
            PsResidentHistory::model()->addHistory($historyParams, $historyInfo);
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollback();
            throw new MyException($e->getMessage());
        }
        //获取用户当前房屋数量
        $residentAuditNum = PsResidentAudit::find()
            ->where(['member_id' => $result['member_id'], 'status' => [0, 2]])
            ->count();
        $roomUserNum = PsRoomUser::find()
            ->where(['member_id' => $result['member_id']])
            ->andWhere(['!=', 'status', 1])
            ->count();
        $info['community_mobile'] = $roomInfo['community_mobile'];
        $info['house_num'] = (int)($residentAuditNum + $roomUserNum);

        //添加工作提醒和消息模板
        try {
            //获取小区名称
            $community_name = (new Query())
                ->select('name')
                ->from('ps_community')
                ->where(['id' => $roomInfo['community_id'], 'status' => 1])
                ->createCommand()
                ->queryScalar();
            $address = $community_name . $roomInfo['group'] . $roomInfo['building'] . $roomInfo['unit'] . $roomInfo['room'];
            if ($model->status == 2) {
                $status = 2;
            } else {
                $status = 1;
            }
            $this->addMessage($roomInfo['community_id'], $community_name, $status, $historyParams['id'],
                $member_id, $memberInfo['name'], $address, $params['identity_type']);
        } catch (\Exception $e) {
            \Yii::info($e->getMessage(), 'messageError');
        }
        return $info;

    }

    /**
     * @param $params
     * @return array
     * @throws MyException
     * @throws \Throwable
     * @throws \yii\db\Exception
     * @author wyf
     * @date 2019/5/25
     * @api 编辑住户房屋信息
     */
    public function update($params)
    {
        //统一验证
        $result = static::commonCheck($params);
        $memberInfo = $result['memberInfo'];
        $member_id = $result['member_id'];
        $roomInfo = $result['roomInfo'];
        $expired_time = empty($params['expired_time']) ? "" : $params['expired_time'];

        $images = static::_checkImage($params);
        if (!empty($params['rid'])) {
            //验证room_user表数据是否存在
            $roomUserModel = PsRoomUser::find()->where(['id' => $params['rid'], 'member_id' => $member_id])->one();
            if (!$roomUserModel) {
                throw new MyException('房屋信息不存在');
            }
            if ($roomUserModel->status == 2) {
                throw new MyException('当前房屋已认证');
            }
            if ($params['room_id'] != $roomUserModel->room_id) {
                //验证待房屋信息是否存在
                static::checkResidentAudit($member_id, $params['room_id']);
                //验证房屋信息是否存在
                static::checkRoomUser($member_id, $params['room_id']);
                //进行数据比对,验证
                $checkRoomUserExist = static::checkRooUserExist($memberInfo['mobile'], $memberInfo['name'], $params['room_id'], $member_id, $params['identity_type']);
                if ($checkRoomUserExist) {
                    $model = PsRoomUser::find()->where(['id' => $checkRoomUserExist])->one();
                    $model->status = 2;
                    $model->time_end = empty($expired_time) ? 0 : strtotime(date('Y-m-d 23:59:59', strtotime($expired_time)));
                    $model->update_at = time();
                    $model->auth_time = time();
                } else {
                    $model = static::addResidentAudit($roomInfo, $member_id, $memberInfo, $params['room_id'], $params['identity_type'], $expired_time, $images);
                }
            } else {
                //数据直接进入待审核
                $model = static::addResidentAudit($roomInfo, $member_id, $memberInfo, $params['room_id'], $params['identity_type'], $expired_time, $images);
            }
            //旧住户房屋数据删除
            $delModel = $roomUserModel;
        } elseif (!empty($params['resident_id'])) {
            $redidentAuditModel = PsResidentAudit::find()->where(['id' => $params['resident_id'], 'member_id' => $member_id])->one();
            if (!$redidentAuditModel) {
                throw new MyException('房屋信息不存在');
            }
            if ($redidentAuditModel->room_id != $params['room_id']) {
                //验证待房屋信息是否存在
                static::checkResidentAudit($member_id, $params['room_id']);
                //验证房屋信息是否存在
                static::checkRoomUser($member_id, $params['room_id']);
                //进行数据比对,验证
                $checkRoomUserExist = static::checkRooUserExist($memberInfo['mobile'], $memberInfo['name'], $params['room_id'], $member_id, $params['identity_type']);
                if ($checkRoomUserExist) {
                    $model = PsRoomUser::find()->where(['id' => $checkRoomUserExist])->one();
                    $model->status = 2;
                    $model->time_end = empty($expired_time) ? 0 : strtotime(date('Y-m-d 23:59:59', strtotime($expired_time)));
                    $model->update_at = time();
                    $model->auth_time = time();
                } else {
                    $model = static::addResidentAudit($roomInfo, $member_id, $memberInfo, $params['room_id'], $params['identity_type'], $expired_time, $images);
                }
                $delModel = $redidentAuditModel;
            } else {
                if ($redidentAuditModel->status == 0) {
                    throw new MyException('房屋信息待审核');
                }
                $model = $redidentAuditModel;
                $model->time_end = empty($expired_time) ? 0 : strtotime(date('Y-m-d 23:59:59', strtotime($expired_time)));
                $model->status = 0;
                $model->identity_type = $params['identity_type'];
                $model->images = $images;
            }
        } else {
            throw new MyException('更新异常');
        }
        //新增到待审核表中
        $trans = \Yii::$app->getDb()->beginTransaction();
        try {
            if (!empty($delModel)) {
                $delModel->delete();
            }
            $model->save();
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollback();
            throw new MyException($e->getMessage());
        }
        $info['community_mobile'] = $roomInfo['community_mobile'];
        return $info;
    }

    /**
     * @param $mobile
     * @param $name
     * @param $room_id
     * @param $member_id
     * @param $identity_type
     * @return false|null|string
     * @throws \yii\db\Exception
     * @author wyf
     * @date 2019/5/25
     * @api 进行数据比对,验证房屋信息是否存在
     */
    public static function checkRooUserExist($mobile, $name, $room_id, $member_id, $identity_type)
    {
        //进行数据比对,验证
        $checkRoomUserExist = (new Query())
            ->select('id')
            ->from('ps_room_user')
            ->where(['mobile' => $mobile, 'name' => $name, 'member_id' => $member_id,
                'room_id' => $room_id, 'identity_type' => $identity_type, 'status' => 1])
            ->createCommand()->queryScalar();
        return $checkRoomUserExist;
    }

    /**
     * @param $params
     * @return array|string
     * @throws MyException
     * @author wyf
     * @date 2019/5/25
     * @api 证件信息组合过滤
     */
    private static function _checkImage($params)
    {
        if (!empty($params['card_url'])) {
            $images = implode(',', $params['card_url']);//array_filter(explode(',', $params['card_url']));
            if (count($params['card_url']) > 5) {
                throw new MyException('证件图片最多5张');
            }
            unset($params['card_url']);
        } else {
            $images = '';
        }
        return $images;
    }

    /**
     * @param $roomInfo
     * @param $member_id
     * @param $memberInfo
     * @param $room_id
     * @param $identity_type
     * @param $expired_time
     * @param $images
     * @return PsResidentAudit
     * @throws MyException
     * @api 房屋审核数据新增
     * @author wyf
     * @date 2019/5/25
     */
    public static function addResidentAudit($roomInfo, $member_id, $memberInfo, $room_id, $identity_type, $expired_time, $images)
    {
        $data['community_id'] = $roomInfo['community_id'];
        $data['member_id'] = $member_id;
        $data['room_id'] = $room_id;
        $data['sex'] = $memberInfo['sex'];
        $data['name'] = $memberInfo['name'];
        $data['mobile'] = $memberInfo['mobile'];
        $data['images'] = $images;
        $data['identity_type'] = $identity_type;
        $data['time_end'] = empty($expired_time) ? 0 : strtotime(date('Y-m-d 23:59:59', strtotime($expired_time)));
        $data['status'] = 0;
        $data['create_at'] = time();
        $data['update_at'] = time();
        $model = new PsResidentAudit();
        $model->setAttributes($data);
        return $model;
    }

    /**
     * @param $params
     * @return array
     * @throws MyException
     * @throws \yii\db\Exception
     * @api 新增和编辑统一验证
     * @author wyf
     * @date 2019/5/24
     */
    protected static function commonCheck($params)
    {
        if (empty($params['room_id'])) {
            throw new MyException('房屋编号不能为空');
        }
        if (empty($params['user_id'])) {
            throw new MyException('用户编号不能为空');
        }
        if (empty($params['identity_type'])) {
            throw new MyException('住户身份不能为空');
        }
        if (!in_array($params['identity_type'], [1, 2, 3])) {
            throw new MyException('住户身份类型不正确');
        }
        if ($params['identity_type'] == 3 && empty($params['expired_time'])) {
            throw new MyException('租客有效期不能为空');
        }
        if (!empty($params['expired_time'])) {
            if ($params['expired_time'] <= date('Y-m-d 23:59:59')) {
                throw new MyException('租客有效期必须大于当天');
            }
        }
        $member_id = MemberService::service()->getMemberId($params['user_id']);
        if (!$member_id) {
            throw new MyException('用户信息不存在');
        }
        //获取当前的用户信息
        $memberInfo = MemberService::service()->getInfo($member_id);
        if (!$memberInfo) {
            throw new MyException('用户信息不存在');
        }
        //获取室信息
        $roomInfo = CommunityRoomService::getCommunityRoominfo($params['room_id']);
        if (!$roomInfo) {
            throw new MyException('房屋不存在');
        }
        //获取物业电话
        $community_mobile = (new Query())->select('phone as community_mobile')
            ->from('ps_community')
            ->where(['id' => $roomInfo['community_id']])->createCommand()->queryScalar();
        $roomInfo['community_mobile'] = empty($community_mobile) ? "" : $community_mobile;
        return ['member_id' => $member_id, 'memberInfo' => $memberInfo, 'roomInfo' => $roomInfo];
    }

    /**
     * @param $member_id
     * @param $room_id
     * @return bool
     * @throws MyException
     * @throws \yii\db\Exception
     * @author wyf
     * @date 2019/5/24
     * @api 验证待房屋信息是否存在
     */
    public static function checkResidentAudit($member_id, $room_id, $member_name = '')
    {
        $residentAuditInfo = static::getResidentAuditView($member_id, $room_id, '', $member_name);
        if (!$residentAuditInfo) {
            return true;
        }
        if ($residentAuditInfo['status'] == 1) {
            return true;
        } elseif ($residentAuditInfo['status'] == 0) {
            throw new MyException('房屋已存在,不可重复添加');
        } else {
            throw new MyException('房屋信息未通过,不可重复添加');
        }
    }

    /**
     * @param $member_id
     * @param $room_id
     * @return bool
     * @throws MyException
     * @throws \yii\db\Exception
     * @author wyf
     * @date 2019/5/24
     * @api 验证房屋信息是否存在
     */
    protected static function checkRoomUser($member_id, $room_id)
    {
        $roomUserInfo = static::getRoomUserView($member_id, $room_id);
        if (!$roomUserInfo) {
            return true;
        }
        if ($roomUserInfo['status']) {
            if ($roomUserInfo['status'] == 1) {
                return true;
            }
            switch ($roomUserInfo['status']) {
                case 2:
                    $error_msg = '已认证';
                    break;
                case 3:
                    $error_msg = '未认证迁出';
                    break;
                case 4:
                    $error_msg = '已认证迁出';
                    break;
                default:
                    $error_msg = '未知';
                    break;
            }
            throw new MyException('房屋信息:' . $error_msg);
        }
        throw new MyException('房屋信息异常');
    }

    /**
     * @param $params
     * @return array
     * @throws MyException
     * @api 已认证/未认证的房屋信息列表
     * @author wyf
     * @date 2019/8/23
     */
    public function getList($params)
    {
        if (empty($params['user_id'])) {
            throw new MyException('用户id不能为空');
        }
        $user_id = $params['user_id'];
        $member_id = $this->getMemberByUser($user_id);
        $member = PsMember::find()->select('name, mobile')->where(['id' => $member_id])->asArray()->one();

        $rooms = PsResidentAudit::find()->alias('ra')->where(['member_id' => $member_id, 'ra.name' => $member['name']])
            ->leftJoin(['cr' => PsCommunityRoominfo::tableName()], 'cr.id = ra.room_id')
            ->leftJoin(['c' => PsCommunityModel::tableName()], 'c.id = ra.community_id')
            ->select(['ra.id as audit_record_id', 'ra.community_id', 'c.phone as community_mobile', 'c.name as community_name', 'ra.time_end', 'ra.identity_type', 'ra.room_id as room_id',
                'cr.group', 'cr.building', 'cr.unit', 'cr.room', 'ra.status'])
            ->asArray()->all();
        $roomData = ['auth' => [], 'unauth' => []];
        //审核跟失败的数据
        if ($rooms) {
            foreach ($rooms as $key => $value) {
                $value['expired_time'] = !empty($value['time_end']) ? date('Y-m-d', $value['time_end']) : '永久';
                $value['identity_label'] = TagLibrary::roomUser('identity_type')[$value['identity_type']];
                $value['room_adress'] = $value['group'] . '-' . $value['building'] . '-' . $value['unit'] . '-' . $value['room'];
                //审核中
                if ($value['status'] == 0) {
                    $value['status'] = 1;
                    $value['is_auth'] = 2;
                    $roomData['unauth'][] = $value;
                }
                //审核未通过
                if ($value['status'] == 2) {
                    $value['is_auth'] = 2;
                    $value['status'] = 3;
                    $roomData['unauth'][] = $value;
                }
            }
        }
        $rooms2 = PsRoomUser::find()->alias('ru')
            ->leftJoin(['c' => PsCommunityModel::tableName()], 'c.id = ru.community_id')
            ->select('ru.community_id,ru.id as rid, c.phone as community_mobile,c.name as community_name,ru.room_id, ru.group, ru.building, ru.unit, ru.room, ru.time_end, ru.identity_type, ru.status, ru.name')
            ->where(['member_id' => $member_id, 'ru.name' => $member['name']])->asArray()->all();
        if ($rooms2) {
            foreach ($rooms2 as $v) {
                $v['audit_record_id'] = 0;
                $v['expired_time'] = !empty($v['time_end']) ? date('Y-m-d', $v['time_end']) : '永久';
                $v['identity_label'] = TagLibrary::roomUser('identity_type')[$v['identity_type']];
                $v['room_adress'] = $v['group'] . '-' . $v['building'] . '-' . $v['unit'] . '-' . $v['room'];
                if ($v['status'] == 1 && $member['name'] == $v['name']) {
                    $v['status'] = 1;
                    $v['is_auth'] = 2;
                    $roomData['unauth'][] = $v;
                }
                //已认证
                if ($v['status'] == 2) {
                    $v['status'] = 2;
                    $v['is_auth'] = 1;
                    $roomData['auth'][] = $v;
                }
                //迁出
                if ($v['status'] == 3 || $v['status'] == 4) {
                    $v['is_auth'] = 2;
                    $v['status'] = 4;
                    $roomData['unauth'][] = $v;
                }
            }
        }
        return $roomData;
    }

    /**
     * @param $params
     * @return array|\yii\db\ActiveRecord|null
     * @throws MyException
     * @api 获取房屋详情
     * @author wyf
     * @date 2019/8/23
     */
    public function view($params)
    {
        $communityId = F::get($params, 'community_id');
        if (empty($communityId)) {
            throw new MyException('小区id不能为空');
        }
        $id = F::get($params, 'audit_record_id');
        $type = 1;
        if (empty($id)) {
            $type = 2;
            $id = F::get($params, 'rid');
            if (empty($id)) {
                throw new MyException('房屋id/审核id不能都为空');
            }
        }
        if ($type == 1) {
            $result = $this->auditShow($id, $communityId);
        } else {
            $result = $this->showOne($id, $communityId);
        }
        $data = [];
        if ($result) {
            $data = $result;
            $data['card_url'] = !empty($result['images']) ? $result['images'] : '';
            $data['expired_time'] = date($result['time_end']);
            $data['refuse_reason'] = !empty($result['reason']) ? $result['reason'] : '';
            $data['room_address'] = $result['group'] . $result['building'] . $result['unit'] . $result['room'];
            $data['identity_label'] = $result['identity_type_des'];
            $data['community_name'] = PsCommunityModel::find()->select(['name'])->where(['id' => $communityId])->asArray()->scalar();
        }
        return $data;
    }

    /*
     * 住户审核详情
     */
    public static function auditShow($id, $communityId)
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
        $data['time_end'] = $data['time_end'] ? date('Y-m-d', $data['time_end']) : 0;
        $data['accept_at'] = $data['accept_at'] ? date('Y-m-d', $data['accept_at']) : '';
        $data['identity_type_des'] = TagLibrary::roomUser('identity_type')[$data['identity_type']];
        $data['images'] = $data['images'] ? explode(',', $data['images']) : [];
        $data['mobile'] = PsCommon::isVirtualPhone($data['mobile']) ? '' : $data['mobile'];
        return $data;
    }

    //住户信息
    public static function showOne($id, $communityId)
    {
        $data = PsRoomUser::find()
            ->select('id, room_id, group, building, unit, room, name, mobile, card_no, identity_type, time_end, status')
            ->where(['id' => $id, 'community_id' => $communityId])->asArray()->one();
        if (!$data) return null;
        $data['time_end'] = $data['time_end'] ? date('Y-m-d', $data['time_end']) : '';
        $data['identity_type_des'] = TagLibrary::roomUser('identity_type')[$data['identity_type']];
        return $data;
    }

    /**
     * @param $member_id
     * @param $room_id
     * @param $select
     * @return array|false
     * @throws \yii\db\Exception
     * @author wyf
     * @date 2019/5/24
     * @api 获取用户房屋详情信息
     */
    public static function getRoomUserView($member_id, $room_id, $select = "")
    {
        $select = empty($select) ? 'status,id' : $select;
        $roomUserInfo = (new Query())
            ->select($select)
            ->from('ps_room_user')
            ->where(['room_id' => $room_id, 'member_id' => $member_id])
            ->orderBy("status")
            ->createCommand()
            ->queryOne();
        return $roomUserInfo;
    }

    /**
     * @param $member_id
     * @param $room_id
     * @param $select
     * @return array|false
     * @throws \yii\db\Exception
     * @author wyf
     * @date 2019/5/24
     * @api 获取审核表中的用户房屋详情信息
     */
    public static function getResidentAuditView($member_id, $room_id, $select = "", $member_name = '')
    {
        $select = empty($select) ? 'status,id' : $select;
        $residentAuditInfo = (new Query())
            ->select($select)
            ->from('ps_resident_audit')
            ->where(['room_id' => $room_id, 'member_id' => $member_id])
            ->andFilterWhere(['name' => $member_name])
            ->andWhere(['!=', 'status', 1])
            ->createCommand()
            ->queryOne();
        return $residentAuditInfo;
    }

    /**
     * @param $room_id
     * @param $member_id
     * @param int $check_type 验证类型,1验证住户表信息是否存在，2验证审核表信息是否存在，3全部验证
     * @param int $type 是否响应结果,1是,其他直接抛出异常响应
     * @return bool/string
     * @throws MyException
     * @api 验证住户房屋信息是否存在
     */
    public static function checkRoomExist($room_id, $member_id, $check_type, $type = 2)
    {
        if (!in_array($check_type, [1, 2, 3])) {
            throw new MyException('请求有误');
        }
        if ($check_type == 1 || $check_type == 3) {
            $roomUserInfo = static::checkRoomUserExist($room_id, $member_id);
            if (!$roomUserInfo) {
                if ($check_type != 3) {
                    return true;
                }
            } else {
                switch ($roomUserInfo['status']) {
                    case 1:
                        $message = "未认证";
                        break;
                    case 2:
                        $message = "已认证";
                        break;
                    case 3:
                        $message = "已迁出";
                        break;
                    case 4:
                        $message = '已迁出';
                        break;
                }
            }

        }
        if (empty($message)) {
            if ($check_type == 2 || $check_type == 3) {
                $residentAuditInfo = static::checkResidentAuditExist($room_id, $member_id);
                if (!$residentAuditInfo) {
                    return true;
                }
                switch ($residentAuditInfo['status']) {
                    case 0:
                        $message = '待审核';
                        break;
                    case 2:
                        $message = '未通过';
                        break;
                    default:
                        return true;
                }
            }
        }
        $message = empty($message) ? "未知错误" : '住户房屋状态:' . $message;
        if ($type == 1) {
            return $message;
        }
        throw new MyException($message);
    }

    public static function checkRoomUserExist($room_id, $member_id)
    {
        return PsRoomUser::getOne(['where' => ['room_id' => $room_id, 'member_id' => $member_id]], 'status,id');
    }

    public static function checkResidentAuditExist($room_id, $member_id)
    {
        return PsResidentAudit::find()
            ->select('status,id')
            ->where(['room_id' => $room_id, 'member_id' => $member_id])
            ->andWhere(['!=', 'status', 1])
            ->asArray()->one();
        //return PsResidentAudit::getOne(['where' => ['room_id' => $room_id, 'member_id' => $member_id]], 'status,id');
    }

    /**
     * @param $community_id
     * @param $community_name
     * @param $status
     * @param $id
     * @param $member_id
     * @param $user_name
     * @param $address
     * @param $identity_type
     * @api 工作提醒和消息中心添加
     * @author wyf
     * @date 2019/6/18
     */
    public function addMessage($community_id, $community_name, $status, $id, $member_id, $user_name, $address, $identity_type)
    {
        try {
            if ($status == 2) {
                //发送消息
                $data = [
                    'community_id' => $community_id,
                    'id' => $id,
                    'member_id' => $member_id,
                    'user_name' => $user_name,

                    'create_user_type' => 2,
                    'remind_tmpId' => 2,
                    'remind_target_type' => 1,
                    'remind_auth_type' => 1,

                    'msg_type' => 1,
                    'msg_tmpId' => 2,
                    'msg_target_type' => 2,
                    'msg_auth_type' => 1,
                    'remind' => [
                        0 => $user_name
                    ],
                    'msg' => [
                        0 => $community_name,
                        1 => $address,
                        2 => $user_name,
                        3 => date('Y-m-d H:i:s', time()),
                    ]
                ];
                MessageService::service()->addMessageTemplate($data);
            } else {
                //发送消息
                $data = [
                    'community_id' => $community_id,
                    'id' => $id,
                    'member_id' => $member_id,
                    'user_name' => $user_name,
                    'create_user_type' => 2,

                    'remind_tmpId' => 6,
                    'remind_target_type' => 6,
                    'remind_auth_type' => 1,

                    'msg_type' => 2,
                    'msg_tmpId' => 6,
                    'msg_target_type' => 6,
                    'msg_auth_type' => 1,
                    'remind' => [
                        0 => $user_name
                    ],
                    'msg' => [
                        0 => $community_name,
                        1 => $address,
                        2 => $user_name,
                        3 => TagLibrary::roomUser('identity_type')[$identity_type],
                        4 => date('Y-m-d H:i:s', time()),
                    ]
                ];
                MessageService::service()->addMessageTemplate($data);
            }
        } catch (\Exception $e) {
            //\Yii::info($e->getMessage(), 'messageError');
        }
    }

    /**
     * 根据房屋信息查看房屋下所有已认证的业主列表
     * @param $communityId
     * @param $group
     * @param $building
     * @param $unit
     * @param $room
     * @return array
     */
    public function getAuthUserByRoomInfo($communityId, $group, $building, $unit, $room)
    {
        $query = new Query();
        $res = $query->select(['ru.member_id', 'ru.name', 'ru.mobile', 'ru.identity_type'])
            ->from('ps_community_roominfo as cr')
            ->leftJoin('ps_room_user as ru', 'ru.room_id=cr.id')
            ->where(['cr.community_id' => $communityId, 'cr.group' => $group, 'cr.building' => $building,
                'cr.unit' => $unit, 'cr.room' => $room, 'ru.status' => 2])
            ->all();
        return $res;
    }
}
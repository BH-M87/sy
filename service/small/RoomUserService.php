<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/5/24
 * Time: 17:36
 */

namespace app\small\services;


use app\models\PsMember;
use app\models\PsResidentAudit;
use app\models\PsResidentHistory;
use app\models\PsRoomUser;
use common\core\TagLibrary;
use common\MyException;
use service\BaseService;
use service\message\MessageService;
use yii\db\Query;

class RoomUserService extends BaseService
{
    /**
     * @api 住户房屋新增
     * @author wyf
     * @date 2019/5/24
     * @param $params
     * @return array
     * @throws MyException
     * @throws \yii\db\Exception
     */
    public function add($params)
    {
        //统一验证
        $result = static::commonCheck($params);
        $memberInfo = $result['memberInfo'];
        $member_id = $result['member_id'];
        $roomInfo = $result['roomInfo'];
        //验证待房屋信息是否存在
        static::checkResidentAudit($member_id, $params['room_id'],$memberInfo['name']);
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
        return $this->success($info);

    }

    /**
     * @api 编辑住户房屋信息
     * @author wyf
     * @date 2019/5/25
     * @param $params
     * @return array
     * @throws MyException
     * @throws \Throwable
     * @throws \yii\db\Exception
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
                    $model = static::_addResidentAudit($roomInfo, $member_id, $memberInfo, $params['room_id'], $params['identity_type'], $expired_time, $images);
                }
            } else {
                //数据直接进入待审核
                $model = static::_addResidentAudit($roomInfo, $member_id, $memberInfo, $params['room_id'], $params['identity_type'], $expired_time, $images);
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
                    $model = static::_addResidentAudit($roomInfo, $member_id, $memberInfo, $params['room_id'], $params['identity_type'], $expired_time, $images);
                }
                $delModel = $redidentAuditModel;
            } else {
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
        return $this->success($info);
    }

    /**
     * @api 进行数据比对,验证房屋信息是否存在
     * @author wyf
     * @date 2019/5/25
     * @param $mobile
     * @param $name
     * @param $room_id
     * @param $member_id
     * @param $identity_type
     * @return false|null|string
     * @throws \yii\db\Exception
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
     * @api 证件信息组合过滤
     * @author wyf
     * @date 2019/5/25
     * @param $params
     * @return array|string
     * @throws MyException
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
     * @api 房屋审核数据新增
     * @author wyf
     * @date 2019/5/25
     * @param $roomInfo
     * @param $member_id
     * @param $memberInfo
     * @param $room_id
     * @param $identity_type
     * @param $expired_time
     * @param $images
     * @throws MyException
     * @return PsResidentAudit
     */
    private static function _addResidentAudit($roomInfo, $member_id, $memberInfo, $room_id, $identity_type, $expired_time, $images)
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
     * @api 新增和编辑统一验证
     * @author wyf
     * @date 2019/5/24
     * @param $params
     * @return array
     * @throws MyException
     * @throws \yii\db\Exception
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
     * @api 验证待房屋信息是否存在
     * @author wyf
     * @date 2019/5/24
     * @param $member_id
     * @param $room_id
     * @return bool
     * @throws MyException
     * @throws \yii\db\Exception
     */
    public static function checkResidentAudit($member_id, $room_id,$member_name = '')
    {
        $residentAuditInfo = static::getResidentAuditView($member_id, $room_id,'',$member_name);
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
     * @api 验证房屋信息是否存在
     * @author wyf
     * @date 2019/5/24
     * @param $member_id
     * @param $room_id
     * @return bool
     * @throws MyException
     * @throws \yii\db\Exception
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
     * @api 获取用户房屋详情信息
     * @author wyf
     * @date 2019/5/24
     * @param $member_id
     * @param $room_id
     * @param $select
     * @return array|false
     * @throws \yii\db\Exception
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
     * @api 获取审核表中的用户房屋详情信息
     * @author wyf
     * @date 2019/5/24
     * @param $member_id
     * @param $room_id
     * @param $select
     * @return array|false
     * @throws \yii\db\Exception
     */
    public static function getResidentAuditView($member_id, $room_id, $select = "",$member_name='')
    {
        $select = empty($select) ? 'status,id' : $select;
        $residentAuditInfo = (new Query())
            ->select($select)
            ->from('ps_resident_audit')
            ->where(['room_id' => $room_id, 'member_id' => $member_id])
            ->andFilterWhere(['name'=>$member_name])
            ->andWhere(['!=', 'status', 1])
            ->createCommand()
            ->queryOne();
        return $residentAuditInfo;
    }

    /**
     * @api 工作提醒和消息中心添加
     * @author wyf
     * @date 2019/6/18
     * @param $community_id
     * @param $community_name
     * @param $status
     * @param $id
     * @param $member_id
     * @param $user_name
     * @param $address
     * @param $identity_type
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
        $res = $query->select(['ru.member_id', 'ru.name','ru.mobile','ru.identity_type'])
            ->from('ps_community_roominfo as cr')
            ->leftJoin('ps_room_user as ru', 'ru.room_id=cr.id')
            ->where(['cr.community_id' => $communityId, 'cr.group' => $group, 'cr.building' => $building,
                'cr.unit' => $unit, 'cr.room' => $room, 'ru.status' => 2])
            ->all();
        return $res;
    }
}
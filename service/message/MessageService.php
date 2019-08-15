<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/6/10
 * Time: 16:57
 */

namespace service\message;


use app\models\PsMessage;
use service\BaseService;
use yii\db\Query;

class MessageService extends BaseService
{
    protected $type = [1 => '系统通知', 2 => '服务提醒', 3 => '互动提醒'];
    /**
     * @var array 权限类型:1.住户管理权限,2.账单管理权限,3.报修管理权限,4.投票权限的用户,5.账单复核权限,6.报修管理权限且对应为指派人,
     * 7.有疑难问题权限,8.有报修管理且有按钮,9.投诉建议权限,10.小区活动权限,11.管家管理权限,12.服务评分权限,13.有邻里互动权限,14.账单核销权限
     */
    protected $auth_type = [
        '1' => 'residentsManage',
        '2' => 'billManage',
        '3' => 'repair',
        '4' => 'vote',
        '5' => 'gatheringCheck',
        '6' => '',//TODO 需要重新整理
        '7' => 'hard',
        '8' => 'property/repair/re-check-repair',//有复核按钮权限的
        '9' => 'complaintManagement',
        '10' => 'communityActivities',
        '11' => 'butlerManage',
        '12' => 'serviceValuation',
        '13' => 'neighbourHood',
        '14' => 'verification',
        '15' => 'exposure',
    ];

    /**
     * @api 创建消息模板
     * @author wyf
     * @date 2019/8/13
     * @param $params
     * @param bool $type
     * @throws \yii\db\Exception
     */
    public function addMessageTemplate($params,$type = true)
    {
        $data = [
            'community_id' => $params['community_id'] ?? 0,
            'id' => $params['id'] ?? 0,
            'member_id' => $params['member_id'] ?? 0,
            'user_name' => $params['user_name'] ?? '',
            'create_user_type' => $params['create_user_type'] ?? 2,
        ];
        //工作提醒
        if ($type) {
            $remind = $data;
            $remind['messageInfo'] = [
                'tmpId' => $params['remind_tmpId'],
                'tmpType' => 1,
                'data' => $params['remind']
            ];
            if (empty($params['assign_id'])) {
                $params['assign_id'] = [];
            }
            $this->addMessage($remind, 4, $params['remind_target_type'], $params['remind_auth_type'], $params['assign_id']);
        }
        //消息中心
        $info = $data;
        $info['messageInfo'] = [
            'tmpId' => $params['msg_tmpId'],
            'tmpType' => 2,
            'data' => $params['msg']
        ];
        $this->addMessage($info, $params['msg_type'], $params['msg_target_type'], $params['msg_auth_type'],$params['user_list'] ?? []);
    }

    /**
     * @api 新增消息内容 TODO Mq
     * @author wyf
     * @date 2019/6/12
     * @param array $params
     * @param int $type 消息类型:1.系统通知,2.服务提醒,3.互动提醒,4.工作提醒
     * @param int $target_type 跳转方式:1.默认跳转,2.住户详情页,3.对应房屋下账单详情,4.工单详情页,5.投票详情页,6.住户管理页面7.报修列表,8.疑难问题列表,
     * 9.投诉建议列表页,10.活动详情页,11.管家详情页,12.服务评分页,13.邻里互动页面,14.复核列表,15.核销列表,16曝光台列表
     * @param int $auth_type 权限类型:1.住户管理权限,2.账单管理权限,3.报修管理权限,4.投票权限的用户,5.复核/核销权限,6.报修管理权限且对应为指派人,
     * 7.有疑难问题权限,8.有报修管理且有按钮,9.投诉建议权限,10.小区活动权限,11.管家管理权限,12.服务评分权限,13.有邻里互动权限,14.核销权限,15曝光台 17支付宝申请
     * @param array $appointUserArray 需要推送的指定人数组
     * @return bool|string
     * @throws \yii\db\Exception
     */
    public function addMessage($params, $type, $target_type, $auth_type, $appointUserArray = array())
    {
        //获取有权限的B端用户,进行消息新增用户id格式:[1,2,3,4,5,6]//现有用户全部为B端用户
        if (empty($auth_type)) {
            return "权限类型不能为空";
        }
        if (!in_array($type, [1, 2, 3, 4])) {
            return "消息类型有误";
        }
        return true;
        /*if (!empty($appointUserArray)) {
            $uidArray = $appointUserArray;
        } else {
            $uidArray = $this->handleUserInfo($params['community_id'], $auth_type);//获取有权限的用户信息
            if (!$uidArray) {
                return false;
            }
        }
        $userInfo = $uidArray;
        MessagePushService::service()->add(
            $params['community_id'],
            $params['messageInfo'],
            $type,
            $target_type,
            $params['id'],
            $params['member_id'],
            $params['user_name'],
            $params['create_user_type'],
            $userInfo
        );
        return true;*/
    }

    /**
     * @api 获取有权限的B端用户id
     * @author wyf
     * @date 2019/6/12
     * @param $community_id
     * @param $type :权限类型:1.住户管理权限,2.账单管理权限,3.报修管理权限,4.投票权限的用户,5.复核权限,6.报修管理权限且对应为指派人,
     * 7.有疑难问题权限,8.有报修管理且有按钮,9.投诉建议权限,10.小区活动权限,11.管家管理权限,12.服务评分权限,13.有邻里互动权限,14.核销权限，15曝光台
     * @return array
     * @throws \yii\db\Exception
     */
    public function handleUserInfo($community_id, $type)
    {
        //获取有小区权限的用户信息
        $userInfo = (new Query())->select('manage_id')
            ->from('ps_user_community')->where(['community_id' => $community_id])
            ->createCommand()
            ->queryColumn();
        if (empty($userInfo)) {
            return [];
        }
        if ($type == 8) {
            $data = $this->auth_type[8];
            $params['action'] = $data[0];
            $params['route'] = $data[1];
        } else {
            $data = $this->auth_type[$type];
            $params['route'] = $data;
        }
        $params['userIds'] = $userInfo;
        $result = UserCenterService::service(2)->request('/userCenter/user/validatePermission', $params);
        if ($result['code'] == 1) {
            if (!empty($result['data']['list'])) {
                //ArrayHelper::getColumn($result['data']['list'], 'id');//无法提取数据
                $uidArray = [];
                foreach ($result['data']['list'] as $item) {
                    if ($item['authorised'] === true) {
                        $uidArray[] = $item['userId'];
                    }
                }
                return $uidArray;
            } else {
                return [];
            }
        } else {
            return [];
        }
    }

    /**
     * @api 获取消息列表
     * @author wyf
     * @date 2019/6/13
     * @param $params
     * @param $uid
     * @return array
     */
    public function getList($params, $uid)
    {
        $communityId = PsCommon::get($params, "community_id");  //小区id
        if (!$communityId) {
            return $this->failed("请选择小区");
        }
        $communityInfo = CommunityService::service()->getInfoById($communityId);
        if (empty($communityInfo)) {
            return $this->failed("请选择有效小区");
        }
        if (empty($params['message_type'])) {
            $type = "";
        } else {
            $type = $params['message_type'];
        }
        if (!isset($params['is_read'])) {
            $is_read = "";
        } else {
            $is_read = $params['is_read'];
        }
        //获取消息列表
        $query = self::getContent($communityId, $uid, $is_read, $type);
        if (empty($type)) {
            $query->andWhere(['!=', 'message.type', 4]);
        }
        $total = $query->count();
        if (empty($type) && $total == 0) {
            $data['total_unread'] = 0;
            $data['sys_unread'] = 0;
            $data['service_unread'] = 0;
            $data['interact_unread'] = 0;
            $data['list'] = [];
            $data['totals'] = 0;
            return $this->success($data);
        }
        //获取系统通知
        $sys_unread = self::unRead($communityId, $uid, 1);
        //获取服务提醒
        $service_unread = self::unRead($communityId, $uid, 2);
        //获取互动提醒
        $interact_unread = self::unRead($communityId, $uid, 3);
        $total_unread = $sys_unread + $service_unread + $interact_unread;
        $data['total_unread'] = (int)$total_unread;
        $data['sys_unread'] = (int)$sys_unread;
        $data['service_unread'] = (int)$service_unread;
        $data['interact_unread'] = (int)$interact_unread;
        if ($total == 0) {
            $data['list'] = [];
            $data['totals'] = 0;
            return $this->success($data);
        }
        if (!empty($params['rows']) && !empty($params['page'])) {
            $page = $params['page'] > ceil($total / $params['rows']) ? ceil($total / $params['rows']) : $params['page'];
            $list = $query->offset(($page - 1) * $params['rows'])->limit($params['rows'])->asArray()->all();
        } else {
            $list = $query->asArray()->all();
        }
        $info = [];
        foreach ($list as $item) {
            $info[] = [
                'id' => (int)$item['id'],
                'target_type' => (int)$item['target_type'],
                'target_id' => (int)$item['target_id'],
                'title' => $item['title'],
                'content' => !empty($item['content']) ? str_replace("<br>", "", $item['content']) : "",
                'type' => (int)$item['type'],
                'type_desc' => $this->type[$item['type']] ?? "",
                'is_read' => (int)$item['is_read'],
                'create_date' => date('Y-m-d H:i:s', $item['created_at']),
            ];
        }
        $data['list'] = $info;
        $data['totals'] = (int)$total;
        return $this->success($data);
    }

    /**
     * @api 获取消息详情
     * @author wyf
     * @date 2019/6/14
     * @param $id
     * @param array $userInfo
     * @return array
     * @throws MyException
     */
    public function view($id, $userInfo = [])
    {
        $messageInfo = PsMessageUser::find()
            ->alias('user')
            ->select('message.id,message.title,message.content,message.type,message.target_type,message.target_id,message.url,message.created_at,user.is_read,message.community_id')
            ->leftJoin('ps_message message', 'message.id = user.message_id')
            ->where(['message.id' => $id, 'user.user_type' => 1, 'user.deleted' => 1])
            ->asArray()
            ->one();
        if (empty($messageInfo)) {
            throw new MyException('无此消息');
        }
        $messageInfo['id'] = (int)$messageInfo['id'];
        $messageInfo['target_id'] = (int)$messageInfo['target_id'];
        $messageInfo['target_type'] = (int)$messageInfo['target_type'];
        $messageInfo['create_date'] = date('Y-m-d H:i:s', $messageInfo['created_at']);
        if (!empty($userInfo['id']) && $messageInfo['is_read']!=1) {
            PsMessageUser::updateAll(['is_read' => 1, 'read_time' => time(), 'updated_at' => time()], ['message_id' => $messageInfo['id'], 'user_id' => $userInfo['id']]);
            $content = "操作的ID有:".$messageInfo['id'];
            self::addLog($userInfo, $messageInfo['community_id'], 1, $content);
        }
        return $this->success($messageInfo);
    }

    /**
     * @api 消息操作
     * @author wyf
     * @date 2019/6/21
     * @param $params
     * @param $userInfo
     * @return array
     * @throws MyException
     */
    public function operation($params, $userInfo)
    {
        if (empty($userInfo)) {
            throw new MyException('用户不能为空');
        }
        if (!isset($params['type'])) {
            throw new MyException('操作类型不能为空');
        }
        if (!in_array($params['type'], [1, 2, 3])) {
            throw new MyException('操作类型有误');
        }
        if (!isset($params['message_type']) && $params['type'] == 3) {
            throw new MyException('消息类型不能为空');
        }
        if (isset($params['message_type'])) {
            if (!in_array($params['message_type'], [0, 1, 2, 3, 4]) && $params['type'] == 3) {
                throw new MyException('操作类型有误');
            }
        }
        try {
            $uid = $userInfo['id'];
            if ($params['type'] == 3) {
                if ($params['message_type'] == 0) {
                    $content = '全部已读';
                    PsMessageUser::updateAll(['is_read' => 1, 'read_time' => time(), 'updated_at' => time()], ['user_id' => $uid]);
                } else {
                    //获取当前消息id
                    $message_ids = PsMessage::find()
                        ->alias('m')
                        ->select('message_id')
                        ->leftJoin('ps_message_user user', 'user.message_id = m.id')
                        ->where(['m.type' => $params['message_type'], 'user.user_id' => $uid, 'user.deleted' => 1, 'user.is_read' => 2])
                        ->column();
                    if ($message_ids) {
                        foreach ($message_ids as $item) {
                            PsMessageUser::updateAll(['is_read' => 1, 'read_time' => time(), 'updated_at' => time()], ['user_id' => $uid, 'message_id' => $item]);
                        }
                        $content = "操作的ID有:" . implode(',', $message_ids);
                    } else {
                        $content = '';
                    }

                    //PsMessageUser::batchUpdateValue(['is_read' => 1, 'read_time' => time(), 'updated_at' => time()], $filter);
                }
                self::addLog($userInfo, $params['community_id'], $params['type'], $content);
                return $this->success();
            }
            $data = [];
            $filter = [];
            if (empty($params['ids'])) {
                throw new MyException('服务不可用');
            }
            if (!is_array($params['ids'])) {
                throw new MyException('服务不可用');
            }
            $times = time();
            foreach ($params['ids'] as $item) {
                if ($params['type'] == 1) {
                    $data['is_read'] = 1;
                    $data['read_time'] = $times;
                    $data['updated_at'] = $times;
                } else {
                    $data['deleted'] = 2;
                    $data['updated_at'] = $times;
                }
                $filter[] = [
                    'user_id' => $uid,
                    'message_id' => $item
                ];
            }
            PsMessageUser::batchUpdateValue($data, $filter);
            $content = "操作的ID有:" . implode(',', $params['ids']);
        } catch (\Exception $e) {
            throw new MyException('操作失败');
        }
        self::addLog($userInfo, $params['community_id'], $params['type'], $content);
        return $this->success();
    }

    /**
     * @api 添加日志
     * @author wyf
     * @date 2019/6/21
     * @param array $userInfo
     * @param $community_id
     * @param $type
     * @param $content
     */
    private static function addLog($userInfo = [], $community_id, $type, $content = "")
    {
        if ($type == 2) {
            $operate_type = '消息删除';
        } elseif ($type == 1) {
            $operate_type = '消息已读';
        } else {
            $operate_type = '消息全部已读';
        }
        if (!empty($userInfo)) {
            $operate = [
                "community_id" => $community_id,
                "operate_menu" => "消息中心",
                "operate_type" => $operate_type,
                "operate_content" => $content
            ];
            OperateService::addComm($userInfo, $operate);
        }
    }

    /**
     * @api 获取工作提醒
     * @author wyf
     * @date 2019/6/13
     * @param $params
     * @return array
     */
    public function getWorkerRemind($params)
    {
        $data = [];
        $query = self::getContent($params['community_id'], $params['user_id'], 2, 4);
        $total = $query->count();
        $data['totals'] = (int)$total;
        if ($total == 0) {
            $data['list'] = [];
            return $this->success($data);
        }
        if (!empty($params['rows']) && !empty($params['page'])) {
            $list = $query->offset(($params['page'] - 1) * $params['rows'])->limit($params['rows'])->orderBy('id desc')->asArray()->all();
        } else {
            $list = $query->orderBy('id desc')->asArray()->all();
        }
        $info = [];
        foreach ($list as $item) {
            $info[] = [
                'id' => $item['id'],
                'message_type' => $item['type'],
                'title' => $item['title'],
                'url' => $item['url'],
                'create_date' => date('Y-m-d H:i:s', $item['created_at']),
                'target_type' => $item['target_type'],
                'target_id' => $item['target_id'],
            ];
        }
        $data['list'] = $info;
        return $this->success($data);
    }

    /**
     * @api 获取消息
     * @author wyf
     * @date 2019/6/11
     * @param $communityId
     * @param $uid
     * @param int $is_read
     * @param string $type
     * @return \yii\db\ActiveQuery
     */
    public static function getContent($communityId, $uid, $is_read = 2, $type = "")
    {
        $query = PsMessageUser::find()
            ->alias('user')
            ->select('message.id,message.title,message.content,message.type,message.target_type,message.target_id,message.url,message.created_at,user.is_read')
            ->leftJoin('ps_message message', 'message.id = user.message_id')
            ->where(['user.user_id' => $uid, 'user.user_type' => 1, 'user.deleted' => 1])
            ->andFilterWhere(['message.type' => $type, 'user.is_read' => $is_read])
            ->andFilterWhere(['message.community_id' => $communityId])
            ->orderBy("message.id desc");
        return $query;
    }

    /**
     * @api 获取未读数量
     * @author wyf
     * @date 2019/6/13
     * @param $communityId
     * @param $uid
     * @param int $type
     * @return int|string
     */
    public static function unRead($communityId, $uid, $type = 0)
    {
        if (empty($type)) {
            $type = '';
        }
        $query = PsMessageUser::find()
            ->alias('user')
            ->select('message.id,message.title,message.content,message.type,message.target_type,message.url,user.is_read')
            ->leftJoin('ps_message message', 'message.id = user.message_id')
            ->where(['user.user_id' => $uid, 'user.user_type' => 1, 'user.is_read' => 2, 'user.deleted' => 1])
            ->andFilterWhere(['message.community_id' => $communityId]);
        if (!empty($type)) {
            $query->andFilterWhere(['message.type' => $type]);
        } else {//说明是只获取消息
            $query->andWhere(['!=', 'message.type', 4]);
        }
        return $query->count();
    }
}
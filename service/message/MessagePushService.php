<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/6/17
 * Time: 16:02
 */

namespace service\message;


use app\models\PsMessage;
use app\models\PsMessageUser;
use common\core\SysClient;
use service\BaseService;
use service\producer\MqProducerService;

class MessagePushService extends BaseService
{
    /**
     * @api 消息中心和工作提醒推送到mq中
     * @author wyf
     * @date 2019/6/17
     * @param int $community_id 小区id
     * @param array $messageInfo 消息内容['tmpId'=>1,'tmpType'=>1,'data'=>[""=>"",""=>""]]
     * @param int $type 消息类型:1.系统通知,2.服务提醒,3.互动提醒,4.工作提醒
     * @param int $target_type 跳转方式:1.默认跳转,2.住户详情页,3.对应房屋下账单详情,4.工单详情页,5.投票详情页,6.住户管理页面7.报修列表,8.疑难问题列表,
     * 9.投诉建议列表页,10.活动详情页,11.管家详情页,12.服务评分页,13.邻里互动页面,14.复核列表,15.核销列表
     * @param int $target_id 各个业务所对应的id
     * @param int $member_id 新增消息的用户id
     * @param string $create_name 创建人姓名
     * @param int $create_user_type 创建人类型：1.B端用户,2.C端用户
     * @param array $userInfo 需要新增的用户信息['user_id'=>407,'user_type'=>1]//user_type(用户类型):1.B端用户,2.C端用户
     * @return bool|null|string
     */
    public function add($community_id, $messageInfo, $type, $target_type, $target_id, $member_id, $create_name, $create_user_type, $userInfo)
    {

        $data = [
            'community_id' => $community_id,
            'messageInfo' => $messageInfo,
            'type' => $type,
            'target_type' => $target_type,
            'target_id' => $target_id,
            'url' => "",
            'create_id' => $member_id,
            'is_send' => 1,
            'create_name' => $create_name,
            'create_user_type' => $create_user_type,
            'created_at' => time(),
            'updated_at' => time(),
            'userInfo' => $userInfo,
        ];

        //跳转类型转换
        $data['target_type'] = MessageTemplateService::service()->transTargetType($target_type);
        MessageSendService::service()->addMessage($data);
        return true;
    }

    /**
     * @api 获取消息内容标题和内容(消息中心中title和content有数据,工作提醒title为空,content有数据)
     * @author wyf
     * @date 2019/6/13
     * @param $data
     * @param int $tmpId 模板id
     * @param int $type 模板类型:1.工作提醒,2.消息中心
     * @return array
     */
    private static function getData($data, $tmpId, $type = 1)
    {
        return MessageTemplateService::service()->init($tmpId, $type)->getTempContent($data);
    }

    /**
     * @api 新增
     * @author wyf
     * @date 2019/6/17
     * @param $params
     * @return bool
     */
    public function addMessage($params)
    {
        $trans = \Yii::$app->getDb()->beginTransaction();
        try {
            $info = self::getData($params['messageInfo']['data'], $params['messageInfo']['tmpId'], $params['messageInfo']['tmpType']);
            if ($info) {
                if ($params['type'] == 4) {
                    $title = $info['content'] ?? "";
                    $content = "";
                } else {
                    $title = $info['title'] ?? "";
                    $content = $info['content'] ?? "";
                }
            } else {
                return false;
            }
            $data['community_id'] = $params['community_id'];
            $data['title'] = $title;
            $data['content'] = $content;
            $data['type'] = $params['type'];
            $data['target_type'] = $params['target_type'];
            $data['target_id'] = $params['target_id'];
            $data['url'] = $params['url'];//跳转方式为1是存入url链接,其他跳转方式存入相对应的跳转请求参数
            $data['send_time'] = time();
            $data['is_send'] = 2;
            $data['create_id'] = $params['create_id'];
            $data['create_name'] = $params['create_name'];
            $data['create_user_type'] = $params['create_user_type'];
            $data['created_at'] = $params['created_at'];
            $data['updated_at'] = $params['updated_at'];
            $message = new PsMessage();
            $message->setAttributes($data);
            if (!$message->save()) {
                return false;
            }
            $message_id = $message->id;
            $messageInfo = [];
            //获取b端有权限的用户信息
            if (!empty($params['userInfo']) && is_array(($params['userInfo']))) {
                foreach ($params['userInfo'] as $item) {
                    $messageInfo['user_id'][] = $item;
                    $messageInfo['message_id'][] = $message_id;
                    $messageInfo['created_at'][] = time();
                    $messageInfo['updated_at'][] = time();
                }
            }
            if (!empty($messageInfo)) {
                PsMessageUser::model()->batchInsert($messageInfo);
            }
            $trans->commit();
        } catch (\Exception $exception) {
            $trans->rollBack();
            return false;
        }

        if ($params['type'] == 4 && !empty($params['userInfo'])) {
            //推送给物业后台(一次性推送所有数据)
            $data = [
                'id' => $message_id,
                'message_type' => $data['type'],
                'title' => $data['title'],
                'create_date' =>date('Y-m-d H:i:s',time()),
                'target_id' => $data['target_id'],
                'target_type' => $data['target_type'],
            ];
            SysClient::getInstance()->send(1, $params['community_id'], $params['userInfo'], $data);
        }
        return true;
    }
}
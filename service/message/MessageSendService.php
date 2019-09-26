<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/9/26
 * Time: 16:29
 */

namespace service\message;

use app\models\PsMessage;
use app\models\PsMessageUser;
use service\BaseService;

class MessageSendService extends BaseService
{
    /**
     * @api 获取消息内容标题和内容(消息中心中title和content有数据,工作提醒title为空,content有数据)
     * @author wyf
     * @date 2019/6/13
     * @param $data
     * @param int $tmpId 模板id
     * @param int $type 模板类型:1.工作提醒,2.消息中心
     * @return array
     */
    public function getData($data, $tmpId, $type = 1)
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
            $info = $this->getData($params['messageInfo']['data'], $params['messageInfo']['tmpId'], $params['messageInfo']['tmpType']);
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
        //TODO 暂时不做实时推送
//        if ($params['type'] == 4 && !empty($params['userInfo'])) {
//            //推送给物业后台(一次性推送所有数据)
//            $data = [
//                'id' => $message_id,
//                'message_type' => $data['type'],
//                'title' => $data['title'],
//                'create_date' =>date('Y-m-d H:i:s',time()),
//                'target_id' => $data['target_id'],
//                'target_type' => $data['target_type'],
//            ];
//            SysClient::getInstance()->send(1, $params['community_id'], $params['userInfo'], $data);
//        }
        return true;
    }
}
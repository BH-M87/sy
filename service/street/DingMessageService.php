<?php
/**
 * User: ZQ
 * Date: 2019/9/5
 * Time: 11:27
 * For: 发送钉钉工作通知
 */

namespace service\street;


use app\models\Department;
use app\models\UserInfo;
use common\core\PsCommon;

class DingMessageService extends BaseService
{
    public function send($id,$userList,$title,$organization_id,$operator_name,$create_at)
    {
        //获取这些对象对应的钉钉ID
        $dingdingList = UserInfo::find()->select(['ding_user_id'])->where(['user_id'=>$userList])->andWhere(['<>','ding_user_id',''])->column();
        //给这些未读的对象发送钉钉消息
        $sendData['title'] = '通知通报';
        $markdown = "#### 通知通报";
        $markdown .= "您有一条通知通报消息，请及时查收。";
        $markdown .= $title;
        $sendData['markdown'] = $markdown;
        $departName = Department::find()->select('department_name')->where(['id'=>$organization_id])->asArray()->scalar();
        $sendData['single_title'] = $departName."|".$operator_name." ".date('Y-m-d H:i',$create_at);
        //$query = urlencode("id=".$id);
        $sendData['single_url'] = 'eapp://pages/noticeDetails/noticeDetails?id='.$id;//钉钉端详情页的地址
        $result['data'] = $this->sendMessage(1,$sendData);
        $result['userList'] = $dingdingList ? $dingdingList: [];
        return $result;
    }

    /**
     * 发送工作通知
     * @param $type
     * @param $data
     */
    public function sendMessage($type,$data)
    {
        $backData = [];
        switch($type){
            //发送卡片工作通知
            case "1":
                $backData['msgtype'] = 'action_card';
                $backData['action_card'] = [
                    'title'=>PsCommon::get($data,'title'),
                    'markdown'=>PsCommon::get($data,'markdown'),
                    'single_title'=>PsCommon::get($data,'single_title'),
                    'single_url'=>PsCommon::get($data,'single_url'),
                ];
                break;
            //发送文本工作通知
            case "2":
                $backData['msgtype'] = 'text';
                $backData['text'] = [
                    'content'=>PsCommon::get($data,'content`'),
                ];
                break;
            default:
                $backData['msgtype'] = 'action_card';
                $backData['action_card'] = [
                    'title'=>PsCommon::get($data,'title'),
                    'markdown'=>PsCommon::get($data,'markdown'),
                    'single_title'=>PsCommon::get($data,'single_title'),
                    'single_url'=>PsCommon::get($data,'single_url'),
                ];
                break;
        }
        return $backData;
    }
}
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
use common\core\Curl;
use common\core\PsCommon;

class DingMessageService extends BaseService
{
    public function send($id,$userList,$title,$organization_id,$operator_name,$create_at)
    {
        //获取这些对象对应的钉钉ID
        $dingdingList = UserInfo::find()->select(['ding_user_id'])->where(['user_id'=>$userList])->andWhere(['<>','ding_user_id',''])->column();
        $departName = UserService::service()->getDepartmentNameByCode($organization_id);
        //给这些未读的对象发送钉钉消息
        $sendData['title'] = '通知通报';
        $br = "
        ";
        /*$markdown = "**通知通报**".$br;
        $markdown .= $title.$br;
        $markdown .= $departName."/".$operator_name.$br;
        $markdown .= "提醒时间：".date("Y-m-d H:i:s");*/
        $markdown = "#### **通知通报**";
        $markdown .= "
        ".$title."
".$departName."/".$operator_name."
"."提醒时间：".date("Y-m-d H:i:s");
        $sendData['markdown'] = $markdown;
        $sendData['single_title'] = "查看详情";
        //$query = urlencode("id=".$id);
        $sendData['single_url'] = 'eapp://pages/noticeDetails/noticeDetails?id='.$id;//钉钉端详情页的地址
        $data = $this->sendMessage(1,$sendData);
        $this->sendDingMessage($data,$dingdingList);
        $result['data'] = $data;
        $result['userList'] = $dingdingList ? $dingdingList: [];
        $result = [];
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

    public function getAccessToken()
    {
        $appKey = 'dingvxqretqs7uduiovc';
        $appSecret = '06YC5GujdrjBqydJuEt4P6SieVl9YdmZZwVXJ0XSOQPJ1seJ1mSEC1HIpHGJqhN2';
        $url = 'https://oapi.dingtalk.com/gettoken?appkey='.$appKey.'&appsecret='.$appSecret;
        $result = json_decode(Curl::getInstance()->get($url),true);
        $access_token = '';
        if($result['errcode'] == '0'){
            $access_token = $result['access_token'];
        }
        return $access_token;
    }

    //发送钉钉通知
    public function sendDingMessage($msgdData,$dingList)
    {
        if($dingList){
            $access_token = $this->getAccessToken();
            $url = "https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=".$access_token;
            foreach($dingList as $key=>$value){
                $data['agent_id'] = 281128929;
                $data['userid_list'] = $value."";
                $data['msg'] = json_encode($msgdData);
                $res = Curl::getInstance()->post($url,$data);
                \Yii::info("dingReturn-".$value.":".$res,"api");
            }
        }
    }

    //测试-查看发送情况
    public function getMessageStatus($task_id)
    {
        $access_token = $this->getAccessToken();
        $url = "https://oapi.dingtalk.com/topapi/message/corpconversation/getsendresult?access_token=".$access_token;
        $data['agent_id'] = 281128929;
        $data['task_id'] = $task_id."";
        $res = Curl::getInstance()->post($url,$data);
        var_dump($res);die;
    }

    public function sendTaskMessage($title,$dingId)
    {
        //给这些未读的对象发送钉钉消息
        $sendData['title'] = '工作任务';
        $br = "<br>";
        $markdown = "**工作任务**".$br;
        $markdown .= $title.$br;
        $markdown .= "提醒时间：".date("Y-m-d H:i:s");
        $sendData['markdown'] = $markdown;
        $sendData['single_title'] = "查看详情";
        //$query = urlencode("id=".$id);
        $sendData['single_url'] = 'eapp://pages/noticeDetails/noticeDetails';//钉钉端详情页的地址
        $data = $this->sendMessage(1,$sendData);
        $this->sendDingMessage($data,$dingId);
    }

}
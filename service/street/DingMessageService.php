<?php
/**
 * User: ZQ
 * Date: 2019/9/5
 * Time: 11:27
 * For: 发送钉钉工作通知
 */

namespace service\street;


use common\core\PsCommon;

class DingMessageService extends BaseService
{

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
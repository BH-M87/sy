<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/6/17
 * Time: 16:02
 */

namespace app\services;


use service\BaseService;

class MessagePushService extends BaseService
{
    //数据推送调用的接口
    private $push_url = [
        'message-add' => '/inner/v1/message/add',  //消息新增
    ];

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
        $url = \Yii::$app->getModule('wisdompark')->params['open_api_url_message'];
        $url_send = $url . $this->push_url['message-add'];
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
        return self::curlExec($url_send, $data);
    }

    private static function curlExec($url, $param)
    {
        $data_send['data'] = json_encode($param);
        $options['CURLOPT_HTTPHEADER'] = [
            "application/json;charset=UTF-8",
        ];
        $curl = new Curl($options);
        $res = $curl->post($url, $data_send);
        return $res;
    }
}
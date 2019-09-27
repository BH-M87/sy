<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/6/17
 * Time: 17:39
 */

namespace service\message;

use service\BaseService;
use yii\helpers\ArrayHelper;

class MessageTemplateService extends BaseService
{
    protected $template;
    protected $title;

    /**
     * @var array 工作提醒模板
     */
    protected $remind = [
        ['key' => '2', 'content' => '住户{key0}成功认证本小区，快去查看吧！'],//跳转住户详情
        ['key' => '3', 'content' => '住户{key0}已完成支付宝线上缴费，快去查看吧！'],//跳转到对应房屋下账单详情
        ['key' => '4', 'content' => '您有一条新的工单已完成支付，快去查看吧！'],//跳转到对应工单详情页
        ['key' => '5', 'content' => '住户{key0}已完成在线投票，快去查看吧！'],//跳转到投票详情页
        ['key' => '6', 'content' => '您有一条新的住户信息待审核，快去查看吧！'],//跳转到住户管理页面
        ['key' => '7', 'content' => '您有一条新的工单需要处理，快去查看吧！'],//跳转到报修列表
        ['key' => '8', 'content' => '您有一条新的工单标记为疑难问题，快去查看吧！'],//跳转到疑难问题列表
        ['key' => '9', 'content' => '您有一条新的{key0}需要处理，快去查看吧！'],//跳转到投诉建议列表页
        ['key' => '10', 'content' => '住户{key0}{key1}参加活动，快去查看吧！'],//跳转到活动详情页
        ['key' => '11', 'content' => '管家{key0}收到一条新的{key1}，快去查看吧！'],//跳转到管家详情页
        ['key' => '12', 'content' => '小区{key0}月服务评分已更新，快去查看吧！'],//跳转到服务评分页
        ['key' => '13', 'content' => '住户{key0}发布了一条新的动态，快去查看吧！'],//跳转到邻里互动页面
        ['key' => '14', 'content' => '您有一条新的工单待复核，快去查看吧！'],//跳转到工单列表(报修工单复核)
        ['key' => '15', 'content' => '您有一条新的工单已完成评价，快去查看吧！'],//跳转到工单详情页
        ['key' => '16', 'content' => '您有一条新的账单待复核，快去查看吧！'],//跳转到复核列表
        ['key' => '17', 'content' => '您有新的账单待核销，快去查看吧！'],//跳转到核销列表
        ['key' => '18', 'content' => '您有一条新的曝光需要处理，快去查看吧！'],//跳转到曝光台列表
    ];

    protected $oldRemindTargetType = [
        ['key' => 'userAuth', 'value' => '2'],
        ['key' => 'userRoomBillPay', 'value' => '3'],
        ['key' => 'repairIssuePay', 'value' => '4'],
        ['key' => 'repairIssuePraise', 'value' => '15'],
        ['key' => 'userVote', 'value' => '5'],
        ['key' => 'userRoomCheck', 'value' => '6'],
        ['key' => 'billCheck', 'value' => '16'], //账单复核
        ['key' => 'repairIssueAdd', 'value' => '7'],
        ['key' => 'repairIssueAssign', 'value' => '7'],
        ['key' => 'repairIssueMarkHard', 'value' => '8'],
        ['key' => 'repairIssueRecheck', 'value' => '14'],
        ['key' => 'complainAdd', 'value' => '9'], //投诉建议提交
        ['key' => 'userActivityJoin', 'value' => '10'],
        ['key' => 'guanjiaPraise', 'value' => '11'],//管家评价
        ['key' => 'communityPraise', 'value' => '12'],//小区评价
        ['key' => 'communityDT', 'value' => '13'],//小区动态
        ['key' => 'billChargeOff', 'value' => '17'],//账单核销
    ];

    protected $sqwnRemindTargetType = [
        ['key' => 'userAuth', 'value' => '1'],
        ['key' => 'userRoomBillPay', 'value' => '2'],
        ['key' => 'repairIssuePay', 'value' => '3'],
        ['key' => 'repairIssuePraise', 'value' => '4'],
        ['key' => 'userVote', 'value' => '5'],
        ['key' => 'userRoomCheck', 'value' => '6'],
        ['key' => 'billCheck', 'value' => '7'], //账单复核
        ['key' => 'repairIssueAdd', 'value' => '8'],
        ['key' => 'repairIssueAssign', 'value' => '8'],
        ['key' => 'repairIssueMarkHard', 'value' => '10'],
        ['key' => 'repairIssueRecheck', 'value' => '11'],
        ['key' => 'complainAdd', 'value' => '12'], //投诉建议提交
        ['key' => 'userActivityJoin', 'value' => '13'],
        ['key' => 'guanjiaPraise', 'value' => '14'],//管家评价
        ['key' => 'communityPraise', 'value' => '15'],//小区评价
        ['key' => 'communityDT', 'value' => '16'],//小区动态
        ['key' => 'billChargeOff', 'value' => '17'],//账单核销
    ];

    /**
     * @var array 消息中心模板
     */
    protected $messageTemp = [
        [
            'key' => '2',
            'title' => '住户完成认证通知',
            'content' => '住户{key0}成功认证本小区，快去查看吧！<br>所属房屋：{key1}；<br>住户信息：{key2}；<br>认证时间：{key3}'
        ],//跳转住户详情
        [
            'key' => '3',
            'title' => '住户完成线上缴费通知',
            'content' => '住户{key0}已完成支付宝线上缴费，快去查看吧！<br>所属房屋：{key1}；<br>住户信息：{key2}；<br>缴费时间：{key3}；<br>缴费账单：{key4}<br>'
        ],//跳转到对应房屋下账单详情
        [
            'key' => '4',
            'title' => '报修工单付款通知',
            'content' => '您有一条新的工单已完成支付，快去查看吧！<br>工单编号：{key0}；<br>工单类型：{key1}；<br>工单金额：{key2}；<br>付款方式：{key3}<br>'
        ],//跳转到对应工单详情页
        [
            'key' => '5',
            'title' => '住户完成投票通知',
            'content' => '住户{key0}已完成在线投票，快去查看吧！<br>投票名称：{key1}<br>所属房屋：{key2}；<br>住户信息：{key3}；<br>提交时间：{key4}<br>'
        ],//跳转到投票详情页
        [
            'key' => '6',
            'title' => '住户信息审核提醒',
            'content' => '您有一条新的住户信息待审核，快去查看吧！<br>所属小区：{key0}；<br>所属房屋：{key1}；<br>住户信息：{key2}；<br>住户身份：{key3}；<br>提交时间：{key4}<br>'
        ],//跳转到住户管理页面
        [
            'key' => '7',
            'title' => '报修工单处理提醒',
            'content' => '您有一条新的工单需要处理，快去查看吧！<br>工单编号：{key0}；<br>工单类型：{key1}；<br>提交时间：{key2}<br>'
        ],//跳转到报修列表
        [
            'key' => '8',
            'title' => '报修工单处理提醒',
            'content' => '您有一条新的工单标记为疑难问题，快去查看吧！<br>工单编号：{key0}；<br>工单类型：{key1}；<br>提交时间：{key2}；<br>疑难说明：{key3}<br>'
        ],//跳转到疑难问题列表
        [
            'key' => '9',
            'title' => '投诉建议处理提醒',
            'content' => '您有一条新的{key0}需要处理，快去查看吧！<br>业主姓名：{key1}；<br>内容：{key2}；<br>提交时间：{key3}<br>'
        ],//跳转到投诉建议列表页
        [
            'key' => '10',
            'title' => '住户活动报名通知',
            'content' => '住户{key0}已报名参加{key1}活动，快去查看吧！<br>活动名称：{key2}；<br>所属房屋：{key3}；<br>住户信息：{key4}；<br>报名时间：{key5}<br>'
        ],//跳转到活动详情页
        [
            'key' => '18',
            'title' => '住户取消报名通知',
            'content' => '住户{key0}已取消报名{key1}活动，快去查看吧！<br>活动名称：{key2}；<br>所属房屋：{key3}；<br>住户信息：{key4}；<br>取消报名时间：{key5}<br>'
        ],//跳转到活动详情页
        [
            'key' => '11',
            'title' => '管家评价更新通知',
            'content' => '管家{key0}收到一条新的{key1}，快去查看吧！<br>管家名称：{key2}；<br>评价内容：{key3}；<br>住户信息：{key4}；<br>所属房屋：{key5}；<br>评价时间：{key6}<br>'
        ],//跳转到管家详情页
        [
            'key' => '12',
            'title' => '小区服务评分更新通知',
            'content' => '小区{key0}月服务评分已更新，快去查看吧！ <br> 服务评分：{key1}；<br>评价内容：{key2}；<br>住户信息：{key3}；<br>所属房屋：{key4}；<br>评价时间：{key5}<br>'
        ],//跳转到服务评分页
        [
            'key' => '13',
            'title' => '邻里互动通知',
            'content' => '住户{key0}发布了一条新的动态，快去查看吧！<br>发布内容：{key1}；<br>住户信息：{key2}；<br>所属房屋：{key3}；<br>发布时间：{key4}<br>'
        ],//跳转到邻里互动页面
        [
            'key' => '14',
            'title' => '报修工单复核提醒',
            'content' => '您有一条新的工单待复核，快去查看吧！<br>工单编号：{key0}；<br>工单类型：{key1}；<br>提交时间：{key2}'
        ],//跳转到工单列表(报修工单复核)
        [
            'key' => '15',
            'title' => '报修工单评价通知',
            'content' => '您有一条新的工单已完成评价，快去查看吧！<br>工单编号：{key0}；<br>工单类型：{key1}；<br>评价内容：{key2}；<br>评价时间：{key3}<br>'
        ],//跳转到工单详情页
        [
            'key' => '16',
            'title' => '账单复核提醒',
            'content' => '您有一条新的账单待复核，快去查看吧！<br>交易流水号：{key0}；<br>关联房屋：{key1}；<br>复核/核销金额：{key2}<br>'
        ],//跳转到复核/核销列表
        [
            'key' => '17',
            'title' => '账单核销提醒',
            'content' => '您有新的账单待核销，快去查看吧！'
        ],//跳转到复核/核销列表
        [
            'key' => '19',
            'title' => '您有一条新的曝光需要处理，快去查看吧！',
            'content' => '您有一条新的曝光需要处理，快去查看吧！<br> 曝光类型：{key0} <br> 住户信息：{key1}；<br> 问题地点：{key2}<br> 问题描述：{key3}<br>提交时间：{key4}<br>'
        ],//跳转到曝光台
    ];

    /**
     * @api 获取模板信息
     * @author wyf
     * @date 2019/6/12
     * @param int $tmpId
     * @param int $type 模板类型:1.工作提醒2.消息中心
     * @return $this|string
     */
    public function init($tmpId, $type)
    {
        if ($type == 1) {
            $remind = ArrayHelper::index($this->remind, null, 'key');
            if (!$tmpId || !isset($remind[$tmpId])) {
                return "模版不存在";
            }
            $this->template = $remind[$tmpId][0]['content'];
            $this->title = '';
        } else {
            $messageTemp = ArrayHelper::index($this->messageTemp, null, 'key');
            if (!$tmpId || !isset($messageTemp[$tmpId])) {
                return "模版不存在";
            }
            $this->template = $messageTemp[$tmpId][0]['content'];
            $this->title = $messageTemp[$tmpId][0]['title'];
        }
        return $this;
    }

    /**
     * @api 获取模板标题和内容
     * @author wyf
     * @date 2019/6/12
     * @param $data
     * @return array|string
     */
    public function getTempContent($data)
    {
        if (empty($data) || !is_array($data)) {
            return "数据格式不正确";
        }
        $content = $this->parseContent($data);
        if (!$content) {
            return "数据内容不存在";
        }
        return ['title' => $this->title, 'content' => $content];
    }

    public function transTargetType($oldTargetType)
    {
        $key = '';
        foreach ($this->oldRemindTargetType as $typeList) {
            if ($typeList['value'] == $oldTargetType) {
                $key = $typeList['key'];
            }
        }
        if ($key) {
            foreach ($this->sqwnRemindTargetType as $typeList) {
                if ($key == $typeList['key']) {
                    return $typeList['value'];
                }
            }
        }
        return '100';
    }

    /**
     * @api 数据替换
     * @author wyf
     * @date 2019/6/12
     * @param $data
     * @return mixed
     */
    private function parseContent($data)
    {
        $keys = $values = [];
        foreach ($data as $k => $v) {
            $keys[] = '{key' . $k . '}';//{key0} {key1}
            $values[] = $v;
        }
        return str_replace($keys, $values, $this->template);
    }
}
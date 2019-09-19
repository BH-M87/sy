<?php
/**
 * 短信公共服务，仅开放send, valid两个方法public
 * 短信验证使用cache验证，sms_history表仅做记录，cache有效期30分钟，同一个手机号发送同一个模版的短信，3分钟之内无法重复发送
 * @update 2018-05-09 代码优化版本，去除业主认证走老会员中心逻辑 TODO 新会员中心?是否需要?
 * @author shenyang
 * @date 2017/7/24
 */
namespace service\common;

use common\core\PsCommon;
use service\BaseService;
use Yii;
use common\core\Curl;
use app\models\PsSmsHistory;
use yii\web\HttpException;

Class SmsService extends BaseService
{
    //短信接口地址
    //private $url = 'http://jjt.louzhanggui.com/index.php?r=SendSms';
    private $url = 'http://192.168.13.20:8819/sendMsgService/sendContent';
    private $test_url = 'http://192.168.13.21:8819/sendMsgService/sendContent';
    public $phone;
    public $template = [];//当前实例模版
    public $templateId;//当前模版ID
    public $duplicate = true;//重复发送验证，默认为true
    public $duplicateTime = 180;//手机号180s内不能重复发送

    public $errorCount = false;//统计错误次数

    //sms_history表字段自定义属性
    public $isRemind = 0;
    public $isNew = 1;
    public $objectId = 0;

    //短信模版 TODO 496的短信模版为{key0}（邻易联App登录验证码，30分钟内有效），部分短信验证码模版错误
    private $templates = [
        1 => [
            'id' => 515,
            'captcha' => 1,//是否是验证码
            'content' => '{key0}（系统登录验证码，30分钟内有效）',
            'source' => 'shopWechat',
            'operat_name' => 'shopWechat'
        ],
        2 => [
            'id' => 515,
            'captcha' => 1,
            'content' => '{key0}（邻易联App登录验证码，30分钟内有效）',
            'source' => 'linyilianapp',
            'operat_name' => 'linyilianapp',
        ],
        3 => [
            'id'=>496,
            'content' => '亲爱的业主，您好，现已将一张{key0}发放至您的账户，核销码为{key1}，祝您生活愉快。'
        ],
        4 => [
            'id' => 514,
            'captcha' => 1,
            'content'=>'您的验证码为:{key0},请尽快填写验证码完成身份认证',
        ],
        5 => [
            'id'=>321,
            'content'=> "您好，业主{key0}（{key1}）提交了一个报事报修订单，请尽快登录系统分配",
        ],
        6 => [
            'id'=>331,
            'content'=>'您好，您的报事报修订单已受理，维修人员将尽快与您联系',
        ],
        7 => [
            'id'=>341,
            'content'=>'您好，您的报事报修订单已处理完成，希望已经解决您的问题，如还有问题，欢迎继续反馈',
        ],
        8 => [
            'id'=>'473',
            'content'=>"您好，{key0}{key1}提交了一个报事报修订单，请尽快登录系统分配",
        ],
        9 => [
            'id'=>'351',
            'content'=>"您已开通邻易联管理系统，用户名为您的手机号，初始登录密码为：{key0}，请及时登录系统修改密码，请勿告知他人。",
        ],
        10 => [
            'id'=>'461',
            'content'=>"您重置密码的验证码为: {key0},有效期10分钟,请填写验证码重置密码!",
        ],
        11 => [
            'id'=>'479',
            'content'=>"您好，您的报事报修订单已受理，维修人员{key0}将尽快与您联系",
        ],
        12 => [
            'id'=>'480',
            'content'=>"您好，新的报事报修订单已分配给您，请及时打开APP查看并处理。",
        ],
        13 => [
            'id'=>'481',
            'content'=>"您好，您的报事报修订单还未支付，请尽快登录支付宝生活号付款，如有疑问请拨打电话:{key0}",
        ],
        15 => [
            'id'=>'492',
            'content'=> "您的智慧社区管理后台账号已开通，用户名为您的手机号码，初始登录密码为：{key0}，请及时登录系统修改密码，密码请勿告知他人。",
        ],
        16 => [
            'id' => 515,
            'captcha' => 1,
            'content' => '{key0}（停车驿绑定验证码，30分钟内有效）',
        ],
        17 => [
            'id'=>496,
            'content'=>'［停车驿］恭喜 ！ 您发布的车位信息（{key0}）审核已经通过，请及时设置您的车位空闲时间，即可赚取车位费',
        ],
        18 => [
            'id'=>496,
            'content'=>'［停车驿］很抱歉，您发布的的车位信息（{key0}）审核未通过，请查看原因并修改您的车位信息后，重新交由平台审核',
        ],
        19 => [
            'id'=>496,
            'content'=>'［停车驿］您收到一笔新的车位费用收入，请在我的钱包中查看并核对。',
        ],
        20 => [
            'id'=>496,
            'content'=>'［停车驿］您的提现已经被发起，请在我的钱包中查看并核对。'
        ],
        21 => [
            'id' => 497,
            'content' => '您有包裹已经投递到小区包裹服务站，快递公司：{key0}，单号：{key1}，该包裹为{key2}，请及时去小区服务站领取',
        ],
        23 => [
            'id' => 505,
            'content' => '您好，业主{key0}提交了一个报事报修订单，请及时查看并分配工单。',
        ],
        24 => [
            'id' => 504,
            'content' => '您好，物业工作人员{key0}{key1}提交了一个报事报修订单，请及时查看并分配工单。',
        ],
        25 => [
            'id' => 505,
            'content' => '您好，物业工作人员{key0}{key1}{key2}工单，请及时查看。',
        ],
        26 => [
            'id' => 504,
            'content' => '您好，物业工作人员{key0}{key1}工单已完成，请及时查看并复核工单。',
        ],
        27 => [
            'id' => 503,
            'content' => '您好，新的报事报修订单已分配给您，请及时查看并处理。',
        ],
        28 => [
            'id' => 511,
            'content' => '您好，新的{key0}任务已分配给您，请及时查收任务计划。',
        ],
        29 => [
            'id' => 512,
            'content' => '您好，{key0}任务将在{key1}后开始执行，请安排好您的时间，注意别迟到哦！~',
        ],
        30 => [
            'id' => 516,
            'content' => 'api接口报错，请及时处理，时间:{key0}',
        ],
        31 => [
            'id' => 517,
            'content' => '支付宝回调错误，原因：{key0},支付时间：{key1}',
        ],
        32 => [
            'id' => 525,
            //'content' => '您好，{key0}，业主{key1}已经将您添加为{key2}身份，请进入支付宝关注小区对应生活号并认证成功即可体验筑家易智慧社区服务',
            'content' => '{key0}，您好，{key1}小区业主{key2}已经将您添加为{key3}身份，请进入支付宝搜索“邻易联”小程序即可体验智慧社区服务',
        ],
        33 => [
            'id' => 526,
            'content' => '亲爱的业主您好，您提交的{key0}小区住户信息已审核通过，祝您生活愉快!',
        ],
        34 => [
            'id' => 527,
            'content' => '亲爱的业主您好，您提交的{key0}小区住户信息审核未通过，请打开支付宝小区生活号查看原因并及时修改！'
        ],
        35 => [
            'id' => 528,
            'content' => '已给您添加（{key0}）访客权限：点击 {key1} ，可在对应的门禁机上使用，有效期至：{key2}'
        ],
        36 => [
            'id' => 529,
            'content' => '尊敬的{key0}。您被邀请于{key1}至{key2}到访{key3}。点击查看{key4}。'
        ],
        37 => [
            'id' => 530,
            'content' => '尊敬的{key0}。您被邀请于{key1}至{key2}到访{key3}的邀请已被取消。'
        ],
        38 => [
            'id' => 533,
            'content' => '{key0}：您办理的{key1}会所预登记手续已完成，请仔细核对登记信息和相关须知内容，谢谢！'
        ],
        39 => [
            'id' => 535,
            'content' => '您好，您在{key0}日提交的电动车备案审批未通过，原因：{key1}，请重新修改资料并提交审核；'
        ],
        40 => [
            'id' => 533,
            'content' => '您好，您在{key0}日提交的电动车备案审批已通过。'
        ],
        41 => [
            'id' => 541,
            'content' => '尊敬的{key0}，您被{key1}邀请于{key2}至{key3}到访{key4}，请打开{key5}获取到访通行证'
        ],
        42 => [
            'id' => 542,
            'content' => '您报名参加的{key0}将于{key1}开始，请及时前往指定活动地点，使用支付宝—和窗小程序打卡参加。'
        ],
        43 => [
            'id' => 1,
            'content' => '黑名单住户{key0}在{key1}{key2}产生通行记录，请及时跟进处理。住户手机号码：{key3}。'
        ],
        44 => [
            'id' => 2,
            'content' => '独居老人{key0}，已经三天未产生通行记录，请及时核实探望。住户手机号码{key1}，住户居住地址：{key2}。'
        ],
         45 => [
            'id' => 3,
            'content' => '{key0}您好，您在邻易联申请的支付宝支付接口信息已被驳回，驳回原因：{key1}，请修改信息后重新提交审核。'
        ],
        46 => [
            'id' => 4,
            'content' => '{key0}您好，您在邻易联申请的支付宝支付接口信息已通过审核，请登录对应邮箱，打开邮件，验证账户并完成签约'
        ],
        47 => [
            'id' => 5,
            'content' => '{key0}您好，您在邻易联申请的支付宝支付接口信息待授权，请尽快登录邻易联物业管理系统，完成授权操作。'
        ],
        48 => [
            'id' => 6,
            'content' => '{key0}您好，您在邻易联申请的支付宝支付接口信息已完成签约，登录邻易联物业管理系统后即可使用支付宝线上缴费服务'
        ],

    ];

    //初始化
    public function init($tmpId, $phone, $config = [])
    {
        if (!$tmpId || !isset($this->templates[$tmpId])) {
            throw new HttpException(500, '模版不存在');
        }
        if (!$phone) {
            throw new HttpException(500, '手机号码不能为空');
        }
        $this->phone = $phone;
        $this->templateId = $tmpId;
        $this->template = $this->templates[$tmpId];
        foreach($config as $k=>$v) {
            if(property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
        return $this;
    }

    /**
     * 发送
     * @return bool
     */
    public function send($data = [])
    {
        return $this->sendNormal($data);
    }

    /**
     * 验证码验证(使用cache验证，成功则删除缓存)
     * @param $code
     * @return bool
     */
    public function valid($code)
    {
        return $this->validNormal($code);
    }

    /**
     * 获取验证码错误次数
     * @return int
     */
    public function getErrorNums()
    {
        $key = 'error.'.$this->templateId.'.'.$this->phone;
        return Yii::$app->redis->get($key);
    }

    protected function setErrorNums()
    {
        $key = 'error.'.$this->templateId.'.'.$this->phone;
        Yii::$app->redis->incr($key);
        Yii::$app->redis->expire($key, 900);//15分钟
    }

    //普通验证
    protected function validNormal($code)
    {
        $realCode = $this->_getCode();
        if ($realCode == $code) {
            $this->_cleanCache();
            return true;
        } else {
            if ($this->errorCount) {
                $this->setErrorNums();
            }
        }
        return false;
    }

    //普通发送短信
    protected function sendNormal($data = [])
    {
        if ($this->duplicate && $this->_getSentCache()) {
            return '3分钟内请勿重复发送';
        }
        if (!empty($this->template['captcha'])) {//验证码
            $data = [];
            $data[] = $this->_generateCode($this->phone);
        }

        $insert_msg = [
            'template' => PsCommon::get($this->template, 'id', 496),
            'mobile' => $this->phone,
            'content' => $this->_parseContent($data),
            'source' => PsCommon::get($this->template, 'source', 'property'),
            'operat_name' => PsCommon::get($this->template, 'operat_name', 'property')
        ];

        $msg = [
            "tenantId" => "0",
            "serviceName" => "sendMsgService",
            "methodName" => "sendContent",
            "platformSystemNum" => "ZJY_PROPERTY",
            "operationName" => "物业系统",
            "platformFrom" => "PC",
            "check" => "true",
            "data" => [
                "systemNum" => "ZJY_PROPERTY",
                "templateCode" => "property_".PsCommon::get($this->template, 'id', 496),
                "mobile" => $this->phone,
                "content" => "【筑家易】".$this->_parseContent($data)
            ]
        ];
        $headers = ['CURLOPT_HTTPHEADER'=>['Content-Type: application/json']];
        $SendSms = new Curl($headers);
        $url = YII_ENV == "prod" ? $this->url:$this->test_url;
        $smsCode = $SendSms->post($url, json_encode($msg));
        \Yii::info("params:".json_encode($msg, JSON_UNESCAPED_UNICODE), 'api');
        \Yii::info("result:".$smsCode, 'api');
        $result = json_decode($smsCode,true);
        $result['code'] = 1;
        $insert_msg['result'] = $result['code'];
        $insert_msg['errorMsg'] = !empty($result['error']['errorMsg'])?$result['error']['errorMsg']:$result['code'];
        $this->_log($insert_msg);//记录日志
        if ($result['code'] == 20000 ) { //if ($smsCode == 600) {
            $this->_setSentCache();
            return true;
        }
        return '发送失败';
    }

    /**
     * 生成验证码(6位数字)
     * @param bool $renew
     * @return bool|int
     */
    private function _generateCode($renew=false)
    {
        if(!($code = $this->_getCode()) || $renew) {
            $code = $this->_randNum();
            $this->_setCode($code);
        }
        return $code;
    }

    //随机数
    private function _randNum()
    {
        return mt_rand(100000, 999999);
    }

    //获取已发送标识缓存
    private function _getSentCache()
    {
        $key = 'send.'.$this->templateId.'.'.$this->phone;
        return Yii::$app->redis->get($key);
    }

    //设置已发送标识缓存.一个手机号一个模版180s内只能发送一次,避免频繁发送
    private function _setSentCache()
    {
        $key = 'send.'.$this->templateId.'.'.$this->phone;
        return Yii::$app->redis->set($key, 1, 'EX', $this->duplicateTime);
    }

    //清理已发送标识缓存
    private function _cleanSentCache()
    {
        $key = 'send.'.$this->templateId.'.'.$this->phone;
        return Yii::$app->redis->del($key);
    }

    //解析content内容(用data中值替换{key0})
    private function _parseContent($data)
    {
        $keys = $values = [];
        foreach($data as $k=>$v) {
            $keys[] = '{key'.$k.'}';//{key0} {key1}
            $values[] = $v;
        }
        return str_replace($keys, $values, $this->template['content']);
    }

    /**
     * 获取缓存中的验证码
     * @return mixed
     */
    private function _getCode()
    {
        $cacheKey = $this->_getCacheKey();
        return Yii::$app->redis->get($cacheKey);
    }

    /**
     * 缓存验证码，30分钟有效
     * @param $code
     * @return bool
     */
    private function _setCode($code)
    {
        $cacheKey = $this->_getCacheKey();
        return Yii::$app->redis->set($cacheKey, $code, 'EX', 30*60);
    }

    /**
     * 清除验证码缓存
     * @return bool
     */
    private function _cleanCode()
    {
        $cacheKey = $this->_getCacheKey();
        return Yii::$app->redis->del($cacheKey);
    }

    //清除缓存
    private function _cleanCache()
    {
        $this->_cleanCode();
        $this->_cleanSentCache();
    }

    /**
     * 获取验证码的缓存key
     * @param $phone
     * @return bool|string
     */
    private function _getCacheKey()
    {
        $prefix = "G&35*Y!1";
        return substr(md5($prefix.$this->templateId.'.'.$this->phone), 8, 16);
    }

    /**
     * @param $data
     */
    private function _log($data)
    {
        $model = new PsSmsHistory();
        $model->template = $data['template'];
        $model->customer_id = $this->objectId;
        $model->mobile = (string)$data['mobile'];
        $model->content = $data['content'];
        $model->result = (string)$data['result'];
        $model->description = $data['errorMsg']?(string)$data['errorMsg']:(string)$data['result'];
        $model->is_new = $this->isNew;
        $model->is_remind = $this->isRemind;
        $model->created_at = time();
        $model->operator_id = 0;
        $model->save();
    }
}
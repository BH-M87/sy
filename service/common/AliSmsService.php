<?php
/**
 * 短信公共服务，仅开放send, valid两个方法public
 * 短信验证使用cache验证，sms_history表仅做记录，cache有效期30分钟，同一个手机号发送同一个模版的短信，3分钟之内无法重复发送
 * @update 2018-05-09 代码优化版本，去除业主认证走老会员中心逻辑 TODO 新会员中心?是否需要?
 * @author shenyang
 * @date 2017/7/24
 */

namespace service\common;

use common\Code;
use common\MyException;
use common\sms\AliSms;
use service\BaseService;
use app\models\SmsHistory;
use app\models\SmsTemplate;
use Yii;

Class AliSmsService extends BaseService
{
    public $tenant = ['sms_sign'=>'筑家易','access_key'=>'LTAI7kb9YZFwqZtv','access_secret'=>'30vs4O0sb84Kglase5tbVy9ZyaU3Sq'];//签名和阿里授权key
    public $mobile = [];
    public $template = [];//当前实例模版
    public $templateCode;//当前模版ID
    public $duplicate = true;//重复发送验证，默认为true
    public $duplicateTime = 3;//手机号180s内不能重复发送
    public $captchaCode;
    const CAPTCHA = 1;

    /*
     *  id:日志中需要记录模版表id（兼容记录日志用）
     *  template_code:短信模版code
     *  content:短信模版内容
     *  is_captcha:是否是验证码短信，1是，2不是
     *  created_at:创建时间
     *  change:是否需要直接更新,1更新-第一次添加都需要更新，2不更新（兼容脚本用）
     *  短信模版里面的变量符号中间需要插入"."来拼接
     */
    public $templateList = [
        ["id"=>2,"template_code"=>"SMS_142105050","content"=>"验证码$"."{code}，您正在进行身份验证，打死不要告诉别人哦！","is_captcha"=>"1","created_at"=>"1543321695","change"=>1],
        ["id"=>3,"template_code"=>"SMS_152160101","content"=>"您的动态码为：$"."{code}，您正在进行密码重置操作，如非本人操作，请忽略本短信！","is_captcha"=>"1","created_at"=>"1543547757","change"=>1],
        ["id"=>4,"template_code"=>"SMS_153992159","content"=>"已给您添加（$"."{name}）访客权限：点击  https://t.zje.com/$"."{code}，可在对应的门禁机上使用，有效期至：$"."{time}","is_captcha"=>"2","created_at"=>"1543907726","change"=>1],
        ["id"=>5,"template_code"=>"SMS_152281702","content"=>"您的账号:$"."{account}，登录密码:$"."{password}，为了您的安全，请登录后修改密码。请勿泄漏于他人。","is_captcha"=>"2","created_at"=>"1543998605","change"=>1],
        ["id"=>6,"template_code"=>"SMS_154589639","content"=>"尊敬的$"."{name}女士/先生。您于$"."{time_start}至$"."{time_end}到访$"."{address}的邀请已被取消","is_captcha"=>"2","created_at"=>"1546913936","change"=>1],
        ["id"=>7,"template_code"=>"SMS_155857503","content"=>"尊敬的业主，$"."{address}已完成出租房屋安全排查，排查结果为$"."{inspect_type}，感谢您为出租房屋安全做出的贡献，请在出租房屋租赁期间保证房屋安全。我们将于$"."{next_inspect_date}前再次上门排查。如有疑问，请联系网格员$"."{grid_member_name}，电话:$"."{grid_member_phone}。","is_captcha"=>"2","created_at"=>"1546913939","change"=>1],
        ["id"=>8,"template_code"=>"SMS_160306319","content"=>"已给您添加（$"."{address}）访客权限，车位号：$"."{carport_name}。点击 https://t.zje.com/$"."{code}，可在对应的门禁机上使用，有效期至：$"."{end_date}","is_captcha"=>"2","created_at"=>"1552554902","change"=>1],
        ["id"=>9,"template_code"=>"SMS_160301495","content"=>"已给您添加（$"."{address}）访客权限。点击 https://t.zje.com/$"."{code}，可在对应的门禁机上使用，有效期至：$"."{end_date}","is_captcha"=>"2","created_at"=>"1552554902","change"=>1],
        ["id"=>10,"template_code"=>"SMS_165055077","content"=>"亲爱的业主您好，您提交的$"."{community_name}小区住户信息已审核通过，祝您生活愉快!","is_captcha"=>"2","created_at"=>"1557369730","change"=>1],
        ["id"=>11,"template_code"=>"SMS_165118483","content"=>"亲爱的业主您好，您提交的$"."{community_name}小区住户信息审核未通过，请打开APP查看原因并及时修改！","is_captcha"=>"2","created_at"=>"1557369730","change"=>1],
        ["id"=>12,"template_code"=>"SMS_174277644","content"=>"尊敬的$"."{name}，您好，已为您开通富春智联管理平台账号，用户名为您的手机号码，初始登录密码为：$"."{code}，请及时登录并修改密码，密码请勿告知他人。","is_captcha"=>"2","created_at"=>"1557369730","change"=>1],
        ["id"=>13,"template_code"=>"SMS_174278311","content"=>"$"."{name}，您好，$"."{community_name}小区业主$"."{resident_name}已经将您添加为$"."{resident_type}身份，请进入支付宝搜索“富春智联”小程序即可体验智慧社区服务","is_captcha"=>"2","created_at"=>"1557369730","change"=>1],
        ["id"=>14,"template_code"=>"SMS_174810613","content"=>"尊敬的$"."{name}，您被$"."{resident_name}邀请于$"."{start_date}至$"."{end_date}到访$"."{community_name}，请打开 https://t.zje.com/$"."{code} 获取到访通行证","is_captcha"=>"2","created_at"=>"1557369730","change"=>1],
        ["id"=>15,"template_code"=>"SMS_174810699","content"=>"尊敬的$"."{name}。您被邀请于$"."{start_date}至$"."{end_date}到访$"."{community_name}的邀请已被取消。","is_captcha"=>"2","created_at"=>"1557369730","change"=>1],
        ["id"=>16,"template_code"=>"SMS_177548952","content"=>"亲爱的业主您好，您提交的$"."{community_name}小区住户信息审核未通过，请打开支付宝小程序[$"."{app_name}]查看原因并及时修改！","is_captcha"=>"2","created_at"=>"1557369730","change"=>1],
    ];

    /**
     * 获取短信模版的内容
     * 从以前表里面获取改成从代码里面获取
     * @param $tmpCode
     * @return array|mixed
     */
    public function getSmsTemplate($tmpCode){
        $list = $this->templateList;
        $back = [];
        foreach ($list as $key=>$value) {
            if($value['template_code'] == $tmpCode){
                $back = $value;
            }
        }
        return $back;
    }

    /**
     * 目前只支持，一次发送一个签名，一个模版的一条数据，给一个或多个手机号
     * @param $tmpId
     * @param $mobile
     * @return $this
     * @throws MyException
     */
    public function __construct($params)
    {
        $tmpCode = $params['templateCode'];
        $mobile = $params['mobile'];
        //$template = SmsTemplate::find()->where(['template_code' => $tmpCode])->one();
        $template = $this->getSmsTemplate($tmpCode);
        if (!$tmpCode || !$template) {
            throw new MyException(Code::$codes[Code::TEMPLATE_EMPTY]);
        }
        $this->mobile = $this->formatMobile($mobile);
        if (!$this->mobile) {
            throw new MyException(Code::$codes[Code::MOBILE_EMPTY]);
        }
        if ($template['is_captcha'] == self::CAPTCHA && count($this->mobile) > 1) {
            throw new MyException(Code::$codes[Code::PARAMS_ERROR], '验证码类短信只支持单个手机号发送');
        }
        $this->templateCode = $tmpCode;
        $this->template = $template;
        return $this;
    }


    //普通发送短信
    public function send($data = [])
    {
        $tenant = $this->tenant;
        if (!$tenant) {
            throw new MyException(Code::$codes[Code::TENANT_INVALID]);
        }
        if ($this->duplicate && $this->getCache()) {
            throw new MyException(Code::$codes[Code::SMS_DUPLICATE]);
        }
        if ($this->template['is_captcha'] == self::CAPTCHA) {//验证码
            if (empty($data['code'])) {
                $data['code'] = mt_rand(100000, 999999);
            }
            $this->captchaCode = $data['code'];
            $this->setCache(30, $data['code'], 'captcha');
        }
        $aliSms = new AliSms($tenant['sms_sign'], $tenant['access_key'], $tenant['access_secret']);
        $result = $aliSms->send($this->template['template_code'], $this->mobile, $data);
        $this->_log($result, $data);//记录日志

        if ($result['Code'] == 'OK') {
            $this->setCache($this->duplicateTime, 1);
            return true;
        }
        throw new MyException(Code::$codes[Code::SMS_SEND_FAILED], '短信发送失败(' . $result['Message'] . ')');
    }

    /**
     * 短信验证码验证
     * @param $code
     */
    public function valid($code)
    {
        if (!$code) {
            throw new MyException(Code::$codes[Code::SMS_CODE_EMPTY]);
        }
        $realCode = $this->getCache('captcha');
        if ($realCode != $code) {
            throw new MyException(Code::$codes[Code::SMS_CODE_INVALID]);
        }
        $this->cleanAllCache();
        return true;
    }

    // 手机号格式整理
    private function formatMobile($mobile)
    {
        $mobileArr = explode(',', $mobile);
        foreach ($mobileArr as &$m) {
            $m = trim($m);
            //验证手机号格式
            if (!preg_match("/^1[0-9]{10}$/", $m)) {
                throw new MyException(Code::$codes[Code::SMS_MOBILE_INVALID]);
            }
        }
        return $mobileArr;
    }

    private function cacheName($type = '')
    {
        return 'rj:sms:' . $type . ':' . md5($this->tenantId . '.' . $this->templateCode . '.' . json_encode($this->mobile));
    }

    //获取缓存
    private function getCache($type = '')
    {
        return Yii::$app->redis->get($this->cacheName($type));
    }

    //设置缓存，有效期单位分钟
    private function setCache($expired, $value, $type = '')
    {
        return Yii::$app->redis->set($this->cacheName($type), $value, 'EX', $expired * 60);
    }

    //清理缓存
    private function cleanCache($type = '')
    {
        return Yii::$app->redis->del($this->cacheName($type));
    }

    //清除缓存
    private function cleanAllCache()
    {
        $this->cleanCache();
        $this->cleanCache('captcha');
    }

    private function parseContent($data)
    {
        $keys = $values = [];
        foreach ($data as $k => $v) {
            $keys[] = '${' . $k . '}';//${code} -> 111111
            $values[] = $v;
        }
        return str_replace($keys, $values, $this->template['content']);
    }

    /**
     * @param $data
     */
    private function _log($result, $params)
    {
        $data = [];
        $content = $this->parseContent($params);
        foreach ($this->mobile as $mobile) {
            $data[] = [
                'template_id' => $this->template['id'],
                'mobile' => $mobile,
                'params' => json_encode($params),
                'content' => $content,
                'is_send' => 1,
                'send_status' => $result['Code'] == 'OK' ? 1 : 2,
                'send_time' => time(),
                'send_response' => json_encode($result),
                'create_at' => time(),
            ];
        }
        Yii::$app->db->createCommand()->batchInsert(SmsHistory::tableName(), [
            'template_id', 'mobile', 'params', 'content', 'is_send', 'send_status',
            'send_time', 'send_response', 'created_at'
        ], $data)->execute();
    }
}
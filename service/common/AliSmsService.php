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
        $template = SmsTemplate::find()->where(['template_code' => $tmpCode])->one();
        if (!$tmpCode || !$template) {
            throw new MyException(Code::$codes[Code::TEMPLATE_EMPTY]);
        }
        $this->mobile = $this->formatMobile($mobile);
        if (!$this->mobile) {
            throw new MyException(Code::$codes[Code::MOBILE_EMPTY]);
        }
        if ($template['is_captcha'] == SmsTemplate::CAPTCHA && count($this->mobile) > 1) {
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
        if ($this->template['is_captcha'] == SmsTemplate::CAPTCHA) {//验证码
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
            return $this->captchaCode;
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
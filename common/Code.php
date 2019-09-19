<?php
/**
 * 状态码
 * @author shenyang
 * @date 2018-11-17
 */

namespace common;

class Code
{

    /**
     * 社区服务错误码范围: 50000 ~ 59999
     * 50000 ~ 50999 为社区服务通用错误码（50开头）
     * 51000 ~ 59999，每个模块单独分配一个包含200个错误码的号段，自行添加
     * v1.0版本分配顺序以模块命名顺序
     * 后续版本按照开发的顺序递增
     * 错误码需要根据错误的通用性酌情添加，新增一个新的错误码，需要在此对象中配置相应的说明
     */

    const ERROR = 0;
    const OK = 1;
    const SIGN_ERROR = 10002;
    const PARAMS_ERROR = 10003;
    const LOGIN_EXPIRED = 10004;
    const USER_NOTEXIST = 10005;
    const TOKEN_EXPIRED = 10006;
    const TOKEN_INVALID = 10007;
    const TOKEN_EMPTY = 10008;

    const API_EXPIRED = 60000;
    const SIGN_NAME_EMPTY = 60001;
    const TEMPLATE_CODE_EMPTY = 60002;
    const BATCH_PARAMS_ERROR = 60003;
    const MOBILE_NUMBER_LIMITED = 60004;
    const TEMPLATE_EMPTY = 60005;
    const MOBILE_EMPTY = 60006;
    const TENANTID_EMPTY = 60007;
    const SMS_DUPLICATE = 60008;
    const SMS_SEND_FAILED = 60009;
    const SMS_CODE_EMPTY = 60010;
    const SMS_CODE_INVALID = 60011;
    const APPKEY_INVALID = 60012;
    const TENANT_INVALID = 60013;
    const SMS_MOBILE_INVALID = 60014;
    const TENANT_CONFIG_ERROR = 60015;

    const UPLOAD_IMAGE_EMPTY = 61000;
    const UPLOAD_IMAGE_INVALID = 61001;
    const UPLOAD_IMAGE_TYPE_INVALID = 61002;
    const UPLOAD_SIZE_LIMITED = 61003;
    const UPLOAD_MOVED_FAILED = 61004;
    const UPLOAD_QINIU_FAILED = 61005;
    const UPLOAD_IMAGE_SAVE_INVALID = 61006;

    const WEATHER_ERROR = 62000;
    const WEATHER_CALLED_LIMITED = 62001;

    const SHORT_URL_NOEXIST = 63001;

    public static $codes = [
        self::ERROR => '系统错误',
        self::OK => 'success',
        self::SIGN_ERROR => '签名验证错误',
        self::LOGIN_EXPIRED => '登陆失效，请重新登陆',
        self::USER_NOTEXIST => '用户不存在',
        self::PARAMS_ERROR => '参数验证错误',
        self::TOKEN_EXPIRED => '登录信息过期',
        self::TOKEN_INVALID => '登录验证失败',
        self::TOKEN_EMPTY => '未登陆',
        self::API_EXPIRED => '接口已过期',
        self::APPKEY_INVALID => '应用错误',
        self::TENANT_INVALID => '租户不存在',
        self::TENANT_CONFIG_ERROR => '租户接口配置参数错误',
        self::SIGN_NAME_EMPTY => '短信签名不能为空',
        self::TEMPLATE_CODE_EMPTY => '短信模版不能为空',
        self::BATCH_PARAMS_ERROR => '批量发送短信模版参数错误',
        self::MOBILE_NUMBER_LIMITED => '短信发送手机号最多不超过100个',
        self::TEMPLATE_EMPTY => '短信模版不存在',
        self::MOBILE_EMPTY => '手机号不能为空',
        self::TENANTID_EMPTY => '租户编号不能为空',
        self::SMS_DUPLICATE => '短信已发送，请勿重复发送',
        self::SMS_SEND_FAILED => '短信发送失败',
        self::SMS_CODE_EMPTY => '验证码不能为空',
        self::SMS_CODE_INVALID => '验证码错误',
        self::SMS_MOBILE_INVALID => '手机号格式错误',
        self::UPLOAD_IMAGE_EMPTY => '未获取到上传图片',
        self::UPLOAD_IMAGE_INVALID => '不是真实的图片',
        self::UPLOAD_IMAGE_TYPE_INVALID => '图片类型错误',
        self::UPLOAD_SIZE_LIMITED => '文件大小超出范围',
        self::UPLOAD_MOVED_FAILED => '本地保存失败',
        self::UPLOAD_QINIU_FAILED => '七牛上传失败',
        self::UPLOAD_IMAGE_SAVE_INVALID => '图片数据保存出错',
        self::WEATHER_ERROR => '天气接口调用出错',
        self::WEATHER_CALLED_LIMITED => '天气接口超量',
        self::SHORT_URL_NOEXIST => '短链不存在'
    ];

    // 获取message
    public static function getMessage($code, $default = '未知错误')
    {
        return empty(self::$codes[$code]) ? $default : self::$codes[$code];
    }
}

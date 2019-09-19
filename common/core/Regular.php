<?php
/**
 * 通用正则
 * User: yshen
 * Date: 2018/5/21
 * Time: 00:57
 */

namespace common\core;

Class Regular
{

    /**
     * 非负整数
     */
    public static function unsigned($zero = true)
    {
        if ($zero) {
            return '/^[1-9]\d*|0$/';
        } else {
            return '/^[1-9]\d*$/';
        }
    }

    /**
     * 0-9, a-z, A-Z...
     * @param $from
     * @param $to
     * @return string
     */
    public static function range($from, $to)
    {
        return '/^['.$from.'-'.$to.']$/';
    }

    /**
     * 纯数字
     * @return string
     */
    public static function number()
    {
        return "/^[0-9]+$/";
    }

    /**
     * 区号+电话 或者 手机号 或者400电话
     * @return string
     */
    public static function telOrPhone()
    {
        return '/^((0\d{2,3}-\d{7,8})|(1[3|4|5|6|7|8|9][0-9]{9})|(400([0-9-]{7,10})([0-9]){1}))$/';
    }

    /**
     * 手机号码
     * @return string
     */
    public static function phone()
    {
        return "/^1[0-9]{10}$/";
    }

    /**
     * 中文字符
     * @param $len
     * @return string
     */
    public static function hanzi($min, $max = '')
    {
        if (!$max) {
            return "/^[\x{4e00}-\x{9fa5}]{1,".($min)."}$/iu";
        } else {
            return "/^[\x{4e00}-\x{9fa5}]{".$min.",".($max)."}$/iu";
        }
    }

    /**
     * 网址
     * @return string
     */
    public static function url()
    {
        return "/^((https?|ftp|news):\/\/)?([a-z]([a-z0-9\-]*[\.。])+([a-z]{2}|aero|arpa|biz|com|coop|edu|gov|info|int|jobs|mil|museum|name|nato|net|org|pro|travel)|(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]))(\/[a-z0-9_\-\.~]+)*(\/([a-z0-9_\-\.]*)(\?[a-z0-9+_\-\.%=&]*)?)?(#[a-z][a-z0-9_]*)?$/";
    }

    /**
     * 中文，英文，数字
     * @return string
     */
    public static function string($min = '', $max = '')
    {
        if (!$min && !$max) {
            return "/^[A-Za-z0-9\x{4e00}-\x{9fa5}]+$/u";
        } else {
            return "/^[A-Za-z0-9\x{4e00}-\x{9fa5}]{".$min.",".$max."}$/u";
        }
    }

    /**
     * 英文字母和数字
     * @return string
     */
    public static function letterOrNumber($min = 0, $max = 99)
    {
        if ($min == 0) {
            return "/^[A-Za-z0-9]+$/";
        }
        return '/^[a-zA-Z0-9]{'.$min.','.$max.'}$/';
    }

    /**
     * 中英文
     * @param $min
     * @param $max
     * @return string
     */
    public static function hanziOrLetter($min, $max)
    {
        return '/^[A-Za-z\x{4e00}-\x{9fa5}]{'.$min.','.$max.'}+$/u';
    }

    /**
     * 特殊符号
     * @return string
     */
    public static function symbol()
    {
        return '/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\\" | \| /';
    }

    /**
     * 正整数 或 小数，最多保留$len位小数
     * @param int $len
     * @return string
     */
    public static function float($len = 0)
    {
        if ($len) {
            return '/^\d+(\.\d{'.$len.'})?$/iu';
        } else {
            return '/^\d+(\.\d+)?$/iu';
        }
    }

    /**
     * 身份证
     */
    public static function idCard()
    {
        return '/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/';
    }

    /**
     * 邮箱
     * @return string
     */
    public static function email()
    {
        return '/^[A-Za-z0-9_u4e00-u9fa5]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/';
    }

    /**
     * 支付宝账号
     */
    public static function alipayAccount()
    {
        return '/^((\w)+(\.\w+)*@([\w-])+((\.[\w-]+)+)|(1\d{10}))$/';
    }
}

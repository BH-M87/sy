<?php
/**
 * AES ECB 加密解密
 * User: fengwenchao
 * Date: 2018/4/20
 * Time: 15:03
 */
namespace common\core;

class Security
{
    /**
     * 加密
     * @param $input 加密字符串
     * @param $key   加密秘钥
     * @return string
     */
    public static function encrypt($input, $key) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = Security::pkcs5_pad($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    private static function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    /**
     * 解密
     * @param $sStr 要解密的字符串
     * @param $sKey 解密秘钥
     * @return string
     */
    public static function decrypt($sStr, $sKey) {
        $decrypted= mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128,
            $sKey,
            base64_decode($sStr),
            MCRYPT_MODE_ECB
        );

        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s-1]);
        $decrypted = substr($decrypted, 0, -$padding);
        return $decrypted;
    }

    public static function getKey($md5str)
    {
        $tmpStr = strrev($md5str);
        $tmpB = preg_match('/\d+/',$tmpStr,$arr);
        $no = $arr[0];
        if (strlen($no) > 1) {
            $no = substr($no, 0, 1);
        }
        $key = substr($md5str, $no, 10);
        return $key;
    }

    public static function getRandomString($len, $chars = null)
    {
        if (is_null($chars)) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
        mt_srand(10000000 * (double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }
}


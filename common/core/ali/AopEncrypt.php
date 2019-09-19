<?php
/**
 * 阿里sdk加解密
 * @author shenyang
 * @date 2017-06-07
 */
namespace common\core\ali;

Class AopEncrypt {
    /**
     * 加密方法
     * @param string $str
     * @return string
     */
    public static function encrypt($str,$screct_key){
        //AES, 128 模式加密数据 CBC
        $screct_key = base64_decode($screct_key);
        $str = trim($str);
        $str = self::addPKCS7Padding($str);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC),1);
        $encrypt_str =  mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_CBC);
        return base64_encode($encrypt_str);
    }

    /**
     * 解密手机号方法
     * @param string $str
     * @return string
     */
    public static function decrypt($str,$secretKey){
        //浏览器会将+号转换为空格
        $str = base64_decode($str);
        $secretKey = base64_decode($secretKey);
        //设置全0的IV
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC);
        $iv = str_repeat("\0", $iv_size);
        $decrypt_str = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $secretKey, $str, MCRYPT_MODE_CBC, $iv);
        $decrypt_str = self::stripPKSC7Padding($decrypt_str);
        return $decrypt_str;
    }

    /**
     * 填充算法
     * @param string $source
     * @return string
     */
    public static function addPKCS7Padding($source){
        $source = trim($source);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);

        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    }

    /**
     * 移去填充算法
     * @param string $source
     * @return string
     */
    public static function stripPKSC7Padding($source) {
        $char = substr($source, -1);
        $num = ord($char);
        if($num==62)return $source;
        $source = substr($source,0,-$num);
        return $source;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/7/2
 * Time: 17:36
 */

namespace service\common;

use common\core\Curl;
use common\core\F;
use service\alipay\MemberCardService;
use service\BaseService;
use service\qiniu\UploadService;
use yii\helpers\FileHelper;

class AliPayQrCodeService extends BaseService
{

    /**
     * @api 生成小程序推广二维码
     * @author wyf
     * @date 2019/7/1
     * @param $url_param
     * @param $query_param x=1&y=2
     * @param $desc
     * @param int $is_down 是否需要固定url 1:是;2否
     * @return string
     * @throws \yii\base\Exception
     */
    public static function createQrCode($url_param, $query_param, $desc, $is_down = 2)
    {
        //组装参数
        $params['type'] = 'small';//指向哪个小程序
        $params['url_param'] = $url_param;//url 地址
        $params['query_param'] = $query_param; //参数
        $params['describe'] = $desc;//二维码描述
        //二维码方法
        $result = MemberCardService::service()->getQrcode($params);
        if ($result['code'] = '10000') {
            $url = $result['qr_code_url'];
        } else {
            return "";
        }
        $options = [
            'Content-Type: application/octet-stream'
        ];
        $imageData = Curl::getInstance(['CURLOPT_HTTPHEADER' => $options])->get($url);
        $filename = date('YmdHis') . mt_rand(1000, 9999);
        $imgUrl = self::createPng($imageData, $filename);
        //TODO 由于前端需要,图片暂时保存到本地,不进行图片处理了
        if ($is_down == 1) {
            $key_name = md5(uniqid(microtime(true), true)) . '.png';
            $imgUrl = UploadService::service()->saveQiniu($key_name, $imgUrl);
            return $imgUrl;
        }
        return $filename;
    }

    /**
     * 获取支付宝二维码
     * @param $url_param
     * @param $query_param
     * @param $desc
     * @return string
     */
    public static function getAliQrCode($url_param, $query_param, $desc)
    {
        //组装参数
        $params['type'] = 'small';//指向哪个小程序
        $params['url_param'] = $url_param;//url 地址
        $params['query_param'] = $query_param; //参数
        $params['describe'] = $desc;//二维码描述
        //二维码方法
        $result = MemberCardService::service()->getQrcode($params);
        if ($result['code'] = '10000') {
            $url = $result['qr_code_url'];
            return $url;
        } else {
            return "";
        }
    }

    /**
     * @api 追加写入文件
     * @author wyf
     * @date 2019/7/2
     * @param $url
     * @param $name
     * @param string $format
     * @return string
     * @throws \yii\base\Exception
     */
    public static function createPng($url, $name, $format = '.png')
    {
        $dir = F::imagePath();
        $filename = $dir . '/' . $name . $format;
        if (!file_exists($dir)) {
            FileHelper::createDirectory($dir);
        }
        $tp = fopen($filename, "a");
        fwrite($tp, $url);
        fclose($tp);
        return $filename;
    }
}
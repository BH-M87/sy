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
use common\MyException;
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
    public static function createQrCode($url_param, $query_param, $desc, $type = 'park')
    {
        //组装参数
        $params['type'] = $type;//指向哪个小程序
        $params['url_param'] = $url_param;//url 地址
        $params['query_param'] = $query_param; //参数
        $params['describe'] = $desc;//二维码描述
        //二维码方法
        $result = MemberCardService::service()->getQrcode($params);
        print_r($result);die;
        if ($result['code'] = '10000') {
            $url = $result['qr_code_url'];
            \Yii::info("export-url:".$url,'api');
        } else {
            throw new MyException('二维码获取失败');
        }
        $options = [
            'Content-Type: application/octet-stream'
        ];
        $imageData = Curl::getInstance(['CURLOPT_HTTPHEADER' => $options])->get($url);
        $filename = date('YmdHis') . mt_rand(1000, 9999);
        $imgUrl = self::createPng($imageData, $filename);
        \Yii::info("img-url:".$imgUrl,'api');
        $fileRe = F::uploadFileToOss($imgUrl);
        $downUrl = $fileRe['filepath'];
        return $downUrl;
    }

    /**
     * 获取支付宝二维码
     * @param $url_param
     * @param $query_param
     * @param $desc
     * @return string
     */
    public static function getAliQrCode($url_param, $query_param, $desc,$type='park')
    {
        //组装参数
        $params['type'] = $type;//指向哪个小程序
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
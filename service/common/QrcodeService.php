<?php
/**
 * 二维码相关服务
 */
namespace service\common;

use common\core\F;
use service\BaseService;
use service\qiniu\UploadService;
use yii\helpers\FileHelper;

require_once dirname(__DIR__) . '/common/phpqrcode/phpqrcode.php';

Class QrcodeService extends BaseService {

    //二维码保存地址
    public $qrFile;

    /**
     * 二维码生成png格式
     * @param $text
     * @param bool $outfile
     * @param int $level
     * @param int $size
     * @return $this
     */
    public function png($text, $outfile=false, $level=QR_ECLEVEL_L, $size=5)
    {
        \QRcode::png($text, $outfile, $level, $size);
        $this->qrFile = $outfile;
        //链式访问
        return $this;
    }

    /**
     * 二维码添加logo
     * TODO 兼容直接返回不生成文件
     * @param $logo
     * @return bool
     */
    public function withLogo($logo)
    {
        if(!$logo || !$this->qrFile) {
            return false;
        }
        $QR = imagecreatefromstring(file_get_contents($this->qrFile));
        $logo = imagecreatefromstring(file_get_contents($logo));
        if (imageistruecolor($logo)) {
            imagetruecolortopalette($logo, false, 65535);
        }

        $QR_width       = imagesx($QR);
        $QR_height      = imagesy($QR);
        $logo_width     = imagesx($logo);
        $logo_height    = imagesy($logo);
        $logo_qr_width  = $QR_width / 5;
        $scale          = $logo_width / $logo_qr_width;
        $logo_qr_height = $logo_height / $scale;
        $from_width     = ($QR_width - $logo_qr_width) / 2;
        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);

        imagepng($QR, $this->qrFile);
    }

    /**
     * 生成小区二维码图片，并保存到七牛
     * @param string $savePath 图片保存路径
     * @param string $url 二维码对应的URL地址
     * @param string $commId 小区id
     * @param string $logoUrl 小区logo图片地址
     * @return string
     */
    public function generateCommCodeImage($savePath, $url, $commId, $logoUrl)
    {
        $imgUrl = "";

        //设置上传路径
        if (!file_exists($savePath)) {
            FileHelper::createDirectory($savePath, 0755, true);
        }

        $img_name = $commId . '.png';
        if (!$logoUrl) {
            $logoUrl = \Yii::$app->basePath . '/web/img/alilogo.png';
        }
        //生成一个二维码图片
        QrcodeService::service()->png($url, $savePath . $img_name, QR_ECLEVEL_H, '100')->withLogo($logoUrl);

        if (file_exists($savePath . $img_name)) {
            chmod($savePath . $img_name, 0755);
            $key_name = md5(uniqid(microtime(true), true)) . '.png';
            $new_file = $savePath . $img_name;
            //return $new_file;
            //图片上传到oss
            $re = F::uploadToOss($new_file, $key_name);
            $imgUrl = $re['filepath'];
        }

        return $imgUrl;
    }
}
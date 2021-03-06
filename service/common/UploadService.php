<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/9/10
 * Time: 16:33
 */

namespace service\common;

use common\core\ImageManage;
use service\BaseService;
use Yii;

class UploadService extends BaseService
{
    //图片允许的最大文件大小2M
    public $imageMaxSize = 4;
    //允许的图片格式
    public $imageMimes = ['image','image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    //允许的图片后缀
    public $imageExt = ['jpg', 'png', 'gif','jpeg', 'bmp', 'pjpeg'];

    /**
     * 上传图片检测
     * @param $img
     * @return array|bool
     */
    public function checkImage($img)
    {
        if(!is_uploaded_file($img['tmp_name'])){
            return $this->failed('非法的上传途径');
        }
        //检测是否是真实的图片
        if(!@getimagesize($img['tmp_name'])){
            return $this->failed('不是真实的图片');
        }
        //检测文件类型
//        if(!in_array($img['type'], $this->imageMimes)){
//            return $this->failed('不允许的文件Mime类型');
//        }
        //检测文件扩展名
        $ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
        if(!in_array($ext, $this->imageExt)){
            return $this->failed('不允许的扩展名');
        }
        if($img['size'] > ($this->imageMaxSize * 1024 * 1024)){
            return $this->failed('文件最大不能超过'.$this->imageMaxSize.'M');
        }
        return $this->success();
    }

    /**
     * 文件保存到本地
     * @param array $file 上传的$_FILES数组
     * @param string $dir 保存外部目录
     * @param string $fileName 保存的新文件名称，默认为毫秒时间+随机数
     * @return array
     */
    public function saveLocal($file, $dir, $fileName = '')
    {
        if (!$file['tmp_name']) {
            return $this->failed('非法的上传途径');
        }
        //完整的目录
        $parentDir = $this->_generateParentDir();
        $realDir = $dir . $parentDir . '/';
        if (!is_dir($realDir)) {//0755: rw-r--r--
            mkdir($realDir, 0755, true);
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        //新文件
        $newFileName = $fileName ? $fileName . '.' . $ext : $this->_generateName($ext);
        $newFile = $realDir . $newFileName;
        if (!move_uploaded_file($file['tmp_name'], $newFile)) {
            return $this->failed('本地保存失败');
        }
        chmod($newFile, 0755);
        //$this->image_png_size_add($newFile,$newFile);
        return $this->success(['fileName' => $newFileName, 'fileDir' => $realDir, 'parentDir' => $parentDir]);
    }

    public function image_png_size_add($imgsrc,$imgdst){
        list($width,$height,$type) = getimagesize($imgsrc);
        $new_width = ($width>600?600:$width)*0.9;
        $new_height =($height>600?600:$height)*0.9;
        switch($type){
            case 1:
                $giftype= $this->check_gifcartoon($imgsrc);
                if($giftype){
                    header('Content-Type:image/gif');
                    $image_wp=imagecreatetruecolor($new_width, $new_height);
                    $image = imagecreatefromgif($imgsrc);
                    imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    imagejpeg($image_wp, $imgdst,75);
                    imagedestroy($image_wp);
                }
                break;
            case 2:
                header('Content-Type:image/jpeg');
                $image_wp=imagecreatetruecolor($new_width, $new_height);
                $image = imagecreatefromjpeg($imgsrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($image_wp, $imgdst,75);
                imagedestroy($image_wp);
                break;
            case 3:
                header('Content-Type:image/png');
                $image_wp=imagecreatetruecolor($new_width, $new_height);
                $image = imagecreatefrompng($imgsrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($image_wp, $imgdst,75);
                imagedestroy($image_wp);
                break;
        }
    }

    /**
     * desription 判断是否gif动画
     * @param sting $image_file图片路径
     * @return boolean t 是 f 否
     */
    public function check_gifcartoon($image_file){
        $fp = fopen($image_file,'rb');
        $image_head = fread($fp,1024);
        fclose($fp);
        return preg_match("/".chr(0x21).chr(0xff).chr(0x0b).'NETSCAPE2.0'."/",$image_head)?false:true;
    }

    /**
     * 保存到七牛上，返回完整URL
     * @param $keyName
     * @param $fileName
     * @param $fileDir
     * @return string
     */
    public function saveQiniu($keyName, $filePath)
    {
        $bucket = Yii::$app->params['bucket'];
        $result = ImageManage::getInstance()->upfile($bucket, $keyName, $filePath);
        if (!$result) {
            return "";
        }
        return Yii::$app->params['fileHostUrl'] . $result;
    }

    /**
     * 生成上层目录名
     * @return false|string
     */
    private function _generateParentDir()
    {
        return date('Y-m-d');//按照日期时间分目录
    }

    /**
     * 创建新的文件名称(以时间区分)
     */
    private function _generateName($ext)
    {
        list($msec, $sec) = explode(' ', microtime());
        $msec = round($msec, 3) * 1000;//获取毫秒
        return date('YmdHis') . $msec . rand(10,100) . '.' . $ext;
    }
}
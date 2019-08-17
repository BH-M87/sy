<?php
/**
 * 文件下载控制器
 * @author shenyang
 * @date 2018-05-19
 */

namespace app\modules\property\modules\v1\controllers;

use common\core\F;
use common\core\PsCommon;
use Yii;

Class DownloadController extends BaseController
{
    public function actionIndex()
    {
        $fileName = PsCommon::get($this->request_params, 'filename');
        if (!$fileName) {
            return PsCommon::responseFailed('文件名不能为空');
        }
        $type = PsCommon::get($this->request_params, 'type', 'temp');
        $filePath = $this->_getDir($type) . $fileName;
        if (!file_exists($filePath)) {
            return PsCommon::responseFailed('文件不存在');
        }
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $newName = PsCommon::get($this->request_params, 'newname');//下载后生成的文件名字
        $newName = $newName ? $newName : pathinfo($fileName, PATHINFO_BASENAME);
        $ctype = $this->_getContentType($ext);
        $fp = fopen($filePath, "r");
        $file_size = filesize($filePath);
        ob_clean(); 
        // 下载文件需要用到的头
        header("Content-type:text/html;charset=utf-8");
        header("Content-type: application/".$ctype.";charset=UTF-8");
        header("Accept-Ranges: bytes");
        header("Accept-Length:" . $file_size);
        header('Content-Disposition: attachment; filename=' . $newName);
        $buffer = 1024;
        $file_count = 0;
        //向浏览器返回数据
        while (!feof($fp) && $file_count < $file_size) {
            $file_con = fread($fp, $buffer);
            $file_count += $buffer;
            echo $file_con;
            ob_flush();
            flush();
        }
        fclose($fp);
        die();
        //下载完成后删除文件 原代码中，zip文件有删除逻辑。删除文件和目录是有风险的，为什么要删除？
//        if ($file_count >= $file_size && in_array($type, ['temp', 'zip', 'error'])) {
//            $this->removeFile($type, $fileName);
//        }
    }

    private function _getDir($type)
    {
        switch ($type) {
            case 'template'://模版文件
                return Yii::$app->basePath . '/templates/';
                break;
            case 'temp'://临时文件
                return Yii::$app->basePath . '/web/store/excel/temp/';
                break;
            case 'error'://上传错误文件
                return Yii::$app->basePath . '/web/store/excel/error/';
                break;
            case 'qrcode'://二维码
                return F::imagePath();
                break;
            case 'zip':
                return Yii::$app->basePath . '/web/store/zip/';
                break;
            case 'file':
                return Yii::$app->basePath . '/web/store/file/';
                break;
            default:
                return Yii::$app->basePath . '/web/store/excel/temp/';
                break;
        }
    }

    public function _getContentType($ext)
    {
        switch ($ext) {
            case 'xlsx':
            case 'xls':
                return 'application/vnd.ms-excel';
                break;
            case 'pdf':
                return 'application/pdf';
                break;
            case 'csv':
                return 'text/csv';
                break;
            case 'zip':
                return 'application/zip';
                break;
            case 'gif':
                return 'image/gif';
                break;
            case 'png':
                return 'image/png';
                break;
            case 'jpg':
            case 'jpeg':
                return 'image/jpg';
                break;
            default:
                return 'application/force-download';
                break;
        }
    }

    public function removeFile($type, $fileName) {
        $filePath = $this->_getDir($type) . $fileName;
        unlink($filePath);
        if ($type == 'zip') {//zip删除上级目录
            $dir = dirname($filePath);
            if (is_dir($dir) && $op = dir($dir)) {
                while (false != ($item = $op->read())) {
                    if ($item == '.' || $item == '..') {
                        continue;
                    }
                    unlink($op->path . '/' . $item);
                }
                rmdir($dir);
            }
        }
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/9/10
 * Time: 16:23
 */

namespace app\modules\ali_small_common\modules\v1\controllers;


use app\modules\ali_small_common\controllers\BaseController;
use common\core\F;
use OSS\Core\OssException;
use OSS\Http\RequestCore;
use OSS\Http\ResponseCore;
use OSS\OssClient;
use service\common\UploadService;

class UploadController extends BaseController
{
    //图片上传
    public function actionImage()
    {
        //图片文件检测
        if (empty($_FILES['file'])) {
            return F::apiFailed('未获取上传文件');
        }

        $accessKeyId = "LTAIRMyJgmFU2NnA";
        $accessKeySecret = "x6iozkqapZVgE5BsKBeU23eP3xDA1p";
        $endpoint = "http://zjy-datav2.oss-cn-hangzhou.aliyuncs.com";
        $bucket = "zjy-datav2";
        $file = $_FILES['file'];
        //图片文件检测
        $r = UploadService::service()->checkImage($file);
        if (!$r['code']) {
            return F::apiFailed($r['msg']);
        }

        //上传到本地
        $r = UploadService::service()->saveLocal($file, F::qiniuImagePath());
        if (!$r['code']) {
            return F::apiFailed($r['msg']);
        }
        $res['file_path'] = \Yii::$app->params['host_name'].'store/uploadFiles/front/original/'.$r['data']['parentDir']."/".$r['data']['fileName'];
        return F::apiSuccess($res);

//        $object = $r['data']['fileName'];
//        $filePath = $r['data']['fileDir'].$r['data']['fileName'];
//        try{
//            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
//            $re = $ossClient->uploadFile($bucket, $object, $filePath);
//            //print_r($re);exit;
//        } catch(OssException $e) {
//
//            //printf(__FUNCTION__ . ": FAILED\n");
//            printf($e->getMessage() . "\n");
//        }
//
//        exit;
//        $timeout = 3600;
//        try {
//            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint, false, '');
//
//            // 生成GetObject的签名URL。
//            $signedUrl = $ossClient->signUrl($bucket, $object, $timeout);
//        } catch (OssException $e) {
//            printf(__FUNCTION__ . ": FAILED\n");
//            printf($e->getMessage() . "\n");
//        }
//        print(__FUNCTION__ . ": signedUrl: " . $signedUrl . "\n");
//
//        exit;

    }

    public function actionGetImage()
    {

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
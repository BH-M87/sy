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

        $accessKeyId = "LTAIG9QWK20XYpp1";
        $accessKeySecret = "yWQNFSfw2Yxo3AeKiHYAlS5UH6MOOF";
        $endpoint = "http://oss-cn-shanghai.aliyuncs.com";
        $bucket= "micro-brain-bucket";
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

        $object = $r['data']['fileName'];
        $filePath = $r['data']['fileDir'].$r['data']['fileName'];
        $fileName = $object;
        try{
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $re = $ossClient->uploadFile($bucket, $object, $filePath);

        } catch(OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
        }



        $timeout = 3600;
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint, false, '');

            // 生成GetObject的签名URL。
            $signedUrl = $ossClient->signUrl($bucket, $object, $timeout);
        } catch (OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
        }
        print(__FUNCTION__ . ": signedUrl: " . $signedUrl . "\n");


        $request = new RequestCore($signedUrl);
// 生成的URL默认以GET方式访问。
        $request->set_method('GET');
        $request->add_header('Content-Type', '');
        $request->send_request();
        $res = new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());
        if ($res->isOK()) {
            print(__FUNCTION__ . ": OK" . "\n");
        } else {
            print(__FUNCTION__ . ": FAILED" . "\n");
        };
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
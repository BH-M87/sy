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
use common\MyException;
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

        $accessKeyId = \Yii::$app->params['oss_access_key_id'];
        $accessKeySecret = \Yii::$app->params['oss_secret_key_id'];
        $endpoint = \Yii::$app->params['oss_domain'];
        $bucket = \Yii::$app->params['oss_bucket'];
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
        $local = $r['data'];
        $object = $local['fileName'];
        $filePath = $local['fileDir'] . $local['fileName'];
        try{
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->uploadFile($bucket, $object, $filePath);
        } catch(OssException $e) {
            throw new MyException($e->getMessage());
        }

        //上传到七牛
        $re['filepath'] = F::getOssImagePath($object);
        $re['key_name'] = $object;
        return F::apiSuccess($re);

    }
}
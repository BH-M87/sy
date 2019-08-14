<?php
/**
 * 七牛文件上传类
 * 七牛上传文件，超过maxSize，则走分段上传，否则form表单一次性上传。注意：callback
 * User: yshen
 * Date: 2018/5/9
 * Time: 22:34
 */

namespace app\modules\qiniu\controllers;

use Yii;
use common\core\F;
use common\core\PsCommon;
use service\qiniu\UploadService;

Class UploadController extends BaseController
{

    //富文本编辑器
    public function actionEditor()
    {
        $action = Yii::$app->request->get('action');
        if ($action == 'config') {
            $configFilePath = Yii::$app->basePath . "/config/ueditor.json";
            $config = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($configFilePath)), true);
            $config['imageUrlPrefix'] = Yii::$app->getModule('qiniu')->params['fileHostUrl'];//七牛域名
            $config['fileManagerUrlPrefix'] = Yii::$app->getModule('qiniu')->params['fileHostUrl'];//七牛域名
            $result = json_encode($config);
            return $result;
        } else {//上传图片
            if (empty($_FILES['upfile'])) {
                return PsCommon::responseFailed('未获取上传文件');
            }
            $file = $_FILES['upfile'];
            //图片文件检测
            $r = UploadService::service()->checkImage($file);
            if (!$r['code']) {
                return PsCommon::responseFailed($r['msg']);
            }
            //上传到本地
            $r = UploadService::service()->saveLocal($file, F::qiniuImagePath());
            if (!$r) {
                return PsCommon::responseFailed($r['msg']);
            }
            $local = $r['data'];
            //上传到七牛
            $re['filepath'] = UploadService::service()->saveQiniu($local['fileName'], $local['fileDir'] . $local['fileName']);
            if (!$re['filepath']) {
                return PsCommon::responseFailed('七牛上传失败');
            }
            $result['state'] = 'SUCCESS';
            $result['url'] = $local['fileName'];
            $result['title'] = $local['fileName'];
            $result['original'] = $local['fileName'];
            $result['type'] = '.' . pathinfo($local['fileName'], PATHINFO_EXTENSION);
            return json_encode($result);
        }
    }

    //上传图片
    public function actionImage()
    {
        if (empty($_FILES['file'])) {
            return PsCommon::responseFailed('未获取上传文件');
        }
        $file = $_FILES['file'];
        //图片文件检测
        $r = UploadService::service()->checkImage($file);
        if (!$r['code']) {
            return PsCommon::responseFailed($r['msg']);
        }
        //上传到本地
        $r = UploadService::service()->saveLocal($file, F::qiniuImagePath());
        if (!$r) {
            return PsCommon::responseFailed($r['msg']);
        }
        $local = $r['data'];
        //上传到七牛
        $re['filepath'] = UploadService::service()->saveQiniu($local['fileName'], $local['fileDir'] . $local['fileName']);
        if (!$re['filepath']) {
            return PsCommon::responseFailed('七牛上传失败');
        }
        $re['localPath'] = 'front/original/' . $local['parentDir'] . '/' . $local['fileName'];
        return PsCommon::responseSuccess($re);
    }

    //以base64编码上传图片
    public function actionStreamImage()
    {
        $img = F::post('img');
        $type = Yii::$app->request->post('type', '');

        if (!$img) {
            return PsCommon::responseFailed('未获取上传文件');
        }
        //图片文件检测
        $r = UploadService::service()->checkStreamImage($img);
        if (!$r['code']) {
            return PsCommon::responseFailed($r['msg']);
        }
        $imgArr = $r['data'];
        $imgString = str_replace($imgArr['result'][1], '', $imgArr['characters']);
        //上传到本地
        $r = UploadService::service()->saveStreamLocal($imgString, 'jpg', F::qiniuImagePath());
        if (!$r) {
            return PsCommon::responseFailed($r['msg']);
        }
        $local = $r['data'];
        //上传到七牛
        $re['filepath'] = UploadService::service()->saveQiniu($local['fileName'], $local['fileDir'] . $local['fileName']);
        if (!$re['filepath']) {
            return PsCommon::responseFailed('七牛上传失败');
        }
        //本地模拟测试的时候没有parentDir字段，因此做了一个判断。add by zq 2019-4-25
        $parentDir = !empty($local['parentDir']) ? $local['parentDir'] : '';
        $re['localPath'] = 'front/original/' . $parentDir . '/' . $local['fileName'];
        if (!empty($type) && $type == "face") {
            //校验人脸照片
            $body['data']['uri'] = $re['filepath'];
            $res = UploadService::service()->checkExistFace('/v1/face/detect',json_encode($body));
            if($res !== true){
                return PsCommon::responseFailed($res);
            }
        }
        return PsCommon::responseSuccess($re);
    }

    //上传版本更新，操作手册
    public function actionVersion()
    {
        if (empty($_FILES['file']) || empty($_FILES['file']['name'])) {
            return PsCommon::responseFailed('操作手册不能为空');
        }
        $file = PsCommon::get($_FILES, 'file', []);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if ($ext != 'pdf') {
            return PsCommon::responseFailed('操作手册仅支持PDF文件');
        }
        //上传到本地
        $r = UploadService::service()->saveVersionFile($file);
        if (!$r) {
            return PsCommon::responseFailed($r['msg']);
        }
        $local = $r['data'];
        //上传到七牛
        $re['filepath'] = UploadService::service()->saveQiniu('version/' . $local['fileName'], $local['fileDir'] . $local['fileName']);
        if (!$re['filepath']) {
            return PsCommon::responseFailed('七牛上传失败');
        }
        return PsCommon::responseSuccess($re);
    }

    //上传文件
    public function actionFile()
    {
        if (empty($_FILES['file']) || empty($_FILES['file']['name'])) {
            return PsCommon::responseFailed('附件不能为空');
        }
        $file = PsCommon::get($_FILES, 'file', []);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        //19-4-4产品定的去掉-街道办上传附件的原因
        //$vali=['doc', 'docx','ppt', 'pdf', 'xlsx', 'zip', '.zip', 'txt', 'text', 'jpg', 'xls', 'rar', 'wps', 'png', 'jpeg', 'pptx', 'JPG', 'act','rec','vy1','vy2','vy3','vy4','sc4','dvf','msc','wma','mp3','wav','amr','m4a','ava','avi','mov','rmvb','rm','flv','mp4','3gp'];
        //if (!in_array($ext, $vali)) {
            //return PsCommon::responseFailed('附件格式错误！');
        //}
        //上传到本地
        $r = UploadService::service()->saveFile($file,$ext);
        if (!$r) {
            return PsCommon::responseFailed($r['msg']);
        }
        $local = $r['data'];
        //上传到七牛
        $re['filepath'] = UploadService::service()->saveQiniu('file/' . $local['fileName'], $local['fileDir'] . $local['fileName']);
        if (!$re['filepath']) {
            return PsCommon::responseFailed('七牛上传失败');
        }
        $re['filename'] = $_FILES['file']['name'];
        return PsCommon::responseSuccess($re);
    }

}

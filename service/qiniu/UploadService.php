<?php
/**
 * 上传Service
 * User: yshen
 * Date: 2018/5/9
 * Time: 22:45
 */

namespace service\qiniu;

use common\core\PsCommon;
use Yii;
use common\core\Curl;
use common\core\F;
use common\core\ImageManage;
use service\BaseService;

Class UploadService extends BaseService
{
    //图片允许的最大文件大小2M
    public $imageMaxSize = 4;
    //允许的图片格式
    public $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
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
        if(!in_array($img['type'], $this->imageMimes)){
            return $this->failed('不允许的文件Mime类型');
        }
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
     * 检查base64编码图片格式
     * @param $img
     * @return array
     */
    public function checkStreamImage($img)
    {
        if (!preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result)) {
            return $this->failed('不是有效的图片');
        }
        $ext = $result[2];
        if (!in_array($ext, $this->imageExt)) {
            return $this->failed('不允许的图片扩展名');
        }
        return $this->success(['result' => $result, 'ext' => $ext, 'characters' => $img]);
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
        return $this->success(['fileName' => $newFileName, 'fileDir' => $realDir, 'parentDir' => $parentDir]);
    }

    /**
     * 保存base64编码的图片到本地
     * @param $img
     * @param $dir
     * @param $fileName
     * @return array
     */
    public function saveStreamLocal($img, $ext, $dir, $fileName = '')
    {
        //完整的目录
        $realDir = $dir . $this->_generateParentDir() . '/';
        if (!is_dir($realDir)) {//0755: rw-r--r--
            mkdir($realDir, 0755, true);
        }
        //新文件
        $newFileName = $fileName ? $fileName . '.' . $ext : $this->_generateName($ext);
        $newFile = $realDir . $newFileName;
        if (!file_put_contents($newFile, base64_decode($img))) {
            return $this->failed('本地保存失败');
        }
        chmod($newFile, 0755);
        return $this->success(['fileName' => $newFileName, 'fileDir' => $realDir]);
    }

    /**
     * 上传版本更新操作手册
     * @param $file
     * @param $dir
     * @return array
     */
    public function saveVersionFile($file)
    {
        $dir = F::storePath() . 'version/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $newFileName = $this->_generateName('pdf');
        $newFile = $dir . $newFileName;
        if (!move_uploaded_file($file['tmp_name'], $newFile)) {
            return $this->failed('保存失败');
        }
        chmod($newFile, 0755);
        return $this->success(['fileName' => $newFileName, 'fileDir' => $dir]);
    }
    /**
     * 上传附件
     * @param $file
     * @param $dir
     * @return array
     */
    public function saveFile($file,$ext)
    {
        $dir = F::storePath() . 'file/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $newFileName = $this->_generateName($ext);
        $newFile = $dir . $newFileName;
        if (!move_uploaded_file($file['tmp_name'], $newFile)) {
            return $this->failed('保存失败');
        }
        chmod($newFile, 0755);
        return $this->success(['fileName' => $newFileName, 'fileDir' => $dir]);
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
        $bucket    = Yii::$app->getModule('qiniu')->params['bucket'];
        $result = ImageManage::getInstance()->upfile($bucket, $keyName, $filePath);
        if (!$result) {
            return "";
        }
        return Yii::$app->getModule('qiniu')->params['fileHostUrl'] . $result;
    }

    /**
     * 将钉钉图片cdn，放到消息队列中，异步处理
     * @param $imageString
     * @param string $type 类型
     */
    public function pushDing($id, $type, $imageString)
    {
        $cacheName = 'lyl:ding:image:'.YII_ENV;
        $imagesArr = explode(',', $imageString);
        foreach ($imagesArr as $image) {
            if (preg_match('/static.dingtalk.com/', $image)) {
                $data = ['type' => $type, 'id' => $id, 'url' => $image];
                Yii::$app->redis->lpush($cacheName, json_encode($data));
            }
        }
    }

    /**
     * 将钉钉cdn的图片地址转化为本地+七牛
     */
    public function saveFromDingding($imageUrl)
    {
        $parentDir = $this->_generateParentDir();
        $realDir = F::qiniuImagePath() . $parentDir . '/';
        $fileName = $this->_generateName('jpg');
        $filePath = F::curlImage($imageUrl, $realDir, $fileName);//采集到本地
        if (!$info = getimagesize($filePath)) {//非法图片
            return false;
        }
        if(!empty($info['mime']) && !in_array($info['mime'], $this->imageMimes)){
            return false;
        }
        exec('chown -R nginx:nginx '.$realDir);//调用linux命令，修改组和权限
        return $this->saveQiniu($fileName, $filePath);
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

    /**
     * 判断上传的图片是否有人脸
     * @param $url
     * @param $body
     * @param int $count
     * @return bool|string
     */
    public function checkExistFace($url, $body,$count = 1)
    {
        $host = Yii::$app->getModule('qiniu')->params['face_host'];
        $access_key = ImageManage::ACCESS_kEY;
        $contentType = 'application/json';
        $stringToSign = "POST"." ".$url."\nHost: ".$host."\nContent-Type: ".$contentType."\n\n".$body;//组装签名参数
        $signature = \Qiniu\base64_urlSafeEncode(hash_hmac("sha1", $stringToSign, ImageManage::SECRET_KEY, true));//获取签名
        $authHeader = "Qiniu ".$access_key.":".$signature;
        $options['CURLOPT_HTTPHEADER'] = [
            "content-type: ".$contentType,
            "authorization: ".$authHeader
        ];
        $send_url = "http://".$host.$url;
        $curlObject = new Curl($options);
        $res = json_decode($curlObject->post($send_url,$body),true);

        if($res['code'] == 0){
            $face_count = count($res['result']['detections']);
            if($face_count == 0){
                return "图中无人脸";//没有人脸
            }
            if($count == $face_count){
                //图片质量
                $faceScore = $res['result']['detections'][0]['boundingBox']['score'];

                if (bccomp($faceScore, '0.99998', 5) < 0) {
                    return "图片质量不佳";
                }

                return true;
            } else{
                return "图中有脸，但不是".$count."张脸";//数量不匹配
            }
        }else{
            return "七牛鉴定失败";//七牛鉴定失败
        }
    }

    public function stream_image($img,$type = '')
    {
        if (!$img) {
            return PsCommon::responseFailed('未获取上传文件');
        }
        //图片文件检测
        $r = $this->checkStreamImage($img);
        if (!$r['code']) {
            return $this->failed($r['msg']);
        }
        $imgArr = $r['data'];
        $imgString = str_replace($imgArr['result'][1], '', $imgArr['characters']);
        //上传到本地
        $r = $this->saveStreamLocal($imgString, 'jpg', F::qiniuImagePath());
        if (!$r) {
            return $this->failed($r['msg']);
        }
        $local = $r['data'];
        //上传到七牛
        $re['filepath'] = $this->saveQiniu($local['fileName'], $local['fileDir'] . $local['fileName']);
        if (!$re['filepath']) {
            return $this->failed('七牛上传失败');
        }
        //本地模拟测试的时候没有parentDir字段，因此做了一个判断。add by zq 2019-4-25
        $parentDir = !empty($local['parentDir']) ? $local['parentDir'] : '';
        $re['localPath'] = 'front/original/' . $parentDir . '/' . $local['fileName'];
        if (!empty($type) && $type == "face") {
            //校验人脸照片
            $body['data']['uri'] = $re['filepath'];
            $res = $this->checkExistFace('/v1/face/detect',json_encode($body));
            if($res !== true){
                return $this->failed($res);
            }
        }
        return $this->success($re);
    }

}

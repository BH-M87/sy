<?php
/**
 * 通用方法
 * @author shenyang
 * @date 2017/09/14
 */

namespace common\core;

use common\MyException;
use OSS\Core\OssException;
use OSS\OssClient;
use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\web\HttpException;

class F
{
    //TODO CDN
    //path: wechat/web/static/sharepark/css/xxx.css
    public static function staticUrl($type, $file, $module, $project)
    {
        $version = Yii::$app->params[$project][$module]['version'];
        $host = Yii::$app->params[$project]['host'];//wechat
        return $host . '/static/' . $module . '/' . $type . '/' . $file . '?v=' . $version;
    }

    //样式文件url
    public static function cssUrl($name, $module, $project)
    {
        return self::staticUrl('css', $name . '.css', $module, $project);
    }

    //js文件url
    public static function jsUrl($name, $module, $project)
    {
        return self::staticUrl('js', $name . '.js', $module, $project);
    }

    //静态图片文件url
    public static function imageUrl($file, $module, $project)
    {
        return self::staticUrl('images', $file, $module, $project);
    }

    //get, post
    public static function request($name = '', $default = '')
    {
        $get = Yii::$app->request->get();
        $post = Yii::$app->request->post();
        $request = array_merge($get, $post);
        if ($name) {
            return !empty($request[$name]) ? $request[$name] : $default;
        }
        return $request;
    }

    //get
    public static function get($name, $default = '')
    {
        return trim(Yii::$app->request->get($name, $default));
    }

    //post
    public static function post($name, $default = '')
    {
        return trim(Yii::$app->request->post($name, $default));
    }

    //empty
    public static function value($params, $name, $default = '')
    {
        if (!empty($params[$name])) {
            if (is_array($params[$name])) {
                return $params[$name];
            }
            return trim($params[$name]);
        }
        return $default;
    }

    //参数验证成功
    public static function paramsSuccess($data = [])
    {
        $data = [
            'code' => 0,
            'data' => $data,
            'message' => '',
            'error' => ['errorMsg'=>'']
        ];

        return $data;
    }

    //参数验证失败
    public static function paramsFailed($msg = '网络异常', $code = 50001)
    {
        $data = [
            'errCode' => $code,
            'data' => (object)[],
            'message' => $msg,
            'error' => ['errorMsg'=>$msg]
        ];
        return $data;
    }

    //接口请求成功
    public static function apiSuccess($data = [])
    {
        $data = [
            'code' => 1,
            'data' => $data,
            'message' => '',
            'error' => ['errorMsg'=>'']
        ];
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->content = json_encode($data, JSON_UNESCAPED_SLASHES);
        Yii::$app->response->send();
        return null;
    }

    //接口请求失败
    public static function apiFailed($msg = '网络异常', $code = 50001)
    {
        $data = [
            'code' => $code,
            'data' => (object)[],
            'message' => $msg,
            'error' => ['errorMsg'=>$msg]
        ];
        Yii::info(json_encode($msg, 320), 'api');
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->content = json_encode($data, JSON_UNESCAPED_UNICODE);
        Yii::$app->response->send();
        return null;
    }

    //返回json
    public static function ajaxSuccess($data = [])
    {
        $result['code'] = 0;
        $result['data'] = $data;
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    //返回json
    public static function ajaxFailed($msg = '', $code = 1)
    {
        $result['err'] = $code;//1:通用错误，其他值可以自定义
        $result['msg'] = $msg ? $msg : '出错啦';
        return json_encode($result, 256);
    }

    //重复请求的
    public static function _repeatCacheField()
    {
        $url = Yii::$app->request->getPathInfo();//路由，不包括get参数
        if(empty($url)){
            $url = Yii::$app->controller->action->uniqueId;
        }
        $token = self::request('token');
        return 'lyl:repeat:cache' . md5(json_encode([$url, $token]));
    }

    //判断是否重复请求
    public static function repeatRequest()
    {
        $cacheKey = self::_repeatCacheField();
        if (Yii::$app->redis->set($cacheKey, 1, 'EX', 10, 'NX')) {
            return false;
        }
        return true;
    }

    //七天的时间表示
    public static function sevenDays($day = '')
    {
        $days = [];
        $weeks = array('星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六');
        for ($i = 0; $i < 7; $i++) {
            $t = strtotime('+' . $i . ' day');
            if ($i == 0) {
                $text = '今天';
            } elseif ($i == 1) {
                $text = '明天';
            } elseif ($i == 2) {
                $text = '后天';
            } else {
                $text = $weeks[date('w', $t)];
            }
            if ($day && date('Y-m-d', $t) == $day) {
                return $text . ' ' . date('Y-m-d', $t);
            }
            $days[] = [
                'date' => date('m-d', $t),
                'text' => $text
            ];
        }
        if ($day) {
            return '';
        }
        return $days;
    }


    /**
     * 本地存储的文件目录(所有文件的根目录)
     * @return string
     */
    public static function storePath()
    {
        return Yii::$app->basePath . '/web/store/';
    }

    /**
     * 二维码图片地址
     */
    public static function imagePath($dir = '')
    {
        return $dir ? self::storePath() . 'image/' . $dir . '/' : self::storePath() . 'image/';
    }

    /**
     * 下载图片到本地
     * @param $url
     * @param string $name
     */
    public static function curlImage($url, $dir, $name = '')
    {
        $curl = Curl::getInstance(['CURLOPT_FOLLOWLOCATION' => 1]);
        $content = $curl->get($url);
        if (!$content) {
            throw new HttpException(500, '图片无法抓取');
        }
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = $name ? $name : date('YmdHis') . '.png';
        $fileName = $dir . $name;
        $fp = fopen($fileName, 'w');
        fwrite($fp, $content);
        fclose($fp);
        chmod($fileName, 0755);
        return $fileName;
    }

    //获取下载链接地址
    public static function downloadUrl($fileName, $type, $newName = '')
    {
        $data = ['filename' => $fileName, 'type' => $type, 'newname' => $newName];
//        $token = self::request('token');
        $module = 'property/v1';
//        return self::getAbsoluteUrl() . '/' . $module . '/download?data=' . json_encode($data) . '&token=' . $token;
        return self::getAbsoluteUrl() . '/' . $module . '/download?data=' . json_encode($data);
    }

    /*
     * 上传文件到oss
     * input:
     *  fileName 文件名称
     *  local     本地地址
     */
    public static function uploadExcelToOss($fileName, $local){
        $accessKeyId = \Yii::$app->params['platForm_oss_access_key_id'];
        $accessKeySecret = \Yii::$app->params['platForm_oss_secret_key_id'];
        $endpoint = \Yii::$app->params['platForm_oss_domain'];
        $bucket = \Yii::$app->params['platForm_oss_bucket'];
        try{
            $object = $fileName;
            $filePath = $local.$fileName;
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->uploadFile($bucket, $object, $filePath);
            @unlink($filePath);
//            //下载
//            $localFile = Yii::$app->basePath . '/web/store/'.$type.'/record/'.$fileName;
//            $options = array(
//                OssClient::OSS_FILE_DOWNLOAD => $localFile
//            );
//            $downResult = $ossClient->getObject($bucket, $object, $options);
//            print_r($downResult);die;
        } catch(OssException $e) {
            throw new MyException($e->getMessage());
        }

        // 设置URL的有效期为3600秒。
        $timeout = 3600;
        $signedUrl = '';
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            // 生成GetObject的签名URL。
            $signedUrl = $ossClient->signUrl($bucket, $fileName, $timeout);
            return $signedUrl;
        } catch (OssException $e) {
            throw new MyException($e->getMessage());
        }
    }

    /**
     * 创建新的文件名称(以时间区分)
     */
    public static function generateName($ext)
    {
        list($msec, $sec) = explode(' ', microtime());
        $msec = round($msec, 3) * 1000;//获取毫秒
        return date('YmdHis') . $msec . rand(100, 999) . '.' . $ext;
    }

    //获取完整链接，不带get参数
    public static function getAbsoluteUrl()
    {
        $host = Yii::$app->params['api_host_url'];
        return $host;
    }

    public static function arrayFilter($arr)
    {
        $tmpArr = [];
        foreach ($arr as $k => $v) {
            if ($v) {
                array_push($tmpArr, $arr[$k]);
            }
        }
        return $tmpArr;
    }

    //翻译为中文的周几
    public static function getWeekChina($key)
    {
        $week = [
            1 => '周一',
            2 => '周二',
            3 => '周三',
            4 => '周四',
            5 => '周五',
            6 => '周六',
            7 => '周日',
        ];
        return $week[$key];
    }

    //计算两个坐标点之间的距离
    public static function getDistance($lat1, $lon1, $lat2, $lon2)
    {
        $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lon1);
        $radLng2 = deg2rad($lon2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
        return $s;
    }


    public static function excelPath($dir = '')
    {
        $dir = $dir ? $dir . '/' : '';
        return self::storePath() . 'excel/' . $dir;
    }

    /**
     * 上传到七牛的图片，本地备份目录
     */
    public static function qiniuImagePath()
    {
        return self::originalImage() . 'front/original/';
    }

    //上传图片的目录
    public static function originalImage()
    {
        return Yii::$app->basePath . '/web/store/uploadFiles/';
    }

    //上传文件的目录
    public static function originalFile()
    {
        return Yii::$app->basePath . '/web/store/excel/';
    }

    /**
     * 生成订单号统一规则
     * @param $prefix
     */
    public static function generateOrderNo($prefix = '')
    {
        $time = date('YmdHis');//14位
        $incr = Yii::$app->redis->incr('lyl:order_no');//自增数字
        $incr = str_pad(substr($incr, -3), 3, '0', STR_PAD_LEFT);//取最后三位，前置补0
        return $prefix . $time . $incr . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);//除prefix外，共20位
    }

    /**
     * 物业公司随机码(用于授权回调)
     * @return string
     */
    public static function companyCode()
    {
        $str = '';
        for ($i = 0; $i < 8; $i++) {
            $str .= chr(mt_rand(33, 126));
        }
        return md5(uniqid(md5(microtime(true)), true) . $str);
    }

    //时间相减算月差
    public static function  getMonthNum($date1, $date2, $tags='-')
    {
        $date1 = explode($tags,$date1);
        $date2 = explode($tags,$date2);
        return (abs($date1[0] - $date2[0])-1) * 12 + (12-$date1[1]+1)+$date2[1];
    }

    //获取时间的年月日
    public static function  getYearMonth($date)
    {
        $year = date('Y', $date);
        $month = date('m', $date);
        $date1['year'] = $year;
        $date1['month'] = $month;
        return $date1;
    }

    //判断是否重复请求钉钉专用
    public static function repeatRequestDingApp()
    {
        $cacheKey = self::_repeatCacheField();
        if (Yii::$app->redis->set($cacheKey, 1, 'EX', 3, 'NX')) {
            return false;
        }
        return true;
    }

    /**
     * 获取不重复的编码
     * @return string
     */
    public static function getCode($top = '', $cacheKey, $randLength = 6)
    {
        $randStr = $top.self::getRandomString($randLength);
        if (\Yii::$app->redis->sismember($cacheKey, $randStr)) {//集合中已经存在，则递归执行
            return self::getCode($top);
        }
        return $randStr;
    }

    /**
     * 获取随机数
     * @param $len
     * @param null $chars
     * @return string
     */
    public static function getRandomString($len, $chars = null)
    {
        if (is_null($chars)) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
        mt_srand(10000000 * (double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }

    /**
     * 过滤掉搜索条件为null的值
     * @param $where 条件参数数组
     */
    public static function searchFilter($where)
    {
        foreach($where as $key => $value) {
            if($value == null) {
                unset($where[$key]);
            }
        }
        return $where;
    }

    //判断是否重复请求-小程序端
    public static function repeatRequestSmall($time=30)
    {
        $cacheKey = self::_repeatCacheField();
        if (Yii::$app->redis->set($cacheKey, 1, 'EX', $time, 'NX')) {
            return false;
        }
        return true;
    }

    //全角半角互转 $args2 1全角转半角 0是半转全
    public static function sbcDbc($str, $args2) {
        $DBC = Array(
            '０' , '１' , '２' , '３' , '４' ,
            '５' , '６' , '７' , '８' , '９' ,
            'Ａ' , 'Ｂ' , 'Ｃ' , 'Ｄ' , 'Ｅ' ,
            'Ｆ' , 'Ｇ' , 'Ｈ' , 'Ｉ' , 'Ｊ' ,
            'Ｋ' , 'Ｌ' , 'Ｍ' , 'Ｎ' , 'Ｏ' ,
            'Ｐ' , 'Ｑ' , 'Ｒ' , 'Ｓ' , 'Ｔ' ,
            'Ｕ' , 'Ｖ' , 'Ｗ' , 'Ｘ' , 'Ｙ' ,
            'Ｚ' , 'ａ' , 'ｂ' , 'ｃ' , 'ｄ' ,
            'ｅ' , 'ｆ' , 'ｇ' , 'ｈ' , 'ｉ' ,
            'ｊ' , 'ｋ' , 'ｌ' , 'ｍ' , 'ｎ' ,
            'ｏ' , 'ｐ' , 'ｑ' , 'ｒ' , 'ｓ' ,
            'ｔ' , 'ｕ' , 'ｖ' , 'ｗ' , 'ｘ' ,
            'ｙ' , 'ｚ' , '－' , '　' , '：' ,
            '．' , '，' , '／' , '％' , '＃' ,
            '！' , '＠' , '＆' , '（' , '）' ,
            '＜' , '＞' , '＂' , '＇' , '？' ,
            '［' , '］' , '｛' , '｝' , '＼' ,
            '｜' , '＋' , '＝' , '＿' , '＾' ,
            '￥' , '￣' , '｀'
        );

        $SBC = Array( // 半角
            '0', '1', '2', '3', '4',
            '5', '6', '7', '8', '9',
            'A', 'B', 'C', 'D', 'E',
            'F', 'G', 'H', 'I', 'J',
            'K', 'L', 'M', 'N', 'O',
            'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y',
            'Z', 'a', 'b', 'c', 'd',
            'e', 'f', 'g', 'h', 'i',
            'j', 'k', 'l', 'm', 'n',
            'o', 'p', 'q', 'r', 's',
            't', 'u', 'v', 'w', 'x',
            'y', 'z', '-', ' ', ':',
            '.', ',', '/', '%', '#',
            '!', '@', '&', '(', ')',
            '<', '>', '"', '\'','?',
            '[', ']', '{', '}', '\\',
            '|', '+', '=', '_', '^',
            '$', '~', '`'
        );

        if ($args2 == 0) {
            return str_replace($SBC, $DBC, $str);  // 半角到全角
        } else if ($args2 == 1) {
            return str_replace($DBC, $SBC, $str);  // 全角到半角
        } else {
            return false;
        }
    }

    public static function writeLog($path, $file, $content, $type = FILE_APPEND)
    {
        $today    = date("Y-m-d", time());
        $savePath = \Yii::$app->basePath . DIRECTORY_SEPARATOR. 'runtime'. DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $today . DIRECTORY_SEPARATOR;
        if (FileHelper::createDirectory($savePath, 0777)) {
            if (!file_exists($savePath.$file)) {
                file_put_contents($savePath.$file, $content, $type);
                chmod($savePath.$file, 0777);//第一次创建文件，设置777权限
            } else {
                file_put_contents($savePath.$file, $content, $type);
            }
            return true;
        }
        return false;
    }

    /**
     * 手机号脱敏
     * @param $mobile
     * @return string
     */
    public static function processMobile($mobile)
    {
        return substr($mobile, 0, 3).'****'.substr($mobile, -4);
    }

    /**
     * Notes: 用户名脱敏
     * Author: J.G.N
     * Date: 2019/7/27 16:25
     * @param $mobile
     * @return string
     */
    public static function processUserName($str){
        if(mb_strlen($str)==2){
            return mb_substr($str, 0, 1, 'utf-8').'*';
        }else{
            return mb_substr($str, 0, 1, 'utf-8').'*'. mb_substr($str, -1, 1, 'utf-8');
        }
    }

    /**
     * 身份证号脱敏
     * @param $mobile
     * @return string
     */
    public static function processIdCard($idCard)
    {
        if (!empty($idCard)) {
            return substr($idCard, 0, 6).'********'.substr($idCard, -4);
        }
        return '';
    }

    //因为小程序跟物业后台的返回类型不一样，因此在调用Exception的时候，小程序端需要设置一下smallStatus
    public static $smallStatus = '';
    public static function setSmallStatus()
    {
        return self::$smallStatus = 1;
    }

    public static function getSmallStatus()
    {
        return self::$smallStatus;
    }

    /**
     * 根据图片key_name 获取图片可访问路径
     * @param $keyName
     */
    public static function getOssImagePath($keyName, $type = '')
    {

        if (YII_PROJECT == "fuyang") {
            if ($type == 'zjy') {
                $accessKeyId = \Yii::$app->params['zjy_oss_access_key_id'];
                $accessKeySecret = \Yii::$app->params['zjy_oss_secret_key_id'];
                $endpoint = \Yii::$app->params['zjy_oss_domain'];
                $bucket = \Yii::$app->params['zjy_oss_bucket'];
            } else {
                $accessKeyId = \Yii::$app->params['oss_access_key_id'];
                $accessKeySecret = \Yii::$app->params['oss_secret_key_id'];
                $endpoint = \Yii::$app->params['oss_domain'];
                $bucket = \Yii::$app->params['oss_bucket'];
            }
        } else {
            $accessKeyId = \Yii::$app->params['zjy_oss_access_key_id'];
            $accessKeySecret = \Yii::$app->params['zjy_oss_secret_key_id'];
            $endpoint = \Yii::$app->params['zjy_oss_domain'];
            $bucket = \Yii::$app->params['zjy_oss_bucket'];
        }

        // 设置URL的有效期为3600秒。
        $timeout = 3600;
        $signedUrl = '';
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            // 生成GetObject的签名URL。
            $signedUrl = $ossClient->signUrl($bucket, $keyName, $timeout);
        } catch (OssException $e) {

        }
        return $signedUrl;
    }


	//文件上传到oss，用于导入导出及错误文件下载
    public static function uploadFileToOss($localPath)
    {
        $extStr = explode('.', $localPath);
        $ext = $extStr[count($extStr)-1];
        $strArr = explode('/', $localPath);
        $fileName = $strArr[count($strArr)-1];

        $accessKeyId = \Yii::$app->params['zjy_oss_access_key_id'];
        $accessKeySecret = \Yii::$app->params['zjy_oss_secret_key_id'];
        $endpoint = \Yii::$app->params['zjy_oss_domain'];
        $bucket = \Yii::$app->params['zjy_oss_bucket'];

        $object = $fileName;
        $filePath = $localPath;

        try{
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->uploadFile($bucket, $object, $filePath);
        } catch(OssException $e) {
            throw new MyException($e->getMessage());
        }
        //上传到七牛
        $re['filepath'] = F::getOssImagePath($object, 'zjy');

        return $re;
    }

    public static function uploadToOss($localPath, $keyName)
    {
        $accessKeyId = \Yii::$app->params['oss_access_key_id'];
        $accessKeySecret = \Yii::$app->params['oss_secret_key_id'];
        $endpoint = \Yii::$app->params['oss_domain'];
        $bucket = \Yii::$app->params['oss_bucket'];
        $object = $keyName;
        $filePath = $localPath;
        try{
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->uploadFile($bucket, $object, $filePath);
        } catch(OssException $e) {
            throw new MyException($e->getMessage());
        }

        //上传到oss
        $re['filepath'] = F::getOssImagePath($object);
        return $re;
    }
    
    // 图片地址转换
    public static function ossImagePath($p)
    {
        if (is_array($p)) {
            foreach ($p as $k => $v) {
                $p[$k] = self::getOssImagePath($v);
            }
            return $p;
        }
        return self::getOssImagePath($p);
    }

    // 切割字符串，多余部分用...表示
    public static function cutString($str, $limit)
    {
        $len = mb_strlen($str);
        if ($len > $limit) {
            return mb_substr($str, 0, $limit) . '...';
        }
        return $str;
    }

    /**
     * 只保留字符串首尾字符，隐藏中间用*代替（两个字符时只显示第一个）
     * @param string $str 姓名
     * @return string 格式化后的姓名
     */
    public static function substrCut($str)
    {
      $strlen = mb_strlen($str, 'utf-8');
      $firstStr = mb_substr($str, 0, 1, 'utf-8');
      $lastStr= mb_substr($str, -1, 1, 'utf-8');
      return $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($str, 'utf-8') - 1) : $firstStr . str_repeat("*", $strlen - 2) . $lastStr;
    }

    /**
     * 验证传入参数
     * @param $model //对象实例
     * @param $data //验证数据
     * @param $scenario //验证场景
     * @return array
     */
    public static function validParamArr(Model $model, $data, $scenario)
    {
        if (!empty($data)) {
            $model->setScenario($scenario);
            $datas["data"] = $data;
            $model->load($datas, "data");
            if ($model->validate()) {
                return [
                    "status" => true,
                    "data" => $data
                ];
            } else {
                $errorMsg = array_values($model->errors);
                return [
                    "status" => false,
                    'errorMsg' => $errorMsg[0][0]
                ];
            }
        } else {
            return [
                "status" => false,
                'errorMsg' => "未接受到有效数据"
            ];
        }
    }

    /**
     * 人行，车行记录图片先下载图片再保存到oss
     * @param $url
     * @return string
     */
    public static function trunsImg($url)
    {
        $trueUrl = $url;
        $url = str_replace("https://","http://",$url);
        $filePath = F::qiniuImagePath().date('Y-m-d')."/";
        if (!is_dir($filePath)) {//0755: rw-r--r--
            mkdir($filePath, 0755, true);
        }
        $fileName = self::_generateName('jpg');
        $newFile = $filePath."/".$fileName;
        $re = self::dlfile($url, $newFile);
        if (!$re) {
            return '';
        }
        $filesize = abs(filesize($newFile));
        if ($filesize <= 0) {
            //如果图片地址不能下载的话，就默认返回原图地址
            return '';
        }

        $accessKeyId = \Yii::$app->params['zjy_oss_access_key_id'];
        $accessKeySecret = \Yii::$app->params['zjy_oss_secret_key_id'];
        $endpoint = \Yii::$app->params['zjy_oss_domain'];
        $bucket = \Yii::$app->params['zjy_oss_bucket'];
        $object = $fileName;
        $imgKeyData = '';
        try{
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->uploadFile($bucket, $object, $newFile);
            @unlink($newFile);
            $imgKeyData = $object;
        } catch(OssException $e) {
        }
        return $imgKeyData;
    }

    /**
     * 转换人脸图片，人脸图片公用空间处理,图片先下载，再做处理
     * @param $url
     * @return string
     */
    public static function trunsFaceImg($url)
    {
        $filePath = F::qiniuImagePath().date('Y-m-d')."/";
        if (!is_dir($filePath)) {//0755: rw-r--r--
            mkdir($filePath, 0755, true);
        }
        $fileName = self::_generateName('jpg');
        $newFile = $filePath."/".$fileName;
        $re = self::dlfile($url, $newFile);
        if (!$re) {
            return '';
        }
        $filesize = abs(filesize($newFile));
        if ($filesize <= 0) {
            //如果图片地址不能下载的话，就默认返回原图地址
            return '';
        }

        $accessKeyId = \Yii::$app->params['zjy_oss_access_key_id'];
        $accessKeySecret = \Yii::$app->params['zjy_oss_secret_key_id'];
        $endpoint = \Yii::$app->params['zjy_oss_domain'];
        $bucket = \Yii::$app->params['zjy_oss_face_bucket'];
        $object = $fileName;
        $imgKeyData = '';
        try{
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->uploadFile($bucket, $object, $newFile);
            @unlink($newFile);
            $imgKeyData = $object;
        } catch(OssException $e) {
        }
        return $imgKeyData;
    }

    public static function dlfile($file_url, $save_to)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_URL, $file_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $file_content = curl_exec($ch);
        $curl_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($curl_code == 200) {
            $downloaded_file = fopen($save_to, 'w');
            fwrite($downloaded_file, $file_content);
            fclose($downloaded_file);
            return true;
            //echo '连接成功，状态码：' . $curl_code;
        } else {
            return false;
            //echo '连接失败，状态码：' . $curl_code;
        }
    }

    /**
     * 创建新的文件名称(以时间区分)
     */
    public static function _generateName($ext)
    {
        list($msec, $sec) = explode(' ', microtime());
        $msec = round($msec, 3) * 1000;//获取毫秒
        return date('YmdHis') . $msec . rand(10,100) . '.' . $ext;
    }
}


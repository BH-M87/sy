<?php
/**
 * 通用方法
 * @author shenyang
 * @date 2017/09/14
 */

namespace common\core;

use Yii;
use yii\web\HttpException;

Class F
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
        return !empty($params[$name]) ? $params[$name] : $default;
    }

    //参数验证成功
    public static function paramsSuccess($data = [])
    {
        $data = [
            'errCode' => 0,
            'data' => $data,
            'errMsg' => ""
        ];

        return $data;
    }

    //参数验证失败
    public static function paramsFailed($msg = '网络异常', $code = 50001)
    {
        $data = [
            'errCode' => $code,
            'data' => (object)[],
            'errMsg' => $msg
        ];
        return $data;
    }

    //接口请求成功
    public static function apiSuccess($data = [])
    {
        $data = [
            'errCode' => 0,
            'data' => $data,
            'errMsg' => ""
        ];

        return json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    //接口请求失败
    public static function apiFailed($msg = '网络异常', $code = 50001)
    {
        $data = [
            'errCode' => $code,
            'data' => (object)[],
            'errMsg' => $msg
        ];
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    //返回json
    public static function ajaxSuccess($data = [])
    {
        $result['err'] = 0;
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
        $token = self::request('token');
        return 'lyl:repeat:cache' . md5(json_encode([$url, $token]));
    }

    //判断是否重复请求
    public static function repeatRequest()
    {
        $cacheKey = self::_repeatCacheField();
        if (Yii::$app->redis->set($cacheKey, 1, 'EX', 30, 'NX')) {
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

    //获取物业后台/运营后台下载链接地址
    //edit by wenchao.feng [add $pathName: 域名对应的目录，对应到 test/release/test2..]
    public static function downloadUrl($systemType, $fileName, $type, $newName = '', $pathName = '')
    {
        $data = ['filename' => $fileName, 'type' => $type, 'newname' => $newName];
        $token = self::request('token');
        $module = '';
        if ($systemType == 1) {
            $module = 'operation';
        } elseif ($systemType == 2) {
            $module = 'property';
        } elseif ($systemType == 3) {
            $module = 'street/backend';
        } elseif ($systemType == 4) {
            $module = 'petition/backend';
        }
        return self::getAbsoluteUrl($pathName) . '/' . $module . '/download?data=' . json_encode($data) . '&token=' . $token;
    }

    //获取完整链接，不带get参数
    public static function getAbsoluteUrl($pathName = '')
    {
        $host = Yii::$app->request->getHostInfo();
        if (strlen(DOWNLOAD_PATH) > 0) {
            $host .= '/' . DOWNLOAD_PATH . '/web';
        }
        if (YII_ENV == 'test') {//测试环境
            $host .= '/test/web';
        } elseif (YII_ENV == 'release') {//预发环境
            $host .= '/release/web';
        } elseif (YII_ENV == 'test2') {//测试环境2，供多个项目同时开发测试环境不够用的情况
            $host .= '/test2/web';
        }
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

    //判断是否重复请求钉钉专业
    public static function repeatRequest2()
    {
        $cacheKey = self::_repeatCacheField();
        if (Yii::$app->redis->set($cacheKey, 1, 'EX', 3, 'NX')) {
            return false;
        }
        return true;
    }

}


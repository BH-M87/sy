<?php
/**
 * 通用方法
 * User: fengwenchao
 * Date: 2019/8/12
 * Time: 10:45
 */
namespace common\core;
use Yii;

class PsCommon {

    public static $secrets = [
        1 => 'YHi$rW8N', //运营后台
        2 => 'HU6%12(w', //物业后台
        3 => '5H!@Odk3',
        4 => '5H!@Odk3',
    ];

    public static $log;//是否记录日志

    /**
     * 简易isset判断
     * @param $data
     * @param $key
     * @param string $default
     * @return mixed
     */
    public static function get($data, $key, $default = '')
    {
        return !empty($data[$key]) ? $data[$key] : $default;
    }

    /**
     * 接口访问跨域过滤
     */
    public static function corsFilter($origins, $allowAll = false)
    {
        if (YII_ENV != 'master') {//本地/测试环境 postman测试的origin=chrome-extension://xxxx，需要排除掉
            if (Yii::$app->request->getHeaders()->get('Black-Hole') == 'zhujia360') {
                return true;
            }
        }
        //Yii2 Cors 作为filter只能放到behavior中，执行顺序优先级低于beforeAction，所以采用init，手动配置header方法
        $currentOrigin = PsCommon::get($_SERVER, 'HTTP_ORIGIN');
        $host = parse_url($currentOrigin, PHP_URL_HOST);
        $hosts = (YII_ENV == 'dev' || $allowAll) ? [$host] : $origins;//本地开发环境域名太多，所以绕开域名限制判断
        if ($currentOrigin && $hosts) {
            //只有origin符合条件，才允许访问接口
            if ($host == 'localhost' || in_array($host, $hosts)) {//host符合要求即可，不管是http还是https
                header('Access-Control-Allow-Origin: ' . $currentOrigin);
                header("Access-Control-Allow-Headers: Origin, Content-Type, X_Requested_With, X-Requested-With, Accept, Zj-Custom-Rand, Zj-Custom-Timestamp, Zj-Custom-Sign");//
                header('Access-Control-Allow-Methods: GET, POST');//允许请求方式
                header('Access-Control-Max-Age: 7200');//options有效期1个小时，有效期内不再发送options请求

                $method = Yii::$app->request->getMethod();
                if ($method == 'OPTIONS') {//OPTIONS请求，直接返回1
                    echo 1;
                    exit;
                }
            } else {
                //记录日志
                $log['url'] = Yii::$app->request->getUrl();
                $log['origin'] = $currentOrigin;
                Yii::info(json_encode($log), 'cors');
                echo 'illegal request';
                exit;
            }
        }
    }

    /**
     * 签名验证
     * @param $systemType
     * @return bool|string
     */
    public static function validSign($systemType)
    {
        if (Yii::$app->request->getHeaders()->get('skip-sign')) {
            return true;
        }
        if (YII_ENV != 'master') {//本地/测试环境 测试人员绕开验签
            if (Yii::$app->request->getHeaders()->get('Black-Hole') == 'zhujia360') {
                return true;
            }
        }
        $data['rand'] = Yii::$app->request->getHeaders()->get('Zj-Custom-Rand');//1000～9999随机四位数字
        $data['token'] = F::request('token');//token
        $params = F::request('data');
        $data['data'] = $params ? json_decode($params) : new \stdClass();//js中默认空对象为{}
        $data['timestamp'] = Yii::$app->request->getHeaders()->get('Zj-Custom-Timestamp');//客户端当前时间戳
        if (!$data['rand'] || !$data['timestamp']) {
            return "验参参数不全";
        }
        $sign = Yii::$app->request->getHeaders()->get('Zj-Custom-Sign');//签名参数
        if (!$sign) {
            return '签名不能为空';
        }

        if (empty(self::$secrets[$systemType])) {
            return '无法识别的系统类型';
        }
        ksort($data);//按照key升序排列
        $rightSign = md5(md5(json_encode($data, 320)) . self::$secrets[$systemType]);
        if ($rightSign != $sign) {
            //记录验签失败日志
            $data['rightSign'] = $rightSign;
            Yii::info(json_encode($data, 320), 'api');
            return "签名验证失败";
        }
        return true;
    }

    /**
     * api调用成功
     * @param array $data
     * @return string
     */
    public static function responseSuccess($data = [])
    {
        if (self::$log) {
            $log['action'] = Yii::$app->controller->action->getUniqueId();
            $log['response'] = $data;
            $log['data'] = F::request();
            Yii::info(json_encode($log, 320), 'api-success');
        }
        if (empty($data)) {
            $data = (object)$data;
        }
        return self::ajaxReturn(20000, $data, ['errorMsg' => '']);
    }

    /**
     * api调用失败
     * @param string $msg
     * @param int $code
     */
    public static function responseFailed($msg = '系统错误', $code = 50001)
    {
        if (self::$log) {
            $log['action'] = Yii::$app->controller->action->getUniqueId();
            $log['msg'] = $msg;
            $log['data'] = F::request();
            Yii::info(json_encode($log, 320), 'api-failed');
        }
        return self::ajaxReturn($code, (object)[], ['errorMsg' => $msg]);
    }

    /**
     * 获取model保存时的错误信息
     * @param $model
     * @return mixed
     */
    public static function getModelError($model)
    {
        $errorMsg = array_values($model->errors);
        return $errorMsg[0][0];
    }

    /**
     * 通用返回格式，私有方法，不对外提供调用，所有接口对外输出格式化出口，请勿随意修改！！
     * 控制器返回需要调用，responseSuccess, response, responseFailed等
     * @param int $code
     * @param array $data
     * @param array $error
     * @return null
     */
    private static function ajaxReturn($code = 20000, $data = [], $error = [])
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->content = json_encode([
            'code' => $code,
            'data' => $data,
            'error' => $error,
        ], JSON_UNESCAPED_UNICODE);
        Yii::$app->response->send();
        return null;
    }




}
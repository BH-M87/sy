<?php
/**
 * 通用方法
 * User: fengwenchao
 * Date: 2019/8/12
 * Time: 10:45
 */
namespace common\core;
use Yii;
use yii\base\Model;

class PsCommon {

    public static $secrets = [
        1 => 'YHi$rW8N', //运营后台
        2 => 'HU6%12(w', //物业后台
        3 => '5H!@Odk3',
        4 => '5H!@Odk3',
    ];

    public static $cycle_days = [
        ["key" => "1", "name" => "按年"],
        ["key" => "2", "name" => "按半年"],
        ["key" => "3", "name" => "按季度"],
        ["key" => "4", "name" => "按月"],
    ];
    //算费年份
    public static $year = [
        ["key" => "2015", "name" => "2015"],
        ["key" => "2016", "name" => "2016"],
        ["key" => "2017", "name" => "2017"],
        ["key" => "2018", "name" => "2018"],
        ["key" => "2019", "name" => "2019"],
        ["key" => "2020", "name" => "2020"],
    ];
    //半年账期
    public static $half_year = [
        ["key" => "1", "name" => "上半年"],
        ["key" => "2", "name" => "下半年"],
    ];
    //季度账期
    public static $quarter = [
        ["key" => "1", "name" => "一季度"],
        ["key" => "2", "name" => "二季度"],
        ["key" => "3", "name" => "三季度"],
        ["key" => "4", "name" => "四季度"],
    ];
    //月度账期
    public static $month = [
        ["key" => "1", "name" => "01"],
        ["key" => "2", "name" => "02"],
        ["key" => "3", "name" => "03"],
        ["key" => "4", "name" => "04"],
        ["key" => "5", "name" => "05"],
        ["key" => "6", "name" => "06"],
        ["key" => "7", "name" => "07"],
        ["key" => "8", "name" => "08"],
        ["key" => "9", "name" => "09"],
        ["key" => "10", "name" => "10"],
        ["key" => "11", "name" => "11"],
        ["key" => "12", "name" => "12"],
    ];

    //推送方式
    public static $push_type = [
        ["key" => "1", "name" => "一次性推送"],
        ["key" => "2", "name" => "定期推送"],
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

    /**
     * 获取不重复的字符串
     * @param string $pre
     * @param $cacheKey
     * @param int $charLength
     * @return string
     */
    public static function getNoRepeatChar($pre = '', $cacheKey, $charLength = 6)
    {
        $randStr = $pre . self::getRandomString($charLength);
        if (\Yii::$app->redis->sismember($cacheKey, $randStr)) {//集合中已经存在，则递归执行
            return self::getNoRepeatChar($pre, $cacheKey);
        }
        return $randStr;
    }


    /**
     * 获取带自增的字符串
     * @param string $pre
     * @param $cacheKey 自增redis key
     * @param int $charLength 除去前缀的数字长度
     * @return string
     */
    public static function getIncrStr($pre = '', $cacheKey, $charLength = 8)
    {
        $str = $pre;
        $incr = Yii::$app->redis->incr($cacheKey);//自增数字
        $str .= sprintf("%0{$charLength}d", $incr);
        return $str;
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
     * 将定义的 $key=>$value 数组当二维数组返回
     * @param $data
     * @return array
     */
    public static function returnKeyValue($data)
    {
        $arr = [];
        if (empty($data)) {
            return $arr;
        }
        foreach ($data as $key => $value) {
            $arr[] = ["key" => $key, "value" => $value];
        }
        return $arr;
    }

    public static function getPayType($index = '')
    {
        $model = ['1' => '一次付清', '2' => '分次付清'];
        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    public static function getPayChannel($index = '', $type = 'key')
    {
        $model = ['1' => '现金', '2' => '支付宝', '3' => '微信', '4' => '刷卡', '5' => "对公", "6" => "支票"];
        switch ($type) {
            case "key" :
                $result = $model[$index];
                break;
            case "value" :
                foreach ($model as $key => $val) {
                    if ($val == $index) {
                        $result = $key;
                    }
                }
                break;
            default:
                $result = $model;
        }
        return $result;
    }

    //新版本的账单状态，2018-1-29 陈科浪新增给前端查询使用
    public static function getPayBillSearchStatus($index = '')
    {
        $model = ['1' => '未缴费', '2' => '线上已缴', '6' => '发布失败', '7' => "线下已缴", '8' => '线下扫码'];
        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    //新版本的账单状态，2018-1-29 陈科浪新增
    public static function getPayBillStatus($index = '')
    {
        $model = ['1' => '未缴费', '2' => '线上已缴', '3' => '未出账单', '4' => '发布中', '6' => '发布失败', '7' => "线下已缴", '8' => '线下扫码'];
        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    public static function getFormulaVar($index = '')
    {
        $model = ['h' => '房屋面积', 'c' => '车位面积'];
        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    //缴费公式：计算规则
    public static function getFormulaRule($index = '')
    {
        $calc = [
            0 => [
                'key' => 1,
                'value' => '整数'
            ],
            1 => [
                'key' => 2,
                'value' => '小数点后一位'
            ],
            2 => [
                'key' => 3,
                'value' => '小数点后两位'
            ]
        ];
        if ($index) {
            return $calc[($index - 1)]['value'];
        }
        return $calc;
    }

    //缴费公式：小数点去尾方式
    public static function getFormulaWay($index = '')
    {
        $way = [
            0 => [
                'key' => 1,
                'value' => '四舍五入'
            ],
            1 => [
                'key' => 2,
                'value' => '向上取整'
            ],
            2 => [
                'key' => 3,
                'value' => '向下取整'
            ]
        ];
        if ($index) {
            return $way[($index - 1)]['value'];
        }
        return $way;
    }

    //交易类型
    public static function getIncomePayType($index = '')
    {
        $model = ['1' => '线上付款', '2' => '线下付款'];
        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    /**
     * 2016-12-16
     * 获取房屋状态
     */
    public static function houseStatus($index = 0)
    {
        $model = ['1' => '已售', '2' => '未售'];

        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    //交易类型
    public static function getTradeType($index = '')
    {
        $model = ['1' => '收款', '2' => '退款'];
        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }
}
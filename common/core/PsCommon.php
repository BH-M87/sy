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
        ["key" => "2021", "name" => "2021"],
        ["key" => "2022", "name" => "2022"],
        ["key" => "2023", "name" => "2023"],
        ["key" => "2024", "name" => "2024"],
        ["key" => "2025", "name" => "2025"],
        ["key" => "2026", "name" => "2026"],
        ["key" => "2027", "name" => "2027"],
        ["key" => "2028", "name" => "2028"],
        ["key" => "2029", "name" => "2029"],
        ["key" => "2030", "name" => "2030"],
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
    public static $sharedType = [
        '1' => '电梯用电',
        '2' => '楼道用电',
        '3' => '整体用水用电'
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
        if (YII_ENV != 'prod') {//本地/测试环境 postman测试的origin=chrome-extension://xxxx，需要排除掉
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
        return true;
        if (YII_ENV != 'prod') {//本地/测试环境 测试人员绕开验签
            if (Yii::$app->request->getHeaders()->get('Black-Hole') == 'zhujia360') {
                return true;
            }
        }
        $data['rand'] = Yii::$app->request->getHeaders()->get('Zj-Custom-Rand');//1000～9999随机四位数字
        $data['token'] = F::request('token',null);//token
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
    public static function responseSuccess($data = [],$object = true)
    {
        if (self::$log) {
            $log['action'] = Yii::$app->controller->action->getUniqueId();
            $log['response'] = $data;
            $log['data'] = F::request();
            Yii::info(json_encode($log, 320), 'api-success');
        }
        if (empty($data) && $object) {
            $reData = (object)$data;
        } else {
            $reData = $data;
        }
        return self::ajaxReturn(1, $reData, ['errorMsg' => '']);
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
            Yii::info(json_encode($log, 320), 'api');
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
            'message' => !empty($error['errorMsg'])?$error['errorMsg']:'',
            'error' => $error,
        ], JSON_UNESCAPED_UNICODE);
        Yii::$app->response->send();
        exit();
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

    public static function getKeyValue($key,$data)
    {
        return ['key'=>$key,'value'=>$data[$key]];
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

    // 系统公用订单费用类型
    public static function getPayTypes($index = '')
    {
        $m = [1 => '物业管理费', 2 => '水费', 3 => '电费', 4 => '公摊水电费', 5 => '其他费用', 6 => '房租费', 7 => '燃气费', 8 => '能耗费', 9 => '车位费', 10 => '报事报修', 11 => '临时停车', 12 => '公维金'];
        if ($index) {
            return $m[$index];
        } else {
            return $m;
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

    /**
     * 2016-12-16
     * 获取物业类型
     */
    public static function propertyType($index = 0)
    {
        $model = ['1' => '居住物业', '2' => '商业物业','3'=>'工业物业'];

        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }


    /**
     * 获取小区类型
     */
    public static function getCommType($index = '')
    {
        $model = ['1' => '物业', '2' => '新房'];

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

    /**
     * 获取小区归属
     */
    public static function getHouseType($index = '')
    {
        $model = ['1' => '老旧小区', '2' => '商品房', '3'=>'安置小区'];
        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    /**
     * api调用成功
     * @author shenyang
     */
    public static function responseAppSuccess($data = [])
    {
        if (empty($data)) {
            return self::ajaxReturn(20000, (object)$data, ['errorMsg' => '']);
        } else {
            return self::ajaxReturn(20000, $data, ['errorMsg' => '']);
        }
    }

    /**
     * api调用失败
     * @author shenyang
     */
    public static function responseAppFailed($msg, $code = 50001)
    {
        //调用失败，记录日志，便于调试查找bug
        $log['action'] = Yii::$app->controller->action->getUniqueId();
        $log['msg'] = $msg;
        $log['data'] = F::request();
        Yii::info(json_encode($log, 320), 'api');
        return self::ajaxReturn($code, (object)[], ['errorMsg' => $msg]);
    }

    public static function isCarLicense($license)
    {
        if (empty($license)) {
            return false;
        }
        /*匹配民用车牌和使馆车牌
        　判断标准
        　1，第一位为汉字省份缩写
        　2，第二位为大写字母城市编码
        　3，后面是5位仅含字母和数字的组合
        */
        $regular = "/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新使]{1}[A-Z]{1}[0-9a-zA-Z]{5}$/u";
        preg_match($regular, $license, $match);
        if (isset($match[0])) {
            return true;
        }
        /*匹配特种车牌(挂,警,学,领,港,澳)
        　　参考 https://wenku.baidu.com/view/4573909a964bcf84b9d57bc5.html
        */
        $regular = '/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新]{1}[A-Z]{1}[0-9a-zA-Z]{4}[挂警学领港澳]{1}$/u';
        preg_match($regular, $license, $match);
        if (isset($match[0])) {
            return true;
        }

        /* #匹配武警车牌
        　　#参考 https://wenku.baidu.com/view/7fe0b333aaea998fcc220e48.html
        */
        $regular = '/^WJ[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新]?[0-9a-zA-Z]{5}$/ui';
        preg_match($regular, $license, $match);
        if (isset($match[0])) {
            return true;
        }

        /*　#匹配军牌
        　　#参考 http://auto.sina.com.cn/service/2013-05-03/18111149551.shtml
        */
        $regular = "/[A-Z]{2}[0-9]{5}$/";
        preg_match($regular, $license, $match);
        if (isset($match[0])) {
            return true;
        }

        /* #匹配新能源车辆6位车牌
        　　#参考 https://baike.baidu.com/item/%E6%96%B0%E8%83%BD%E6%BA%90%E6%B1%BD%E8%BD%A6%E4%B8%93%E7%94%A8%E5%8F%B7%E7%89%8C
        */
        //小型新能源车
        $regular = "/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新]{1}[A-Z]{1}[DF]{1}[0-9a-zA-Z]{5}$/u";
        preg_match($regular, $license, $match);
        if (isset($match[0])) {
            return true;
        }
        //大型新能源车
        $regular = "/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新]{1}[A-Z]{1}[0-9a-zA-Z]{5}[DF]{1}$/u";
        preg_match($regular, $license, $match);
        if (isset($match[0])) {
            return true;
        }
        return false;
    }

    /*
     * 获取业主身份标识
     * */
    public static function getIdentityType($index = '', $type = '')
    {
        $model = ['1' => '业主', '2' => '家人', '3' => '租客','4'=>'访客'];
        $result = '';
        switch ($type) {
            case "key" :
                $result = $model[$index];
                break;
            case "value" :
                foreach ($model as $key => $val) {
                    if ($val == $index) {
                        $result = $key;
                        break;
                    }
                }
                break;
            default:
                $result = $model;
        }
        return $result;
    }

    /*
    * 获取业主认证状态
    * */
    public static function getIdentityStatus($index = '')
    {
        $model = ['1' => '未认证', '2' => '已认证', '3' => '迁出', '4' => '迁出'];
        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    /**
     * 生成随机手机号
     * @return string
     */
    public static function generateVirtualPhone()
    {
        $randStr = '120';
        $incr = Yii::$app->redis->incr('lyl:virtual-phone');//自增数字
        $randStr .= sprintf("%08d", $incr);
        return $randStr;
    }

    /**
     * 判断用户手机号是否为虚拟手机号
     * @param $str
     * @return bool
     */
    public static function isVirtualPhone($str)
    {
        if (strpos($str, '120') === 0) {
            return true;
        }
        return false;
    }

    /**
     * 计算两个时间的时间差
     * @author yjh
     * @param $begin_time
     * @param $end_time
     * @return string
     */
    public static function timediff($begin_time,$end_time)
    {
        if($begin_time < $end_time){
            $starttime = $begin_time;
            $endtime = $end_time;
        } else {
            $starttime = $end_time;
            $endtime = $begin_time;
        }
        //计算天数
        $timediff = $endtime-$starttime;
        $days = intval($timediff/86400);
        //计算小时数
        $remain = $timediff%86400;
        $hours = intval($remain/3600);
        //计算分钟数
        $remain = $remain%3600;
        $mins = intval($remain/60);
        //计算秒数
        $secs = $remain%60;
        $timeStr = '';
        $res = array("天" => $days, "小时" => $hours, "分" => $mins);
        foreach ($res as $k => $v) {
            $timeStr .= $v > 0 ? $v.$k : '';
        }
        return $timeStr;
    }

    /*
     * 获取性别
     */
    public static function getFlipKey($data, $index, $default = 0)
    {
        $flip = array_flip($data);
        return isset($flip[$index]) ? $flip[$index] : $default;
    }

    //隐藏手机号(隐藏中间四位)
    public static function hideMobile($mobile)
    {
        return $mobile ? substr_replace($mobile, '****', 3, 4) : '';
    }

    //隐藏姓名
    public static function hideName($name){
        $len = mb_strlen($name);
        if($len <= 2){
            $newName = mb_substr($name,0,1)."*";
        }else{
            $str = '';
            for($i=0;$i<$len-2;$i++){
                $str.="*";
            }
            $newName = mb_substr($name,0,1).$str.mb_substr($name,-1);
        }
        return $newName;
    }

    /**
     * 给集合增加元素
     * @param $cacheKey
     * @param $str
     * @return mixed
     */
    public static function addNoRepeatChar($cacheKey, $str)
    {
        return Yii::$app->redis->sadd($cacheKey, $str);
    }
}

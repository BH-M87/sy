<?php

namespace common\core;

use app\models\PsCommunityRoominfo;
use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;

/**
 * 2016-12-16
 * 公共使用类
 */
class PsCommon
{
    public static $costType = [
        'park' => '临时停车',
        'repair' => '报事报修'
    ];
    //============================================公摊项目3.6需求==============================================
    public static $sharedType = [
        '1' => '电梯用电',
        '2' => '楼道用电',
        '3' => '整体用水用电'
    ];
    public static $periodStatus = [
        '1' => '未发布账单',
        '2' => '已发布账单'
    ];
    //============================================账期3.5需求==============================================
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

    //投诉建议状态
    public static $complaintStatus = [
        '1' => '待处理',
        '2' => '已取消',
        '3' => '已处理'
    ];

    //访客状态
    public static $visitStatus = [
        '1' => '未到访',
        '2' => '已到访',
        '3' => '已过期'
    ];

    /**
     * 2017-02-27
     * 获取广告位从属页面
     */
    public static function getAdvertPositionPage($index = 0)
    {
        $page[] = ['key' => 1, 'value' => '首页'];
        $page[] = ['key' => 2, 'value' => '品质装修'];
        $page[] = ['key' => 3, 'value' => '我要卖房'];
        $page[] = ['key' => 4, 'value' => '我要租房'];
        $page[] = ['key' => 5, 'value' => '生活号首页'];
        if ($index) {
            return !empty($page[$index - 1]) ? $page[$index - 1] : [];
        }
        return $page;
    }

    /**
     * 2017-02-27
     * 获取广告配置范围
     */
    public static function getAdvertType($index = 0)
    {
        $type[] = ['key' => 1, 'value' => '新房全局'];
        $type[] = ['key' => 2, 'value' => '新房区域'];
        $type[] = ['key' => 3, 'value' => '物业全局'];
        $type[] = ['key' => 4, 'value' => '物业区域'];
        if ($index) {
            return !empty($type[$index - 1]) ? $type[$index - 1] : [];
        }
        return $type;
    }

    /**
     * 2017-02-27
     * 获取广告状态
     */
    public static function getAdvertStatus($index = 0)
    {
        $status[] = ['key' => 1, 'value' => '显示'];
        $status[] = ['key' => 2, 'value' => '隐藏'];
        if ($index) {
            return !empty($status[$index - 1]) ? $status[$index - 1] : [];
        }
        return $status;
    }

    /**
     * 2017-02-27
     * 获取广告位状态
     */
    public static function getAdvertPositionStatus($index = 0)
    {
        $status[] = ['key' => 1, 'value' => '上线'];
        $status[] = ['key' => 2, 'value' => '下线'];
        if ($index) {
            return !empty($status[$index - 1]) ? $status[$index - 1] : [];
        }
        return $status;
    }

    /**
     * 2017-02-27
     * 获取广告位类型
     */
    public static function getAdvertPositionType($index = 0)
    {

        $type[] = ['key' => 1, 'value' => '单张'];
        $type[] = ['key' => 2, 'value' => '轮播'];

        return $type;
    }

    /**
     * 获取公告类型
     * @return array
     */
    public static function getNoticeType($index = null)
    {
        $type = [
            ['key' => "1", 'value' => '通知'],
            ['key' => "2", 'value' => '新闻'],
//            ['key'   => "3",'value' => '阳光公告']
        ];
        if ($index) {
            return $type[$index - 1]['value'];
        } else {
            return $type;
        }
    }

    public static function getNoticeTypeLabel($key)
    {
        $noticeTypes = self::getNoticeType();
        foreach ($noticeTypes as $type) {
            if ($key == $type['key']) {
                return $type['value'];
            }
        }
        return '';
    }

    /**
     * 2016-12-16
     * 获取物业类型
     */
    public static function propertyType($index = 0)
    {
        $model = ['1' => '住宅', '2' => '商用'];

        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    /**
     * 查询所有有房屋的小区
     * @return array
     */
    public static function getHasHouseCommunity()
    {
        $psCommunityRoomInfo = PsCommunityRoominfo::find()
            ->select(['community_id'])
            ->groupBy(['community_id'])
            ->asArray()
            ->column();
        return $psCommunityRoomInfo;
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
     * 获取状态
     */
    public static function getStatus($index = 0)
    {
        $model = ['1' => '启用', '2' => '禁用'];

        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    /**
     * 2017-3-4
     * 获取状态 1 空闲 2已售 3 已租
     */
    public static function getParkStatus($index = 0, $type = 'key')
    {
        $model = ['1' => '空闲', '2' => '已售', "3" => "已租"];
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

    //老版本的账单状态
    public static function getBillStatus($index = '')
    {
        $model = ['1' => '未缴费', '2' => '线上已缴', '3' => '未出账单', '4' => '发布失败', '5' => "线下已缴"];
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

    public static function convertUrlQuery($query)
    {
        $queryParts = explode('&', $query);
        $params = array();

        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }

    public static function getUrlQuery($array_query)
    {

        $tmp = array();
        foreach ($array_query as $k => $param) {
            $tmp[] = $k . '=' . $param;
        }
        $params = implode('&', $tmp);
        return $params;
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
     * 通用返回格式，私有方法，不对外提供调用，所有接口对外输出格式化出口，请勿随意修改！！
     * 控制器返回需要调用，responseSuccess, response, responseFailed等
     * @author shenyang
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

    public static function getPayType($index = '')
    {
        $model = ['1' => '一次付清', '2' => '分次付清'];
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
     * 获取材料单位
     */
    public static function getMaterialUnit($index = '')
    {
        $model = ['1' => '/米', '2' => '/卷', '3' => '/个', '4' => '/次'];
        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    /*
     * 获取公告的发送渠道
     * */
    public static function getNoticeSendType($index = '')
    {
        $model = ['1' => "全部", '2' => '业主', '3' => '内部员工'];
        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    /**
     * 获取性别
     */
    public static function getFlipKey($data, $index, $default = 0)
    {
        $flip = array_flip($data);
        return isset($flip[$index]) ? $flip[$index] : $default;
    }

    /*
     * 获取报事报修来源渠道
     * */
    public static function getRepairFrom($index = '')
    {
        $model = ['1' => "生活号报修", '2' => '物业后台报修', '3' => '邻易联app报修'];
        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    /**
     * 原ApiResponse类返回成功
     */
    public static function webappSuccess($data = [])
    {
        $response = [
            'code' => 20000,
            'data' => $data,
            'errorMsg' => '',
        ];
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 原ApiResponse类返回失败
     */
    public static function webappFailed($msg = '', $code = 50001)
    {
        $response = [
            'code' => $code,
            'data' => [],
            'errorMsg' => $msg,
        ];
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    public static function response($result)
    {
        if (!$result['code']) {
            return self::responseFailed($result['msg']);
        }
        return self::responseSuccess($result['data']);
    }

    /**
     * api调用成功
     * @author shenyang
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
        return self::ajaxReturn(20000, $data, ['errorMsg' => '']);
    }

    /**
     * api调用失败
     * @author shenyang
     * @param string $msg
     * @param int $code
     */
    public static function responseFailed($msg = '系统错误', $code = 50001)
    {
        //调用失败，记录日志，便于调试查找bug
        $log['action'] = Yii::$app->controller->action->getUniqueId();
        $log['msg'] = $msg;
        $log['data'] = F::request();
        Yii::info(json_encode($log, 320), 'api-failed');
        return self::ajaxReturn($code, [], ['errorMsg' => $msg]);
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

    /**
     * 简易isset判断
     * @param $data
     * @param $key
     * @param string $default
     * @return mixed
     */
    public static function get($data, $key, $default = '')
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }

    public static function getTodaytimezone()
    {
        $y = date("Y");
        $m = date("m");
        $d = date("d");

        $todayStartTime = mktime(0, 0, 0, $m, $d, $y);
        $todayEndTime = mktime(23, 59, 59, $m, $d, $y);

        return [
            'start' => $todayStartTime,
            'end' => $todayEndTime
        ];
    }


    //隐藏手机号(隐藏中间四位)
    public static function hideMobile($mobile)
    {
        return $mobile ? substr_replace($mobile, '****', 3, 4) : '';
    }

    /**
     * 拆分二维数组
     * @param $originArr 原始数组
     * @param $sigleNum 每个二维数组个数
     * @return array
     */
    public static function divideArr($originArr, $sigleNum)
    {
        $arr = [];
        $arrKey = 0;

        foreach ($originArr as $k => $item) {
            if (($k + 1) % $sigleNum == 0) {
                $arr[$arrKey][] = $item;
                $arrKey++;
            } else {
                $arr[$arrKey][] = $item;
            }
        }
        return $arr;
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
     * 给集合增加元素
     * @param $cacheKey
     * @param $str
     * @return mixed
     */
    public static function addNoRepeatChar($cacheKey, $str)
    {
        return Yii::$app->redis->sadd($cacheKey, $str);
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
     * 三个后台(物业+运营+街道办)签名验证基本算法
     * @param $data
     * @param $systemType
     * @param $sign
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
        $secrets = [
            1 => 'YHi$rW8N',
            2 => 'HU6%12(w',
            3 => '5H!@Odk3',
            4 => '5H!@Odk3',
        ];
        if (empty($secrets[$systemType])) {
            return '无法识别的系统类型';
        }
        ksort($data);//按照key升序排列
        $rightSign = md5(md5(json_encode($data, 320)) . $secrets[$systemType]);
        if ($rightSign != $sign) {
            //记录验签失败日志
            $data['rightSign'] = $rightSign;
            Yii::info(json_encode($data, 320), 'api');
            return "签名验证失败";
        }
        return true;
    }

    //记录日志返回日志
    public static function beginLog($bool)
    {
        self::$log = $bool;
    }

    /**
     * 日志处理报错
     * @author yjh
     * @param $name
     * @param $error
     * @return void
     */
    public static function writeLog($name, $error)
    {
        $file_name = $name . date("Ymd") . '.txt';
        $savePath = Yii::$app->basePath . '/runtime/'.$name.'/';
        if (!file_exists($savePath)) {
            FileHelper::createDirectory($savePath, 0777, true);
        }
        if (file_exists($savePath . $file_name)) {
            file_put_contents($savePath . $file_name, "\r\n", FILE_APPEND);
            file_put_contents($savePath . $file_name, $error, FILE_APPEND);
        } else {
            file_put_contents($savePath . $file_name, $error);
        }
    }

    /**
     * 计算两个时间的时间差
     * @param $begin_time 开始时间
     * @param $end_time 结束时间
     * @return array
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
}

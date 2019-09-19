<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * 墨迹天气相关接口
 * Date: 2019/3/28
 * Time: 10:04
 */

namespace service\small;

use common\core\Curl;
use common\core\PsCommon;
use service\BaseService;

class MojiService extends BaseService
{
    const CURRENT_WEATHER = 1;
    const CLOTHES_SUGGEST = 2;
    const CAR_LIMIT = 3;

    public $appCode = '5d2bf457ac994d719fe5b877515cc00b';
    public $apis = [
        self::CURRENT_WEATHER => [
            'name' => '天气实况',
            'token' => 'ff826c205f8f4a59701e64e9e64e01c4',
            'url' => 'http://aliv8.data.moji.com/whapi/json/aliweather/condition'
        ],
        self::CLOTHES_SUGGEST => [
            'name' => '生活指数',
            'token' => '42b0c7e2e8d00d6e80d92797fe5360fd',
            'url' => 'http://aliv8.data.moji.com/whapi/json/aliweather/index'
        ],
        self::CAR_LIMIT => [
            'name' => '限行数据',
            'token' => 'c712899b393c7b262dd7984f6eb52657',
            'url' => 'http://aliv8.data.moji.com/whapi/json/aliweather/limit'
        ],
    ];

    //调用墨迹接口
    private function request($apiType, $data)
    {
        $api = PsCommon::get($this->apis, $apiType);
        if (!$api) {
            return [];
        }
        $headers = [
            'Authorization:APPCODE ' . $this->appCode,
            'Content-Type: aplication/x-www-form-urlencoded; charset=UTF-8',
        ];
        $data['token'] = $api['token'];

        $response = Curl::getInstance(['CURLOPT_HTTPHEADER' => $headers])->post($api['url'], http_build_query($data));
        //var_dump($response);
        if (Curl::getHttpCode() == '403') {//接口请求超量
            \Yii::info($api['name'].'response1:'.$response,'api-failed');
            \Yii::info($api['name'].'接口请求超量，时间'.date("Y-m-d H:i:s"),'api-failed');
            return [];
        }
        $result = json_decode($response, true);
        if (!$result) {
            \Yii::info($api['name'].'response2:'.$response,'api-failed');
            \Yii::info($api['name'].'接口调用出错，时间'.date("Y-m-d H:i:s"),'api-failed');
            return [];
        }
        if ($result['code']) {
            \Yii::info($api['name'].'response3:'.$response,'api-failed');
            \Yii::info($api['name'].'接口调用出错2:'.$result['msg'],'api-failed');
            return [];
        }
        return $result['data'];
    }


    //获取缓存天气信息
    public function getWeather($community_id,$lon,$lat,$type = 1)
    {
        if(empty($community_id)){
            return [];
        }
        $w = \Yii::$app->redis->get('weather_'.$type.'_'.$community_id);
        if($w){
            $weather = json_decode($w,true);
            if($weather['time'] > time()){
                return $weather['data'];
            }else{
                $data = $this->getMojiWeather($lon,$lat);
                $weather['time'] = time() + 7200;
                $weather['data'] = $data;
                \Yii::$app->redis->set('weather_'.$type.'_'.$community_id,json_encode($weather));
                return $data;
            }
        }else{
            $data = $this->getMojiWeather($lon,$lat);
            $weather['time'] = time() + 7200;
            $weather['data'] = $data;
            \Yii::$app->redis->set('weather_'.$type.'_'.$community_id,json_encode($weather));
            return $data;
        }
    }

    //获取墨迹天气信息
    public function getMojiWeather($lon,$lat)
    {

        $data = ['lat' => $lat, 'lon' => $lon];
        $weather = $this->request(self::CURRENT_WEATHER, $data);
        if (empty($weather['city']) || empty($weather['condition'])) {
            return [];
        }
        return [
            //'city' => $weather['city']['name'],
            'city' => $weather['city']['name'],//修改，根据小区id去判断
            'condition' => $weather['condition']['condition'],//晴
            'conditionId'=>$weather['condition']['conditionId'],//天气对应的Id
            'icon' => 'https://zhihuirenju.zje.com/weather/' . $weather['condition']['icon'] . '.png',//天气图标
            'temp' => $weather['condition']['temp'],//温度
            'windDir' => $weather['condition']['windDir'],//风
            'windLevel' => $weather['condition']['windLevel'],//风速
        ];
    }

    public function getSuggest($community_id,$lon,$lat,$type =1)
    {
        if(empty($community_id)){
            return [];
        }
        $w = \Yii::$app->redis->get('suggest_'.$type.'_'.$community_id);
        if($w){
            $weather = json_decode($w,true);
            if($weather['time'] > time()){
                return $weather['data'];
            }else{
                $data = $this->getMojiSuggest($lon,$lat);
                $weather['time'] = time() + 7200;
                $weather['data'] = $data;
                \Yii::$app->redis->set('suggest_'.$type.'_'.$community_id,json_encode($weather));
                return $data;
            }
        }else{
            $data = $this->getMojiSuggest($lon,$lat);
            $weather['time'] = time() + 7200;
            $weather['data'] = $data;
            \Yii::$app->redis->set('suggest_'.$type.'_'.$community_id,json_encode($weather));
            return $data;
        }
    }

    public function getMojiSuggest($lon,$lat)
    {
        $data = ['lat' => $lat, 'lon' => $lon];
        $suggest = $this->request(self::CLOTHES_SUGGEST, $data);
        $result = [];
        if(!empty($suggest)){
            foreach ($suggest['liveIndex'] as $day => $lives) {
                foreach ($lives as $live) {
                    if ($live['name'] == '穿衣指数') {
                        $result['clothes'] = $live['desc'];
                    } elseif ($live['name'] == '运动指数') {
                        $result['sport'] = $live['desc'];
                    } elseif ($live['name'] == '洗车指数') {
                        $result['car'] = $live['desc'];
                    }elseif ($live['name'] == '空气污染扩散指数') {
                        $result['air'] = $live['desc'];
                    }
                }
            }
        }
        return $result;
    }

    public function getLimit($community_id,$lon,$lat,$type =1)
    {
        if(empty($community_id)){
            return [];
        }
        $w = \Yii::$app->redis->get('limit_'.$type.'_'.$community_id);
        if($w){
            $weather = json_decode($w,true);
            if($weather['time'] > time()){
                return $weather['data'];
            }else{
                $data = $this->getMojiLimit($lon,$lat);
                $weather['time'] = time() + 7200;
                $weather['data'] = $data;
                \Yii::$app->redis->set('limit_'.$type.'_'.$community_id,json_encode($weather));
                return $data;
            }
        }else{
            $data = $this->getMojiLimit($lon,$lat);
            $weather['time'] = time() + 7200;
            $weather['data'] = $data;
            \Yii::$app->redis->set('limit_'.$type.'_'.$community_id,json_encode($weather));
            return $data;
        }
    }

    public function getMojiLimit($lon,$lat)
    {

        $data = ['lat' => $lat, 'lon' => $lon];
        $carLimit = $this->request(self::CAR_LIMIT, $data);
        $today = date('Y-m-d');
        $prompt = "";
        if (!empty($carLimit['limit'])) {
            foreach ($carLimit['limit'] as $v) {
                if ($v['date'] == $today) {
                    $prompt = $v['prompt'];
                }
            }
        }
        if ($prompt == 'w' || !$prompt) {
            //return '无限行信息';
            return [];
        }
        $promptArr = str_split($prompt);
        //return "今日限行尾号" . implode('和', $promptArr);
        return $promptArr;
    }
}
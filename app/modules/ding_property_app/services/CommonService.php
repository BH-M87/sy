<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/3/25
 * Time: 14:32
 */
namespace app\modules\ding_property_app\services;

use common\core\Client;
use common\core\PsCommon;
use service\BaseService;
use Yii;

class CommonService extends BaseService{

    //根据高德经纬度 获得详情
    public function getGeoInfo($params){

        if(empty($params['latitude'])){
            return PsCommon::responseFailed("纬度不能为空");
        }
        if(empty($params['longitude'])){
            return PsCommon::responseFailed("经度不能为空");
        }
        $location = $params['longitude'].','.$params['latitude'];
        $client = new Client();
        $source_url = 'http://restapi.amap.com/v3/geocode/regeo';
        $geoParams = [
            'location' => $location,
            'output' => 'JSON',
            'key' => Yii::$app->params['gaode_key']
        ];

        $response = $client->fetch($source_url, $geoParams, "GET");
        $result = json_decode($response, true);
        $data = [];
        if($result['status']==1){
            $data = $result['regeocode'];
        }
        return $data;
    }
}
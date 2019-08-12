<?php
/**
 * 帮助函数
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/5/21
 * Time: 19:31
 */

namespace common\core;

class Helpers
{
    /**
     * @api 获取经纬度范围内的经纬度
     * @param  $latitude    纬度
     * @param  $longitude    经度
     * @param  $raidus        半径范围(单位：米)
     * @return multitype:number
     */
    public static function getAround($latitude, $longitude, $raidus)
    {
        $PI = 3.14159265;
        $degree = (24901 * 1609) / 360.0;
        $dpmLat = 1 / $degree;
        $radiusLat = $dpmLat * $raidus;
        $minLat = $latitude - $radiusLat;
        $maxLat = $latitude + $radiusLat;
        $mpdLng = $degree * cos($latitude * ($PI / 180));
        $dpmLng = 1 / $mpdLng;
        $radiusLng = $dpmLng * $raidus;
        $minLng = $longitude - $radiusLng;
        $maxLng = $longitude + $radiusLng;
        return ['minLat' => round($minLat, 6), 'maxLat' => round($maxLat, 6), 'minLng' => round($minLng, 6), 'maxLng' => round($maxLng, 6)];
    }

    /**
     * @desc 根据两点间的经纬度计算距离
     * @param float $lat 纬度值
     * @param float $lng 经度值
     * @return 米
     */
    public static function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6367000;
        $lat1 = ($lat1 * pi()) / 180;
        $lng1 = ($lng1 * pi()) / 180;
        $lat2 = ($lat2 * pi()) / 180;
        $lng2 = ($lng2 * pi()) / 180;
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;
        return round($calculatedDistance);
    }
}
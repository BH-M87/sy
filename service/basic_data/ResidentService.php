<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/20
 * Time: 16:27
 */

namespace service\basic_data;

use Yii;

class ResidentService extends BaseService
{
    public static function getCommunityConfig($community_id)
    {
        $config = Yii::$app->db->createCommand("SELECT * FROM ps_community_config where community_id = :community_id")->bindValue(':community_id', $community_id)->queryOne();
        return !empty($config) ? $config['is_family'] : 1;
    }
}
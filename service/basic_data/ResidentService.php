<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/20
 * Time: 16:27
 */

namespace service\basic_data;

use app\models\PsResidentAudit;
use app\models\PsRoomUser;
use Yii;

class ResidentService extends BaseService
{
    public static function getCommunityConfig($community_id)
    {
        $config = Yii::$app->db->createCommand("SELECT * FROM ps_community_config where community_id = :community_id")->bindValue(':community_id', $community_id)->queryOne();
        return !empty($config) ? $config['is_family'] : 1;
    }

    //当前小区下是否有已认证的房屋
    public function isAuthByNameMobile($communityId, $name, $mobile)
    {
        //5.1 edit by wenchao.feng 认证状态不区分小区，只有有认证过的房屋，默认为认证状态
        $memberModel = PsRoomUser::find()
            ->where(['name' => $name,'mobile' => $mobile, 'status' => PsRoomUser::AUTH])
            ->asArray()
            ->one();
        //$member = PsMember::find()->where(['name' => $name,'mobile'=>$mobile])->asArray()->one();
        //有一套房认证，或者业主是实名认证的
        //if ($memberModel || $member['is_real']==1) {
        if ($memberModel) {
            return true;
        }

        return PsRoomUser::find()
                ->where([
                    //'community_id' => $communityId,
                    'name' => $name,
                    'mobile' => $mobile,
                    'status' => PsRoomUser::AUTH,
                ])->andWhere(['OR', ['time_end' => 0], ['<=', 'time_end', time()]])
                ->exists() || PsResidentAudit::find()->where([
                'community_id' => $communityId,
                'name' => $name,
                'mobile' => $mobile,
                'status' => PsResidentAudit::AUDIT_WAIT,
            ])->exists();
    }
}
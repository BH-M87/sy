<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/10/31
 * Time: 14:06
 */

namespace service\street;
use app\models\PsMember;
use app\models\PsRoomUser;
use app\models\StLabelsRela;


class PersonDataService extends BaseService
{

    public function getList($params, $page, $rows)
    {
        $reData['list'] = [];
        $reData['totals'] = 0;
        //街道社区小区搜索条件处理
        $searchCommunityIds = [];
        //查询登录账号的小区列表
        $communityIds = UserService::service()->getCommunityList($params['organization_type'], $params['organization_id']);
        if ($params['community_code']) {
            $searchCommunityIds[0] = BasicDataService::service()->getCommunityIdByCommunityCode($params['community_code']);
            $communityIds = array_intersect($communityIds, $searchCommunityIds);
        } elseif (empty($params['community_code']) && empty($params['street_code']) && $params['district_code']) {
            $searchCommunityIds = BasicDataService::service()->getCommunityIdByDistrictCode($params['district_code']);
            $communityIds = array_intersect($communityIds, $searchCommunityIds);
        } elseif (empty($params['community_code']) && empty($params['district_code']) && $params['street_code']) {
            $searchCommunityIds = BasicDataService::service()->getCommunityIdByStreetCode($params['street_code']);
            $communityIds = array_intersect($communityIds, $searchCommunityIds);
        }
        if (empty($communityIds)) {
            return $reData;
        }

        //标签搜索条件处理
        $labels = [];
        if (array_search(-1,$params['label_id']) !== false) {
            //查询日常画像所有标签
            $labels = BasicDataService::service()->getLabelByType(1, 2, $params['street_code']);
        } elseif (array_search(-2,$params['label_id']) !== false) {
            //查询重点关注所有标签
            $labels = BasicDataService::service()->getLabelByType(2, 2, $params['street_code']);
        } elseif (array_search(-3,$params['label_id']) !== false) {
            //查询关怀对象所有标签
            $labels = BasicDataService::service()->getLabelByType(3, 2, $params['street_code']);
        }
        $params['label_id'] = array_merge($params['label_id'], $labels);
        $memberIds = [];
        if ($params['label_id']) {
            $memberIds = StLabelsRela::find()
                ->select('data_id')
                ->where(['labels_id' => $params['label_id']])
                ->andWhere(['data_type' => 2])
                ->asArray()
                ->column();
        }
        $query = PsRoomUser::find()
            ->alias('u')
            ->leftJoin('ps_member m', 'm.id = u.member_id')
            ->groupBy('u.member_id');
        $query->where("1=1");
        if ($communityIds) {
            $query->andWhere(['u.community_id' => $communityIds]);
        }
        if ($memberIds) {
            $query->andWhere(['u.member_id' => $memberIds]);
        }
        $reData['totals'] = $query->select('m.id')->count();
        $reData['list'] = $query->select('m.id,m.mobile,u.card_no,m.name,u.group,u.building,u.unit,u.room,m.face_url')
            ->offset((($page - 1) * $rows))
            ->limit($rows)
            ->orderBy('u.id desc')
            ->asArray()
            ->all();
        return $reData;
    }
}
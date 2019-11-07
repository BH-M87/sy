<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/10/31
 * Time: 14:06
 */

namespace service\street;
use app\models\DoorRecord;
use app\models\PsMember;
use app\models\PsRoomUser;
use app\models\StLabelsRela;
use common\core\F;
use service\door\DoorRecordService;


class PersonDataService extends BaseService
{
    //人员
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

        $query = PsMember::find()
            ->alias('m')
            ->leftJoin('ps_room_user u', 'u.member_id = m.id')
            ->leftJoin('ps_community c', 'c.id = u.community_id');
        $query->where("1=1");
        if ($communityIds) {
            $query->andWhere(['u.community_id' => $communityIds]);
        }
        if ($memberIds) {
            $query->andWhere(['u.member_id' => $memberIds]);
        }
        if ($params['member_name']) {
            $query->andWhere(['or', ['like', 'm.name', $params['member_name']], ['like','m.mobile',$params['member_name']]]);
        }
        if ($params['card_no']) {
            $query->andWhere(['like','u.card_no',$params['card_no']]);
        }

        $reData['totals'] = $query->select('m.id')->count();
        $list = $query->select('m.id,m.mobile,u.card_no,m.name as member_name,c.name as community_name, u.group,u.building,u.unit,u.room,m.face_url')
            ->offset((($page - 1) * $rows))
            ->limit($rows)
            ->orderBy('u.id desc')
            ->asArray()
            ->all();
        foreach ($list as $key => $val) {
            $list[$key]['mobile'] = F::processMobile($val['mobile']);
            $list[$key]['card_no'] = F::processIdCard($val['card_no']);
            $list[$key]['address'] = $val['community_name'].$val['group'].$val['building'].$val['unit'].$val['room'];

            //查询所有标签
            $list[$key]['label'] = $this->getMemberLabels($val['id']);

        }
        $reData['list'] = $list;
        return $reData;
    }

    private function getMemberLabels($memberId, $steetCode = null)
    {
         $query = StLabelsRela::find()
            ->select('l.id, l.name, l.label_type')
            ->alias('lr')
            ->leftJoin('st_labels l','l.id = lr.labels_id')
            ->where(['lr.data_id' => $memberId, 'lr.data_type' => 2]);
         if ($steetCode) {
             $query->andWhere(['lr.organization_id' => $steetCode]);
         }

         return $query->asArray()
            ->all();
    }

    //详情
    public function view($params)
    {
        //查询基础信息
        $userInfo = PsRoomUser::find()
            ->alias('u')
            ->select('u.member_id as id,m.name as member_name,m.face_url,u.card_no,u.sex,
            u.identity_type,u.mobile,u.time_end as expired_time,u.enter_time,u.nation,u.reason as enter_reason,
            u.face,u.work_address as company,u.marry_status,u.household_type,u.household_address,u.`group`,
            u.building,u.unit,u.room,u.qq,u.wechat,u.email,u.telephone,u.emergency_contact,u.emergency_mobile,
            com.name as community_name,n.name as nation_name
            ')
            ->leftJoin('ps_member m', 'm.id = u.member_id')
            ->leftJoin('ps_community com','com.id = u.community_id')
            ->leftJoin('ps_nation n','n.id = u.nation')
            ->where(['u.member_id' => $params['id']])
            ->orderBy('u.id desc')
            ->limit(1)
            ->asArray()
            ->one();
        if ($userInfo) {
            $userInfo['card_no'] = $userInfo['card_no'] ? F::processIdCard($userInfo['card_no']) : '';
            $userInfo['identity'] = [
                'id' => $userInfo['identity_type'],
                'name' => PsRoomUser::$identity_type[$userInfo['identity_type']]
            ];
            $userInfo['expired_time'] = $userInfo['expired_time'] ? date("Y-m-d", $userInfo['expired_time']) : '长期';
            $userInfo['enter_time'] = $userInfo['enter_time'] ? date("Y-m-d", $userInfo['enter_time']) : '';

            $userInfo['nation'] = [
                'id' => !empty($userInfo['nation']) ? $userInfo['nation'] : '',
                'name' => $userInfo['nation_name'] ? $userInfo['nation_name'] : ''
            ];
            $userInfo['face'] = [
                'id' => !empty($userInfo['face']) ? $userInfo['face'] : '',
                'name' => $userInfo['face'] ? PsRoomUser::$face_desc[$userInfo['face']] : ''
            ];
            $userInfo['marry_status'] = [
                'id' => !empty($userInfo['marry_status']) ? $userInfo['marry_status'] : '',
                'name' => $userInfo['marry_status'] ? PsRoomUser::$marry_status_desc[$userInfo['marry_status']] : ''
            ];
            $userInfo['household_type'] = [
                'id' => !empty($userInfo['household_type']) ? $userInfo['household_type'] : '',
                'name' => $userInfo['household_type'] ? PsRoomUser::$household_type_desc[$userInfo['household_type']] : ''
            ];
            $userInfo['household_address'] = $userInfo['household_address'] ? $userInfo['household_address'] : '';
            $userInfo['address'] = $userInfo['community_name'].$userInfo['group'].
                $userInfo['building'].$userInfo['unit'].$userInfo['room'];
            $userInfo['qq'] = $userInfo['qq'] ? $userInfo['qq'] : '';
            $userInfo['wechat'] = $userInfo['wechat'] ? $userInfo['wechat'] : '';
            $userInfo['email'] = $userInfo['email'] ? $userInfo['email'] : '';
            $userInfo['telephone'] = $userInfo['telephone'] ? $userInfo['telephone'] : '';
            $userInfo['emergency_contact'] = $userInfo['emergency_contact'] ? $userInfo['emergency_contact'] : '';
            $userInfo['emergency_mobile'] = $userInfo['emergency_mobile'] ? $userInfo['emergency_mobile'] : '';
            unset($userInfo['nation_name']);
            unset($userInfo['group']);
            unset($userInfo['building']);
            unset($userInfo['unit']);
            unset($userInfo['room']);
            $userInfo['label'] = $this->getMemberLabels($params['id']);
        }

        return $userInfo;
    }

    public function getAcrossDayDetail($params, $page, $rows)
    {
        //获取查询日期0点及24点时间戳
        $startTime = strtotime($params['day']);
        $endTime = $startTime + 60*60*24;
        $query = DoorRecord::find()
            ->alias('r')
            ->leftJoin('ps_member m', 'm.mobile = r.user_phone')
            ->leftJoin('ps_community c','c.id = r.community_id')
            ->leftJoin('ps_device d', 'd.device_no = r.device_no')
            ->where(['m.id' => $params['id']])
            ->andWhere(['>', 'r.open_time', $startTime])
            ->andWhere(['<', 'r.open_time', $endTime]);
        $reData['totals'] = $query->select('r.id')->count();
        $list = $query
            ->select('c.name as community_name,r.capture_photo,r.open_time,r.device_name as device_name,
            r.open_type,r.card_no as open_card_no,r.`group`,r.building,r.unit,r.room')
            ->offset((($page - 1) * $rows))
            ->limit($rows)
            ->orderBy('r.open_time desc')
            ->asArray()
            ->all();
        foreach ($list as $key => $val) {
            $list[$key]['capture_photo'] = $val['capture_photo'] ? F::getOssImagePath($val['capture_photo'], 'zjy') : '';
            $list[$key]['open_time'] = $val['open_time'] ? date("Y-m-d H:i:s",$val['open_time']) : '';
            $list[$key]['open_type'] = [
                'id' => !empty($val['open_type']) ? $val['open_type'] : '',
                'name' => !empty($val['open_type']) ? DoorRecordService::service()->_open_door_type[$val['open_type']] : '',
            ];
            $list[$key]['room_address'] = $val['group'].$val['building'].$val['unit'].$val['name'];
            unset($list[$key]['group']);
            unset($list[$key]['building']);
            unset($list[$key]['unit']);
            unset($list[$key]['room']);
        }
        $reData['list'] = $list;
        return $reData;
    }

    public function returnIdName($data)
    {
        return parent::returnIdName($data); // TODO: Change the autogenerated stub
    }

    public function getDayReport($id)
    {
        return BasicDataService::service()->getDayReport($id,2,30);
    }

    public function getTravelReport($id)
    {
        $list = BasicDataService::service()->getDayReport($id,2,30);
        $data = [];
        if($list){
            $data['week'] = array_slice($list,0,7);
            $sortColumn = array_column($data['week'],'id');
            array_multisort($sortColumn,SORT_DESC,$data['week']);
            $data['halfMonth'] = array_slice($list,0,15);
            $sortColumn = array_column($data['halfMonth'],'id');
            array_multisort($sortColumn,SORT_DESC,$data['halfMonth']);
            $data['month'] = $list;
            $sortColumn = array_column($data['month'],'id');
            array_multisort($sortColumn,SORT_DESC,$data['month']);
        }
        return $data;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/8/24
 * Time: 16:31
 */

namespace service\door;

use app\models\PsMember;
use common\core\PsCommon;
use service\BaseService;
use service\resident\ResidentService;
use service\room\RoomService;
use service\small\MemberService;

class SelfService extends BaseService
{
    public $identity_type = [
        '1' => '业主',
        '2' => '家人',
        '3' => '租客',
    ];

    //业主管理首页
    public function owner_home($user_id)
    {
        $member_id = $this->getMemberByUser($user_id);
        $member = PsMember::find()->select('name, mobile')->where(['id' => $member_id])->asArray()->one();
        
        $rooms = PsResidentAudit::find()->alias('ra')->where(['member_id'=>$member_id,'ra.name'=>$member['name']])
            ->leftJoin(['cr'=>PsCommunityRoominfo::tableName()],'cr.id = ra.room_id')
            ->leftJoin(['c'=>PsCommunityModel::tableName()],'c.id = ra.community_id')
            ->select(['ra.id as audit_record_id','ra.community_id','c.phone as community_mobile','c.name as community_name','ra.time_end','ra.identity_type','ra.room_id as room_id',
                'cr.group','cr.building','cr.unit','cr.room','ra.status'])
            ->asArray()->all();
        $roomData = ['auth' => [], 'unauth' => []];
        //审核跟失败的数据
        if($rooms){
            foreach($rooms as $key=>$value){
                $value['expired_time'] = !empty($value['time_end']) ? date('Y-m-d',$value['time_end']): '永久';
                $identity = KeyService::service()->identity;
                $value['identity_label'] = $identity[$value['identity_type']];
                $value['room_adress'] = $value['group'].'-'.$value['building'].'-'.$value['unit'].'-'.$value['room'];
                //审核中
                if($value['status'] == 0){
                    $value['status'] = 1;
                    $value['is_auth'] = 2;
                    $roomData['unauth'][] = $value;
                }
                //审核未通过
                if($value['status'] == 2){
                    $value['is_auth'] = 2;
                    $value['status'] = 3;
                    $roomData['unauth'][] = $value;
                }
            }
        }
        $rooms2 = PsRoomUser::find()->alias('ru')
            ->leftJoin(['c'=>PsCommunityModel::tableName()],'c.id = ru.community_id')
            ->select('ru.community_id,ru.id as rid, c.phone as community_mobile,c.name as community_name,ru.room_id, ru.group, ru.building, ru.unit, ru.room, ru.time_end, ru.identity_type, ru.status, ru.name')
            ->where(['member_id' => $member_id,'ru.name' => $member['name']])->asArray()->all();
        if($rooms2){
            foreach ($rooms2 as $v) {
                $v['audit_record_id'] = 0;
                $v['expired_time'] = !empty($v['time_end']) ? date('Y-m-d',$v['time_end']): '永久';
                $v['identity_label'] = PsCommon::getIdentityType($v['identity_type'], 'key');
                $v['room_adress'] = $v['group'].'-'.$v['building'].'-'.$v['unit'].'-'.$v['room'];
                //迁入未认证并且名字要一致才能显示 2019-6-28说改的
                if($v['status'] == 1 && $member['name'] == $v['name']){
                    $v['status'] = 1;
                    $v['is_auth'] = 2;
                    $roomData['unauth'][] = $v;
                }
                //已认证
                if($v['status'] == 2){
                    $v['status'] = 2;
                    $v['is_auth'] = 1;
                    $roomData['auth'][] = $v;
                }
                //迁出
                if($v['status'] == 3||$v['status'] == 4){
                    $v['is_auth'] = 2;
                    $v['status'] = 4;
                    $roomData['unauth'][] = $v;
                }
            }
        }

        return $this->success($roomData);
    }

    //小区列表页面
    public function community_list($name)
    {
        $list = [];
        $community = AppUserService::getMyCommunitysByUserId(0,$name);
        if($community){
            foreach($community as $key =>$value){
                $array = [];
                $array['pinyin'] = $key;
                $array['communitys'] = $this->deal_community_name($value);
                $list[] = $array;
            }
        }
        return $this->success($list);
    }

    //处理返回得到的小区字段
    private function deal_community_name($community)
    {
        $list = [];
        if($community){
            foreach($community as $key=>$value){
                $array = [];
                $array['community_id'] = $value['community_id'];
                $array['community_name'] = $value['name'];
                $list[] = $array;
            }
        }
        return $list;
    }

    //房屋列表
    public function house_list($community_id)
    {
        $list = $this->deal_model($community_id);//一次查找然后用数组处理
        return $this->success($list);
    }

    private function deal_model($community_id)
    {
        $model = PsCommunityRoominfo::find()->alias('r')
            ->rightJoin(['u'=>PsCommunityUnits::tableName()],'u.id = r.unit_id')
            ->rightJoin(['b'=>PsCommunityBuilding::tableName()],'b.id = u.building_id')
            ->rightJoin(['g'=>PsCommunityGroups::tableName()],'g.id = u.group_id')
            ->where(['r.community_id'=>$community_id])
            ->select(['r.community_id','r.id as room_id','u.id as unit_id','u.building_id','u.group_id','r.room','u.name as unit','b.name as building','g.name as group'])
            ->asArray()->all();
        $list = [];
        if($model){
            $units = $building =$group = [];
            foreach($model as $key=>$value){
                $room['name'] = $value['room'];
                $room['subList'] = [];

                $units[$value['unit_id']]['name'] = $value['unit'];
                $units[$value['unit_id']]['subList'][] = $room;

                $building[$value['building_id']]['name'] = $value['building'];
                $building[$value['building_id']]['subList'][$value['unit_id']] = $units[$value['unit_id']];

                $list[$value['group_id']]['name'] = $value['group'];
                $list[$value['group_id']]['subList'][$value['building_id']]= $building[$value['building_id']];

            }
            $list = array_values($list);
            foreach($list as $ke=>$ve){
                foreach($ve['subList'] as $k=>$v){
                    sort($list[$ke]['subList'][$k]['subList']);
                }
                sort($list[$ke]['subList']);
            }
        }
        return $list;
    }

    public function audit_submit($user_id, $communityId, $data,$id,$rid)
    {
        $params['community_id'] = $communityId;
        $room = RoomService::service()->findRoom($communityId, $data['group'], $data['building'],$data['unit'],$data['room'] );
        $params['room_id'] = $room['id'];
        $params['images'] = is_array($data['card_url']) ? implode(',',$data['card_url']) : $data['card_url'];
        $params['name'] = $data['name'];
        $params['mobile'] = $data['mobile'];
        $params['time_end'] = !empty($data['expired_time']) ? $data['expired_time'] : 0;
        $params['card_no'] = $data['card_no'];
        $params['identity_type'] = $data['identity_type'];
        //return ResidentService::service()->saveAudit($user_id, $communityId, $params,$id);
        return ResidentService::service()->recommit($user_id,$communityId, $params,$id,$rid);
        //return $this->success($r);
    }

    public function audit_detail($id, $communityId,$type)
    {
        if($type == 1){
            $result = ResidentService::service()->auditShow($id, $communityId);
        }else{
            $result = ResidentService::service()->showOne($id, $communityId);
        }
        $data = [];
        if($result){
            $data = $result;
            $data['card_url'] = !empty($result['images']) ? $result['images'] : '';
            $data['expired_time'] = date($result['time_end']);
            $data['refuse_reason'] = !empty($result['reason']) ? $result['reason'] : '';
            $data['room_address'] = $result['group'].$result['building'].$result['unit'].$result['room'];
            $data['identity_label'] = $result['identity_type_des'];
            $data['community_name'] = PsCommunityModel::find()->select(['name'])->where(['id'=>$communityId])->asArray()->scalar();
        }
        return $this->success($data);
    }

    public function audit_house($user_id, $communityId)
    {
        $rooms = MemberCommService::service()->myRooms($user_id, $communityId);
        return $this->success($rooms);
    }

    public function get_common($user_id, $type = 1)
    {
        $memberId = MemberService::service()->getMemberId($user_id);
        var_dump($memberId);die;
        $data = PsMember::find()->select(['id', 'name', 'mobile'])->where(['id' => $memberId])->asArray()->one();
        $identity = [];
        foreach($this->identity_type as $key =>$value){
            if ($type == 2 && $value == "业主") {
                continue;
            }
            $array['key'] = $key;
            $array['value'] = $value;
            $identity[] = $array;
        }
        $data['identity'] = $identity;
        return $this->success($data);
    }

    //获取业务id
    public function get_biz_id($user_id)
    {
        $pre = date('YmdHis').str_pad($user_id, 6, '0', STR_PAD_LEFT);
        $bizId = PsCommon::getNoRepeatChar($pre, YII_ENV.'aliBizIdUniqueList');  //商户调用支付宝人脸采集
        $data['biz_id'] = $bizId;
        return $this->success($data);
    }
}
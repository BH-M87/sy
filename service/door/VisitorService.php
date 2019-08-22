<?php
/**
 * 访客管理相关服务
 * User: fengwenchao
 * Date: 2019/8/21
 * Time: 16:15
 */

namespace service\door;


use app\models\PsRoomVistors;
use common\core\F;
use common\core\PsCommon;
use service\BaseService;
use service\common\CsvService;
use service\rbac\OperateService;

class VisitorService extends BaseService
{
    public $_visit_status = [
        '1' => '未到访',
        '2' => '已到访',
        '3' => '已取消',
    ];

    public function getCommon()
    {
        $comm = [
            'visit_status' => PsCommon::returnKeyValue($this->_visit_status)
        ];
        return $comm;
    }

    //列表
    public function getList($params)
    {
        $model = PsRoomVistors::find()->alias("room")
            ->leftJoin("ps_member member", "room.member_id=member.id")
            ->where(['room.community_id' => $params['community_id']]);
        if(!empty($params['group'])){
            $model->andFilterWhere(['room.group'=>$params['group']]);
        }
        if (!empty($params['building'])){
            $model->andFilterWhere(['room.building'=>$params['building']]);
        }
        if (!empty($params['unit'])){
            $model->andFilterWhere(['room.unit' => $params['unit']]);
        }
        if (!empty($params['room'])){
            $model->andFilterWhere(['room.room'=>$params['room']]);
        }
        if (!empty($params['name'])){
           $model->andFilterWhere(["or",["like","room.vistor_name",$params['name']],["like","room.vistor_mobile",$params['name']]]);
        }
        if (!empty($params['member_name'])){
            $model->andFilterWhere(['like', 'member.name', $params['member_name']]);
        }
        if(!empty($params['start_time'])){
            $start_time = strtotime($params['start_time']);
            $model->andFilterWhere(['>=','room.start_time',$start_time]);
        }
        if (!empty($params['end_time'])) {
            $end_time = strtotime($params['end_time'].' 23:59:59');
            $model->andFilterWhere(['<=','room.end_time',$end_time]);
        }
        if (!empty($params['status'])) {
            if($params['status'] == 1){
                //未到访
                $model->andFilterWhere(['room.is_cancel' => 2]);
                $model->andFilterWhere(['room.status' => [1,3]]);
            } elseif ($params['status'] == 3){
                //已取消
                $model->andFilterWhere(['room.is_cancel' => 1]);
            } else {
                //已到访
                $model->andFilterWhere(['room.is_cancel' => 2]);
                $model->andFilterWhere(['room.status' => 2]);
            }
        }
        $re['total'] = $model->count();
        $list = $model->select('room.id, room.vistor_name,room.sex,room.vistor_mobile,room.start_time,room.end_time,room.car_number,
        room.is_cancel,room.`group`,room.building,room.unit,room.room,room.reason,room.passage_at,
        member.name as member_name,room.status')
            ->offset((($params['page'] - 1) * $params['page']))
            ->limit($params['rows'])
            ->orderBy("room.id desc")
            ->asArray()
            ->all();
        foreach ($list as $k=>$v) {
            $list[$k]['visit_time'] = date("Y-m-d H:i",$v['start_time']).'-'.date("Y-m-d H:i",$v['end_time']);
            $list[$k]['passage_at'] = !empty($v['passage_at']) ? date("Y-m-d H:i",$v['passage_at']):'';
            if($v['is_cancel'] == 1){
                $list[$k]['status_msg'] = '已取消';
            } else {
                if ($v['status'] == 2) {
                    $list[$k]['status_msg'] = '已到访';
                } else {
                    $list[$k]['status_msg'] = '未到访';
                }
            }
            $list[$k]['room_address'] = $v['group'].$v['building'].$v['unit'].$v['room'];
            $list[$k]['sex_msg'] = $v['sex'] == 1 ? '男' : '女';
        }
        $re['list'] = $list;
        return $re;
    }

    //导出
    public function export($params,$userInfo = [])
    {
        $result = $this->getList($params);
        $config = [
            ['title' => '访客姓名', 'field' => 'vistor_name'],
            ['title' => '性别', 'field' => 'sex_msg'],
            ['title' => '联系电话', 'field' => 'vistor_mobile'],
            ['title' => '到访时间', 'field' => 'visit_time'],
            ['title' => '车牌号', 'field' => 'car_number'],
            ['title' => '到访地址', 'field' => 'room_address'],
            ['title' => '被访人', 'field' => 'member_name'],
            ['title' => '业主留言', 'field' => 'reason'],
            ['title' => '到访状态', 'field' => 'status_msg'],
            ['title' => '实际到访时间', 'field' => 'passage_at'],
        ];
        $filename = CsvService::service()->saveTempFile(1, $config, $result['list'], 'roomVisitors');
        $downUrl = F::downloadUrl($filename, 'roomVisitors', 'RoomVisitors.csv');
        $operate = [
            "community_id" => $params["community_id"],
            "operate_menu" => "门禁管理",
            "operate_type" => "导出访客记录",
            "operate_content" => "导出",
        ];
        OperateService::addComm($userInfo, $operate);
        return $downUrl;
    }
}
<?php
namespace service\property_basic;

use Yii;
use yii\db\Query;
use yii\base\Exception;
use common\MyException;
use common\core\PsCommon;

use service\BaseService;

use app\models\PsRoomVote;
use app\models\PsRoomVoteRecord;

use service\property_basic\JavaOfCService;

class RoomVoteService extends BaseService
{
    public function index($p)
    {
        $id = PsRoomVote::find()->select('id')->where(['communityId' => $p['communityId']])->scalar();

        if (empty($id)) {
            $arr['communityId'] = $p['communityId'];
            $arr['name'] = '业委会申请成立投票';
            $arr['vote_desc'] = '现需收集各位业主对本会成立意见';
            $id = self::_voteAdd($arr)['id'];
        }

        $m = PsRoomVoteRecord::find()
            ->where(['room_vote_id' => $id, 'roomId' => $p['roomId'], 'memberId' => $p['memberId']])->asArray()->one();

        $user = JavaOfCService::service()->residentDetail(['id' => $p['residentId'], 'token' => $p['token']]);

        return ['voteId' => (int)$id, 'type' => !empty($m) ? 1 : 2, 'memberType' => $user['memberType']];
    }

    // 投票 新增
    public function _voteAdd($p)
    {
        $m = new PsRoomVote(['scenario' => 'add']);

        if (!$m->load($p, '') || !$m->validate()) {
            return PsCommon::responseFailed($this->getError($m));
        }

        if (!$m->saveData($scenario, $p)) {
            return PsCommon::responseFailed($this->getError($m));
        }

        $id = $m->attributes['id'];

        return ['id' => $id];
    }

    // 投票统计 户数
    public function statisticMember($p)
    {
        $arr = JavaOfCService::service()->getTotalResidentAndAreaSize(['token' => $p['token'], 'id' => $p['communityId']]);

        $p['type'] = 1;
        $total_1 = (int)self::voteRecordSearch($p)->count();

        $p['type'] =2;
        $total_2 = (int)self::voteRecordSearch($p)->count();

        $p['type'] = 3;
        $total_3 = (int)self::voteRecordSearch($p)->count();

        $total = $arr['totalResident'];
        $total_9 = $total-$total_1-$total_2-$total_3;

        $rate1 = $total > 0 ? round($total_1 / $total, 2) * 100 . '%' : '0%';
        $rate2 = $total > 0 ? round($total_2 / $total, 2) * 100 . '%' : '0%';
        $rate3 = $total > 0 ? round($total_3 / $total, 2) * 100 . '%' : '0%';
        $rate9 = $total > 0 ? round($total_9 / $total, 2) * 100 . '%' : '0%';

        $r = [
            ['type' => '赞成', 'rate' => $rate1, 'data' => $total_1, 'color' => '#1577FC'],
            ['type' => '反对', 'rate' => $rate2, 'data' => $total_2, 'color' => '#F29927'],
            ['type' => '弃权', 'rate' => $rate3, 'data' => $total_3, 'color' => '#F35A4C'],
            ['type' => '未表态', 'rate' => $rate9, 'data' => $total_9, 'color' => '#9CA4BB']
        ];

        return $r;
    }

    // 投票统计 面积
    public function statisticArea($p)
    {
        $arr = JavaOfCService::service()->getTotalResidentAndAreaSize(['token' => $p['token'], 'id' => $p['communityId']]);

        $p['type'] = 1;
        $total_1 = (int)self::voteRecordSearch($p, 'sum(roomArea)')->scalar();

        $p['type'] =2;
        $total_2 = (int)self::voteRecordSearch($p, 'sum(roomArea)')->scalar();

        $p['type'] = 3;
        $total_3 = (int)self::voteRecordSearch($p, 'sum(roomArea)')->scalar();

        $total = $arr['totalAreaSize'];
        $total_9 = $total-$total_1-$total_2-$total_3;

        $rate1 = $total > 0 ? round($total_1 / $total, 2) * 100 . '%' : '0%';
        $rate2 = $total > 0 ? round($total_2 / $total, 2) * 100 . '%' : '0%';
        $rate3 = $total > 0 ? round($total_3 / $total, 2) * 100 . '%' : '0%';
        $rate9 = $total > 0 ? round($total_9 / $total, 2) * 100 . '%' : '0%';

        $r = [
            ['type' => '赞成', 'rate' => $rate1, 'data' => $total_1, 'color' => '#1577FC'],
            ['type' => '反对', 'rate' => $rate2, 'data' => $total_2, 'color' => '#F29927'],
            ['type' => '弃权', 'rate' => $rate3, 'data' => $total_3, 'color' => '#F35A4C'],
            ['type' => '未表态', 'rate' => $rate9, 'data' => $total_9, 'color' => '#9CA4BB']
        ];

        return $r;
    }

    // 投票 详情
    public function blockList($p)
    {
        $r = JavaOfCService::service()->blockList(['token' => $p['token'], 'id' => $p['communityId']])['list'];

        $arr = [];
        $i = 0;
        if (!empty($r)) {
            foreach ($r as $k => $v) {
                if (!empty($v['dataList'])) {
                    foreach ($v['dataList'] as $key => $val) {
                        $arr[$i]['buildingId'] = $val['id'];
                        $arr[$i]['buildingName'] = $val['buildingName'];
                        $arr[$i]['groupName'] = $val['groupName'];
                        $i++;
                    }
                }
            }
        }

        if (!empty($arr)) {
            foreach ($arr as $k => $v) {
                $arr[$k]['favor'] = self::voteRecordSearch(['type' => 1, 'buildingId' => $v['buildingId']])->count();
                $arr[$k]['total'] = self::voteRecordSearch(['buildingId' => $v['buildingId']])->count();
                $arr[$k]['rate'] = $arr[$k]['total'] > 0 ? round($arr[$k]['favor'] / $arr[$k]['total'], 2) * 100 : 0;
            }
        }

        return $arr;
    }

    // 投票 详情
    public function show($p)
    {
        $r = PsRoomVote::find()->where(['id' => $p['id']])->asArray()->one();
        if (!empty($r)) {
            return $r;
        }

        throw new MyException('投票不存在!');
    }

    // 公告 详情
    public function noticeShow($p)
    {
        $r['title'] = '公告标题';
        $r['content'] = '公告内容';

        return $r;
    }

    // 投票 新增
    public function add($p)
    {
        $record = PsRoomVoteRecord::find()
            ->where(['room_vote_id' => $p['room_vote_id'], 'roomId' => $p['roomId'], 'memberId' => $p['memberId']])->asArray()->one();

        if (!empty($record)) {
            return PsCommon::responseFailed('不能重复投票！');
        }

        $m = new PsRoomVoteRecord(['scenario' => 'add']);

        if (!$m->load($p, '') || !$m->validate()) {
            return PsCommon::responseFailed($this->getError($m));
        }

        if (!$m->saveData($scenario, $p)) {
            return PsCommon::responseFailed($this->getError($m));
        }

        $id = $m->attributes['id'];

        return ['id' => $id];
    }

    // 投票成功
    public function success($p)
    {
        $arr = JavaOfCService::service()->getTotalResidentAndAreaSize(['token' => $p['token'], 'id' => $p['communityId']]);

        $p['type'] = 1;
        $total_1 = self::voteRecordSearch($p)->count();
        $ticket = ceil($arr['totalResident'] * 0.2 - $total_1);

        $p['type'] =2;
        $total_2 = self::voteRecordSearch($p)->count();

        $p['type'] = 3;
        $total_3 = self::voteRecordSearch($p)->count();

        $total = $total_1 + $total_2 + $total_3;
        $rate1 = $total > 0 ? round($total_1 / $total, 2) * 100 : 0;
        $rate2 = $total > 0 ? round($total_2 / $total, 2) * 100 : 0;
        $rate3 = $total > 0 ? round($total_3 / $total, 2) * 100 : 0;

        $r['list'] = [
            ['type' => '赞成', 'total' => $total_1, 'rate' => $rate1],
            ['type' => '反对', 'total' => $total_2, 'rate' => $rate2],
            ['type' => '弃权', 'total' => $total_3, 'rate' => $rate3]
        ];
        $r['total'] = $ticket > 0 ? $ticket : 0;

        return $r;
    }

    // 投票统计 列表
    public function voteList($p)
    {
        if ($p['type'] == 9) { // 9未表态
            $room = JavaOfCService::service()->roomList(['token' => $p['token'], 'id' => $p['buildingId']])['list'];

            $arr = [];
            $i = 0;
            if (!empty($room)) {
                foreach ($room as $k => $v) {
                    if (!empty($v['dataList'])) {
                        foreach ($v['dataList'] as $key => $val) {
                            $arr[$i]['roomId'] = $val['id'];
                            $arr[$i]['roomName'] = $val['roomName'];
                            $i++;
                        }
                    }
                }
            }

            $m = self::voteRecordSearch(['buildingId' => $p['buildingId']], 'distinct(roomId), roomName')->orderBy('id desc')->asArray()->all();

            if (!empty($arr)) {
                foreach ($arr as $k => $v) {
                    if (!empty($m)) {
                        foreach ($m as $key => $val) {
                            if ($val['roomId'] == $v['roomId']) {
                                unset($arr[$k]);
                            }
                        }
                    }
                }
            }
            return array_values($arr);
        } else { // 1赞成 2反对 3弃选
            $m = self::voteRecordSearch($p, 'distinct(roomId), roomName')->orderBy('id desc')->asArray()->all();

            return $m;
        }
    }

    // 列表参数过滤
    private static function voteRecordSearch($p, $select = '*')
    {
        $m = PsRoomVoteRecord::find()
            ->select($select)
            ->filterWhere(['=', 'communityId', PsCommon::get($p, 'communityId')])
            ->andFilterWhere(['=', 'communityId', PsCommon::get($p, 'community_id')])
            ->andFilterWhere(['in', 'communityId', PsCommon::get($p, 'communityList')])
            ->andFilterWhere(['=', 'groupId', PsCommon::get($p, 'groupId')])
            ->andFilterWhere(['=', 'buildingId', PsCommon::get($p, 'buildingId')])
            ->andFilterWhere(['=', 'unitId', PsCommon::get($p, 'unitId')])
            ->andFilterWhere(['=', 'roomId', PsCommon::get($p, 'roomId')])
            ->andFilterWhere(['=', 'type', PsCommon::get($p, 'type')]);
        return $m;
    }  
}
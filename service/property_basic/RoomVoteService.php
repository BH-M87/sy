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
        /*$m = PsRoomVoteRecord::find()->asArray()->all();
        foreach ($m as $k => $v) {
            $buildingFullName = $v['groupName'].$v['buildingName'];
            $unitFullName = $buildingFullName.$v['unitName'];
            $roomFullName = $v['communityName'].$unitFullName.$v['roomName'];

            PsRoomVoteRecord::updateAll(['buildingFullName' => $buildingFullName, 'unitFullName' => $unitFullName, 'roomFullName' => $roomFullName], ['id' => $v['id']]);
        }*/

        $id = PsRoomVote::find()->select('id')->where(['communityId' => $p['communityId']])->scalar();

        if (empty($id)) {
            $arr['communityId'] = $p['communityId'];
            $arr['name'] = '业委会申请成立投票';
            $arr['vote_desc'] = '现需收集各位业主对本会成立意见';
            $id = self::_voteAdd($arr)['id'];
        }
        
        $m = PsRoomVoteRecord::find()->where(['room_vote_id' => $id, 'roomFullName' => $p['roomFullName']])
            ->andWhere(['communityId' => $p['communityId']])->asArray()->one();
        $type = 2; // 未投票
        if (!empty($m)) {
            $type = 1; // 已投票
        }

        $user = JavaOfCService::service()->residentDetail(['id' => $p['residentId'], 'token' => $p['token']]);

        return ['voteId' => (int)$id, 'type' => $type, 'memberType' => $user['memberType']];
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
        $all = $total_1 + $total_2 + $total_3;
        if ($all > $total) {
            $total = $all;
        }

        $total_9 = $total-$total_1-$total_2-$total_3;
        $total_9 = $total_9 > 0 ? $total_9 : 0;

        $rate1 = $total > 0 ? round($total_1 / $total, 4) * 100 : 0;
        $rate2 = $total > 0 ? round($total_2 / $total, 4) * 100 : 0;
        $rate3 = $total > 0 ? round($total_3 / $total, 4) * 100 : 0;
        $rate9 = $total > 0 ? round($total_9 / $total, 4) * 100 : 0;

        $r = [
            ['type' => '赞成', 'rate' => $rate1 . '%', 'data' => $total_1, 'color' => '#1577FC'],
            ['type' => '反对', 'rate' => $rate2 . '%', 'data' => $total_2, 'color' => '#F29927'],
            ['type' => '弃权', 'rate' => $rate3 . '%', 'data' => $total_3, 'color' => '#F35A4C'],
            ['type' => '未表态', 'rate' => $rate9 . '%', 'data' => $total_9, 'color' => '#9CA4BB']
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
        $all = $total_1 + $total_2 + $total_3;
        if ($all > $total) {
            $total = $all;
        }

        $total_9 = $total-$total_1-$total_2-$total_3;
        $total_9 = $total_9 > 0 ? $total_9 : 0;

        $rate1 = $total > 0 ? round($total_1 / $total, 4) * 100 : 0;
        $rate2 = $total > 0 ? round($total_2 / $total, 4) * 100 : 0;
        $rate3 = $total > 0 ? round($total_3 / $total, 4) * 100 : 0;
        $rate9 = $total > 0 ? round($total_9 / $total, 4) * 100 : 0;

        $r = [
            ['type' => '赞成', 'rate' => $rate1 . '%', 'data' => $total_1, 'color' => '#1577FC'],
            ['type' => '反对', 'rate' => $rate2 . '%', 'data' => $total_2, 'color' => '#F29927'],
            ['type' => '弃权', 'rate' => $rate3 . '%', 'data' => $total_3, 'color' => '#F35A4C'],
            ['type' => '未表态', 'rate' => $rate9 . '%', 'data' => $total_9, 'color' => '#9CA4BB']
        ];

        return $r;
    }

    // 已投楼栋
    public function blockList($p)
    {
        $r = JavaOfCService::service()->blockListForPhp(['token' => $p['token'], 'id' => $p['communityId']])['list'];

        $arr = [];
        $i = 0;
        if (!empty($r)) {
            foreach ($r as $k => $v) {
                if (!empty($v['dataList'])) {
                    foreach ($v['dataList'] as $key => $val) {
                        $arr[$i]['buildingFullName'] = $val['groupName'].$val['buildingName'];
                        $arr[$i]['groupName'] = $val['groupName'];
                        //$arr[$i]['buildingId'] = $val['id'];
                        $arr[$i]['buildingName'] = $val['buildingName'];
                        
                        $building[] =  $arr[$i]['buildingFullName'];
                        $i++;
                    }
                }
            }
        }

        $arr = array_unique($arr, SORT_REGULAR);

        if (!empty($arr)) {
            foreach ($arr as $k => $v) {
                $arr[$k]['favor'] = self::voteRecordSearch(['type' => 1, 'buildingFullName' => $v['buildingFullName'], 'communityId' => $p['communityId']])->count();
                $arr[$k]['total'] = self::voteRecordSearch(['buildingFullName' => $v['buildingFullName'], 'communityId' => $p['communityId']])->count();
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
        $r['title'] = 'Q&A';
        $r['image'] = 'http://static.zje.com/2020042715352788422.png';
        $r['content'] = '<p><span style="color: rgb(17, 31, 44); font-family: &quot;Microsoft YaHei&quot;, &quot;Segoe UI&quot;, system-ui, Roboto, &quot;Droid Sans&quot;, &quot;Helvetica Neue&quot;, sans-serif, Tahoma, &quot;Segoe UI SymbolMyanmar Text&quot;, 微软雅黑; font-size: 14px; white-space: pre-wrap; background-color: rgb(255, 255, 255);">Q：为什么要成立业主委员会？</span><br/><span style="color: rgb(17, 31, 44); font-family: &quot;Microsoft YaHei&quot;, &quot;Segoe UI&quot;, system-ui, Roboto, &quot;Droid Sans&quot;, &quot;Helvetica Neue&quot;, sans-serif, Tahoma, &quot;Segoe UI SymbolMyanmar Text&quot;, 微软雅黑; font-size: 14px; white-space: pre-wrap; background-color: rgb(255, 255, 255);">A：因为业主有监督物业的权利，业主委员会是由业主选举产生的，业主委员会代表每个业主的意愿，所以请大家尽量配合，这样物业就不会胡作非为了。</span><br/><br/><span style="color: rgb(17, 31, 44); font-family: &quot;Microsoft YaHei&quot;, &quot;Segoe UI&quot;, system-ui, Roboto, &quot;Droid Sans&quot;, &quot;Helvetica Neue&quot;, sans-serif, Tahoma, &quot;Segoe UI SymbolMyanmar Text&quot;, 微软雅黑; font-size: 14px; white-space: pre-wrap; background-color: rgb(255, 255, 255);">Q：如何支持成立业主委员会？</span><br/><span style="color: rgb(17, 31, 44); font-family: &quot;Microsoft YaHei&quot;, &quot;Segoe UI&quot;, system-ui, Roboto, &quot;Droid Sans&quot;, &quot;Helvetica Neue&quot;, sans-serif, Tahoma, &quot;Segoe UI SymbolMyanmar Text&quot;, 微软雅黑; font-size: 14px; white-space: pre-wrap; background-color: rgb(255, 255, 255);">A：1.点击投票界面顶部的【小区显示区域】，根据操作指引，完成房屋认证，获取投票资格。</span><br/><span style="color: rgb(17, 31, 44); font-family: &quot;Microsoft YaHei&quot;, &quot;Segoe UI&quot;, system-ui, Roboto, &quot;Droid Sans&quot;, &quot;Helvetica Neue&quot;, sans-serif, Tahoma, &quot;Segoe UI SymbolMyanmar Text&quot;, 微软雅黑; font-size: 14px; white-space: pre-wrap; background-color: rgb(255, 255, 255);"> &nbsp; &nbsp; &nbsp;2.完成房屋认证后，点击投票界面的【去投票】的图片，进入投票界面，选中您的【投票意向】，点击【确认投票】，即表决成功。</span><br/><br/><span style="color: rgb(17, 31, 44); font-family: &quot;Microsoft YaHei&quot;, &quot;Segoe UI&quot;, system-ui, Roboto, &quot;Droid Sans&quot;, &quot;Helvetica Neue&quot;, sans-serif, Tahoma, &quot;Segoe UI SymbolMyanmar Text&quot;, 微软雅黑; font-size: 14px; white-space: pre-wrap; background-color: rgb(255, 255, 255);">Q：如何查看投票结果？</span><br/><span style="color: rgb(17, 31, 44); font-family: &quot;Microsoft YaHei&quot;, &quot;Segoe UI&quot;, system-ui, Roboto, &quot;Droid Sans&quot;, &quot;Helvetica Neue&quot;, sans-serif, Tahoma, &quot;Segoe UI SymbolMyanmar Text&quot;, 微软雅黑; font-size: 14px; white-space: pre-wrap; background-color: rgb(255, 255, 255);">A：点击下方菜单的【投票统计】查看总的投票情况；点击投票界面的【去投票】图片查看自己的投票记录。</span></p>';
        
        return $r;
    }

    // 投票 新增
    public function add($p)
    {
        $record = PsRoomVoteRecord::find()
            ->where(['room_vote_id' => $p['room_vote_id'], 'roomFullName' => $p['roomFullName']])
            ->andWhere(['communityId' => $p['communityId']])->asArray()->one();

        if (!empty($record)) {
            return PsCommon::responseFailed('该户已投票！');
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
        $r['type'] = PsRoomVoteRecord::find()->select('type')->where(['roomFullName' => $p['roomFullName']])
            ->andWhere(['communityId' => $p['communityId']])->scalar();

        $arr = JavaOfCService::service()->getTotalResidentAndAreaSize(['token' => $p['token'], 'id' => $p['communityId']]);

        $p['roomFullName'] = '';
        $p['type'] = 1;
        $total_1 = self::voteRecordSearch($p)->count();
        $ticket = ceil($arr['totalResident'] * 0.2 - $total_1);

        $p['type'] =2;
        $total_2 = self::voteRecordSearch($p)->count();

        $p['type'] = 3;
        $total_3 = self::voteRecordSearch($p)->count();

        $total = $total_1 + $total_2 + $total_3;
        $rate1 = $total > 0 ? round($total_1 / $total, 4) * 100 : 0;
        $rate2 = $total > 0 ? round($total_2 / $total, 4) * 100 : 0;
        $rate3 = $total > 0 ? round($total_3 / $total, 4) * 100 : 0;

        $r['list'] = [
            ['type' => '1', 'typeMsg' => '赞成', 'total' => $total_1, 'rate' => (string)$rate1],
            ['type' => '2', 'typeMsg' => '反对', 'total' => $total_2, 'rate' => (string)$rate2],
            ['type' => '3', 'typeMsg' => '弃权', 'total' => $total_3, 'rate' => (string)$rate3]
        ];
        $r['total'] = $ticket > 0 ? $ticket : 0;

        return $r;
    }

    // 投票统计 列表
    public function voteList($p)
    {
        if ($p['type'] == 9) { // 9未表态
            
            $room = JavaOfCService::service()->selectRoomList(['token' => $p['token'], 'communityId' => $p['communityId'], 'groupName' => $p['groupName'], 'buildingName' => $p['buildingName']])['list'];
            $arr = [];
            if (!empty($room)) {
                foreach ($room as $k => $v) {
                    $arr[$k]['roomFullName'] = $p['communityName'].$p['groupName'].$p['buildingName'].$v['unitName'].$v['roomName'];
                    $arr[$k]['roomName'] = $v['roomName'];
                }
            }

            $m = self::voteRecordSearch(['buildingFullName' => $p['buildingFullName']], 'distinct(roomFullName), roomName')->orderBy('id desc')->asArray()->all();

            if (!empty($arr)) {
                foreach ($arr as $k => $v) {
                    if (!empty($m)) {
                        foreach ($m as $key => $val) {
                            if ($val['roomFullName'] == $v['roomFullName']) {
                                unset($arr[$k]);
                            }
                        }
                    }
                }
            }
            return array_values($arr);
        } else { // 1赞成 2反对 3弃选
            $m = self::voteRecordSearch($p, 'distinct(roomFullName), roomName')->orderBy('id desc')->asArray()->all();

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
            ->andFilterWhere(['=', 'groupName', PsCommon::get($p, 'groupName')])
            ->andFilterWhere(['=', 'buildingName', PsCommon::get($p, 'buildingName')])
            ->andFilterWhere(['=', 'unitName', PsCommon::get($p, 'unitName')])
            ->andFilterWhere(['=', 'roomName', PsCommon::get($p, 'roomName')])

            ->andFilterWhere(['=', 'buildingFullName', PsCommon::get($p, 'buildingFullName')])
            ->andFilterWhere(['=', 'unitFullName', PsCommon::get($p, 'unitFullName')])
            ->andFilterWhere(['=', 'roomFullName', PsCommon::get($p, 'roomFullName')])
            
            ->andFilterWhere(['=', 'type', PsCommon::get($p, 'type')]);
        return $m;
    }  
}
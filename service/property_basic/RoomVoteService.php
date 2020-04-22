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

class RoomVoteService extends BaseService
{
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
        $p['type'] = 1;
        $total_1 = self::voteRecordSearch($p)->count();

        $p['type'] =2;
        $total_2 = self::voteRecordSearch($p)->count();

        $p['type'] = 3;
        $total_3 = self::voteRecordSearch($p)->count();

        $total = $total_1 + $total_2 + $total_3;
        $rate1 = $total > 0 ? round($total_1 / $total, 2) * 100 : 0;
        $rate2 = $total > 0 ? round($total_2 / $total, 2) * 100 : 0;
        $rate3 = $total > 0 ? round($total_3 / $total, 2) * 100 : 0;

        $r = [
            ['type' => 1, 'total' => $total_1, 'rate' => $rate1],
            ['type' => 2, 'total' => $total_2, 'rate' => $rate2],
            ['type' => 3, 'total' => $total_3, 'rate' => $rate3]
        ];

        return $r;
    }

    // 投票统计 列表
    public function voteList($p)
    {
        $m = self::voteRecordSearch($p, 'distinct(roomName)')->orderBy('id desc')->asArray()->all();

        return $m;
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
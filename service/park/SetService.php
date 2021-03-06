<?php
namespace service\park;

use Yii;
use yii\db\Query;
use yii\base\Exception;
use common\MyException;
use common\core\PsCommon;
use common\core\Curl;

use service\BaseService;

use service\property_basic\JavaService;

use app\models\PsParkSet;
use app\models\PsParkBlack;
use app\models\PsParkBreakPromise;
use app\models\PsParkReservation;
use app\models\PsParkSpace;

class SetService extends BaseService
{
    public static $status = [
        1 => ['id' => 1, 'name' => '待预约'],
        2 => ['id' => 2, 'name' => '已预约'],
        3 => ['id' => 3, 'name' => '使用中'],
        4 => ['id' => 4, 'name' => '已关闭'],
        5 => ['id' => 5, 'name' => '已完成'],
    ];

    // 新增
    public function addSet($p, $userInfo)
    {
        return self::_saveSet($p, 'add', $userInfo);
    }

    // 编辑
    public function editSet($p, $userInfo)
    {
        return self::_saveSet($p, 'edit', $userInfo);
    }

    public function _saveSet($p, $scenario, $userInfo)
    {
        if ($scenario == 'edit') {
            $model = PsParkSet::find()->where(['corp_id' => $p['corp_id']])->one();
            if (empty($model)) {
                $scenario = 'add';
            } else {
                $p['id'] = $model->id;
            }
        }

        $param['id'] = $p['id'];
        $param['corp_id'] = $p['corp_id'];
        $param['cancle_num'] = $p['cancle_num'];
        $param['late_at'] = $p['late_at'];
        $param['due_notice'] = $p['due_notice'];
        $param['black_num'] = $p['black_num'];
        $param['appointment'] = $p['appointment'];
        $param['appointment_unit'] = $p['appointment_unit'];
        $param['lock'] = $p['lock'];
        $param['lock_unit'] = $p['lock_unit'];
        $param['min_time'] = $p['min_time'];
        $param['integral'] = $p['integral'];

        $model = new PsParkSet(['scenario' => $scenario]);

        if (!$model->load($param, '') || !$model->validate()) {
            throw new MyException($this->getError($model));
        }

        if (!$model->saveData($scenario, $param)) {
            throw new MyException($this->getError($model));
        }

        $id = $scenario == 'add' ? $model->attributes['id'] : $p['id'];

        return ['id' => $id];
    }

    // 详情
    public function showSet($p)
    {
        $r = PsParkSet::find()->where(['corp_id' => $p['corp_id']])->asArray()->one();
        if (!empty($r)) {
            return $r;
        }

        //throw new MyException('数据不存在!');
    }

    // 黑名单 列表
    public function listBlack($p)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : '1';
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : '10';

        $totals = self::searchBlack($p)->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::searchBlack($p)
            ->offset(($p['page'] - 1) * $p['rows'])
            ->limit($p['rows'])
            ->orderBy('id desc')->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $r = JavaService::service()->roomDetail(['id' => $v['room_id'], 'token' => $p['token']]);
                $v['room_name'] = $r['communityName'].$r['groupName'].$r['buildingName'].$r['unitName'].$r['roomName'];
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 黑名单 列表参数过滤
    private static function searchBlack($p)
    {
        $m = PsParkBlack::find()
            ->filterWhere(['like', 'name', $p['name']])
            ->andFilterWhere(['like', 'room_name', $p['room_name']])
        ->andFilterWhere(['in', 'community_id', $p['communityList']]);
        return $m;
    }

    // 黑名单 删除
    public function deleteBlack($p)
    {
        if (empty($p['id'])) {
            throw new MyException('id不能为空');
        }

        $m = PsParkBlack::findOne($p['id']);
        if (empty($m)) {
            throw new MyException('数据不存在');
        }

        PsParkBlack::deleteAll(['id' => $p['id']]);

        return true;
    }

    // 违约名单 列表
    public function listPromise($p)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : '1';
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : '10';

        $totals = self::searchPromise($p)->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::searchPromise($p)
            ->offset(($p['page'] - 1) * $p['rows'])
            ->limit($p['rows'])
            ->orderBy('id desc')->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $r = JavaService::service()->roomDetail(['id' => $v['room_id'], 'token' => $p['token']]);
                $v['room_name'] = $r['communityName'].$r['groupName'].$r['buildingName'].$r['unitName'].$r['roomName'];
                $v['lock_at'] = !empty($v['lock_at']) ? date('Y/m/d H:i', $v['lock_at']) : '';
                $v['break_time'] = $v['break_time'] . '分钟';
                $v['num'] = $v['num'] . '次';
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 违约名单 列表参数过滤
    private static function searchPromise($p)
    {
        $m = PsParkBreakPromise::find()
            ->filterWhere(['like', 'name', $p['name']])
            ->andFilterWhere(['like', 'mobile', $p['mobile']])
            ->andFilterWhere(['in', 'community_id', $p['communityList']])
            ->andFilterWhere(['=', 'community_id', $p['community_id']]);
        return $m;
    }

    // 违约名单 解锁
    public function deletePromise($p)
    {
        if (empty($p['id'])) {
            throw new MyException('id不能为空');
        }

        $m = PsParkBreakPromise::findOne($p['id']);
        if (empty($m)) {
            throw new MyException('数据不存在');
        }

        PsParkBreakPromise::updateAll(['lock_at' => 0], ['id' => $p['id']]);

        return true;
    }

    // 共享记录 列表
    public function listRecord($p)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : '1';
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : '10';

        $totals = self::searchRecord($p)->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::searchRecord($p)
            ->offset(($p['page'] - 1) * $p['rows'])
            ->limit($p['rows'])
            ->orderBy('id desc')->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $r = JavaService::service()->roomDetail(['id' => $v['room_id'], 'token' => $p['token']]);
                $v['room_name'] = $r['communityName'].$r['groupName'].$r['buildingName'].$r['unitName'].$r['roomName'];
                $v['use_time'] = !empty($v['out_at']) ? ceil(($v['out_at'] - $v['enter_at']) / 60) : 0;
                $v['over_time'] = $v['out_at'] > $v['end_at'] ? ceil(($v['out_at'] - $v['end_at']) / 60) : 0;
                $v['enter_at'] = !empty($v['enter_at']) ? date('Y/m/d H:i', $v['enter_at']) : '';
                $v['out_at'] = !empty($v['out_at']) ? date('Y/m/d H:i', $v['out_at']) : '';
                $v['start_at'] = !empty($v['start_at']) ? date('Y/m/d H:i', $v['start_at']) : '';
                $v['end_at'] = !empty($v['end_at']) ? date('Y/m/d H:i', $v['end_at']) : '';
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 共享记录 列表参数过滤
    private static function searchRecord($p)
    {
        $m = PsParkReservation::find()
            ->filterWhere(['like', 'appointment_name', $p['appointment_name']])
            ->andFilterWhere(['like', 'car_number', $p['car_number']])
            ->andFilterWhere(['in', 'community_id', $p['communityList']])
            ->andFilterWhere(['=', 'community_id', $p['community_id']]);
        return $m;
    }

    // 共享车位 列表
    public function listSpace($p)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : '1';
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : '10';

        $totals = self::searchSpace($p)->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::searchSpace($p)
            ->offset(($p['page'] - 1) * $p['rows'])
            ->limit($p['rows'])
            ->orderBy('id desc')->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $room = explode($v['community_name'], $v['room_name']);
                $roomInfo = !empty($room[1]) ? $room[1] : $room[0];
                $v['room_name'] = $v['community_name'].$roomInfo;
                $v['shared_at'] = date('Y/m/d', $v['shared_at']);
                $v['start_at'] = date('H:i', $v['start_at']);
                $v['end_at'] = date('H:i', $v['end_at']);
                $v['statusMsg'] = self::$status[$v['status']]['name'];
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 共享车位 列表参数过滤
    private static function searchSpace($p)
    {
        $m = PsParkSpace::find()
            ->filterWhere(['=', 'status', $p['status']])
            ->andFilterWhere(['like', 'park_space', $p['park_space']])
            ->andFilterWhere(['in', 'community_id', $p['communityList']])
            ->andFilterWhere(['=', 'community_id', $p['community_id']]);
        return $m;
    }
}
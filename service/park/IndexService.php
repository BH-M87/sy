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

use app\models\PsParkReservation;
use app\models\PsParkSpace;
use app\models\PsParkBlack;
use app\models\PsParkBreakPromise;

class IndexService extends BaseService
{
    // 首页 列表
    public function index($p)
    {
        if ($p['status'] == 2) { // 已预约+使用中
            $p['status'] = [2,3];
        } else { // 待预约
            $p['status'] = [1];
        }

        switch ($p['type']) {
            case '2': // 明天
                $m = self::list($p, 2);
                break;
            case '3':
                $m = self::list($p, 3);
                break;
            case '4':
                $m = self::list($p, 4);
                break;
            case '5':
                $m = self::list($p, 5);
                break;
            case '6':
                $m = self::list($p, 6);
                break;
            case '7': // 将来
                $m = self::list($p, 7);
                break;
            default: // 今天
                $m = self::list($p, 1);
                break;
        }

        // 头部时间筛选
        $p['status'] = [1,2,3];
        $dt = self::dayTime($p);

        $m['timeList'] = [
            ['name' => $dt['time']['time_1'], 'num' => $dt['day']['day_1']['totals'], 'type' => '1'],
            ['name' => $dt['time']['time_2'], 'num' => $dt['day']['day_2']['totals'], 'type' => '2'],
            ['name' => $dt['time']['time_3'], 'num' => $dt['day']['day_3']['totals'], 'type' => '3'],
            ['name' => $dt['time']['time_4'], 'num' => $dt['day']['day_4']['totals'], 'type' => '4'],
            ['name' => $dt['time']['time_5'], 'num' => $dt['day']['day_5']['totals'], 'type' => '5'],
            ['name' => $dt['time']['time_6'], 'num' => $dt['day']['day_6']['totals'], 'type' => '6'],
            ['name' => $dt['time']['time_7'], 'num' => $dt['day']['day_7']['totals'], 'type' => '7']
        ];

        return $m;
    }

    public function list($p, $type)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : '1';
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : '10';

        switch ($type) {
            case '2': // 明天
                $p['shared_at'] = strtotime(date('Y-m-d').'00:00:00')+ 86400;
                break;
            case '3': // 后天
                $p['shared_at'] = strtotime(date('Y-m-d').'00:00:00') + 86400 * 2;
                break;
            case '4': // 大后天
                $p['shared_at'] = strtotime(date('Y-m-d').'00:00:00') + 86400 * 3;
                break;
            case '5': // 大大后天
                $p['shared_at'] = strtotime(date('Y-m-d').'00:00:00') + 86400 * 4;
                break;
            case '6': // 大大大后天
                $p['shared_at'] = strtotime(date('Y-m-d').'00:00:00') + 86400 * 5;
                break;
            case '7': // 将来
                $p['start_at'] = strtotime(date('Y-m-d').'00:00:00') + 86400 * 6;
                break;
            default: // 今天
                $p['shared_at'] = strtotime(date('Y-m-d').'00:00:00');
                break;
        }

        $totals = self::searchSpace($p)->count();

        $list = self::searchSpace($p)
            ->select('id, status, shared_at, start_at, end_at, community_id, community_name, park_space')
            ->offset(($p['page'] - 1) * $p['rows'])
            ->limit($p['rows'])
            ->orderBy('status asc, id desc')->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                if ($v['status'] == 1) {
                    $v['statusMsg'] = '待预约';
                } else if ($v['status'] == 2) {
                    $v['statusMsg'] = '已预约';
                } else if ($v['status'] == 3) {
                    $v['statusMsg'] = '使用中';
                } else {
                    $v['statusMsg'] = '已关闭';
                }
                $v['shared_at'] = date('Y-m-d', $v['shared_at']);
                $v['start_at'] = date('H:i', $v['start_at']);
                $v['end_at'] = date('H:i', $v['end_at']);
            }
        }

        // 查已预约和待预约的总数
        $p['status'] = [1,2,3];
        $listStatus = self::searchSpace($p)
            ->select('status, count(id) total')
            ->groupBy('status')->asArray()->all();

        $reserved = $free = 0;
        if (!empty($listStatus)) {
            foreach ($listStatus as $key => $val) {
                if ($val['status'] == 1) {
                    $free += $val['total'];
                } else {
                    $reserved += $val['total'];
                }
            }
        }

        $lock_at = PsParkBreakPromise::find()->select('lock_at')->where(['user_id' => $p['user_id']])->scalar();
        $black = PsParkBlack::find()->where(['user_id' => $p['user_id']])->one();

        return [
            'list' => $list, 'totals' => $totals, 'reserved' => $reserved, 'free' => $free, 
            'lock_at' => $lock_at > time() ? date('Y-m-d H:i', $lock_at) : '',
            'is_black' => !empty($black) ? 1 : 2,
        ];
    }

    // 共享车位 列表参数过滤
    private static function searchSpace($p)
    {
        $m = PsParkSpace::find()
            ->andfilterWhere(['community_id' => $p['community_id']])
            ->andfilterWhere(['in', 'status', $p['status']])
            ->andfilterWhere(['=', 'shared_at', $p['shared_at']])
            ->andfilterWhere(['>=', 'shared_at', $p['start_at']]);
        return $m;
    }

    public function dayTime($p)
    {
        $w = ["周日", "周一", "周二", "周三", "周四", "周五", "周六"];

        $arr['day']['day_1'] = self::list($p, 1);
        $arr['day']['day_2'] = self::list($p, 2);
        $arr['day']['day_3'] = self::list($p, 3);
        $arr['day']['day_4'] = self::list($p, 4);
        $arr['day']['day_5'] = self::list($p, 5);
        $arr['day']['day_6'] = self::list($p, 6);
        $arr['day']['day_7'] = self::list($p, 7);

        $arr['time']['time_1'] = '今天';
        $arr['time']['time_2'] = $w[date("w", time() + 86400*1)];
        $arr['time']['time_3'] = $w[date("w", time() + 86400*2)];
        $arr['time']['time_4'] = $w[date("w", time() + 86400*3)];
        $arr['time']['time_5'] = $w[date("w", time() + 86400*4)];
        $arr['time']['time_6'] = $w[date("w", time() + 86400*5)];
        $arr['time']['time_7'] = '将来';

        return $arr;
    }

    // 预约历史记录 列表
    public function listHistory($p)
    {
        $list = self::searchHistory($p)->select('distinct(car_number)')
            ->orderBy('id desc')->asArray()->all();

        return ['list' => $list];
    }

    // 预约历史记录 列表参数过滤
    private static function searchHistory($p)
    {
        $m = PsParkReservation::find()
            ->filterWhere(['=', 'appointment_id', $p['user_id']])
            ->andFilterWhere(['like', 'room_name', $p['room_name']])
            ->andFilterWhere(['=', 'community_id', $p['community_id']]);
        return $m;
    }
}
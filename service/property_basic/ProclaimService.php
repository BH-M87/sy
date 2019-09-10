<?php
namespace service\property_basic;

use Yii;
use yii\base\Exception;

use service\BaseService;
use service\rbac\OperateService;

use common\core\PsCommon;

use app\models\PsProclaim;
use app\models\StScheduling;
use app\models\PsLabelsRela;
use app\models\PsCommunityRoominfo;
use app\models\PsRoomUser;
use app\models\ParkingCars;
use app\models\PsLifeBroadcastRecord;

class ProclaimService extends BaseService
{
    public function home($p)
    {
        // 值班人员
        $week = date("w") == 0 ? 7 : date("w");
        $r['schedule'] = StScheduling::getList(['day_type' => $week]);

        $user_num = PsRoomUser::getCount(['community_id' => $p['community_id']]);
        $flow = PsRoomUser::getCount(['community_id' => $p['community_id'], 'identity_type' => 3, 'time_end' => 0]);
        $user_label[] = ['name' => '流动人口', 'total' => $flow, 'rate' => round($flow / $user_num, 2)];
        $user_label[] = ['name' => '常住人口', 'total' => $user_num - $flow, 'rate' => round(($user_num - $flow) / $user_num, 2)];
        $r['areaBase']['user_label'] = $user_label;
        $r['areaBase']['user_num'] = $user_num;
        
        $r['areaBase']['room_label'] = PsLabelsRela::rate(['label_attribute' => 1], 4);
        $r['areaBase']['room_num'] = PsCommunityRoominfo::find()
            ->andFilterWhere(['=', 'community_id', $p['community_id']])->count();
        
        $car_num = ParkingCars::find()
            ->andFilterWhere(['=', 'community_id', $p['community_id']])->count();
        $car_label[] = ['name' => '机动车', 'total' => $car_num, 'rate' => round(($car_num) / $car_num, 2)];
        $r['areaBase']['car_label'] = $car_label;
        $r['areaBase']['car_num'] = $car_num;
        // 重点人员
        $r['carePeople'] = PsLabelsRela::rate(['label_type' => 2]);
        // 关怀人群
        $r['keyPeople'] = PsLabelsRela::rate(['label_type' => 3]);

        return $this->success($r);
    }

    // 公告 新增
    public function add($p, $scenario = 'add')
    {
        return $this->_saveProclaim($p, $scenario);
    }

    // 公告 编辑
    public function edit($p, $scenario = 'edit')
    {
        return $this->_saveProclaim($p, $scenario);
    }

    // 新增编辑 公告
    public function _saveProclaim($p, $scenario)
    {
        $m = new PsProclaim();

        if (!empty($p['id'])) {
            $m = PsProclaim::getOne($p);

            if (!$m) {
                return $this->failed("数据不存在");
            }

            if ($m->is_show == 2) {
                return $this->failed("数据已上线不可编辑");
            }
        }
        
        $data = PsCommon::validParamArr($m, $p, $scenario);

        if (empty($data['status'])) {
            return $this->failed($data['errorMsg']);
        }

        if ($p['is_top'] == 2) {
            $m->top_at = time();
        }

        if ($m->save()) {
            return $this->success($m->id);
        }
    }

    // 公告列表
    public function list($p)
    {
        $m = PsProclaim::getList($p);

        return $this->success($m);
    }

    // 公告是否显示
    public function editShow($p)
    {
        $m = PsProclaim::getOne($p);
        
        if (!$m) {
            return $this->failed("数据不存在");
        }

        if ($m->is_show == 1) {
            $m->is_show = 2; // 置顶
            $m->show_at = time();
        } else {
            $m->is_show = 1; // 不置顶
            $m->show_at = 0;
        }

        if ($m->save()) {
            return $this->success();
        }
        return $this->failed();
    }

    // 公告是否置顶
    public function editTop($p)
    {
        $m = PsProclaim::getOne($p);

        if (!$m) {
            return $this->failed("数据不存在");
        }

        if ($m->is_top == 1) {
            $m->is_top = 2; // 置顶
            $m->top_at = time();
        } else {
            $m->is_top = 1; // 不置顶
            $m->top_at = 0;
        }

        if ($m->save()) {
            return $this->success();
        }
        return $this->failed();
    }

    // 公告详情 系统公告详情
    public function show($p)
    {
        if ($p['msg_type'] == 2) { // 系统公告推送到小程序
            $m = PsLifeBroadcastRecord::find()->alias('A')
                ->leftJoin('ps_life_broadcast B', 'A.broadcast_id = B.id')
                ->select('A.id, B.title, B.content, B.image as img_url, A.send_at as show_at, B.type as proclaim_cate')
                ->where(['A.id' => $p['id']])
                ->asArray()->one();

            $m['show_at'] = !empty($m['show_at']) ? date('Y-m-d H:i', $m['show_at']) : '';
            $m['proclaim_cate_desc'] = !empty($m['proclaim_cate']) ? PsProclaim::$proclaim_cate[$m['proclaim_cate']] : '';
        } else {
            $m = PsProclaim::getOne($p);

            if (!$m) {
                return $this->failed("数据不存在");
            }

            $m = $m->toArray();

            $m['create_at'] = !empty($m['create_at']) ? date("Y-m-d H:i", $m['create_at']) : '';
            $m['show_at'] = !empty($m['show_at']) ? date("Y-m-d H:i", $m['show_at']) : '';
            $m['top_at'] = !empty($m['top_at']) ? date("Y-m-d H:i", $m['top_at']) : '';
            $m['proclaim_type_desc'] = PsProclaim::$proclaim_type[$m['proclaim_type']];
            $m['proclaim_cate_desc'] = PsProclaim::$proclaim_cate[$m['proclaim_cate']];
            $m['is_top_desc'] = $m['is_top'] == 2 ? '是' : '否';
            $m['receive'] = [];
        }

        return $this->success($m);
    }

    // 公告删除
    public function del($p)
    {
        $m = PsProclaim::getOne($p);

        if (!$m) {
            return $this->failed("数据不存在");
        }
    
        if ($m->is_show == 2) {
            return $this->failed("数据已上线不可删除");
        }

        if ($m->delete()) {
            return $this->success();
        }   
    }

    // 我的消息列表 小区公告和系统公告
    public function news($p)
    {
        $p['type'] = !empty($p['type']) ? $p['type'] : 0;
        $proclaim = $news = [];

        if ($p['type'] != 1) { // 系统公共 运营后台添加
            $news = PsLifeBroadcastRecord::find()->alias('A')
                ->leftJoin('ps_life_broadcast B', 'A.broadcast_id = B.id')
                ->select(['A.id', 'B.title', 'B.content', 'B.type as cate', 'B.image as img_url', 'A.send_at as show_at'])
                ->where(['A.community_id' => $p['community_id'], 'A.status' => 1])
                ->andWhere(['=', 'B.push_type', 2])->orderBy('A.send_at desc')->asArray()->all();

            if (!empty($news)) {
                foreach ($news as &$n) {
                    $n['show_at'] = !empty($n['show_at']) ? date('Y-m-d H:i', $n['show_at']) : '';
                    $n['msg_type'] = 2;
                    $n['msg_type_desc'] = '系统公告';
                }
            }
        }
      
        if ($p['type'] != 2) { // 物业公共 物业后台添加 小区公告
            $proclaim = PsProclaim::find()->select('id, title, content, img_url, show_at, proclaim_type as type, proclaim_cate as cate')->where(['community_id' => $p['community_id'], 'is_show' => 2])
                ->orderBy('show_at desc')->asArray()->all();
            if (!empty($proclaim)) {
                foreach ($proclaim as &$p) {
                    $p['show_at'] = !empty($p['show_at']) ? date('Y-m-d H:i', $p['show_at']) : '';
                    $p['type_desc'] = PsProclaim::$proclaim_type[$p['type']];
                    $p['msg_type'] = 1;
                    $p['msg_type_desc'] = '小区通知';
                }
            }
        }

        $r = array_merge($news, $proclaim);
        // 根据显示时间倒序排序
        //$arr1 = array_map(create_function('$n', 'return $n["show_at"];'), $r);
        //array_multisort($arr1, SORT_DESC, $r);

        return $this->success($r);
    }
}
<?php
namespace service\inspect;

use Yii;
use yii\db\Query;
use yii\base\Exception;

use common\MyException;
use common\core\F;
use common\core\PsCommon;

use service\BaseService;
use service\property_basic\JavaService;
use service\common\QrcodeService;

use app\models\PsInspectLinePoint;
use app\models\PsInspectPoint;
use app\models\PsInspectDevice;
use app\models\PsInspectLine;
use app\models\PsInspectRecord;
use app\models\PsInspectRecordPoint;

class PointService extends BaseService
{
    public static $recordStatus = [
        "1" => "待巡检",
        "2" => "巡检中",
        "3" => "已完成",
        "4" => "已关闭",
    ];

    public static $runStatus = [
        "1" => "逾期",
        "2" => "旷巡",
        "3" => "正常"
    ];

    public static $deviceStatus = [
        "1" => "正常",
        "2" => "异常",
        "0" => "-"
    ];

    /**  物业后台接口 start */

    //巡检点新增
    public function add($p, $userInfo)
    {
        self::checkCommon($p, $userInfo, 'add');
    }

    //巡检点编辑
    public function edit($p, $userInfo)
    {
        self::checkCommon($p, $userInfo, 'update');
    }

    protected static function checkCommon($p, $userInfo = [], $scenario = 'add')
    {
        $model = new PsInspectPoint();
        $p = $model->validParamArr($p, $scenario);
        
        if ($scenario == 'update') {
            $model = PsInspectPoint::findOne($p['id']);
            if (empty($model)) {
                throw new MyException('巡检点不存在!');
            }
            $deviceId = $model->deviceId;
        } else {
            $p['createAt'] = time();
        }

        if (in_array('2', $p['type'])) { // 当选择需要定位时判断是否有经纬度
            if (empty($p['location']) || empty($p['lon']) || empty($p['lat'])) {
                throw new MyException('定位经纬度与位置不能为空!');
            }
        } else { // 不需要定位
            $p['lat'] = '';
            $p['lon'] = '';
            $p['location'] = '';
        }

        if (in_array('3', $p['type'])) {
            if (empty($p['deviceId'])) {
                throw new MyException('请选择智点名称!');
            }

            if ($deviceId != $p['deviceId']) {
                $device = PsInspectDevice::findOne($p['deviceId']);
                if (!$device || !empty($device->communityId)) {
                    throw new MyException('设备不存在!');
                }
            }
        } else {
            $p['deviceId'] = '0';
        }

        $p['type'] = implode(',', $p['type']);

        $point = PsInspectPoint::find()->where(['name' => $p['name'], 'communityId' => $p['communityId']])
            ->andFilterWhere(['!=', 'id', $p['id']])->one();
        if (!empty($point)) {
            throw new MyException('巡检点已存在!');
        }

        $model->setAttributes($p, false);
        if ($model->save()) { // 保存新增数据
            self::createQrcode($model, $model->id);
            PsInspectDevice::updateAll(['communityId' => $p['communityId']], ['id' => $p['deviceId']]);

            if ($deviceId != $p['deviceId'] && $scenario == 'update') {
                PsInspectDevice::updateAll(['communityId' => ''], ['id' => $deviceId]);
            }
            return true;
        } else {
            throw new MyException('操作失败');
        }
    }

    // 生成二维码图片
    private static function createQrcode($model, $id)
    {
        $savePath = F::imagePath('inspect');
        $logo = Yii::$app->basePath . '/web/img/lyllogo.png'; // 二维码中间的logo
        $url = Yii::$app->getModule('property')->params['ding_web_host'] . '#/scanList?type=scan&id=' . $id;
        $imgUrl = QrcodeService::service()->generateCommCodeImage($savePath, $url, $id, $logo, $model); // 生成二维码图片
        PsInspectPoint::updateAll(['codeImg' => $imgUrl], ['id' => $id]);
    }

    // 巡检点详情
    public function view($p)
    {
        if (empty($p['id'])) {
            throw new MyException('巡检点id不能为空');
        }

        $r = PsInspectPoint::find()->where(['id' => $p['id']])->asArray()->one();
        if (!empty($r)) {
            $r['type'] = explode(',', $r['type']);
            $r['deviceName'] = PsInspectDevice::findOne($r['deviceId'])->name;
            $community = JavaService::service()->communityDetail(['token' => $p['token'], 'id' => $r['communityId']]);
            $r['communityName'] = $community['communityName'];
            return $r;
        }

        throw new MyException('巡检点不存在!');
    }

    // 巡检点删除
    public function del($p, $userInfo)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (is_array($p['id']) && !empty($p['id'])) {
                foreach ($p['id'] as $k => $v) {
                    if (empty($p['id'])) {
                        throw new MyException('巡检点id不能为空');
                    }

                    if (PsInspectLinePoint::find()->where(['pointId' => $p['id']])->exists()) {
                        throw new MyException('请先修改巡检线路');
                    }

                    PsInspectPoint::deleteAll(['id' => $p['id']]);
                }

                $transaction->commit();
                return true;
            } else {
                throw new MyException('巡检点id不能为空');
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            return $e->getMessage();
        }

    }

    // 巡检点列表
    public function pointList($p)
    {
        $totals = self::pointSearch($p,'id')->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::pointSearch($p)
            ->offset(($p['page'] - 1) * $p['rows'])
            ->limit($p['rows'])
            ->orderBy('id desc')->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $community = JavaService::service()->communityDetail(['token' => $p['token'], 'id' => $v['communityId']]);
                $v['communityName'] = $community['communityName'];
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 巡检点下拉
    public function getPoint($p)
    {
        $checked = PsCommon::get($p, 'checked');
        $m = PsInspectPoint::find()->select('id, name')
            ->filterWhere(['=', 'communityId', PsCommon::get($p, 'communityId')])->asArray()->all();
        if (!empty($m)) {
            foreach ($m as $k => $v) {
                $point = PsInspectLinePoint::find()
                    ->filterWhere(['=', 'lineId', PsCommon::get($p, 'lineId') ? $p['lineId'] : 0])
                    ->andFilterWhere(['=', 'pointId', $v['id']])->one();

                if (!empty($checked) && empty($point)) {
                    unset($m[$k]);
                }
            }
        }
        return array_values($m);
    }

    //下载二维码
    public function downloadCode($params, $systemType)
    {
        $data = $this->view($params);

        if (empty($data['data']['code_image'])) {
            return PsCommon::responseFailed("二维码不存在！");
        }

        $savePath = F::imagePath('inspect'); // 图片保存的位置
        $img_name = $data['data']['id'] . '.png';
        $fileName = $data['data']['name'] . '.png';

        if (!file_exists($savePath . $img_name)) { // 文件不存在，去七牛下载
            F::curlImage($data['data']['code_image'], F::imagePath('inspect'), $img_name);
        }

        if (!file_exists($savePath . $img_name)) { // 下载未成功
            return PsCommon::responseFailed('二维码不存在');
        }

        $downUrl = F::downloadUrl($systemType, 'inspect/' . $img_name, 'qrcode', $fileName);

        return ['down_url' => $downUrl];
    }

    // 设备列表
    public function listDevice($p)
    {
        $query = new Query();
        $query->from('ps_inspect_device')
            ->andfilterWhere(['communityId' => $p['communityId']])
            ->andfilterWhere(['like', 'name', $p['name']])
            ->andfilterWhere(['like', 'deviceNo', $p['deviceNo']]);

        $r['totals'] = $query->count();

        $m = $query->offset(($p['page'] - 1) * $p['rows'])->limit($p['rows'])->orderBy('id desc')->createCommand()->queryAll();

        if (!empty($m)) {
            foreach ($m as $k => &$v) {
                $v['communityName'] = '';
                if (!empty($v['communityId'])) {
                    $community = JavaService::service()->communityDetail(['token' => $p['token'], 'id' => $v['communityId']]);
                    $v['communityName'] = $community['communityName'];
                }
                $point = PsInspectPoint::find()->select('name')->where(['=', 'deviceId', $v['id']])->scalar();
                $v['point'] = $point['name'] ?? '';
            }
        }
        
        $r['list'] = $m;

        return $r;
    }

    // 设备名称下拉列表
    public function deviceDropDown($p)
    {
        $query = new Query();
        $query->from('ps_inspect_device')->select('id, name')
            ->andfilterWhere(['=', 'companyId', $p['corp_id']])
            ->andfilterWhere(['<=', 'communityId', 0]);

        $m = $query->orderBy('id desc')->createCommand()->queryAll();

        return $m;
    }

    public function dingList($p, $type)
    {
        switch ($type) {
            case '1':
                $end = strtotime(date('Y-m-d').'00:00:00') - 1;
                break;
            case '3':
                $start = strtotime(date('Y-m-d').'00:00:00') + 86400;
                $end = strtotime(date('Y-m-d').'23:59:59') + 86400;
                break;
            case '4':
                $start = strtotime(date('Y-m-d').'00:00:00') + 86400 * 2;
                $end = strtotime(date('Y-m-d').'23:59:59') + 86400 * 2;
                break;
            case '5':
                $start = strtotime(date('Y-m-d').'00:00:00') + 86400 * 3;
                $end = strtotime(date('Y-m-d').'23:59:59') + 86400 * 3;
                break;
            case '6':
                $start = strtotime(date('Y-m-d').'00:00:00') + 86400 * 4;
                $end = strtotime(date('Y-m-d').'23:59:59') + 86400 * 4;
                break;
            case '7':
                $start = strtotime(date('Y-m-d').'00:00:00') + 86400 * 5;
                break;
            default:
                $start = strtotime(date('Y-m-d').'00:00:00');
                $end = strtotime(date('Y-m-d').'23:59:59');
                break;
        }

        $query = new Query();
        $query->from('ps_inspect_record')->where("1=1")
            ->andfilterWhere(['user_id' => $p['user_id']])
            ->andfilterWhere(['status' => [1,2]])
            ->andfilterWhere(['and', 
                ['>=', 'check_start_at', $start], 
                ['<', 'check_end_at', $end]
            ]);

        $r['totals'] = $query->count();

        $query->select('id, status, run_status, line_name, point_count, finish_count, check_start_at, check_end_at');
        $query->orderBy('id desc');

        $query->offset(($p['page'] - 1) * $p['rows'])->limit($p['rows']);

        $m = $query->createCommand()->queryAll();
        foreach ($m as $k => &$v) {
            $v['status_msg'] = $v['status'] == 1 ? '代巡检' : '巡检中';

            if ($v['run_status'] == 1) {
                $v['run_status_msg'] = '逾期';
            } else if ($v['run_status'] == 2) {
                $v['run_status_msg'] = '旷巡';
            } else {
                $v['run_status_msg'] = '正常';
            }

            $v['img'] = PsInspectLine::findOne($v['line_id'])->img;

            $v['check_at'] = date('Y/m/d H:i', $v['check_start_at']) . '-' . date('H:i', $v['check_end_at']);
        }

        $r['list'] = $m;

        return $r;
    }

    public function dayTime($p)
    {
        $w = ["周日", "周一", "周二", "周三", "周四", "周五", "周六"];

        $arr['day']['day_1'] = self::dingList($p, 1);
        $arr['day']['day_2'] = self::dingList($p, 2);
        $arr['day']['day_3'] = self::dingList($p, 3);
        $arr['day']['day_4'] = self::dingList($p, 4);
        $arr['day']['day_5'] = self::dingList($p, 5);
        $arr['day']['day_6'] = self::dingList($p, 6);
        $arr['day']['day_7'] = self::dingList($p, 7);

        $arr['time']['time_1'] = '过去';
        $arr['time']['time_2'] = '今天';
        $arr['time']['time_3'] = $w[date("w", time() + 86400*1)];
        $arr['time']['time_4'] = $w[date("w", time() + 86400*2)];
        $arr['time']['time_5'] = $w[date("w", time() + 86400*3)];
        $arr['time']['time_6'] = $w[date("w", time() + 86400*4)];
        $arr['time']['time_7'] = '将来';

        return $arr;
    }

    // 代办列表
    public function taskList($p)
    {
        $dt = self::dayTime($p);

        switch ($p['type']) {
            case '1':
                $m = $dt['day']['day_1'];
                $r['time'] = $dt['time']['time_1'];
                break;
            case '3':
                $m = $dt['day']['day_3'];
                $r['time'] = $dt['time']['time_3'];
                break;
            case '4':
                $m = $dt['day']['day_4'];
                $r['time'] = $dt['time']['time_4'];
                break;
            case '5':
                $m = $dt['day']['day_5'];
                $r['time'] = $dt['time']['time_5'];
                break;
            case '6':
                $m = $dt['day']['day_6'];
                $r['time'] = $dt['time']['time_6'];
                break;
            case '7':
                $m = $dt['day']['day_7'];
                $r['time'] = $dt['time']['time_7'];
                break;
            default:
                $m = $dt['day']['day_2'];
                $r['time'] = $dt['time']['time_2'];
                break;
        }

        $r['timeList'] = [
            ['name' => $dt['time']['time_1'], 'num' => $dt['day']['day_1']['totals'], 'type' => '1'],
            ['name' => $dt['time']['time_2'], 'num' => $dt['day']['day_2']['totals'], 'type' => '2'],
            ['name' => $dt['time']['time_3'], 'num' => $dt['day']['day_3']['totals'], 'type' => '3'],
            ['name' => $dt['time']['time_4'], 'num' => $dt['day']['day_4']['totals'], 'type' => '4'],
            ['name' => $dt['time']['time_5'], 'num' => $dt['day']['day_5']['totals'], 'type' => '5'],
            ['name' => $dt['time']['time_6'], 'num' => $dt['day']['day_6']['totals'], 'type' => '6'],
            ['name' => $dt['time']['time_7'], 'num' => $dt['day']['day_7']['totals'], 'type' => '7']
        ];

        $r['list'] = $m['list'];
        $r['totals'] = $m['totals'];

        return $r;
    }

    // 详情
    public function taskShow($p)
    {
        if (empty($p['id'])) {
            throw new MyException('id不能为空');
        }

        $r = PsInspectRecord::find()
            ->where(['id' => $p['id'], 'user_id' => $p['user_id']])
            ->select('id, task_name, status, line_name, check_start_at, check_end_at, point_count, finish_count, run_status')
            ->asArray()->one();
        if (!empty($r)) {
            $r['check_start_at'] = !empty($r['check_start_at']) ? date('Y/m/d H:i', $r['check_start_at']) : '???';
            $r['check_end_at'] = !empty($r['check_end_at']) ? date('H:i', $r['check_end_at']) : '???';
            $r['check_at'] = $r['check_start_at'] . '-' . $r['check_end_at']; // 巡检时间
            $r['status_msg'] = !empty($r['status']) ? self::$recordStatus[$r['status']] : "未知";
            $r['run_status_msg'] = !empty($r['run_status']) ? self::$runStatus[$r['run_status']] : "未知";
            // 获取任务下的巡检点
            $pointList = PsInspectRecordPoint::find()
                ->where(['record_id' => $p['id']])
                ->select('id, finish_at, device_status, status, point_name, location, point_id, record_note, imgs, type, device_status')
                ->asArray()->all();

            if (!empty($pointList)) {
                foreach ($pointList as &$v) {
                    $pointInfo = PsInspectPoint::findOne($v['point_id']);
                    $v['finish_at'] = !empty($v['finish_at']) ? date("Y-m-d H:i", $v['finish_at']) : '';
                    $v['device_status_msg'] = self::$deviceStatus[$v['device_status']];
                    $v['imgs'] = explode(',', $v['imgs']);
                    $v['type'] = explode(',', $v['type']);
                }
            }
            $r['pointList'] = $pointList;

            return $r;
        }

        throw new MyException('任务不存在');
    }

    /**  物业后台接口 end */

    /**  钉钉接口 start */

    //巡检点列表
    public function getList($params)
    {
        $page = !empty($params['page']) ? $params['page'] : 1;
        $rows = !empty($params['rows']) ? $params['rows'] : 5;
        //列表

        $query = self::pointSearch($params,['point.id', 'comm.name as community_name', 'point.name', 'point.device_name', 'point.need_location', 'point.need_photo', 'point.code_image']);

        $list = $query
            ->alias('point')
            ->andwhere(['community_id' => $params['communitys']])
            ->leftJoin("ps_community comm", "comm.id=point.community_id")
            ->orderBy('point.id desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)->asArray()->all();
        return ['list' => $list];
    }

    //设备列表
    public function getDeviceList($params)
    {
        $arrList = [];
        //获取分类
        $resultAll = PsDeviceCategory::find()->select(['id', 'name'])->andWhere(['or', ['=', 'community_id', $params['community_id']], ['=', 'community_id', 0]])->asArray()->all();
        if (!empty($resultAll)) {
            foreach ($resultAll as $result) {
                $deviceAll = PsDevice::find()->alias("device")
                    ->where(['device.community_id' => $params['community_id'], 'category_id' => $result['id']])
                    ->select(['id', 'name'])
                    ->asArray()->all();
                if (!empty($deviceAll)) {
                    $arr['category_id'] = $result['id'];
                    $arr['category_name'] = $result['name'];
                    //说明需要查询巡检点选择的设备
                    $deviceList = [];
                    foreach ($deviceAll as $device) {
                        $device['is_checked'] = 0;
                        if (!empty($params['point_id'])) {
                            $point = PsInspectPoint::find()->where(['id' => $params['point_id']])->asArray()->one();
                            if (!empty($point) && $point['device_id'] == $device['id']) {
                                $device['is_checked'] = 1;//说明选择了当前设备
                            }
                        }
                        $deviceList[] = $device;
                    }
                    $arr['device_list'] = $deviceList;
                    $arrList[] = $arr;
                }
            }
            return $arrList;
        }
        throw new MyException('设备不存在！');
    }

    //巡检列表-线路新增页面使用
    public function getPointList($reqArr)
    {
        $query = self::pointSearch(['community_id' => $reqArr['community_id']],'point.id,point.name');
        $arr = $query->alias('point')->asArray()->all();
        //说明需要查询线路对应选择的巡检点
        if (!empty($reqArr['line_id'])) {
            $sel_point = PsInspectPoint::find()->alias("point")
                ->where(['community_id' => $reqArr['community_id'], "line_point.line_id" => $reqArr['line_id']])
                ->select(['point.id', 'point.name'])
                ->leftJoin("ps_inspect_line_point line_point", "line_point.point_id=point.id")
                ->asArray()->all();
        }else{
            $sel_point = [];
        }
        return ['list' => $arr, 'sel_list' => $sel_point];
    }

    /**  钉钉接口 end */

    /**  公共接口 start */

    // 列表参数过滤
    private static function pointSearch($p, $filter = '')
    {
        $filter = $filter ?? "*";
        $m = PsInspectPoint::find()
            ->select($filter)
            ->filterWhere(['like', 'name', PsCommon::get($p, 'name')])
            ->andFilterWhere(['=', 'communityId', PsCommon::get($p, 'communityId')])
            ->andFilterWhere(['=', 'deviceId', PsCommon::get($p, 'deviceId')]);
        return $m;
    }


    //自动验证小区权限
    public function validaCommunit($params)
    {
        $communitys = $params['communitys'];
        $communityId = !empty($params['community_id']) ? $params['community_id'] : 0;
        if (!$communityId) {
            throw new MyException('小区id不能为空！');
        }
        if (!in_array($communityId, $communitys)) {
            throw new MyException('无此小区权限！');
        }
        return true;
    }
    /**  公共接口 end */
}
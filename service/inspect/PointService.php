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
use service\common\ExcelService;

use app\models\PsInspectLinePoint;
use app\models\PsInspectPoint;
use app\models\PsInspectDevice;
use app\models\PsInspectLine;
use app\models\PsInspectRecord;
use app\models\PsInspectRecordPoint;

class PointService extends BaseService
{
    public static $recordStatus = ["1" => "待巡检", "2" => "巡检中", "3" => "已完成", "4" => "已关闭"];
    public static $runStatus = ["1" => "逾期", "2" => "旷巡", "3" => "正常"];
    public static $deviceStatus = ["1" => "正常", "2" => "异常", "0" => "-"];
    public static $pointType = ["1" => "扫码", "2" => "定位", "3" => "智点", "4" => "拍照"];

    // ----------------------------------     后端接口     ------------------------------

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
        $p['deviceNo'] = $p['deviceId'];
        unset($p['deviceId']);
        $model = new PsInspectPoint();
        $p = $model->validParamArr($p, $scenario);
        
        if ($scenario == 'update') {
            $model = PsInspectPoint::findOne($p['id']);
            if (empty($model)) {
                throw new MyException('巡检点不存在!');
            }
            $deviceNo = $model->deviceNo;
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
            if (empty($p['deviceNo'])) {
                throw new MyException('请选择智点名称!');
            }

            if ($deviceNo != $p['deviceNo']) {
                $device = PsInspectDevice::find()->where(['deviceNo' => $p['deviceNo']])->one();
                if (empty($device)) {
                    throw new MyException('设备不存在!');
                }

                $inspectPoint = PsInspectPoint::find()->where(['deviceNo' => $p['deviceNo']])->one();
                if (!empty($inspectPoint)) {
                    throw new MyException('设备已经被绑定!');
                }
            }
        } else {
            $p['deviceNo'] = '';
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
            $r['deviceId'] = $r['deviceNo'] ?? '';
            $r['deviceName'] = PsInspectDevice::findOne($r['deviceNo'])->name;
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
                    $model = PsInspectPoint::findOne($v);
                    if (empty($model)) {
                        throw new MyException('巡检点不存在');
                    }

                    if (PsInspectLinePoint::find()->where(['pointId' => $v])->exists()) {
                        throw new MyException('请先修改巡检线路');
                    }

                    PsInspectPoint::deleteAll(['id' => $v]);
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

    // 导出二维码图片
    public function downloadCode($p)
    {
        if (!empty($p['id']) && is_array($p['id'])) {
            $time = time();
            $savePath = Yii::$app->basePath . '/web/store/zip/inspect/' . $time . '/';
            foreach ($p['id'] as $id) {
                $m = PsInspectPoint::findOne($id);
                if (empty($m->codeImg)) {
                    return PsCommon::responseFailed("二维码不存在！");
                }

                $img_name = $m->name . '.png';

                if (!file_exists($savePath . $img_name)) { // 文件不存在，去七牛下载
                    F::curlImage($m->codeImg, $savePath, $img_name);
                }

                if (!file_exists($savePath . $img_name)) { // 下载未成功
                    return PsCommon::responseFailed('二维码不存在');
                }
            }

            $path = $savePath . 'qrcode.zip';
            ExcelService::service()->addZip($savePath, $path);

            $downUrl = F::downloadUrl('inspect/'.$time.'/qrcode.zip', 'zip');

            return ['down_url' => $downUrl];
        } else {
            return PsCommon::responseFailed("巡检点id必须是数组格式！");
        }
    }

    // 设备列表
    public function listDevice($p)
    {
        $query = new Query();
        $query->from('ps_inspect_device A')
            ->leftJoin('ps_inspect_point B', 'A.deviceNo = B.deviceNo')
            ->select('A.id, A.companyId, A.name, A.deviceType, A.deviceNo, B.communityId, B.name as point,A.dd_user_list')
            ->where(['A.is_del' => 1])
            ->andfilterWhere(['B.communityId' => $p['communityId']])
            ->andfilterWhere(['like', 'A.name', $p['name']])
            ->andfilterWhere(['like', 'A.deviceNo', $p['deviceNo']]);

        $r['totals'] = $query->count();

        $m = $query->offset(($p['page'] - 1) * $p['rows'])->limit($p['rows'])->orderBy('A.id desc')->createCommand()->queryAll();

        if (!empty($m)) {
            //获得钉钉绑定人员
            $service = new JavaService();
            $userResult = $service->bindUserList($p);
            $userArr = !empty($userResult['list'])?array_column($userResult['list'],'trueName','ddUserId'):'';
            foreach ($m as $k => &$v) {
                $v['communityName'] = '';
                if (!empty($v['communityId'])) {
                    $community = JavaService::service()->communityDetail(['token' => $p['token'], 'id' => $v['communityId']]);
                    $v['communityName'] = $community['communityName'];
                }
                $v['point'] = $v['point'] ?? '';
                $v['communityId'] = $v['communityId'] ?? '';
                $m[$k]['dd_user_list_msg'] = '';
                if(!empty($v['dd_user_list'])){
                    $userList = explode(',',$v['dd_user_list']);
                    foreach($userList as $value){
                        if($userArr[$value]){
                            $m[$k]['dd_user_list_msg'] .= $userArr[$value]."、";
                        }
                    }
                    $m[$k]['dd_user_list_msg'] = mb_substr($m[$k]['dd_user_list_msg'],0,-1);
                }
            }
        }
        
        $r['list'] = $m;

        return $r;
    }

    // 设备名称下拉列表
    public function deviceDropDown($p)
    {
        $deviceNo = PsInspectPoint::find()->select('deviceNo')->where(['>', 'deviceNo', '0'])->asArray()->all();
        $arr = array_column($deviceNo, 'deviceNo');

        $query = new Query();
        $query->from('ps_inspect_device')->select('deviceNo as id, name')
            ->where(['is_del' => 1])
            ->andfilterWhere(['=', 'companyId', $p['corp_id']])
            ->andfilterWhere(['not in', 'deviceNo', $arr]);

        $m = $query->orderBy('id desc')->createCommand()->queryAll();

        return $m;
    }

    // ----------------------------------     钉钉端接口     ------------------------------

    // 提交巡检点
    public function pointAdd($p)
    {
        if (empty($p['id'])) {
            throw new MyException('id不能为空');
        }

        $trans = \Yii::$app->getDb()->beginTransaction();
        try {
            $m = PsInspectRecordPoint::findOne($p['id']);
            if (empty($m)) {
                throw new MyException('任务不存在!');
            }

            if ($m['status'] == 3) {
                throw new MyException('任务已巡检!');
            }

            // 得到对应的巡检点信息
            $point = PsInspectPoint::findOne($m['point_id']);
            $typeArr = explode(',', $point['type']);

            if ($p['device_status'] == 1) { // 正常时需要判断 异常时可以不填
                if (in_array('1', $typeArr) && empty($p['sweepStatus'])) {
                    throw new MyException('该任务需扫码,扫码状态不能为空!');
                }

                if (in_array('2', $typeArr) && (empty($p['lat']) || empty($p['lon']) || empty($p['location']))) {
                    throw new MyException('该任务需定位,经纬度不能为空!');
                }

                if (in_array('3', $typeArr) && empty($p['deviceStatus'])) {
                    throw new MyException('该任务需智点,智点状态不能为空!');
                }

                if (in_array('4', $typeArr) && empty($p['picture'])) {
                    throw new MyException('该任务需拍照,图片不能为空!');
                }

                $type = explode(',', $info['type']);
                if (in_array('2', $typeArr)) { // 如果需要定位的话判断距离误差
                    $distance = F::getDistance($p['lat'], $p['lon'], $info['point_lat'], $info['point_lon']);
                    if ($distance > \Yii::$app->getModule('property')->params['distance']) {
                        throw new MyException('当前位置不可巡检！');
                    }
                }
            }

            if ($p['device_status'] == 2 && empty($p['record_note'])) { // 异常时必填
                throw new MyException('备注说明必填!');
            }

            $info = PsInspectRecordPoint::find()->alias("A")
                ->select('A.id, A.device_status, A.point_name, A.type, A.status, A.point_lat, A.point_lon')
                ->where(['A.id' => $p['id']])
                ->andWhere(['<=', 'B.check_start_at', time()])
                ->andWhere(['>=', 'B.check_end_at', time()])
                ->leftJoin("ps_inspect_record B", "B.id = A.record_id")
                ->asArray()->one();
            if (empty($info)) {
                throw new MyException('当前时间不可执行任务!');
            }

            $p['status'] = 2;
            $p['finish_at'] = time();
            $p['imgs'] = !empty($p['imgs']) ? implode(',', $p['imgs']) : '';

            $m->scenario = 'edit';  # 设置数据验证场景为 新增
            $m->load($p, '');   # 加载数据
            if ($m->validate()) {  # 验证数据
                if ($m->save()) {  # 保存新增数据
                    // 更新任务完成数,完成率
                    $record = PsInspectRecord::findOne($m->record_id);              
                    $pointInfo = PsInspectPoint::findOne(['id' => $m->point_id]);

                    if ($p['device_status'] == 2) { // 设备异常
                        PsInspectRecord::updateAll(['issue_count' => $record->issue_count + 1], ['id' => $m->record_id]);
                    }

                    PsInspectRecord::updateAll(['status' => 2, 'update_at' => time()], ['id' => $m->record_id]);

                    $finish_count = $record->finish_count + 1;
                    $finish_rate = ($finish_count / $record->point_count) * 100;
                    PsInspectRecord::updateAll(['finish_count' => $finish_count, 'finish_rate' => $finish_rate], ['id' => $m->record_id]);
                    
                    $trans->commit();

                    return $this->success([]);
                }
                throw new MyException($m->getErrors());
            } else {
                throw new MyException($m->getErrors());
            }
        } catch (\Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
    }

    // 打卡更新
    public function pointUpdate($p)
    {
        if (empty($p['id'])) {
            throw new MyException('id不能为空');
        }

        $trans = \Yii::$app->getDb()->beginTransaction();
        try {
            $m = PsInspectRecordPoint::findOne($p['id']);
            if (empty($m)) {
                throw new MyException('任务不存在!');
            }

            if ($m['status'] == 3) {
                throw new MyException('任务已巡检!');
            }

            // 得到对应的巡检点信息
            $point = PsInspectPoint::findOne($m['point_id']);
            $typeArr = explode(',', $point['type']);

            if ($p['device_status'] == 1) { // 正常时需要判断 异常时可以不填
                if (in_array('1', $typeArr) && empty($p['sweepStatus'])) {
                    throw new MyException('该任务需扫码,扫码状态不能为空!');
                }

                if (in_array('2', $typeArr) && (empty($p['lat']) || empty($p['lon']) || empty($p['location']))) {
                    throw new MyException('该任务需定位,经纬度不能为空!');
                }

                if (in_array('3', $typeArr) && empty($p['deviceStatus'])) {
                    throw new MyException('该任务需智点,智点状态不能为空!');
                }

                if (in_array('4', $typeArr) && empty($p['picture'])) {
                    throw new MyException('该任务需拍照,图片不能为空!');
                }

                $type = explode(',', $info['type']);
                if (in_array('2', $typeArr)) { // 如果需要定位的话判断距离误差
                    $distance = F::getDistance($p['lat'], $p['lon'], $info['point_lat'], $info['point_lon']);
                    if ($distance > \Yii::$app->getModule('property')->params['distance']) {
                        throw new MyException('当前位置不可巡检！');
                    }
                }
            }

            if ($p['device_status'] == 2 && empty($p['record_note'])) { // 异常时必填
                throw new MyException('备注说明必填!');
            }

            $info = PsInspectRecordPoint::find()->alias("A")
                ->select('A.id, A.device_status, A.point_name, A.type, A.status, A.point_lat, A.point_lon')
                ->where(['A.id' => $p['id']])
                ->andWhere(['<=', 'B.check_start_at', time()])
                ->andWhere(['>=', 'B.check_end_at', time()])
                ->leftJoin("ps_inspect_record B", "B.id = A.record_id")
                ->asArray()->one();
            if (empty($info)) {
                throw new MyException('当前时间不可执行任务!');
            }

            $p['imgs'] = is_array($p['imgs']) ? implode(',', $p['imgs']) : '';

            $device_status = $m['device_status'];

            $m->scenario = 'edit';  # 设置数据验证场景为 新增
            $m->load($p, '');   # 加载数据
            if ($m->validate()) {  # 验证数据
                if ($m->save()) {  # 保存新增数据
                    // 更新任务完成数,完成率
                    $record = PsInspectRecord::findOne($m->record_id);              

                    if ($p['device_status'] == 2 && $device_status == 1) { // 设备异常 原来正常
                        PsInspectRecord::updateAll(['issue_count' => $record->issue_count + 1], ['id' => $m->record_id]);
                    }

                    if ($p['device_status'] == 1 && $device_status == 2) { // 设备正常 原来异常
                        PsInspectRecord::updateAll(['issue_count' => $record->issue_count - 1], ['id' => $m->record_id]);
                    }
                    
                    $trans->commit();

                    return $this->success([]);
                }
                throw new MyException($m->getErrors());
            } else {
                throw new MyException($m->getErrors());
            }
        } catch (\Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
    }

    // 标记完成
    public function pointFinish($p)
    {
        $m = PsInspectRecord::findOne($p['id']);
        if (empty($m)) {
            throw new MyException('任务不存在!');
        }

        // 查询是否还有未完成的巡检点,没有则任务是完成状态
        $m = PsInspectRecordPoint::find()
            ->where(['record_id' => $p['id'], 'status' => 1])
            ->one();
        if (empty($m)) {
            $record = PsInspectRecord::findOne($p['id']);

            if ($record->issue_count == 0) {
                $result_status = 3;
            } else {
                $result_status = 2;
            }

            PsInspectRecord::updateAll(['status' => 3, 'result_status' => $result_status, 'update_at' => time()], ['id' => $p['id']]);

            return $this->success([]);
        } else {
            throw new MyException('还有未打卡的巡检点!');
        }
    }

    // 巡检点详情
    public function pointShow($p)
    {
        $m = PsInspectRecordPoint::find()
            ->where(['id' => $p['id']])->asArray()->one();

        if (empty($m)) {
            throw new MyException('数据不存在!');
        }

        $m['device_status_msg'] = self::$deviceStatus[$v['device_status']];
        $m['imgs'] = !empty($m['imgs']) ? explode(',', $m['imgs']) : [];

        $typeArr = explode(',', $m['type']);
        $newArr = [];
        if ($typeArr) {
            foreach ($typeArr as $key => $val) {
                $newArr[$key]['id'] = $val;
                $newArr[$key]['name'] = self::$pointType[$val];

                if ($val == 3) { // 智点打卡
                    $deviceNo = PsInspectPoint::findOne($m['point_id'])->deviceNo;
                    $m['dd_mid_url'] = PsInspectDevice::find()->where(['deviceNo' => $deviceNo, 'is_del' => 1])->one()->dd_mid_url;
                }
            }
        }
        $m['type'] = $newArr;

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
            ->andfilterWhere(['community_id' => $p['communityId']])
            ->andfilterWhere(['status' => [1,2,3]])
            ->andfilterWhere(['and', 
                ['>=', 'check_start_at', $start], 
                ['<', 'check_end_at', $end]
            ]);

        $r['totals'] = $query->count();

        $query->select('id, status, task_name, run_status, user_id, line_id, line_name, point_count, finish_count, check_start_at, check_end_at');
        $query->orderBy('status asc, id desc');

        $query->offset(($p['page'] - 1) * $p['rows'])->limit($p['rows']);

        $m = $query->createCommand()->queryAll();
        foreach ($m as $k => &$v) {
            $v['status_msg'] = self::$recordStatus[$v['status']];

            if ($v['run_status'] == 1) {
                $v['run_status_msg'] = '逾期';
            } else if ($v['run_status'] == 2) {
                $v['run_status_msg'] = '旷巡';
            } else {
                $v['run_status_msg'] = '正常';
            }

            $v['img'] = PsInspectLine::findOne($v['line_id'])->img;

            $v['check_at'] = date('Y/m/d H:i', $v['check_start_at']) . '-' . date('H:i', $v['check_end_at']);

            if ($v['status'] == 3) {
                $r['totals'] = $r['totals'] - 1;
            }
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
                ->select('id, finish_at, device_status, picture, status, point_name, location, point_id, record_note, imgs, type, device_status')
                ->asArray()->all();

            if (!empty($pointList)) {
                foreach ($pointList as &$v) {
                    $pointInfo = PsInspectPoint::findOne($v['point_id']);
                    $v['finish_at'] = !empty($v['finish_at']) ? date("Y-m-d H:i", $v['finish_at']) : '';
                    $v['device_status_msg'] = self::$deviceStatus[$v['device_status']];
                    $v['imgs'] = explode(',', $v['imgs']);

                    $typeArr = explode(',', $v['type']);
                    $newArr = [];
                    if ($typeArr) {
                        foreach ($typeArr as $key => $val) {
                            $newArr[$key]['id'] = $val;
                            $newArr[$key]['name'] = self::$pointType[$val];
                        }
                    }
                    $v['type'] = $newArr;
                }
            }
            $r['pointList'] = $pointList;

            return $r;
        }

        throw new MyException('任务不存在');
    }

    // ----------------------------------     公共接口     ------------------------------

    // 列表参数过滤
    private static function pointSearch($p, $filter = '')
    {
        $filter = $filter ?? "*";
        $m = PsInspectPoint::find()
            ->select($filter)
            ->filterWhere(['like', 'name', PsCommon::get($p, 'name')])
            ->andFilterWhere(['=', 'communityId', PsCommon::get($p, 'communityId')])
            ->andFilterWhere(['=', 'deviceNo', PsCommon::get($p, 'deviceNo')]);
        return $m;
    }

    // 自动验证小区权限
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
}
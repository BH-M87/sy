<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/7/1
 * Time: 16:11
 */

namespace service\parking;

use app\models\ParkingCoupon;
use app\models\ParkingCouponRecord;
use common\core\Curl;
use common\core\F;
use common\core\PsCommon;
use common\MyException;
use service\BaseService;
use service\common\AliPayQrCodeService;
use service\common\QrcodeService;
use yii\db\Query;

class CouponService extends BaseService
{
    //活动类型
    public $_types = [
        1 => ['id' => 1, 'name' => '小时券'],
        2 => ['id' => 2, 'name' => '金额券'],
    ];

    //活动状态
    public $_active_status = [
        1 => ['id' => 1, 'name' => '未开始'],
        2 => ['id' => 2, 'name' => '进行中'],
        3 => ['id' => 3, 'name' => '已结束'],
    ];

    /**
     * @api 优惠券新增
     * @author wyf
     * @date 2019/7/2
     * @param $params
     * @param array $userInfo
     * @return bool
     * @throws MyException
     */
    public function create($params, $userInfo = [])
    {
        $result = $this->checkValidate($params);
        $model = $result['model'];
        $data = $result['data'];
        $community_id = $data['community_id'];
        $couponNum = self::checkCouponNum($community_id);
        if ($couponNum >= 1) {
            throw new MyException('一个小区只能有一个优惠券活动');
        }
        $model->setAttributes($data);
        if (!$model->save()) {
            throw new MyException('新增失败');
        }
        return true;
    }

    /**
     * @api 优惠券编辑
     * @author wyf
     * @date 2019/7/2
     * @param $params
     * @param $userInfo
     * @return bool
     * @throws MyException
     * @throws \yii\db\Exception
     */
    public function update($params, $userInfo = [])
    {
        $result = $this->checkValidate($params, 'update');
        $data = $result['data'];
        $model = $this->getOne($params['id']);
        if (!$model) {
            throw new MyException('优惠券不存在');
        }
        $total_num = $model->amount;
        $left_num = $model->amount_left;
        $amount_use = $total_num - $left_num;//使用数量
        if ($data['amount'] < $total_num) {
            throw new MyException('总数量不能低于上次总数量');
        }
        $amount_left = $data['amount'] - $amount_use;
        $data['amount_left'] = $amount_left;
        $model->setAttributes($data);
        $trans = \Yii::$app->getDb()->beginTransaction();
        try {
            $model->save();
            $trans->commit();
        } catch (\Exception $exception) {
            $trans->rollBack();
            throw new MyException('编辑失败');
        }
        return true;
    }

    /**
     * @api 新增编辑统一验证
     * @author wyf
     * @date 2019/7/2
     * @param $params
     * @param string $scenario
     * @return array
     * @throws MyException
     */
    public function checkValidate($params, $scenario = 'create')
    {
        $model = new ParkingCoupon();
        $data = $model->validParamArr($params, $scenario);
        if (!in_array($data['user_limit'], [0, 1])) {
            throw new MyException('每人领取券的上限格式有误');
        }
        if ($data['user_limit'] == 1 && empty($params['user_limit_num'])) {
            throw new MyException('每人领取券的上限数量错误');
        }
        if (isset($params['user_limit_num']) && !is_numeric($params['user_limit_num'])) {
            throw new MyException('每人领取券的上限数量格式有误');
        }
        if ($data['user_limit'] == 1 && !empty($params['user_limit_num'])) {
            if ($params['user_limit_num'] > $params['amount']) {
                throw new MyException('每人领取券的上限数量不能超过券的总数量');
            }
        }
        $money = $params['money'] ?? 0;
        if (strlen((int)$money) > 4) {
            $desc_name = $params['type'] == 1 ? "抵扣时长不可超过9999分钟" : "抵扣金额不可超过9999.99元";
            throw new MyException($desc_name);
        }
        if ($money <= 0) {
            $error_msg = $params['type'] == 1 ? "抵扣时长不可低于0分钟" : "抵扣金额不可低于0.01元";
            throw new MyException($error_msg);
        }
        $new_data = [
            'community_id' => $data['community_id'],
            'title' => $data['title'],
            'type' => $data['type'],
            'money' => $data['money'],
            'amount' => $data['amount'],
            'amount_left' => $data['amount'],
            'amount_use' => 0,
            'expired_day' => 1,
            'start_time' => 0,
            'end_time' => 0,
            'date_type' => 1,
            'user_limit' => $data['user_limit'] == 1 ? $params['user_limit_num'] : 0,
            'activity_start' => strtotime($data['activity_start_date']),
            'activity_end' => strtotime($data['activity_end_date'] . ' 23:59:59'),
            'note' => $data['note'],
        ];
        if ($new_data['date_type'] == 1) {
            $new_data['start_time'] = 0;
            $new_data['end_time'] = 0;
        }
        if ($new_data['date_type'] == 2) {
            $new_data['expired_day'] = 0;
        }
        return ['model' => $model, 'data' => $new_data];
    }

    /**
     * @api 获取优惠券详情
     * @author wyf
     * @date 2019/7/3
     * @param $params
     * @return array
     * @throws MyException
     */
    public function view($params)
    {
        $model = new ParkingCoupon();
        $data = $model->validParamArr($params, 'view');
        $model = $this->getOne($data['id']);
        if (!$model) {
            throw new MyException('优惠券不存在');
        }
        $result = $model->toArray();
        $data = [
            'id' => (int)$result['id'],
            'title' => $result['title'],
            'type' => $result['type'],
            'money' => $result['type'] == 2 ? (double)$result['money'] : (int)$result['money'],
            'amount' => (int)$result['amount'],
            'start_date' => '',
            'end_date' => '',
            'user_limit' => $result['user_limit'] == 0 ? 0 : 1,
            'user_limit_num' => $result['user_limit'] == 0 ? 0 : $result['user_limit'],
            'date_type' => 1,
            'expired_day' => 1,
            'note' => $result['note'],
            'activity_start_date' => date('Y-m-d', $result['activity_start']),
            'activity_end_date' => date('Y-m-d', $result['activity_end']),
            'coupon_name' => ($result['type'] == 2 ? $result['money'] . '元' : (int)$result['money'] . '分钟') . "停车抵扣券",
        ];
        return $this->success($data);
    }

    /**
     * @api 列表
     * @author wyf
     * @date 2019/7/8
     * @param $params
     * @return array
     * @throws MyException
     */
    public function getList($params)
    {
        if (empty($params['community_id'])) {
            throw new MyException('小区编号不能为空');
        }
        $page = PsCommon::get($params, 'page', 1);
        $rows = PsCommon::get($params, 'rows', 20);
        $title = PsCommon::get($params, 'title', '');
        $type = PsCommon::get($params, 'type', '');
        $start_date = PsCommon::get($params, 'start_date', '');
        $end_date = PsCommon::get($params, 'end_date', '');
        $activity_status = PsCommon::get($params, 'activity_status', '');
        $query = $this->getCouponAll($params['community_id'], $title, $type);
        $is_filter = 2;
        if (!empty($start_date) && !empty($end_date)) {
            $query = $query->andWhere(['>=', 'activity_start', strtotime($start_date)])->andWhere(['<=', 'activity_end', strtotime($end_date . '23:59:59')]);
        }
        $activity_status_desc = '';
        if (!empty($activity_status)) {
            if (!in_array($activity_status, [1, 2, 3])) {
                throw new MyException('活动状态有误');
            }
            if (empty($start_date) && empty($end_date)) {
                switch ($activity_status) {
                    case 1:
                        $query = $query->andWhere(['>', 'activity_start', time()]);
                        $activity_status = 1;
                        $activity_status_desc = '未开始';
                        break;
                    case 2:
                        $query = $query
                            ->andWhere(['and', ['<=', 'activity_start', time()], ['>=', 'activity_end', time()]]);
                        $activity_status = 2;
                        $activity_status_desc = '进行中';
                        break;
                    case 3:
                        $query = $query->andWhere(['<', 'activity_end', time()]);
                        $activity_status = 3;
                        $activity_status_desc = '已结束';
                        break;
                }
                $is_filter = 1;
            }
        }
        $couponInfo = $query->offset(($page - 1) * $rows)->limit($rows)->asArray()->all();
        $data = [];
        if ($couponInfo) {
            foreach ($couponInfo as $item) {
                if ($is_filter == 2) {
                    if (!empty($activity_status)) {
                        if ($activity_status == 1 && $item['activity_start'] < time()) {
                            continue;
                        } elseif ($item['activity_end'] > time() && $activity_status == 3) {
                            continue;
                        } elseif (($item['activity_start'] > time() || $item['activity_end'] < time()) && $activity_status == 2) {
                            continue;
                        }
                    }
                    if ($item['activity_start'] > time()) {
                        $activity_status = 1;
                        $activity_status_desc = '未开始';
                    } elseif ($item['activity_end'] < time()) {
                        $activity_status = 3;
                        $activity_status_desc = '已结束';
                    } else {
                        $activity_status = 2;
                        $activity_status_desc = '进行中';
                    }
                }
                $desc = PsCommon::get($this->_types, $item['type'], '');
                //优惠卷的数量 add by zq 2019-7-25
                $item['amount_use'] = ParkingCouponRecord::find()->where(['coupon_id'=>$item['id'],'status'=>2])->count();
                $data[] = [
                    'title' => $item['title'],
                    'type' => (int)$item['type'],
                    'type_desc' => $desc ? $desc['name'] : "",
                    'amount' => (int)$item['amount'],
                    'start_date' => '',
                    'end_date' => '',
                    'user_limit' => $item['user_limit'] == 0 ? 0 : 1,
                    'user_limit_desc' => $item['user_limit'] == 0 ? 0 : (int)$item['user_limit'],
                    'date_type' => 1,
                    'expired_day' => 1,
                    'activity_status' => $activity_status,
                    'activity_status_desc' => $activity_status_desc,
                    'created_date' => empty($item['created_at']) ? "" : date('Y-m-d H:i:s', $item['created_at']),
                    'get_num' => (int)($item['amount'] - $item['amount_left']),
                    'verification_num' => (int)$item['amount_use'],
                    'activity_start_date' => date('Y-m-d', $item['activity_start']),
                    'activity_end_date' => date('Y-m-d', $item['activity_end']),
                    'id' => (int)$item['id'],
                ];
            }
            $totals = (int)count($data);
        } else {
            $totals = 0;
        }
        return $this->success(['list' => $data, 'totals' => $totals]);
    }

    /**
     * @api 优惠券删除
     * @author wyf
     * @date 2019/7/3
     * @param $params
     * @param $userInfo
     * @return bool
     * @throws MyException
     */
    public function del($params, $userInfo = [])
    {
        $model = new ParkingCoupon();
        $data = $model->validParamArr($params, 'delete');
        $model = $this->getOne($data['id']);
        if (!$model) {
            throw new MyException('优惠券不存在');
        }
        $model->deleted = 2;
        $id = $model->id;
        $title = $model->id;
        if (!$model->save()) {
            throw new MyException('删除失败');
        }
        return true;
    }

    /**
     * @api 核销列表
     * @author wyf
     * @date 2019/7/3
     * @param $params
     * @return array
     * @throws MyException
     */
    public function closureList($params)
    {
        $model = new ParkingCoupon();
        $data = $model->validParamArr($params, 'view');
        $model = $this->getOne($data['id']);
        if (!$model) {
            throw new MyException('优惠券不存在');
        }
        $page = PsCommon::get($params, 'page', 1);
        $rows = PsCommon::get($params, 'rows', 20);
        $query = ParkingCouponRecord::find()
            ->select('id,plate_number,status,closure_time,created_at')
            ->where(['coupon_id' => $params['id']]);
        $totals = (int)($query->count());
        $info = [];
        $list = $query->offset(($page - 1) * $rows)
            ->limit($rows)
            ->orderBy('id desc')
            ->asArray()->all();
        if ($list) {
            foreach ($list as $item) {
                $info[] = [
                    'plate_number' => $item['plate_number'],
                    'status' => $item['status'] == 2 ? 1 : 2,
                    'status_desc' => $item['status'] == 2 ? "是" : "否",
                    'created_date' => date('Y-m-d H:i:s', $item['created_at']),
                    'closure_date' => empty($item['closure_time']) ? "" : date('Y-m-d H:i:s', $item['closure_time']),
                ];
            }
        }

        $new_data['list'] = $info;
        $new_data['totals'] = $totals;
        return $this->success($new_data);
    }

    /**
     * @api 获取下载的二维码
     * @author wyf
     * @date 2019/7/2
     * @param $params
     * @return array
     * @throws MyException
     * @throws \yii\base\Exception
     */
    public function downCode($params)
    {
        $model = new ParkingCoupon();
        $data = $model->validParamArr($params, 'download');
        $model = $this->getOne($data['id']);
        if (!$model) {
            throw new MyException('优惠券不存在');
        }
        $type = $model->type;
        if ($type == 1) {
            $name = (int)($model->money) . '分钟';
        } else {
            $name = $model->money . '金额';
        }
        $desc = $name . '停车抵扣券';

        $savePath = F::imagePath('parking-coupon-code');
        $logo = \Yii::$app->basePath .'/web/img/lyllogo.png';//二维码中间的logo
        $appId = \Yii::$app->params['fczl_app_id'];
        $url = "alipays://platformapi/startapp?appId={$appId}&page=pages/park/reviceCoupon/reviceCoupon&query=".urlencode("couponId={$data['id']}");;
        $filename = QrcodeService::service()->generateCommCodeImage($savePath, $url, $data['id'], $logo);
        $file_size = filesize($filename);
        $fp = fopen($filename, "r");

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="图片.jpg"');
        header( 'Content-Length: ' . filesize ( $filename) );
        header( 'Cache-Control: max-age=0' );
        header("Accept-Ranges: bytes");
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        $fp = fopen ( $filename, 'r' );
        while ( ! feof ( $fp ) ) {
            $buffer = fread ( $fp, 10 );
            ob_flush ();
            flush ();
            echo $buffer;
        }
        ob_flush ();
        flush ();
        ob_clean ();
        fclose ( $fp );die;
        //return $this->success(['down_url' => $filename]);
    }

    /**
     * @api 获取公共参数
     * @author wyf
     * @date 2019/7/4
     * @param $params
     * @return array
     */
    public function getCommon($params)
    {
        $community_id = $params['community_id'] ?? "";
        if (empty($community_id)) {
            $count = 1;
        } else {
            $count = self::checkCouponNum($community_id);
        }
        $data = [
            'type' => array_values($this->_types),
            'active_status' => array_values($this->_active_status),
            'active_add' => $count < 1 ? 1 : 2,//是否可以新增活动,1.可以;2.不可以
        ];
        return $this->success($data);
    }

    /**
     * @api 获取车场优惠券列表数据
     * @author wyf
     * @date 2019/7/4
     * @param $community_id
     * @param string $title
     * @param string $type
     * @return \yii\db\ActiveQuery
     */
    public function getCouponAll($community_id, $title = '', $type = '')
    {
        $query = ParkingCoupon::find()
            ->where(['community_id' => $community_id, 'deleted' => 1])
            ->andFilterWhere(['like', 'title', $title])
            ->andFilterWhere(['type' => $type]);
        return $query;
    }

    public function getOne($id)
    {
        return ParkingCoupon::find()->where(['id' => $id, 'deleted' => 1])->one();
    }

    /**
     * @api 获取活动数量
     * @author wyf
     * @date 2019/7/11
     * @param $community_id
     * @return int|string
     */
    private static function checkCouponNum($community_id)
    {
        return ParkingCoupon::find()->where(['community_id' => $community_id, 'deleted' => 1])->count();
    }

    /******小程序端服务******/
    //小程序获取优惠券详情
    public function getInfo($reqArr)
    {
        $couponInfo = ParkingCoupon::find()
            ->alias('coupon')
            ->select('coupon.title, coupon.type, coupon.money as face_value, coupon.amount,
             coupon.amount_left, coupon.activity_start, coupon.activity_end,
              coupon.note, comm.id as community_id, comm.name as community_name')
            ->leftJoin('ps_community comm', 'coupon.community_id = comm.id')
            ->where(['coupon.id' => $reqArr['coupon_id'], 'coupon.deleted' => 1])
            ->asArray()
            ->one();
        if (!$couponInfo) {
            return [];
        }
        $couponInfo['plate_number'] = '';
        $couponType = PsCommon::get($this->_types, $couponInfo['type'], '');
        $couponInfo['type_desc'] = $couponType['name'];
        //面值处理，时间券时取整数
        if ($couponInfo['type'] == 1) {
            $couponInfo['face_value'] = intval($couponInfo['face_value']);
        }

        //状态判断
        if ($couponInfo['activity_start'] > time()) {
            $couponInfo['activity_status'] = 1;
        } elseif ($couponInfo['activity_end'] < time()) {
            $couponInfo['activity_status'] = 3;
        } else {
            $couponInfo['activity_status'] = 2;
        }
        $couponStatus = PsCommon::get($this->_active_status, $couponInfo['activity_status'], '');
        $couponInfo['activity_status_desc'] = $couponStatus['name'];
        $couponInfo['activity_start'] = date("Y-m-d H:i", $couponInfo['activity_start']);
        $couponInfo['activity_end'] = date("Y-m-d H:i", $couponInfo['activity_end']);

        //查询上次使用的车牌
        $couponRecord = ParkingCouponRecord::find()
            ->select('plate_number')
            ->where(['app_user_id' => $reqArr['user_id']])
            ->orderBy('id desc')
            ->asArray()
            ->one();
        if ($couponRecord) {
            $couponInfo['plate_number'] = $couponRecord['plate_number'];
        }
        return $couponInfo;
    }

    //获取优惠券领取记录
    public function recordList($reqArr)
    {
        $query = ParkingCouponRecord::find()
            ->alias('cr')
            ->leftJoin('parking_coupon c', 'c.id = cr.coupon_id')
            ->select('cr.coupon_type as type, cr.coupon_money as face_value, cr.id, cr.start_time, cr.expired_time as end_time,cr.status,cr.closure_time')
            ->where(['cr.app_user_id' => $reqArr['user_id']]);
        if ($reqArr['status']) {
            if ($reqArr['status'] != 1) {
                $query->andWhere(['cr.status' => $reqArr['status']]);
            } else {
                $query->andWhere(['in', 'cr.status', [1, 4]]);
            }

        }

        $totals = $query->count('cr.id');
        if ($reqArr['status']) {
            switch ($reqArr['status']) {
                case 1:
                    $query->orderBy('cr.id desc');
                    break;
                case 2:
                    $query->orderBy('cr.closure_time desc,id desc');
                    break;
                case 3:
                    $query->orderBy('cr.expired_time desc,id desc');
                    break;
            }
        }
        $list = $query
            ->offset((($reqArr['page'] - 1) * $reqArr['rows']))
            ->limit($reqArr['rows'])
            ->asArray()
            ->all();
        foreach ($list as $k => $v) {
            $typeDesc = PsCommon::get($this->_types, $v['type'], '');
            if ($v['type'] == 1) {
                //小时券，取整
                $list[$k]['face_value'] = intval($v['face_value']);
            }
            $list[$k]['type_desc'] = $typeDesc['name'];
            $list[$k]['start_time'] = $v['start_time'] ? date("Y-m-d H:i", $v['start_time']) : '';
            $list[$k]['end_time'] = $v['end_time'] ? date("Y-m-d H:i", $v['end_time']) : '';
        }
        $recordRe['totals'] = $totals;
        $recordRe['list'] = $list;
        return $recordRe;
    }

    //领取优惠券
    public function getCoupon($reqArr)
    {
        //验证车牌
        if (!PsCommon::isCarLicense(str_replace(' ', '', $reqArr['plate_number']))) {
            return "车牌号输入有误!";
        }

        $couponInfo = ParkingCoupon::find()
            ->select('type, amount, amount_left, user_limit, expired_day, date_type, start_time, end_time, activity_start, activity_end, deleted, money')
            ->where(['id' => $reqArr['coupon_id']])
            ->asArray()
            ->one();
        if (!$couponInfo) {
            return "优惠券活动不存在！";
        }
        if ($couponInfo['deleted'] == 2) {
            return "优惠券活动已删除！";
        }
        if ($couponInfo['activity_start'] > time()) {
            return "优惠券活动未开始！";
        }
        if ($couponInfo['activity_end'] < time()) {
            return "优惠券活动已结束！";
        }
        if ($couponInfo['amount_left'] < 1) {
            return "券已经领完了！";
        }

        //领取限制
        if ($couponInfo['user_limit']) {
            $getCount = ParkingCouponRecord::find()
                ->where(['coupon_id' => $reqArr['coupon_id'], 'app_user_id' => $reqArr['user_id']])
                ->count();
            if ($getCount >= $couponInfo['user_limit']) {
                return "超过领取上限！";
            }
        }

        //添加领取记录
        //处理有效期
        $startTime = $endTime = 0;
        if ($couponInfo['date_type'] == 1) {
            //相对时间
            $startTime = time();
            $endTime = strtotime(date('Y-m-d 00:00:00', time() + $couponInfo['expired_day'] * 86400));
        } elseif ($couponInfo['date_type'] == 2) {
            //绝对时间
            $startTime = $couponInfo['start_time'];
            $endTime = $couponInfo['end_time'];
        }
        $model = new ParkingCouponRecord();
        $model->coupon_id = $reqArr['coupon_id'];
        $model->app_user_id = $reqArr['user_id'];
        $model->plate_number = $reqArr['plate_number'];
        $model->coupon_type = $couponInfo['type'];
        $model->coupon_money = $couponInfo['money'];
        $model->status = 1;
        $model->coupon_code = PsCommon::getNoRepeatChar('', YII_ENV . 'couponList', 8);
        $model->type = 2;
        $model->start_time = $startTime;
        $model->expired_time = $endTime;
        $model->created_at = $model->updated_at = time();
        if ($model->save()) {
            //优惠券数量减少
            $model = ParkingCoupon::findOne($reqArr['coupon_id']);
            $model->amount_left = $model->amount_left - 1 > 0 ? $model->amount_left - 1 : 0;
            $model->save();

            //优惠券下发
//            $acrossRecord = ParkFeeService::service()->getCarAcrossByPlateNumber($reqArr['plate_number']);
//            if ($acrossRecord) {
//                $this->couponSend($acrossRecord['id']);
//            }
            $res['plate_number'] = $reqArr['plate_number'];
            $res['record_id'] = $model->id;

            return $res;
        }
        return "领取失败";
    }

    /**
     * 优惠券下发
     * @param $orderId
     * @return mixed
     */
    private function couponSend($carAcrossId)
    {
        $url = \Yii::$app->getModule('wisdompark')->params['open_api_url'];
        $url_send = $url . 'inner/v2/iot/use-coupon';
        $data_send['id'] = $carAcrossId;
        $res = Curl::getInstance()->post($url_send, $data_send);
        $resArr = json_decode($res, true);
        if ($resArr['code'] == 1) {
            return $resArr['data'];
        } else {
            return $resArr['error']['errorMsg'];
        }
    }
}
<?php
/**
 * 停车服务
 * @author shenyang
 * @date 2017/09/14
 */
namespace alisa\modules\sharepark\controllers;

use alisa\services\AlipaySmallApp;
use common\libs\F;
use common\services\park\ParkService;
use common\services\park\UserService;
use Yii;

Class ParkController extends AuthController
{
    //空闲列表
    public function actionFree()
    {
        $communityId = F::value($this->params, 'community_id');
        if(!$communityId) {
            return F::apiFailed('请选择小区');
        }
        $day = F::value($this->params, 'day', date('m-d'));
        $fullDay = date('Y') . '-' . $day;
        $location = [];
        $r = ParkService::service()->getFreeList($communityId, $fullDay, $location, $this->page, $this->pageSize);
        if($r['errCode']) {
            return F::apiFailed($r['errMsg']);
        }
        $result = $r['data'];
        $result['days'] = F::sevenDays();
        $result['plate_number'] = F::value($this->user, 'plate_number', '') ;
        return F::apiSuccess($result);
    }

    //车位共享时间段
    public function actionShareIndex()
    {
        $ppId = F::value($this->params, 'pp_id');
        $day = F::value($this->params, 'day', date('m-d'));
        if(!$ppId) {
            return F::apiFailed('请选择车位');
        }
        $r = ParkService::service()->shareIndexes($ppId, $day);
        if($r['errCode']) {
            return F::apiFailed($r['errMsg']);
        }
        $result['shares'] = array_values($r['data']);
        $result['current_index'] = date('H') + 1;
        return F::apiSuccess($result);
    }

    //预约
    public function actionReserve()
    {
        $indexes = F::value($this->params, 'indexes');
        $plateNumber = F::value($this->params, 'plate_number');
        $ppId = F::value($this->params, 'pp_id');
        $day = F::value($this->params, 'day');
        if(!$ppId || !$day || !$plateNumber || !$indexes) {
            return F::apiFailed('参数错误');
        }
        $params = [
            'ppid'=>$ppId,
            'time_indexes'=>explode(',', $indexes),
            'day'=>date('Y').'-'.$day,
            'user_id'=>$this->user['id'],
            'plate_number'=>$plateNumber
        ];
        $r = ParkService::service()->reserve($params);
        if($r['errCode']) {
            return F::apiFailed($r['errMsg']);
        }
        $result['plate_number'] = $plateNumber;
        $result['park_text'] = ParkService::service()->parkTimeText($day, $indexes);
        return F::apiSuccess($result);
    }

    //我的停车记录列表
    public function actionMyPark()
    {
        $r = ParkService::service()->getParkList($this->user['id'], $this->page, $this->pageSize);
        if($r['errCode']) {
            return F::apiFailed($r['errMsg']);
        }
        return F::apiSuccess($r['data']);
    }

    //我的停车详情(非支付,完成页面)
    public function actionMyParkDetail()
    {
        $prId = F::value($this->params, 'pr_id');
        if(!$prId) {
            return F::apiFailed('参数错误');
        }
        $response = ParkService::service()->getParkDetail($this->user['id'], $prId);
        if($response['errCode']) {
            return F::apiFailed($response['errMsg']);
        }
        $result = $response['data'];
        if(!$result) {
            return F::apiSuccess();
        }
        if($result['status']['id'] == 5) {
            return F::apiSuccess();
        }
        $r = UserService::service()->getUserMobile($result['owner_id']);
        if($r['errCode']) {
            return F::apiFailed($r['errMsg']);
        }
        $result['mobile'] = F::value($r['data'], 'mobile');
        if(!empty($result['create_at'])) {
            $result['create_at'] = date('Y-m-d H:i:s', $result['create_at']);
        }
        $result['avatar'] = $this->user['avatar'];
        return F::apiSuccess($result);
    }

    //我的停车详情(支付页)
    public function actionMyParkBill()
    {
        $prId = F::value($this->params, 'pr_id');
        if(!$prId) {
            return F::apiFailed('参数错误');
        }
        $r = ParkService::service()->payBilling($prId, $this->user['id']);
        if($r['errCode']) {
            return F::apiFailed($r['errMsg']);
        }
        if(empty($r['data']['status']) || $r['data']['status'] != 4) {//非支付订单
            return F::apiFailed('数据错误');
        }
        $result = $r['data'];
        if(!empty($result['create_at'])) {
            $result['create_at'] = date('Y-m-d H:i:s', $result['create_at']);
        }
        $result['avatar'] = $this->user['avatar'];
        return F::apiSuccess($result);
    }

    //我的停车详情(已结束页)
    public function actionMyParkDetailEnd()
    {
        $prId = F::value($this->params, 'pr_id');
        if(!$prId) {
            return F::apiFailed();
        }
        $r = ParkService::service()->getParkDetailEnd($this->user['id'], $prId);
        if($r['errCode']) {
            return F::apiFailed($r['errMsg']);
        }
        $result = $r['data'];
        if(!$result) {
            return F::apiSuccess();
        }
        $r = UserService::service()->getUserMobile($result['owner_id']);
        if($r['errCode']) {
            return F::apiFailed($r['errMsg']);
        }
        $result['mobile'] = F::value($r['data'], 'mobile');
        if(!empty($result['create_at'])) {
            $result['create_at'] = date('Y-m-d H:i:s', $result['create_at']);
        }
        $result['avatar'] = $this->user['avatar'];
        return F::apiSuccess($result);
    }

    //结束计费
    public function actionEnd()
    {
        $prId = F::value($this->params, 'pr_id');
        if(!$prId) {
            return F::apiFailed('参数错误');
        }
        $r = ParkService::service()->getBilling($prId, $this->user['id']);
        if($r['errCode']) {
            return F::apiFailed($r['errMsg']);
        }
        return F::apiSuccess();
    }

    //取消预约
    public function actionCancelReserve()
    {
        $prId = F::value($this->params, 'pr_id');
        if(!$prId) {
            return F::apiFailed('参数错误');
        }
        $r = ParkService::service()->cancelReserve($this->user['id'], $prId);
        if($r['errCode']) {
            return F::apiFailed($r['errMsg']);
        }
        return F::apiSuccess();
    }

    //投诉内容
    public function actionReasons()
    {
        $r = UserService::service()->complaintReasons(1);
        if($r['errCode']) {
            return F::apiFailed($r['errMsg']);
        }
        return F::apiSuccess(['reasons'=>$r['data']]);
    }

    //投诉
    public function actionComplaint()
    {
        $reason = F::value($this->params, 'reason');
        $prId = F::value($this->params, 'pr_id');
        if(!$reason || !$prId) {
            return F::apiFailed('参数错误');
        }
        $r = UserService::service()->complaint($this->user['id'], $prId, 1, $reason);
        if($r['errCode']) {
            return F::apiFailed($r['errMsg']);
        }
        return F::apiSuccess();
    }

    //支付宝支付orderStr
    public function actionOrderStr()
    {
        $prId = F::value($this->params, 'pr_id');
        if(!$prId) {
            return F::apiFailed('参数错误');
        }
        $r = ParkService::service()->payBilling($prId, $this->user['id']);
        if($r['errCode']) {
            return F::apiFailed($r['errMsg']);
        }
        if(empty($r['data']['status']) || $r['data']['status'] != 4) {//非支付订单
            return F::apiFailed('数据错误');
        }
        $bill = $r['data'];
        $notify = Yii::$app->params['host'].'/sharepark/callback/alipay';

        $service = new AlipaySmallApp('sharepark');
        $str = $service->getOrderStr('共享停车支付', '共享停车',
            $bill['trade_no'], $bill['amount'], $notify);
        return F::apiSuccess(['str'=>$str]);
    }
}

<?php
/**
 * 优惠券相关接口
 * User: wenchao.feng
 * Date: 2019/9/23
 * Time: 15:51
 */

namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\UserBaseController;
use common\core\F;
use service\parking\CouponService;

class CouponController extends UserBaseController
{
    //获取活动详情
    public function actionView()
    {
        $reqArr['coupon_id'] = F::value($this->params, 'coupon_id');
        $reqArr['user_id'] = F::value($this->params, 'user_id');
        if (empty($reqArr['coupon_id'])) {
            return F::apiFailed('优惠券id不能为空');
        }

        $couponInfo = CouponService::service()->getInfo($reqArr);
        if (!$couponInfo) {
            return F::apiFailed('优惠券不存在');
        }
        return F::apiSuccess($couponInfo);
    }

    //获取领取记录
    public function actionRecordList()
    {
        $reqArr['user_id'] = F::value($this->params, 'user_id');
        $reqArr['status'] = F::value($this->params, 'status');
        $reqArr['page'] = F::value($this->params, 'page');
        $reqArr['rows'] = F::value($this->params, 'rows', 20);
        $recordList = CouponService::service()->recordList($reqArr);
        return F::apiSuccess($recordList);
    }

    //领取优惠券
    public function actionGet()
    {
        $reqArr['coupon_id'] = F::value($this->params, 'coupon_id');
        $reqArr['user_id'] = F::value($this->params, 'user_id');
        $reqArr['plate_number'] = F::value($this->params, 'plate_number');
        $re = CouponService::service()->getCoupon($reqArr);
        if (is_array($re)) {
            return F::apiSuccess($re);
        } else {
            return F::apiFailed($re);
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/9/2
 * Time: 15:32
 */

namespace app\modules\property\modules\v1\controllers;
use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\parking\CouponService;

class ParkingCouponController extends BaseController
{
    /**
     * @api 优惠券活动新增
     * @author wyf
     * @date 2019/7/2
     * @return string
     */
    public function actionCreate()
    {
        CouponService::service()->create($this->request_params, $this->user_info);
        return PsCommon::responseSuccess();
    }

    /**
     * @api 优惠券活动编辑
     * @author wyf
     * @date 2019/7/2
     * @return string
     */
    public function actionUpdate()
    {
        CouponService::service()->update($this->request_params, $this->user_info);
        return PsCommon::responseSuccess();
    }

    /**
     * @api 优惠券活动详情
     * @author wyf
     * @date 2019/7/2
     * @return null|string
     */
    public function actionView()
    {
        $result = CouponService::service()->view($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /**
     * @api 优惠券活动列表
     * @author wyf
     * @date 2019/7/2
     * @return null|string
     */
    public function actionList()
    {
        $result = CouponService::service()->getList($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /**
     * @api 优惠券活动删除
     * @author wyf
     * @date 2019/7/2
     * @return string
     */
    public function actionDelete()
    {
        CouponService::service()->del($this->request_params, $this->user_info);
        return PsCommon::responseSuccess();
    }

    /**
     * @api 优惠券领取张数及核销张数列表
     * @author wyf
     * @date 2019/7/2
     * @return null|string
     */
    public function actionClosureList()
    {
        $result = CouponService::service()->closureList($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /**
     * @api 优惠券二维码下载接口
     * @author wyf
     * @date 2019/7/2
     * @return null|string
     */
    public function actionDownCode()
    {
        phpinfo();exit;
        $result = CouponService::service()->downCode($this->request_params);
        \Yii::info("down-code-url:".json_encode($result),'api');
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    public function actionGetCommon()
    {
        $result = CouponService::service()->getCommon($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }
}
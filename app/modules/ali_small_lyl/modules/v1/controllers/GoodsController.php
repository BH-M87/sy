<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;

use service\property_basic\GoodsService;

class GoodsController extends BaseController
{
    // 核销接口
    public function actionRecordConfirm()
    {
        $r = GoodsService::service()->recordConfirm($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // 核销详情接口
    public function actionRecordConfirmShow()
    {
        $r = GoodsService::service()->recordConfirmShow($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // 商品详情
    public function actionGoodsContent()
    {
        $r = GoodsService::service()->goodsContent($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // 最新商品列表
    public function actionGoodsList()
    {
        if (!$this->params['community_id']) {
            return F::apiFailed('请输入小区ID！');
        }

        $r = GoodsService::service()->goodsList($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // 往期兑换列表
    public function actionGroupList()
    {
        if (!$this->params['community_id']) {
            return F::apiFailed('请输入小区ID！');
        }

        $r = GoodsService::service()->groupListSmall($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 可兑换积分
    public function actionIntegralSurplus()
    {
        if (!$this->params['user_id']) {
            return F::apiFailed('请输入用户ID！');
        }

        $r = GoodsService::service()->integralSurplus($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 文明志愿码
    public function actionCodeInfo()
    {
        if (!$this->params['user_id']) {
            return F::apiFailed('请输入用户ID！');
        }

        $r = GoodsService::service()->codeInfo($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 判断志愿者是否注册过
    public function actionIsRegister()
    {
        if (!$this->params['mobile']) {
            return F::apiFailed('请输入手机号！');
        }

        $r = GoodsService::service()->isRegister($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 判断是否加入过小区队伍
    public function actionIsInTeam()
    {
        if (!$this->params['user_id']) {
            return F::apiFailed('请输入用户ID！');
        }

        if (!$this->params['teamId']) {
            return F::apiFailed('请输入队伍ID！');
        }

        $r = GoodsService::service()->isInTeam($this->params);

        return PsCommon::responseSuccess($r);
    }
}
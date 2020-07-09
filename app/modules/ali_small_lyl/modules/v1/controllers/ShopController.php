<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;

use service\shop\ShopService;

class ShopController extends BaseController
{
    // 社区掌柜首页
    public function actionSmallIndex()
    {
        $r = ShopService::service()->smallIndex($this->params);

        return PsCommon::responseSuccess($r);
    }

    // ----------------------------------     店铺管理     ----------------------------

    // 店铺 新增
    public function actionShopAdd()
    {
        $r = ShopService::service()->shopAdd($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 店铺关联小区
    public function actionShopCommunity()
    {
        $r = ShopService::service()->shopCommunity($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // 店铺详情
    public function actionShopShow()
    {
        $r = ShopService::service()->shopShow($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // 店铺 状态变更
    public function actionShopStatus()
    {
        $r = ShopService::service()->shopStatus($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // ----------------------------------     商品分类管理     ----------------------------

    // 商品分类 新增
    public function actionGoodsTypeAdd()
    {
        $r = ShopService::service()->goodsTypeAdd($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // 商品分类 编辑
    public function actionGoodsTypeEdit()
    {
        $r = ShopService::service()->goodsTypeEdit($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // 商品分类 下拉列表
    public function actionGoodsTypeDropDown()
    {
        $r = ShopService::service()->goodsTypeDropDown($this->params);
        
        return PsCommon::responseSuccess($r, false);
    }

    // 商品分类 列表
    public function actionGoodsTypeList()
    {
        $r = ShopService::service()->goodsTypeList($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // 商品分类 删除
    public function actionGoodsTypeDelete()
    {
        $r = ShopService::service()->goodsTypeDelete($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // ----------------------------------     商品管理     ----------------------------

    // 商品 列表
    public function actionGoodsList()
    {
        $r = ShopService::service()->goodsList($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 商品 新增
    public function actionGoodsAdd()
    {
        $r = ShopService::service()->goodsAdd($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 商品 编辑
    public function actionGoodsEdit()
    {
        $r = ShopService::service()->goodsEdit($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 商品 详情
    public function actionGoodsShow()
    {
        $r = ShopService::service()->goodsShow($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 商品 状态变更
    public function actionGoodsStatus()
    {
        $r = ShopService::service()->goodsStatus($this->params);

        return PsCommon::responseSuccess($r);
    }
}
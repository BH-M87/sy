<?php
namespace app\modules\property\modules\v1\controllers;

use Yii; 

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use service\shop\ShopService;
use service\common\ExcelService;

class ShopController extends BaseController
{
    public function actionShopList()
    {
        $r = ShopService::service()->shopList($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    public function actionShopApp()
    {
        $r = ShopService::service()->shopApp($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    public function actionGoodsList()
    {
        $r = ShopService::service()->goodsList($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    public function actionGoodsExport()
    {
        $this->request_params['rows'] = 100000;

        $r = ShopService::service()->goodsList($this->request_params);
        
        if ($r['totals'] > 0) {
            $config["sheet_config"] = [
                'A' => ['title' => '商品ID', 'width' => 25, 'data_type' => 'str', 'field' => 'goods_code'],
                'B' => ['title' => '商品名称', 'width' => 15, 'data_type' => 'str', 'field' => 'goods_name'],
                'C' => ['title' => '商家ID', 'width' => 25, 'data_type' => 'str', 'field' => 'merchant_code'],
                'D' => ['title' => '店铺ID', 'width' => 25, 'data_type' => 'str', 'field' => 'shop_code'],
                'E' => ['title' => '店铺名称', 'width' => 15, 'data_type' => 'str', 'field' => 'shop_name'],
                'F' => ['title' => '商品状态', 'width' => 10, 'data_type' => 'str', 'field' => 'statusMsg'],
                'G' => ['title' => '最近修改', 'width' => 16, 'data_type' => 'str', 'field' => 'update_at'],
            ];
            $config["save"] = true;
            
            $savePath = Yii::$app->basePath . '/web/store/excel/shopGoods/';
            $config["save_path"] = $savePath;
            $config["file_name"] = "shopGoods.xlsx";

            $file_name = ExcelService::service()->recordDown($r['list'], $config);
            $downUrl = F::downloadUrl('shopGoods/' . $file_name, 'excel');
            return PsCommon::responseSuccess(['down_url' => $downUrl]);
        } else {
            return PsCommon::responseFailed("暂无数据！");
        }
    }
}
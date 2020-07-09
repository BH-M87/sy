<?php
namespace app\modules\property\modules\v1\controllers;

use Yii; 

use app\modules\property\controllers\BaseController;

use common\core\F;
use common\core\PsCommon;

use service\shop\ShopService;
use service\common\ExcelService;
use service\common\CsvService;

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
    {/*
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
*/

        $this->request_params['page'] = 1;
        $this->request_params['rows'] = 100000;

        $result = ShopService::service()->goodsList($this->request_params);

        $config = [
            ['title' => '商品ID', 'width' => 25, 'data_type' => 'str', 'field' => 'goods_code'],
            ['title' => '商品名称', 'width' => 15, 'data_type' => 'str', 'field' => 'goods_name'],
            ['title' => '商家ID', 'width' => 25, 'data_type' => 'str', 'field' => 'merchant_code'],
            ['title' => '店铺ID', 'width' => 25, 'data_type' => 'str', 'field' => 'shop_code'],
            ['title' => '店铺名称', 'width' => 15, 'data_type' => 'str', 'field' => 'shop_name'],
            ['title' => '商品状态', 'width' => 10, 'data_type' => 'str', 'field' => 'statusMsg'],
            ['title' => '最近修改', 'width' => 16, 'data_type' => 'str', 'field' => 'update_at'],
        ];

        $filename = CsvService::service()->saveTempFile(1, $config, $result, 'shopGoods');
        $filePath = F::originalFile().'temp/'.$filename;
        $fileRe = F::uploadFileToOss($filePath);
        $downUrl = $fileRe['filepath'];

        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }
}
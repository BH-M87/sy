<?php
namespace app\modules\operation\modules\v1\controllers;

use Yii; 

use app\modules\operation\controllers\BaseController;

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

    public function actionShopDropDown()
    {
        $r = ShopService::service()->shopDropDown($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    public function actionGoodsList()
    {
        $r = ShopService::service()->goodsList($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    public function actionGoodsDropDown()
    {
        $r = ShopService::service()->goodsDropDown($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    /*public function actionGoodsExport()
    {
        $this->request_params['page'] = 1;
        $this->request_params['rows'] = 100000;

        $result = ShopService::service()->goodsList($this->request_params);

        $config = [
            ['title' => '商品ID', 'field' => 'goods_code', 'width' => 25],
            ['title' => '商品名称', 'field' => 'goods_name', 'width' => 25],
            ['title' => '商家ID', 'field' => 'merchant_code', 'width' => 25],
            ['title' => '店铺ID', 'field' => 'shop_code', 'width' => 25],
            ['title' => '店铺名称', 'field' => 'shop_name', 'width' => 25],
            ['title' => '商品状态', 'field' => 'statusMsg', 'width' => 25],
            ['title' => '最近修改', 'field' => 'update_at', 'width' => 45],
        ];

        $filename = CsvService::service()->saveTempFile(1, $config, $result['list'], 'shopGoods');
        $filePath = F::originalFile().'temp/'.$filename;
        $fileRe = F::uploadFileToOss($filePath);
        $downUrl = $fileRe['filepath'];

        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }*/

    public function actionGoodsExport()
    {
        $result = ShopService::service()->goodsList($this->request_params);

        $getTotals = $result['totals'];
        if ($getTotals > 0) {
            $cycle = ceil($getTotals / 1000);
            $config["sheet_config"] = [
                'A' => ['title' => '商品ID', 'width' => 20, 'data_type' => 'str', 'field' => 'goods_code'],
                'B' => ['title' => '商品名称', 'width' => 25, 'data_type' => 'str', 'field' => 'goods_name'],
                'C' => ['title' => '商家ID', 'width' => 25, 'data_type' => 'str', 'field' => 'merchant_code'],
                'D' => ['title' => '店铺ID', 'width' => 25, 'data_type' => 'str', 'field' => 'shop_code'],
                'E' => ['title' => '店铺名称', 'width' => 25, 'data_type' => 'str', 'field' => 'shop_name'],
                'F' => ['title' => '商品状态', 'width' => 10, 'data_type' => 'str', 'field' => 'statusMsg'],
                'G' => ['title' => '最近修改', 'width' => 25, 'data_type' => 'str', 'field' => 'update_at'],
            ];
            $config["save"] = true;
            $date = date('Y-m-d',time());
            $savePath = Yii::$app->basePath . '/web/store/zip/shop/' . $date . '/';
            $config["save_path"] = $savePath;
            //房屋数量查过一千则导出压缩文件
            if ($cycle == 1) {//下载单个文件
                $config["file_name"] = "goods1.xlsx";
                $params['page'] = 1;
                $params['rows'] = 1000;
                
                $result = ShopService::service()->goodsList($params);
                
                $file_name = ExcelService::service()->recordDown($result['list'], $config);
                $downUrl = F::downloadUrl('shop/' . $date . '/'. $file_name, 'zip');
                
                return PsCommon::responseSuccess(['down_url' => $downUrl]);
            } else {//下载zip压缩包
                for ($i = 1; $i <= $cycle; $i++) {
                    $config["file_name"] = "goods" . $i . ".xlsx";
                    $params['page'] = $i;
                    $params['rows'] = 1000;
                    
                    $result = ShopService::service()->goodsList($params);
 
                    ExcelService::service()->recordDown($result['list'], $config);
                }
                $path = $savePath . 'goods.zip';
                ExcelService::service()->addZip($savePath, $path);
                $downUrl = F::downloadUrl('shop/'.$date.'/goods.zip', 'zip');
                
                return PsCommon::responseSuccess(['down_url' => $downUrl]);
            }
        } else {
            return PsCommon::responseFailed("暂无数据！");
        }
    }
}
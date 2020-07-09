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
    }
}
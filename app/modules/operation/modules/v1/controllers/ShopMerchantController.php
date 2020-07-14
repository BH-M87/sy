<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/8
 * Time: 11:21
 * Desc: 商户
 */
namespace app\modules\operation\modules\v1\controllers;

use app\modules\operation\controllers\BaseController;
use common\core\F;
use service\common\ExcelService;
use service\shop\MerchantService;
use yii\base\Exception;
use common\core\PsCommon;
use Yii;

class ShopMerchantController extends BaseController {

    //入驻审核列表
    public function actionCheckList(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new MerchantService();
            $result = $service->checkList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //商家列表
    public function actionMerchantList(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new MerchantService();
            $result = $service->merchantList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //审核详情
    public function actionCheckDetail(){
        try{
            $params = $this->request_params;
            $service = new MerchantService();
            $result = $service->checkDetail($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     * 商家详情
     */
    public function actionMerchantDetail(){
        try{
            $params = $this->request_params;
            $service = new MerchantService();
            $result = $service->merchantDetail($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     * 商家审核
     */
    public function actionMerchantChecked(){
        try{
            $params = $this->request_params;
            $service = new MerchantService();
            $result = $service->merchantChecked($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     * 商家编辑
     */
    public function actionMerchantEdit(){
        try{
            $params = $this->request_params;
            $service = new MerchantService();
            $result = $service->merchantEdit($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     * 商家导出
     */
    public function actionMerchantExport(){
        try{
            $params = $this->request_params;
            $service = new MerchantService();
            $result = $service->merchantList($params);
            if ($result['code']) {
                $getTotals = $result['data']['totals'];
                if ($getTotals > 0) {

                    $cycle = ceil($getTotals / 1000);
                    $config["sheet_config"] = [

                        'A' => ['title' => '商家ID', 'width' => 20, 'data_type' => 'str', 'field' => 'merchant_code'],
                        'B' => ['title' => '商家名称', 'width' => 25, 'data_type' => 'str', 'field' => 'name'],
                        'C' => ['title' => '商家类型', 'width' => 15, 'data_type' => 'str', 'field' => 'type_msg'],
                        'D' => ['title' => '店铺数', 'width' => 10, 'data_type' => 'str', 'field' => 'count'],
                        'E' => ['title' => '商家状态', 'width' => 10, 'data_type' => 'str', 'field' => 'status_msg'],
                        'F' => ['title' => '入驻时间', 'width' => 25, 'data_type' => 'str', 'field' => 'create_at_msg'],
                    ];
                    $config["save"] = true;
                    $date = date('Y-m-d',time());
                    $savePath = Yii::$app->basePath . '/web/store/zip/shop/' . $date . '/';
                    $config["save_path"] = $savePath;
                    //房屋数量查过一千则导出压缩文件
                    if ($cycle == 1) {//下载单个文件
                        $config["file_name"] = "MuBan1.xlsx";
                        $params['page'] = 1;
                        $params['pageSize'] = 1000;
                        $result = $service->merchantList($params);
                        $file_name = ExcelService::service()->recordDown($result['data']['list'], $config);
                        $downUrl = F::downloadUrl('shop/' . $date . '/'. $file_name, 'zip');
                        return PsCommon::responseSuccess(['down_url' => $downUrl]);
                    } else {//下载zip压缩包
                        for ($i = 1; $i <= $cycle; $i++) {
                            $config["file_name"] = "MuBan" . $i . ".xlsx";
                            $params['page'] = $i;
                            $params['pageSize'] = 1000;
                            $result = $service->merchantList($params);
                            $config["file_name"] = "MuBan" . $i . ".xlsx";
                            ExcelService::service()->recordDown($result['data']['list'], $config);
                        }
                        $path = $savePath . 'merchant.zip';
                        ExcelService::service()->addZip($savePath, $path);
                        $downUrl = F::downloadUrl('shop/'.$date.'/merchant.zip', 'zip');
                        return PsCommon::responseSuccess(['down_url' => $downUrl]);
                    }
                } else {
                    return PsCommon::responseFailed("暂无数据！");
                }
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //社区推广新增
    public function actionAddPromote(){
        try{
            $params = $this->request_params;
            $service = new MerchantService();
            $result = $service->addPromote($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //社区推广修改
    public function actionEditPromote(){
        try{
            $params = $this->request_params;
            $service = new MerchantService();
            $result = $service->editPromote($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //社区推广详情
    public function actionPromoteDetail(){
        try{
            $params = $this->request_params;
            $service = new MerchantService();
            $result = $service->promoteDetail($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //社区推广列表
    public function actionPromoteList(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new MerchantService();
            $result = $service->promoteList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //商户下拉
    public function actionDropMerchant(){
        try{
            $service = new MerchantService();
            $result = $service->dropMerchant();
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }
}
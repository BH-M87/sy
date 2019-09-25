<?php
/**
 * User: ZQ
 * Date: 2019/9/24
 * Time: 9:55
 * For: ****
 */

namespace app\modules\hard_ware_butt\modules\v1\controllers;


use app\models\ParkingAcrossForm;
use app\modules\hard_ware_butt\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\parking\CarAcrossService;

class ParkingController extends BaseController
{
    //入库记录同步
    public function actionEnter()
    {
        if (empty($this->params)) {
            echo PsCommon::responseFailed("未接受到有效数据");exit;
        }

        if ($this->requestType != 'POST') {
            echo PsCommon::responseFailed("请求方式错误");exit;
        }

        //校验格式
        $valid = F::validParamArr(new ParkingAcrossForm(),$this->params,'enter');
        if(!$valid["status"] ) {
            echo PsCommon::responseFailed($valid["errorMsg"]);exit;
        }
        $data = $valid["data"];
        $data['supplier_id'] = $this->supplierId;
        $data['community_id'] = $this->communityId;
        $data['open_alipay_parking'] = $this->openAlipayParking;
        $data['interface_type'] = $this->interfaceType;
        $data['data_type'] = "enter-data";
        CarAcrossService::service()->enterData($data);
        return PsCommon::responseSuccess();
    }

    //出库记录同步
    public function actionExit()
    {
        if (empty($this->params)) {
            echo PsCommon::responseFailed("未接受到有效数据");exit;
        }

        if ($this->requestType != 'POST') {
            echo PsCommon::responseFailed("请求方式错误");exit;
        }

        //校验格式
        $valid = F::validParamArr(new ParkingAcrossForm(),$this->params,'exit');
        if(!$valid["status"] ) {
            echo PsCommon::responseFailed($valid["errorMsg"]);exit;
        }

        $data = $valid["data"];
        $data['supplier_id'] = $this->supplierId;
        $data['community_id'] = $this->communityId;
        $data['open_alipay_parking'] = $this->openAlipayParking;
        $data['interface_type'] = $this->interfaceType;
        $data['data_type'] = "exit-data";
        CarAcrossService::service()->exitData($data);
        return PsCommon::responseSuccess();
    }

    public function actionTestImg()
    {
        $url = "http://218.108.151.10/pic?1dde82z87-=s4114215a6e9=t1i1m*=p2p3i=d1s*i8d7d*=*1b5i705a8006cb702--82b601-449i42e*e82ei32=";
        $filePath = F::qiniuImagePath().date('Y-m-d')."/";
        if (!is_dir($filePath)) {//0755: rw-r--r--
            mkdir($filePath, 0755, true);
        }
        $newFile = $filePath."/".$this->_generateName('jpg');
        echo $newFile;
        $this->dlfile($url, $newFile);
    }

    private function dlfile($file_url, $save_to)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_URL, $file_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $file_content = curl_exec($ch);
        curl_close($ch);
        $downloaded_file = fopen($save_to, 'w');
        fwrite($downloaded_file, $file_content);
        fclose($downloaded_file);
    }

    /**
     * 创建新的文件名称(以时间区分)
     */
    private function _generateName($ext)
    {
        list($msec, $sec) = explode(' ', microtime());
        $msec = round($msec, 3) * 1000;//获取毫秒
        return date('YmdHis') . $msec . rand(10,100) . '.' . $ext;
    }



}
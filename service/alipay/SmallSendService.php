<?php
/**
 * 支付宝商户会员卡相关
 * 第一步：上传相关图片到支付宝（先上传到自己的服务器）
 * 第二步：创建会员卡模板 拿到模板ID
 * 第三步：会员卡开卡表单模板配置
 * 第四步：获取会员卡领卡投放链接（小程序调首页接口 返回投放链接地址）
 * 第五步：小程序授权成功调用会员卡卡开接口 开卡成功调卡详情接口 获取pass_id
 */

namespace service\alipay;

use Yii;
use yii\helpers\FileHelper;
use service\alipay\IsvLifeService;
use service\BaseService;

class SmallSendService extends BaseService
{

    //获取阿里实例
    public function getAliService()
    {
        $alipayPublicKey = file_get_contents(Yii::$app->params['repair_alipay_public_key_file']);
        $rsaPrivateKey = file_get_contents(Yii::$app->params['repair_rsa_private_key_file']);
        $alipayLifeService = new IsvLifeService(Yii::$app->params['repair_app_id'], null, null, $alipayPublicKey, $rsaPrivateKey);
        return $alipayLifeService;
    }

    public function sendRepairMsg($to_user_id, $form_id, $id)
    {
        $alipayLifeService = $this->getAliService();
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }
        $data = ['keyword1' => ['value' => '您提交的报事报修已处理，请查看详情！']];
        $reqArr  = [
            'to_user_id' => $to_user_id,
            'form_id' => $form_id,
            'user_template_id' => 'NGQ1MmNmYTQ1NzUzYTZlYmUyY2UwNmU0M2EzNzI0ZTM=',
            'page' => "/pages/orderDetails/orderDetails?repair_id={$id}",
            'data' => json_encode($data),
        ];

        $result = $alipayLifeService->sendSmallMsg($reqArr);
        return $result;
    }

    private function _writeLog($error_msg, $data)
    {
        $html = " \r\n";
        $html .= "请求时间:" . date('YmdHis') . "  请求结果:" . $error_msg . "\r\n";
        $html .= "请求数据:" . json_encode($data) . "\r\n";
        $file_name = date("Ymd") . '.txt';
        $savePath = Yii::$app->basePath . '/runtime/interface_log/';
        if (!file_exists($savePath)) {
            FileHelper::createDirectory($savePath, 0777, true);
//            mkdir($savePath,0777,true);
        }
        if (file_exists($savePath . $file_name)) {
            file_put_contents($savePath . $file_name, $html, FILE_APPEND);
        } else {
            file_put_contents($savePath . $file_name, $html);
        }
    }

    private function _response($data, $status, $msg = '')
    {
        if ($status == 'success') {
            $msg = $status;
        }

        $this->_writeLog($msg, $data);
    }

}

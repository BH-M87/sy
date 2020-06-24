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
use yii\web\Response;
use yii\helpers\FileHelper;

use common\core\F;
use service\BaseService;
use app\models\PsAppUser;
use app\models\PsAlipayCardRecord;

class MemberCardService extends BaseService
{
    //申请单前缀
    const BIZ_NO_PREFIX = 'lyl';
    public $park_template_id = "Yzc4MThiNTY1Y2UyNTQxMGM1YzhiYWUwNzUyYmVjN2Y="; //共享停车小程序消息模板id
    public $small_url;

    //获取阿里实例
    public function getAliService($type = 'park')
    {
        $this->small_url = 'alipays://platformapi/startapp?appId='.Yii::$app->params['park_app_id'].'&pages/homePage/homePage/homePage';
        switch ($type){
            default://共享停车
                $this->template_id = $this->park_template_id;
                $alipayPublicKey = file_get_contents(Yii::$app->params['park_alipay_public_key_file']);
                $rsaPrivateKey = file_get_contents(Yii::$app->params['park_rsa_private_key_file']);
                $alipayLifeService = new IsvLifeService(Yii::$app->params['park_app_id'], null, null, $alipayPublicKey, $rsaPrivateKey);
        }

        return $alipayLifeService;
    }

    // 添加图片
    public function createImg($param)
    {
        $alipayLifeService = $this->getAliService($param['type']);
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }

        $img_type = explode('.', $param['local_url']);
        $imgPara['image_type'] = $img_type[1];
        $imgPara['image_name'] = uniqid();
        $img_url = "@" . F::originalImage() . $param['local_url'];
        $imgPara['image_content'] = $img_url;

        $imgResult = $alipayLifeService->createImg($imgPara);
      
        if ($imgResult['code'] == 10000) {
            return $imgResult['image_id'];
        } else {
            return "图片错误:" . $imgResult['sub_msg'];
        }
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

    /**
     *  获取小程序二维码
     * @param $params   :type:small(邻易联)，door(筑家易智能门禁),edoor(筑家e门禁)；url_param:小程序路由地址；query_param小程序参数（x=1）
     * @return IsvLifeService|array|bool    qr_code_url：二维码图片链接地址。
     */
    public function getQrcode($params)
    {
        $alipayLifeService = $this->getAliService($params['type']);
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }
        $reqArr = [
            'url_param' => $params['url_param'],
            'query_param' => $params['query_param'],
            'describe' => $params['describe']
        ];

        $result = $alipayLifeService->smallQrcode($reqArr);
        return $result;
    }

    /**
     *  发送小程序模板消息
     */
    public function sendMessage($params)
    {
        $params['type'] = 'park';
        $alipayLifeService = $this->getAliService($params['type']);
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }
        $result = $alipayLifeService->smallPushMsg($params);
        return $result;
    }

}

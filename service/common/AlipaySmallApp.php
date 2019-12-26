<?php
/**
 * 支付宝小程序服务
 * @author shenyang
 * @date 2017/9/14
 */

namespace service\common;

use service\alipay\IsvLifeService;
use common\MyException;
use Yii;

Class AlipaySmallApp
{
    private $_aop;
    private $_aes_secret = '';

    //文件固定路径: alisa/rsa_files/module_name/xxx.txt
    public function getAliService()
    {
        $alipayPublicKey = file_get_contents(Yii::$app->params['repair_alipay_public_key_file']);
        $rsaPrivateKey = file_get_contents(Yii::$app->params['repair_rsa_private_key_file']);
        $alipayLifeService = new IsvLifeService(Yii::$app->params['repair_app_id'], null, null, $alipayPublicKey, $rsaPrivateKey);
        return $alipayLifeService;
    }


    //报事报修发送消息
    public function sendRepairMsg($to_user_id, $form_id, $id, $notifyUrl = null)
    {
        $alipayLifeService = $this->getAliService();
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }
        $data = ['keyword1' => ['value' => '您提交的报事报修已处理，请查看详情！']];
        $biz = [
            'to_user_id' => $to_user_id,
            'form_id' => $form_id,
            'user_template_id' => 'MTE0NGUxNTJmNWQ1NDE4MTcwOTIwM2RhN2U5OWUxNWM=',
            'page' => "/pages/orderDetails/orderDetails?repair_id={$id}",
            'data' => ($data),
        ];
        $restu = $alipayLifeService->sendRepairMsg($biz);
    }

    //验签
    public function rsaCheck($params)
    {
        return $this->_aop->rsaCheckV1($params);
    }

    //获取人脸采集特征值
    public function getZolozIdentification($bizId, $zimId, $bizType)
    {
        $biz = [
            'biz_id' => $bizId,
            'zim_id' => $zimId,
            'extern_param' => [
                'bizType' => $bizType
            ],
        ];
        $params['biz_content'] = json_encode($biz);
        return $this->_aop->execute('zoloz.identification.user.web.query', $params);
    }

    //解密字符串

    /**
     * Notes: 小程序解密数据
     * @param $query
     * @return string
     * @throws Exception
     */
    public function decryptData($query)
    {
        try {
            //判断报文是否加密, 非加密数据直接返回数据
            if (is_array($query['response'])) {
                $res = $query['response'];
            } else {
                $query['sign_type'] = $query['sign_type'] ? $query['sign_type'] : 'RSA2';
                //验签
                $query['sign'] = str_replace(" ", '+', $query['sign']);
                $query['response'] = str_replace(" ", '+', $query['response']);
                $signData = "\"{$query['response']}\"";
//                $signRes = $this->_aop->verify($signData, $query['sign'], $this->_aop->alipayrsaPublicKey, $query['sign_type']);
//                if (!$signRes) {
//                    throw new MyException('验签失败，请检查验签配置是否正确');
//                }
                //解密
                $res = AopEncrypt::decrypt($query['response'], $this->_aes_secret);
                $result = json_decode($res, true);
                if ($result['code'] != 10000) {
                    throw new MyException($res['msg']);
                }
                return $result['mobile'];
            }
            return $res;
        } catch (\Exception $e) {
            throw new MyException($e->getMessage());
        }
    }
}

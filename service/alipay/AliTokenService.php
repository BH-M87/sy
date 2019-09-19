<?php
/**
 * 支付宝授权token服务
 * @author shenyang
 * @date 2017/07/29
 */
namespace service\alipay;

use app\models\PsUser;
use app\models\PsCommunityModel;
use app\models\PsPropertyAlipay;
use app\models\PsPropertyAlipayInfo;
use app\models\PsPropertyIsvToken;
use common\core\ali\AopRedirect;
use service\common\SmsService;
use service\BaseService;
use Yii;
use app\models\PsAliToken;
use app\models\PsPropertyCompany;
use yii\helpers\FileHelper;

Class AliTokenService extends BaseService {

    /**
     * 添加token入表
     * @param $type
     * @param $typeId
     * @param $response
     * @return bool
     */
    public function addToken($type, $typeId, $response)
    {
        if($type != 1 && !$type != 2) {
            return false;
        }
        $one = PsAliToken::find()->where(['type'=>$type, 'type_id'=>$typeId])->one();
        if($one) {
            $one->token = $response['app_auth_token'];
            $one->refresh_token = $response['app_refresh_token'];
            $one->expires_in = $response['expires_in'];
            $one->re_expires_in = $response['re_expires_in'];
            $one->create_at = time();
            return $one->save();
        }
        $model = new PsAliToken();
        $model->type = $type;//1:物业公司，2企业商家
        $model->type_id = $typeId;
        $model->token = $response['app_auth_token'];
        $model->refresh_token = $response['app_refresh_token'];
        $model->expires_in = $response['expires_in'];
        $model->re_expires_in = $response['re_expires_in'];
        $model->create_at = time();
        return $model->save();
    }

    /**
     * 根据type+nonce获取相关ID
     * @param $type
     * @param $nonce
     */
    public function getTypeId($type, $nonce)
    {
        if($type != 1 && $type != 2) {
            return false;
        }
        if($type == 1) {
            return PsPropertyCompany::find()->select('id')
                ->where(['nonce'=>$nonce])
                ->scalar();
        }
    }

    /**
     * 从表中获取token
     * @param $type
     * @param $typeId
     * @return mixed
     */
    public function getTokenByType($type, $typeId)
    {
        $model = PsPropertyIsvToken::find()
            ->where(['type'=>$type, 'type_id' => $typeId])
            ->one();
        if($model) {
            if(time() - $model["create_at"] >= 300 * 3600 * 24) {
                $result = $this->getScanToken($model['refresh_token'], true);//刷新
                if ($result['code'] == 10000) {
                    //更新token
                    $model->token = $result['app_auth_token'];
                    $model->refresh_token = $result['app_refresh_token'];
                    $model->create_at = time();
                    return $model->token;
                }
            }
            return $model->token;
        }
        return '';
    }


    //获取当面付授权令牌
    public function getScanToken($code, $refresh=false, $nonce = '')
    {
        $aop = new AopRedirect();
        $aop->appId = Yii::$app->params['property_isv_app_id'];
        $aop->gatewayUrl = Yii::$app->params['gate_way_url'];
        $aop->alipayrsaPublicKey = file_get_contents(Yii::$app->params['property_isv_alipay_public_key_file']);
        $aop->rsaPrivateKey = file_get_contents(Yii::$app->params['property_isv_merchant_private_key_file']);
        $aop->signType = 'RSA2';

        if($refresh) {
            $data['grant_type'] = 'refresh_token';
            $data['refresh_token'] = $code;
            $params['biz_content'] = json_encode($data);
            $result = $aop->execute('alipay.open.auth.token.app', $params);
        } else {
            $data['grant_type'] = 'authorization_code';
            $data['code'] = $code;
            $params['biz_content'] = json_encode($data);
            $result = $aop->execute('alipay.open.auth.token.app', $params);
        }
        if(!empty($result['code']) && $result['code'] == 10000) {
            $logs['msg'] = 'success';
        } else {
            $logs['msg'] = 'failed';
        }
        $logs['code'] = $code;
        $logs['refresh'] = $refresh;
        $logs['result'] = $result;
        $logs['nonce'] = $nonce;
        $this->log(json_encode($logs));
        return $result;
    }

    /**
     * 添加token入表
     * @param $type
     * @param $typeId
     * @param $response
     * @return bool
     */
    public function addScanToken($type, $typeId, $response)
    {
        if($type != 1 && !$type != 2) {
            return false;
        }
        $one = PsPropertyIsvToken::find()->where(['type'=>$type, 'type_id'=>$typeId])->one();
        if($one) {
            $one->token = $response['app_auth_token'];
            $one->refresh_token = $response['app_refresh_token'];
            $one->expires_in = $response['expires_in'];
            $one->re_expires_in = $response['re_expires_in'];
            $one->create_at = time();
            return $one->save();
        }
        $model = new PsPropertyIsvToken();
        $model->type = $type;//1:物业公司，2企业商家
        $model->type_id = $typeId;
        $model->token = $response['app_auth_token'];
        $model->refresh_token = $response['app_refresh_token'];
        $model->expires_in = $response['expires_in'];
        $model->re_expires_in = $response['re_expires_in'];
        $model->create_at = time();
        if ($model->save()) {
            //保存公司信息
            $companyModel = PsPropertyCompany::findOne($typeId);
            $companyModel->has_sign_qrcode = 1;
            $companyModel->save();
            return true;
        }
    }

    /**
     * 校验token值
     * @param $tokenModel token 记录
     * @param $type bool 是否签约当面付 true 已签约 false 未签约
     * @return mixed
     */
    public function checkToken($tokenModel)
    {
        if (time() - $tokenModel->create_at >= 300 * 3600 * 24) {
            $result = $this->getScanToken($tokenModel->refresh_token, true);
            if ($result['code'] == 10000) {
                //更新token
                $tokenModel->token = $result['app_auth_token'];
                $tokenModel->refresh_token = $result['app_refresh_token'];
                $tokenModel->create_at = time();
                if ($tokenModel->save()) {
                    $msg = '物业公司Id='.$tokenModel->type_id.'的token更新成功';
                } else {
                    $msg = '物业公司Id='.$tokenModel->type_id.'的token:'.$tokenModel->token.',refresh_token:'.$tokenModel->refresh_token.'更新失败';
                }
                $this->log($msg, 'cplife/replace-token');
                return $tokenModel->token;
            }
        } else {
            return $tokenModel->token;
        }
    }

    //记录支付宝 token 操作日志
    public function log($message, $action='')
    {
        if(!$action) {
            $action = Yii::$app->controller->action->uniqueId;
        }
        $name = date('Y-m-d');
        $msg = date('Y-m-d H:i:s') . '、action：' . $action . '、' . $message. "\r\n";
        $path = Yii::$app->basePath . '/runtime/token/' . $name ;

        if (FileHelper::createDirectory($path, 0777)) {
            if (!file_exists($path.'/token.txt')) {
                file_put_contents($path.'/token.txt', $msg, FILE_APPEND);
                chmod($path.'/token.txt', 0777);//第一次创建文件，设置777权限
            } else {
                file_put_contents($path.'/token.txt', $msg, FILE_APPEND);
            }
        }
    }

    /**
     * 根据物业公司id查询授权token
     * @param $proCompanyId
     * @return mixed|string
     */
    public function getTokenByCompany($proCompanyId)
    {
        $userToken = PsPropertyIsvToken::find()
            ->where(['type'=>1, 'type_id' => $proCompanyId])
            ->one();

        //判断token是否快要过期，如果要过期，更新token
        if (!$userToken) {
            return '';
        }
        $token = $this->checkToken($userToken);
        return $token;
    }

    /**
     * 根据支付宝小区编号获取token
     */
    public function getTokenByCommunityNo($communityNo)
    {
        $companyId = PsCommunityModel::find()->select('pro_company_id')
            ->where(['community_no' => $communityNo])
            ->scalar();
        return $this->getTokenByCompany($companyId);
    }

    /**
     * 根据小区id获取授权token值
     * @param $communityId
     * @return mixed
     */
    public function getTokenByCommunityId($communityId)
    {
        $companyId = PsCommunityModel::find()->select('pro_company_id')
            ->where(['id' => $communityId])
            ->scalar();
        return $this->getTokenByCompany($companyId);
    }


}
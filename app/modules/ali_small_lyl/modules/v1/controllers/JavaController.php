<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2019/12/4
 * Time: 10:09
 */
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use common\core\F;
use service\property_basic\JavaOfCService;
use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\PsCommon;
use yii\base\Exception;

class JavaController extends BaseController{

    /*
     * 支付宝授权码获取openId/访问令牌
     */
    public function actionLoginAuth(){
        try{
            $data = $this->params;
            $result = JavaOfCService::service()->loginAuth($data);
            if(!empty($result['message'])){
                return PsCommon::responseAppFailed($result['message']);
            }
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    // 授权码换取token信息
    public function actionExchangeAuthValue()
    {
        try {
            $data = $this->params;
            $result = JavaOfCService::service()->exchangeAuthValue($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }


    /**
     * Notes: 首页展示的房屋信息[鉴权]
     * Author: zph
     * Date: 2019/12/7 14:33
     */
    public function actionLastChosenRoom(){
        try{
            $data = $this->params;
            $result = JavaOfCService::service()->lastChosenRoom($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /**
     * 用户信息
     * @return string
     */
    public function actionMemberBase(){
        try{
            $data = $this->params;
            $result = JavaOfCService::service()->memberBase($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /**
     * 图片上传
     * @return string
     */
    public function actionUploadImg(){
        try{
            $data = $this->params;
            if (empty($data['uploadFile'])) {
                return F::apiFailed('请上传文件！');
            }

            $result = JavaOfCService::service()->uploadImg($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    // 七牛token
    public function actionQiniuToken()
    {
        try {
            $data = $this->params;
            $result = JavaOfCService::service()->qiniuToken($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    // 我的房屋
    public function actionMyRoom()
    {
        try {
            $data = $this->params;
            $result = JavaOfCService::service()->myRoomList($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }
}
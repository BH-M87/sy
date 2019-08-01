<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/7/26
 * Time: 11:10
 */

namespace alisa\modules\door\controllers;


use common\services\door\KeyService;
use common\libs\F;

class KeyController extends BaseController
{

    //获取token
    public function actionGetToken()
    {
        $result = KeyService::service()->get_token($this->params);
        return $this->dealResult($result);
    }

    //验证人脸是否上传
    public function actionCheckFace()
    {
        $result = KeyService::service()->check_face($this->params);
        return $this->dealResult($result);
    }

    //上传人脸照片识别
    public function actionUploadFace()
    {
        $params = F::request();
        \Yii::info("face_post_data:".json_encode($params), 'api');
        \Yii::info("face_post_file:".json_encode($_FILES), 'api');
        $result = KeyService::service()->upload_face($params,$_FILES['face']['tmp_name']);
        return $this->dealResult($result);
    }

    //获取房源列表接口
    public function actionGetHouseList()
    {
        $result = KeyService::service()->get_house_list($this->params);
        return $this->dealResult($result);
    }

    //常用钥匙列表
    public function actionGetKeys()
    {
        $result = KeyService::service()->get_keys($this->params);
        return $this->dealResult($result);
    }

    //保存访客信息
    public function actionVisitorAdd()
    {
        $result = KeyService::service()->visitor_add($this->params);
        return $this->dealResult($result);
    }

    //获取最后一次访问记录
    public function actionGetLastVisit()
    {
        $result = KeyService::service()->get_last_visit($this->params);
        return $this->dealResult($result);
    }

    //保存最后一次访问记录
    public function actionLastVisit()
    {
        $result = KeyService::service()->last_visit($this->params);
        return $this->dealResult($result);
    }


    /**
     * 获取全部钥匙列表
     * @return string
     */
    public function actionGetKeyList()
    {
        $result = KeyService::service()->get_key_list($this->params);
        return $this->dealResult($result);
    }

    /**
     * 编辑常用钥匙
     * @return string
     */
    public function actionEditKeys()
    {
        $result = KeyService::service()->edit_keys($this->params);
        return $this->dealResult($result);
    }

    /**
     * 上传图片接口
     * @return string
     */
    public function actionUploadImage()
    {
        if(empty($_FILES['file']['tmp_name'])){
            return F::apiFailed("图片内容不能为空");
        }
        $result = KeyService::service()->upload_image($_FILES['file']['tmp_name']);
        return $this->dealResult($result);
    }

    /**
     * 访客密码
     * @return string
     */
    public function actionVisitorPassword()
    {
        $result = KeyService::service()->visitor_password($this->params);
        return $this->dealResult($result);
    }

    //获取开门二维码
    public function actionGetCode()
    {
        $result = KeyService::service()->get_code($this->params);
        return $this->dealResult($result);
    }

    /**
     * 远程开门
     * @return string
     */
    public function actionOpenDoor()
    {
        $result = KeyService::service()->open_door($this->params);
        return $this->dealResult($result);
    }



}
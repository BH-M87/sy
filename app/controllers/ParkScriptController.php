<?php
namespace app\controllers;


use service\park\ParkScriptService;
use yii\base\Controller;
use yii\base\Exception;

class ParkScriptController extends Controller  {


    //业主车辆在场 预约时间开始前15分钟内 脚本
    public function actionNotice15(){
        try{
            ParkScriptService::service()->notice15($this->params);
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //业主车辆在场 预约时间开始前15分钟内 脚本
    public function actionNotice5(){
        try{
            ParkScriptService::service()->notice5($this->params);
        }catch (Exception $e){
            exit($e->getMessage());
        }

    }

}
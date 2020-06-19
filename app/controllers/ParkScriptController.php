<?php
namespace app\controllers;


use service\park\ParkScriptService;
use yii\base\Controller;
use yii\base\Exception;

class ParkScriptController extends Controller  {


    //业主车辆在场 预约时间开始前15分钟内 脚本
    public function actionNotice15(){
        try{
            ParkScriptService::service()->notice15();
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //业主车辆在场 预约时间开始前15分钟内 脚本
    public function actionNotice5(){
        try{
            ParkScriptService::service()->notice5();
        }catch (Exception $e){
            exit($e->getMessage());
        }

    }

    //预约人迟到 取消预约
    public function actionLateCancel(){
        try{
            ParkScriptService::service()->lateCancel();
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //预约时间结束前15分钟提醒
    public function actionNoticeOut(){
        try{
            ParkScriptService::service()->noticeOut();
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2019/12/25
 * Time: 16:43
 */
namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\services\TestService;
use service\property_basic\JavaDDService;
use yii\base\Controller;
use yii\base\Exception;
use Yii;
class TestController extends Controller {

    public function actionIndex(){
        try{
            $service = new JavaDDService();
            $params=[
                'id' => "1208946410513555457"
            ];
            $result = $service->getCompanyToken($params);
            print_r($result);
//            $service = new TestService();
//            $result = $service->getAccessToken();
//            $result = $service->getSuiteToken();
//            $result = $service->instanceAdd();
//            $result = $service->instancePosition();
//            $result = $service->instanceAddGroup();
//            $result = $service->instanceAddSelPosition();
//            $result = $service->instanceAddPosition();
//            $result = $service->instanceAddUser();
//            $result = $service->instanceSelUser();//查看打卡组内的用户
//            $result = $service->callback();//设置回调
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }
}
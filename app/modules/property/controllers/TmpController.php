<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/9/26
 * Time: 10:53
 */

namespace app\modules\property\controllers;


use app\models\ParkingCars;
use common\core\F;
use yii\web\Controller;

class TmpController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionParkingCars()
    {
        $cars = \Yii::$app->db->createCommand("select * from parking_cars_copy")->queryAll();
        foreach ($cars as $car) {
            $carModel = new ParkingCars();
            $carModel->community_id = 37;
            $img = '';
            //图片处理
            if ($car['car_img']) {
                $img = F::trunsImg($car['car_img']);
            }

            $carModel->images = $img;
            if ($carModel->save()) {
                echo $carModel->id.'--车牌号:'.$carModel->car_num."\r\n";
            }
        }

    }
}
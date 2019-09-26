<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/9/21
 * Time: 14:15
 */
namespace console\controllers;

use app\models\ParkingCars;
use app\models\PsCommunityBuilding;
use app\models\PsCommunityUnits;
use common\core\F;

include_once dirname(__DIR__,2)."/app/models/BaseModel.php";
include_once dirname(__DIR__,2)."/app/models/PsCommunityBuilding.php";
include_once dirname(__DIR__,2)."/app/models/PsCommunityUnits.php";
include_once dirname(__DIR__,2)."/app/models/ParkingCars.php";


class TmpController extends ConsoleController
{
    public function actionUnit()
    {
        $buildings = PsCommunityBuilding::find()
            ->where(['community_id' => [37,38,39,40,41]])
            ->asArray()
            ->all();
        foreach ($buildings as $k => $v) {
            $unitNum = PsCommunityUnits::find()
                ->where(['community_id' => $v['community_id'], 'building_id' => $v['id']])
                ->asArray()
                ->count();
            $model = PsCommunityBuilding::findOne($v['id']);
            $model->unit_num = $unitNum;
            if ($model->save()) {
                echo "success"."\r\n";
            }
        }
    }

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
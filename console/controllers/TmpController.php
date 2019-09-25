<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/9/21
 * Time: 14:15
 */
namespace console\controllers;

use app\models\PsCommunityBuilding;
use app\models\PsCommunityUnits;

include_once dirname(__DIR__,2)."/app/models/BaseModel.php";
include_once dirname(__DIR__,2)."/app/models/PsCommunityBuilding.php";
include_once dirname(__DIR__,2)."/app/models/PsCommunityUnits.php";


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
}
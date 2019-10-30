<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/10/18
 * Time: 11:35
 */

namespace app\modules\hard_ware_butt\controllers;


use app\models\ParkingCars;
use app\models\ParkingUserCarport;
use app\models\ParkingUsers;
use app\models\PsRoomUser;
use common\core\F;
use service\common\ExcelService;
use yii\db\mssql\PDO;
use yii\web\Controller;

class TmpController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionImportCar()
    {
        $communityId = F::request('community_id');
        $file = $_FILES['file'];
        $excel = ExcelService::service();
        $sheet = $excel->loadFromImport($file);
        $totals = $sheet->getHighestRow();//总条数
        $importDatas = $sheet->toArray(null, false, false, true);
        unset($importDatas[1]);
        foreach ($importDatas as $v) {
            $params['user_name'] = $v['A'];
            $params['user_mobile'] = (string)$v['D'];
            $params['plate_no'] = $v['F'];

            $carInfo = ParkingCars::find()
                ->where(['car_num' => $params['plate_no'], 'community_id' => $communityId])
                ->asArray()
                ->one();
            if ($carInfo) {
                continue;
            }
            $carModel = new ParkingCars();
            $carModel->community_id = $communityId;
            $carModel->car_num = $params['plate_no'];
            $carModel->created_at = time();
            if ($carModel->save()) {
                $carUserInfo = ParkingUsers::find()
                    ->where(['user_mobile' =>params['user_mobile'], 'community_id' => $communityId])
                    ->one();
                if ($carUserInfo) {
                    $carUserInfo->user_name = $params['user_name'];
                    $carUserInfo->save();
                } else {
                    //echo $params['user_mobile'];exit;
                    $carUserInfo = new ParkingUsers();
                    $carUserInfo->community_id = $communityId;
                    $carUserInfo->user_name = $params['user_name'];
                    $carUserInfo->user_mobile = $params['user_mobile'];
                    $carUserInfo->created_at = time();
                    if (!$carUserInfo->save()) {
                        print_r($carUserInfo->getErrors());exit;
                        echo "车主保存失败";
                    }
                }
                //保存关联关系
                $relateModel = ParkingUserCarport::find()
                    ->where(['car_id' => $carModel->id, 'user_id' => $carUserInfo->id])
                    ->asArray()
                    ->one();
                if (!$relateModel) {
                    $relateModel = new ParkingUserCarport();
                    $relateModel->user_id = $carUserInfo->id;
                    $relateModel->car_id = $carModel->id;
                    $relateModel->created_at = time();
                    $relateModel->save();
                }
            }
            echo $params['plate_no']."--小区--".$communityId."\r\n";
        }
    }

    public function actionImportYezhu()
    {
        $file = $_FILES['file'];
        $excel = ExcelService::service();
        $sheet = $excel->loadFromImport($file);
        $totals = $sheet->getHighestRow();//总条数
        $importDatas = $sheet->toArray(null, false, false, true);
        $insertSql = "insert  into `ps_tmp_room_user`(`card_no`,`mobile`) VALUES";
        foreach ($importDatas as $v) {
            $cardNo = $v['A'];
            $mobile = $v['B'];
            $insertSql .= "('{$cardNo}','{$mobile}'),";
        }
        $insertSql = substr($insertSql, 0, -1);
        $re = \Yii::$app->getDb()->createCommand($insertSql)->execute();
        var_dump($re);exit;
//        if ($zwpdo->exec($insertSql)) {
//            $doUpdate = true;
//            echo $i."插入成功". "\r\n";
//            break;
//        };
    }


    public function actionUpdateUser()
    {
        $sql = "SELECT b.room_user_id,b.xqcard_no,b.tcard_no,b.mobile,b.community_id,b.`name`,b.yzname
from (
SELECT pu.card_no xqcard_no, t.card_no tcard_no,pu.mobile,pu.community_id,m.`name`,pu.`name` as yzname,pu.id as room_user_id
from ps_room_user pu LEFT JOIN
ps_tmp_room_user t ON pu.mobile = t.mobile
LEFT JOIN ps_community m on m.id = pu.community_id
ORDER BY t.id desc) as b
WHERE b.xqcard_no = \"\" and b.tcard_no != ''
ORDER BY b.community_id DESC";
        $re = \Yii::$app->getDb()->createCommand($sql)->queryAll();

        foreach ($re as $v) {
            $updateSql = 'update ps_room_user set card_no="'.$v['tcard_no'].'" where id='.$v['room_user_id'];
            $re = \Yii::$app->getDb()->createCommand($updateSql)->execute();
            echo $re."\r\n";
        }
    }


}
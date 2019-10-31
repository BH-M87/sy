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

use app\models\PsAppMember;
use app\models\PsCommunityExposure;
use app\models\PsCommunityExposureImage;
use common\core\Curl;

use app\models\PsRoomUser;

use common\core\F;
use phpDocumentor\Reflection\Types\Null_;
use service\common\ExcelService;
use yii\db\mssql\PDO;
use yii\web\Controller;

class TmpController extends Controller
{
    // java路由
    public $urlJava= [
        'addEvent' => '/eventDing/addEvent', // 新增曝光台事件
        'dealDetail' => '/community/communityExposure/getExposureDealWithDetailById' // 处理结果
    ];

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


    //修复曝光台事件
    public function actionXfExposureData()
    {
        $exposureData = PsCommunityExposure::find()
            ->select('e.*,c.name as community_name')
            ->alias('e')
            ->leftJoin('ps_community c','c.id = e.community_id')
            ->where(['event_no' => NULL])
            ->asArray()
            ->all();
        foreach ($exposureData as $v) {
            $member = PsAppMember::find()->alias('A')->leftJoin('ps_member B', 'B.id = A.member_id')
                ->select('B.*')->where(['A.app_user_id' => $v['app_user_id']])->asArray()->one();
            if (!$member) {
                echo "业主不存在";die;
            }
            //查询图片
            $imgs = PsCommunityExposureImage::find()
                ->select('image_url')
                ->where(['community_exposure_id' => $v['id']])
                ->asArray()
                ->column();
            // 处理结果 调Java接口
            $data = [
                'title' => $v['title'],
                'description' => $v['describe'],
                'eventFrom' => 2,
                'eventTime' => $v['created_at'],
                'eventType' => $v['event_child_type_id'],
                'imageUrl' => $imgs,
                'reportAddress' => $v['address'],
                'address' => $v['address'],
                'userId' => $member['id'],
                'xqName' => $v['community_name'],
                'xqOrgCode' => $v['event_community_no']
            ];
            $event = Curl::getInstance()->post(\Yii::$app->params['java_domain'].$this->urlJava['addEvent'], json_encode($data,JSON_UNESCAPED_UNICODE), true);
            \Yii::info("tmp-add-event".'request-url:'.\Yii::$app->params['java_domain'].$this->urlJava['addEvent'].' request-params:'.json_encode($data,JSON_UNESCAPED_UNICODE).'---result'.$event,'smallapp');
            $model = PsCommunityExposure::findOne($v['id']);
            $model->event_no = json_decode($event, true)['data'];
            $reData = $model->save();
            \Yii::info("tmp-add-event".'---insert-result'.$reData,'smallapp');
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
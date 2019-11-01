<?php
/**
 * User: ZQ
 * Date: 2019/11/1
 * Time: 10:02
 * For: 一车一档
 */

namespace service\street;


use app\models\ParkingCars;
use app\models\ParkingUserCarport;
use app\models\ParkingUsers;
use common\core\F;
use common\core\PsCommon;

class CarDataService extends BaseService
{
    public function searchList($params,$page, $pageSize)
    {
        $roomId = PsCommon::get($params,"room_id",0);//关联房屋
        $carNum = PsCommon::get($params,"car_num");//车牌号
        $userName = PsCommon::get($params,"member_name");//车主姓名
        $offset = ($page - 1) * $pageSize;
        $model = ParkingUserCarport::find()->alias("puc")
            ->innerJoin(['pu'=>ParkingUsers::tableName()],'pu.id = puc.user_id')
            ->innerJoin(['pc'=>ParkingCars::tableName()],'pc.id = puc.car_id')
            ->leftJoin(['slr'=>"st_labels_rela"],'slr.data_id = puc.car_id')
            ->where(['slr.data_type'=>3]);
        if($roomId){
            $model->andFilterWhere(['puc.room_id'=>$roomId]);
        }
        if($carNum){
            $model->andFilterWhere(['like','pc.car_num',$carNum]);
        }
        if($userName){
            $model->andFilterWhere(['like','pc.user_name',$userName]);
        }
        return $model->select(['pc.id','pc.car_num','pc.car_model','pc.car_color','pc.images'])
            ->groupBy("puc.car_id")
            ->offset($offset)->limit($pageSize)
            ->asArray()->all();

    }

    public function getList($params,$page, $pageSize)
    {
        $list = $this->searchList($params,$page, $pageSize);
        if($list){
            foreach ($list as $key =>$value) {
                $newList = $this->dealDetail($value);
            }
        }
    }

    public function dealDetail($params)
    {
        $detail['car_id'] = PsCommon::get($params,"id",0);
        $detail['car_num'] = PsCommon::get($params,"car_num");
        $car_image = PsCommon::get($params,"images");
        if($car_image){
            $car_image = F::getOssImagePath($car_image,'zje');
        }
        $detail['car_image'] = $car_image;
        $detail['plate_type_str'] = PsCommon::get($params,"car_model");
        $detail['car_color_str'] = PsCommon::get($params,"car_color");
        $detail['label_list'] = PsCommon::get($params,"id",0);

        $detail['user_name'] = PsCommon::get($params,"user_name",0);
        $detail['user_mobile'] = PsCommon::get($params,"user_mobile",0);
        $detail['room_address'] = PsCommon::get($params,"room_address",0);
        $detail['community_name'] = PsCommon::get($params,"community_name",0);
        return $detail;
    }

    public function getLabelListByCarId($car_id)
    {
        $list = "";
        return $list ? $list : [];
    }

}
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
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\StLabelsRela;
use common\core\F;
use common\core\PsCommon;

class CarDataService extends BaseService
{

    //获取搜索列表
    public function getSearchList($params,$userInfo)
    {
        $roomId = PsCommon::get($params,"room_id",0);//关联房屋
        $carNum = PsCommon::get($params,"car_num");//车牌号
        $userName = PsCommon::get($params,"member_name");//车主姓名
        $label_id = PsCommon::get($params,"label_id",[]);//车辆标签
        $street_code = PsCommon::get($params,"street_code");
        $district_code = PsCommon::get($params,"district_code");
        $community_code = PsCommon::get($params,"community_code");
        $model = ParkingUserCarport::find()->alias("puc")
            ->innerJoin(['pu'=>ParkingUsers::tableName()],'pu.id = puc.user_id')
            ->innerJoin(['pc'=>ParkingCars::tableName()],'pc.id = puc.car_id');
        //处理搜索标签
        $labelId = BasicDataService::service()->dealSearchLabel($label_id,$street_code,$userInfo);
        if($labelId){
            $model->leftJoin(['slr'=>StLabelsRela::tableName()],'slr.data_id = puc.car_id')
                ->where(['slr.data_type'=>3,'slr.labels_id'=>$labelId]);
        }
        //根据搜索的条件以及登录的信息，去获取对应的小区id列表
        $community_id = UserService::service()->dealSearchCommunityId($street_code,$district_code,$community_code,$userInfo);
        $model->andWhere(['pc.community_id'=>$community_id]);
        if($roomId){
            $model->andFilterWhere(['puc.room_id'=>$roomId]);
        }
        if($carNum){
            $model->andFilterWhere(['like','pc.car_num',$carNum]);
        }
        if($userName){
            $model->andFilterWhere(['like','pu.user_name',$userName]);
        }
        return $model;
    }

    //获取一车一档的列表
    public function getCarList($params,$page, $pageSize,$userInfo)
    {
        $offset = ($page - 1) * $pageSize;
        $model = $this->getSearchList($params,$userInfo);
        return $model->select(['pc.id','pc.car_num','pc.car_model','pc.car_color','pc.images','pu.user_name','pu.user_mobile'])
            ->groupBy("puc.car_id")
            ->offset($offset)->limit($pageSize)
            ->asArray()->all();
    }

    //获取一车一档的总数
    public function getCarTotal($params,$userInfo)
    {
        $model = $this->getSearchList($params,$userInfo);
        return $model->groupBy("puc.car_id")->count();
    }

    /**
     * 获取一车一档列表的返回数组
     * @param $params
     * @param $page
     * @param $pageSize
     * @param $userInfo
     * @return mixed
     */
    public function getList($params,$page, $pageSize,$userInfo)
    {

        $list = $this->getCarList($params,$page, $pageSize,$userInfo);
        $newList = [];
        if($list){
            //处理一车一档详情
            foreach ($list as $key =>$value) {
                $newList[] = $this->dealDetail($value,"list");
            }
        }
        $return['list'] = $newList;
        //获取一车一档总数
        $return['totals'] = $this->getCarTotal($params,$userInfo);
        return $return;
    }

    //统一处理返回数据
    public function dealDetail($params,$type = '')
    {
        $car_id = PsCommon::get($params,"id",0);
        $detail['car_id'] = $car_id;
        $detail['car_num'] = PsCommon::get($params,"car_num");
        $car_image = PsCommon::get($params,"images");
        if($car_image){
            //根据oss上的key获取图片的具体地址
            $car_image = F::getOssImagePath($car_image,'zje');
        }
        $detail['car_image'] = $car_image;
        $detail['plate_type_str'] = PsCommon::get($params,"car_model");
        $detail['car_color_str'] = PsCommon::get($params,"car_color");
        //获取这个车辆下的所有标签
        $detail['label_list'] = LabelsService::service()->getLabelInfoByCarId($car_id);
        if($type == "detail"){
            $userInfo = ParkingUserCarport::find()->alias('puc')
                ->innerJoin(['pu'=>ParkingUsers::tableName()],'pu.id = puc.user_id')
                ->innerJoin(['pcr'=>PsCommunityRoominfo::tableName()],'pcr.id = puc.room_id')
                ->innerJoin(['c'=>PsCommunityModel::tableName()],'c.id = pu.community_id')
                ->select(['pu.user_name','pu.user_mobile','pcr.address as room_address','c.name as community_name'])
                ->where(['car_id'=>$car_id])
                ->asArray()->one();
            $detail['user_name'] = PsCommon::get($userInfo,"user_name");
            $detail['user_mobile'] = PsCommon::get($userInfo,"user_mobile");
            $detail['room_address'] = PsCommon::get($userInfo,"room_address");
            $detail['community_name'] = PsCommon::get($userInfo,"community_name");
        }
        return $detail;
    }

    /**
     * 获取详情
     * @param $params
     * @return mixed
     */
    public function getDetail($params)
    {
        $id = PsCommon::get($params,"id",0);
        $detail = ParkingCars::find()->where(['id'=>$id])->asArray()->one();
        return $this->dealDetail($detail,"detail");

    }

    public function getDayReport($params)
    {
        //获取今天之前30天的时间数组

        $dayList = '';
    }


}
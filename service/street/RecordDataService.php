<?php
/**
 * User: ZQ
 * Date: 2019/11/1
 * Time: 10:03
 * For: 出入库记录
 */

namespace service\street;


use app\models\DoorRecord;
use app\models\ParkingAcross;
use app\models\ParkingCars;
use app\models\ParkingUserCarport;
use app\models\ParkingUsers;
use app\models\PsMember;
use app\models\StLabelsRela;
use common\core\F;
use common\core\PsCommon;

class RecordDataService extends BaseService
{
    //开门方式
    public $open_type = [
        1 => ['id' => 1, 'name' => '人脸开门'],
        2 => ['id' => 2, 'name' => '蓝牙开门'],
        3 => ['id' => 3, 'name' => '密码开门'],
        4 => ['id' => 4, 'name' => '钥匙开门'],
        5 => ['id' => 5, 'name' => '门卡开门'],
        6 => ['id' => 6, 'name' => '扫码开门'],
        7 => ['id' => 7, 'name' => '临时密码'],
        8 => ['id' => 8, 'name' => '二维码开门'],
    ];

    //车辆进出类型
    public $across_type = [
        1 => ['id' => 1, 'name' => '进库'],
        2 => ['id' => 2, 'name' => '出库'],
    ];

    //车辆属性（车辆类别）
    public $car_type = [
        1 => ['id' => 1, 'name' => '访客'],
        2 => ['id' => 2, 'name' => '会员'],
    ];

    //获取公共参数
    public function getCommon()
    {
        $return['open_type'] = $this->returnIdName($this->open_type);
        $return['across_type'] = $this->returnIdName($this->across_type);
        $return['car_type'] = $this->returnIdName($this->car_type);
        return $return;
    }

    public function getDoorList($params,$page, $pageSize,$userInfo)
    {
        $list = $this->getDoorAllList($params,$page, $pageSize,$userInfo);
        $newList = [];
        //当前用户所拥有街道权限的所有标签
        $organization_type = 1;
        $organization_id = UserService::service()->geyStreetCodeByUserInfo($userInfo);
        if($list){
            //处理一车一档详情
            foreach ($list as $key =>$value) {
                $value['member_id'] = !empty($value['member_id']) ? $value['member_id'] : '';
                $value['face_url'] = !empty($value['face_url']) ? $value['face_url'] : '';
                $value['room_id'] = !empty($value['room_id']) ? $value['room_id'] : '';
                $value['open_time'] = date("Y-m-d H:i:s",$value['open_time']);
                $value['label'] = LabelsService::service()->getLabelInfoByMemberId($value['member_id'],$organization_type,$organization_id);
                $value['user_phone'] =  $value['user_phone'] ? F::processMobile($value['user_phone']) : '';
                $value['capture_photo'] = $value['capture_photo'] ? F::getOssImagePath($value['capture_photo'], 'zjy') : '';
                $newList[] = $value;
            }
        }
        $return['list'] = $newList;
        //获取一车一档总数
        $return['totals'] = $this->getDoorAllTotal($params,$userInfo);
        return $return;
    }

    //获取搜索列表
    public function getDoorSearchList($params,$userInfo)
    {
        $label_id = PsCommon::get($params,"label_id",[]);//车辆标签
        $street_code = PsCommon::get($params,"street_code");
        $district_code = PsCommon::get($params,"district_code");
        $community_code = PsCommon::get($params,"community_code");

        $start_time = PsCommon::get($params,"start_time");//开始时间
        $end_time = PsCommon::get($params,"end_time");//结束时间

        $user = PsCommon::get($params,"user");//人员手机或姓名
        $open_type = PsCommon::get($params,"open_type");//开门方式
        $card_number = PsCommon::get($params,"card_number");//门卡卡号

        $model = DoorRecord::find()->alias("dr")
            ->leftJoin(['m'=>PsMember::tableName()],'dr.user_phone = m.mobile');
        //处理搜索标签
        $labelId = BasicDataService::service()->dealSearchLabel($label_id,$street_code,$userInfo);
        if($labelId){
            $model->leftJoin(['slr'=>StLabelsRela::tableName()],'slr.data_id = m.id')
                ->where(['slr.data_type'=>2,'slr.labels_id'=>$labelId]);
        }
        //根据搜索的条件以及登录的信息，去获取对应的小区id列表
        $community_id = UserService::service()->dealSearchCommunityId($street_code,$district_code,$community_code,$userInfo);
        $model->andWhere(['dr.community_id'=>$community_id]);
        //访客开门记录不展示
        //$model->andWhere(['dr.user_type'=>[1,2,3]]);
        if($start_time && $end_time){
            $start = strtotime($start_time." 00:00:00");
            $end = strtotime($end_time." 23:59:59");
            $model->andFilterWhere(['>=','dr.open_time',$start])
                ->andFilterWhere(['<','dr.open_time',$end]);
        }

        if($user){
            $model->andFilterWhere(['like','dr.user_name',$user])->orFilterWhere(['like','dr.user_phone',$user]);
        }

        if($open_type){
            $model->andFilterWhere(['dr.open_type'=>$open_type]);
        }

        if($card_number){
            $model->andFilterWhere(['like','dr.card_no',$card_number]);
        }
        return $model;
    }

    //获取一车一档的列表
    public function getDoorAllList($params,$page, $pageSize,$userInfo)
    {
        $offset = ($page - 1) * $pageSize;
        $model = $this->getDoorSearchList($params,$userInfo);
        return $model->select(['dr.id as record_id','m.id as member_id','m.face_url','dr.open_time','dr.open_type','dr.device_name','dr.card_no as card_number','dr.room_id',
            'dr.user_name','m.mobile as user_phone', 'dr.capture_photo'])
            ->offset($offset)->limit($pageSize)
            ->orderBy("dr.open_time desc")
            ->asArray()->all();
    }

    //获取一车一档的总数
    public function getDoorAllTotal($params,$userInfo)
    {
        $model = $this->getDoorSearchList($params,$userInfo);
        return $model->count();
    }

    /**
     * 获取停车记录
     * @param $params
     * @param $page
     * @param $pageSize
     * @param $userInfo
     * @return mixed
     */
    public function getCarList($params,$page, $pageSize,$userInfo)
    {
        $list = $this->getCarAllList($params,$page, $pageSize,$userInfo);
        $newList = [];
        if($list){
            //处理一车一档详情
            foreach ($list as $key =>$value) {
                $newList[] = CarDataService::service()->dealDetail($value,"record-list",$userInfo);
            }
        }
        $return['list'] = $newList;
        //获取一车一档总数
        $return['totals'] = $this->getCarAllTotal($params,$userInfo);
        return $return;
    }

    //获取搜索列表
    public function getCarSearchList($params,$userInfo)
    {
        $carNum = PsCommon::get($params,"car_num");//车牌号
        $label_id = PsCommon::get($params,"label_id",[]);//车辆标签
        $street_code = PsCommon::get($params,"street_code");
        $district_code = PsCommon::get($params,"district_code");
        $community_code = PsCommon::get($params,"community_code");

        $start_time = PsCommon::get($params,"start_time");//开始时间
        $end_time = PsCommon::get($params,"end_time");//结束时间
        $across_type = PsCommon::get($params,"across_type");//进出方式
        $car_type = PsCommon::get($params,"car_type");//车辆类别，车辆属性

        $model = ParkingAcross::find();
        //处理搜索标签
        $labelId = BasicDataService::service()->dealSearchLabel($label_id,$street_code,$userInfo);
        if($labelId){
            //根据标签id找到车牌id
            $car_id = StLabelsRela::find()->select(['data_id'])->distinct()->where(['data_type'=>3,'labels_id'=>$labelId])->asArray()->column();
            //根据车牌id找到车牌
            $car_num = ParkingCars::findOne($car_id)->car_num;
            $model->andWhere(['car_num'=>$car_num]);
        }
        //根据搜索的条件以及登录的信息，去获取对应的小区id列表
        $community_id = UserService::service()->dealSearchCommunityId($street_code,$district_code,$community_code,$userInfo);
        $model->andWhere(['community_id'=>$community_id]);
        if($carNum){
            $model->andFilterWhere(['like','car_num',$carNum]);
        }
        if($start_time && $end_time){
            $start = strtotime($start_time." 00:00:00");
            $end = strtotime($end_time." 23:59:59");
            $model->andFilterWhere(['>=','created_at',$start])
                ->andFilterWhere(['<','created_at',$end]);
        }

        if($across_type){
            $model->andFilterWhere(['across_type'=>$across_type]);
        }

        if($car_type){
            $model->andFilterWhere(['car_type'=>$car_type]);
        }
        return $model;
    }

    //获取一车一档的列表
    public function getCarAllList($params,$page, $pageSize,$userInfo)
    {
        $offset = ($page - 1) * $pageSize;
        $model = $this->getCarSearchList($params,$userInfo);
        //'pc.id as car_id','pc.car_num','pc.car_model','pc.car_color','pc.images',
        return $model->select(['id as record_id', 'capture_photo', 'created_at as open_time','across_type as open_type','gate_address as open_addrss','park_time','car_num','community_id'])
            ->offset($offset)->limit($pageSize)
            ->orderBy("created_at desc")
            ->asArray()->all();
    }

    //获取一车一档的总数
    public function getCarAllTotal($params,$userInfo)
    {
        $model = $this->getCarSearchList($params,$userInfo);
        return $model->count();
    }
}
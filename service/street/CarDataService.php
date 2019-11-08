<?php
/**
 * User: ZQ
 * Date: 2019/11/1
 * Time: 10:02
 * For: 一车一档
 */

namespace service\street;


use app\models\ParkingAcross;
use app\models\ParkingCars;
use app\models\ParkingUserCarport;
use app\models\ParkingUsers;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\StLabelsRela;
use app\models\StRecordReport;
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
        /*$model = ParkingUserCarport::find()->alias("puc")
            ->innerJoin(['pu'=>ParkingUsers::tableName()],'pu.id = puc.user_id')
            ->innerJoin(['pc'=>ParkingCars::tableName()],'pc.id = puc.car_id');*/
        $model = ParkingCars::find()->alias("pc")
            ->leftJoin(['puc'=>ParkingUserCarport::tableName()],'pc.id = puc.car_id')
            ->leftJoin(['pu'=>ParkingUsers::tableName()],'pu.id = puc.user_id');
        //处理搜索标签
        $labelId = BasicDataService::service()->dealSearchLabel($label_id,$street_code,$userInfo);
        if($labelId){
            $model->leftJoin(['slr'=>StLabelsRela::tableName()],'slr.data_id = puc.car_id')
                ->where(['slr.data_type'=>3,'slr.labels_id'=>$labelId]);
        }
        //根据搜索的条件以及登录的信息，去获取对应的小区id列表
        $community_id = UserService::service()->dealSearchCommunityId($street_code,$district_code,$community_code,$userInfo);
        $model->andWhere(['pc.community_id'=>$community_id]);
        $group = PsCommon::get($params,"group");
        $building = PsCommon::get($params,"building");
        $unit = PsCommon::get($params,"unit");
        if($roomId){
            $model->andFilterWhere(['puc.room_id'=>$roomId]);
        }
        //如果只搜索到单元，则获取这个单元下面所有的房屋
        if($unit){
            $roomId =PsCommunityRoominfo::find()->select(['id'])->where(['unit'=>$unit])->asArray()->column();
            $model->andFilterWhere(['puc.room_id'=>$roomId]);
        }
        //如果只搜索到楼幢，则获取这个楼幢下面所有的房屋
        if($building){
            $roomId =PsCommunityRoominfo::find()->select(['id'])->where(['building'=>$building])->asArray()->column();
            $model->andFilterWhere(['puc.room_id'=>$roomId]);
        }
        //如果只搜索到苑期区，则获取这个苑期区下面所有的房屋
        if($group){
            $roomId =PsCommunityRoominfo::find()->select(['id'])->where(['group'=>$group])->asArray()->column();
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
            ->groupBy("pc.id")
            ->offset($offset)->limit($pageSize)
            ->asArray()->all();
    }

    //获取一车一档的总数
    public function getCarTotal($params,$userInfo)
    {
        $model = $this->getSearchList($params,$userInfo);
        return $model->groupBy("pc.id")->count();
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
        if(empty($car_id)){
            $car_id = PsCommon::get($params,"car_id",0);
        }
        $detail['car_id'] = $car_id;

        $car_image = PsCommon::get($params,"images");
        if($car_image){
            //根据oss上的key获取图片的具体地址
            $car_image = F::getOssImagePath($car_image,'zje');
        }
        $detail['car_image'] = $car_image;
        if($type == "record-list"){
            $detail['car_img'] = $detail['car_image'];
            $detail['car_number'] = substr_replace(PsCommon::get($params,"car_num"),'****',4,4);
            //获取这个车辆下的所有标签
            $detail['label'] = LabelsService::service()->getLabelInfoByCarId($car_id);
            $detail['open_time'] = date("Y-m-d H:i:s",PsCommon::get($params,"open_time"));
            $detail['open_type'] = PsCommon::get($params,"open_type");
            $detail['open_addrss'] = PsCommon::get($params,"open_addrss");
            $detail['park_time'] = $this->dealParkingTime(PsCommon::get($params,"park_time",0));
        }
        if($type == "list"){
            $detail['car_num'] = substr_replace(PsCommon::get($params,"car_num"),'****',4,4);
            $detail['plate_type_str'] = PsCommon::get($params,"car_model");
            $detail['car_color_str'] = PsCommon::get($params,"car_color");
            //获取这个车辆下的所有标签
            $detail['label_list'] = LabelsService::service()->getLabelInfoByCarId($car_id);
        }
        if($type == "detail"){
            $detail['car_num'] = PsCommon::get($params,"car_num");
            $detail['plate_type_str'] = PsCommon::get($params,"car_model");
            $detail['car_color_str'] = PsCommon::get($params,"car_color");
            //获取这个车辆下的所有标签
            $detail['label_list'] = LabelsService::service()->getLabelInfoByCarId($car_id);
            $userInfo = ParkingUserCarport::find()->alias('puc')
                ->innerJoin(['pu'=>ParkingUsers::tableName()],'pu.id = puc.user_id')
                ->innerJoin(['pcr'=>PsCommunityRoominfo::tableName()],'pcr.id = puc.room_id')
                ->select(['pu.user_name','pu.user_mobile','pcr.address as room_address'])
                ->where(['puc.car_id'=>$car_id])
                ->asArray()->one();
            $community_name = ParkingCars::find()->alias('pc')
                ->leftJoin(['c'=>PsCommunityModel::tableName()],'c.id = pc.community_id')
                ->select(['c.name as community_name'])
                ->where(['pc.id'=>$car_id])->asArray()->scalar();
            $detail['user_name'] = PsCommon::get($userInfo,"user_name");
            $detail['user_mobile'] = PsCommon::get($userInfo,"user_mobile");
            $detail['room_address'] = PsCommon::get($userInfo,"room_address");
            $detail['community_name'] = $community_name;
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
        $id = PsCommon::get($params,"car_id",0);
        $detail = ParkingCars::find()->where(['id'=>$id])->asArray()->one();
        return $this->dealDetail($detail,"detail");

    }

    //获取今天之前30天的时间数组
    public function getDayReport($params)
    {
        $id = PsCommon::get($params,'car_id',0);
        return BasicDataService::service()->getDayReport($id,1,30);
    }

    //返回出行规律图
    public function getTravelReport($params)
    {
        $id = PsCommon::get($params,'car_id',0);
        $list = BasicDataService::service()->getDayReport($id,1,30);
        //var_dump($list);die;
        $data = [];
        if($list){
            //sort($list);
            $data['week'] = array_slice($list,0,7);
            $sortColumn = array_column($data['week'],'day');
            array_multisort($sortColumn,SORT_ASC,$data['week']);
            $data['halfMonth'] = array_slice($list,0,15);
            $sortColumn = array_column($data['halfMonth'],'day');
            array_multisort($sortColumn,SORT_ASC,$data['halfMonth']);
            $data['month'] = $list;
            $sortColumn = array_column($data['month'],'day');
            array_multisort($sortColumn,SORT_ASC,$data['month']);
        }
        return $data;
    }

    //计算停车时间的字符串文字
    public function dealParkingTime($park_time)
    {
        //大于1天
        if($park_time > 1440){
            $day = intval($park_time/1440);//多少天
            $a = $park_time-$day*1440;//剩下的时间
            $hour = intval($a/60);//多少小时
            $minutes = $a-$hour*60;
            $string = $day."天".$hour."小时".$minutes."分钟";

        } elseif ($park_time > 60){
            $hour = intval($park_time/60);//多少小时
            $minutes = $park_time-$hour*60;
            $string = $hour."小时".$minutes."分钟";

        } else{
            $string = $park_time."分钟";
        }
        return $string;
    }

    //获取记录的详情
    public function getDayReportInfo($params)
    {
        $id = PsCommon::get($params,'car_id',0);
        $day = PsCommon::get($params,'day');
        $params['start_time'] = strtotime($day." 00:00:00");//一天的开始时间
        $params['end_time'] = strtotime($day." 23:59:59");//一天的结束时间
        $params['car_num'] = ParkingCars::find()->select(['car_num'])->where(['id'=>$id])->asArray()->scalar();
        //当天的具体记录
        $data = ParkingAcross::getList($params);
        $newList = [];
        if($data['list']){
            foreach ($data['list'] as $key => $value) {
                //处理时间
                $newList[$key]['id'] = $id;
                $newList[$key]['car_img'] = F::getOssImagePath($value['capture_photo']);
                $newList[$key]['type'] = $value['across_type'];
                $newList[$key]['time'] = date("Y-m-d H:i:s",$value['created_at']);
                $newList[$key]['address'] = $value['gate_address'];
                $newList[$key]['park_time'] = !empty($value['park_time']) ? $this->dealParkingTime($value['park_time']) : 0;
                $newList[$key]['community_name'] = $value['community_name'];
                $newList[$key]['room_address'] = $value['room_address'];
            }
        }
        return ['list' => $newList,'totals' => $data['totals']];
    }




}
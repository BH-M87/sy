<?php
namespace service\alipay;
use common\core\F;
use common\core\PsCommon;
use app\models\PsElectricMeter;
use app\models\PsMeterCycle;
use app\models\PsWaterMeterFrom;
use service\common\CsvService;
use service\common\ExcelService;
use service\BaseService;
use service\property_basic\CommonService;
use service\rbac\OperateService;
use service\room\RoomService;
use Yii;

class ElectrictMeterService extends  BaseService {

    //定义水费的缴费类型
    public static $meter_type = ['1'=>"固定电价",'2'=>"阶梯电价"];
    //电表的状态
    public static $meter_status  = ['1'=>"启用",'2'=>"禁用"];


    /**
     * 获取电表列表
     * @author yjh
     * @param $data
     * @return array
     */
    public function lists($data){
        $field = 'id,meter_no,meter_status,latest_record_time,`group_id`,building_id,unit_id,address,community_name,room_id,start_ton,latest_record_time,cycle_time,payment_time,remark';
        $data = $this->getElectrictData($data,$field,true);
        return $this->success($data);
    }

    /**
     * 单条查询
     * @author yjh
     * @param $id
     * @return array|null
     */
    public function show($id){
        $meter = PsElectricMeter::find()->select('id,community_id,meter_no,meter_status,room_id,`group_id`,building_id,unit_id,address,start_ton,latest_record_time,cycle_time,payment_time,remark')->where(["id"=>$id])->asArray()->one();
        if (!empty($meter)) {
            $meter["meter_status_desc"] = $meter["meter_status"] ? ElectrictMeterService::$meter_status[$meter["meter_status"]]:"";
            $meter["latest_record_time"] =  $meter["latest_record_time"]>0 ? date("Y-m-d",$meter["latest_record_time"]): "";
            return $this->success($meter);
        }
        return $this->failed('未找到数据');
    }


    /**
     * 数据验证
     * @author yjh
     * @param $data
     * @param $type 1编辑 2添加
     * @return array
     */
    public function checkElectric($data,$type = 1)
    {
        //鉴定房屋是否只有一个电表
//        $room = $this->checkRoom($data);
//        if ($room['code'] == 0) {
//            return $this->failed($room['msg']);
//        }
//        $room = $room['data'];
//        $data['room_id'] = $room['id'];
        //java 获得房屋信息
        $batchParams['token'] = $data['token'];
        $batchParams['community_id'] = $data['community_id'];
        $batchParams['roomId'] = $data['room_id'];
        $alipayService = new AlipayCostService();
        $roomInfoResult = $alipayService->getBatchRoomData($batchParams);
        if(empty($roomInfoResult[0])){
            return $this->failed("未找到房屋");
        }
        $roomInfo = $roomInfoResult[0];
        // 验证房屋id是否存在
        $is_meter = $this->checkMeter($data);
        if ($is_meter['code'] !== 0 && $type == 1) {
            return $this->failed('没绑定水表');
        } else if($is_meter['code'] == 0 && $type == 2) {
            return $this->failed($is_meter['msg']);
        }
        return $this->success(['room'=>$roomInfo]);
    }


    /**
     * 验证是否已有电表
     * @author yjh
     * @param $data
     * @return array
     */
    public function checkRoom($data)
    {
        $room = RoomService::service()->getRoom($data);
        if(empty($room) ){
            return $this->failed('未找到房屋');
        }
        return $this->success($room);
    }

    /**
     * 检查房屋是否已经绑定
     * @author yjh
     * @param $data
     * @return array
     */
    public function checkMeter($data)
    {
        $where['community_id'] = $data['community_id'];
        $where['room_id'] = !empty($data['room_id']) ? $data['room_id'] : null ;
        $where['id'] = !empty($data['id']) ? $data['id'] : null ;
        $where = F::searchFilter($where);
        $is_meter = PsElectricMeter::find()->where($where)->count('id');
        if(!empty($is_meter)) {
            return $this->failed('房屋已绑定电表');
        }
        return $this->success();
    }


    /**
     * 添加电表
     * @author yjh
     * @param $data
     * @param $user_info
     * @return array
     */
    public function add($data ,$user_info)
    {
        $check = $this->checkElectric($data,2);
        if ($check['code'] == 0) {
            return $this->failed($check['msg']);
        }
        $room = $check['data']['room'];
        $meter_arr = [
            "community_id" => $data["community_id"],
            "community_name" => $room["communityName"],
            "meter_no" => $data["meter_no"],
            "meter_status" => $data["meter_status"],
            "room_id"  => $data["room_id"],
            "group_id" =>     $room["groupId"],
            "building_id" =>  $room["buildingId"],
            "unit_id" =>     $room["unitId"],
            "address"=>   $room["home"],
            "start_ton" => $data["start_ton"],
            "latest_record_time" => strtotime($data["latest_record_time"]),
            "remark"=>PsCommon::get($data,'remark'),
            "create_at" =>time(),
        ];
        $result = $this->addElectric($meter_arr);
        if (!$result['code']) {
            return $this->failed($result['msg']);
        }
        $operate = [
            "community_id"    => $meter_arr["community_id"],
            "operate_menu"    => "电表管理",
            "operate_type"    => "新增电表",
            "operate_content" => "表身号:".$meter_arr["meter_no"],
        ];
        OperateService::addComm($user_info,$operate);
        return $this->success();
    }

    /**
     * 添加电表数据
     * @author yjh
     * @param $param
     * @return array
     */
    public function addElectric($param)
    {
        $model = new PsElectricMeter();
        $valid = PsCommon::validParamArr($model, $param, 'add');
        if (!$valid["status"]) {
            return $this->failed($valid["errorMsg"]);
        }
        $model->save();
        return $this->success();
    }

    /**
     * 修改前验证
     * @author yjh
     * @param $data
     * @return array
     */
    public function checkEdit($data)
    {
        $data['id'] = $data["meter_id"];
        unset($data["water_meter_id"]);
        // 验证是否已抄
        $is_meter = $this->checkMeter($data);
        if ($is_meter['code']) {
            return $this->failed('未绑定电表');
        }
        //数据基本验证
        $check = $this->checkElectric($data);
        if ($check['code'] == 0) {
            return $this->failed($check['msg']);
        }
        return $this->success($check['data']);
    }


    /**
     * 修改电表数据
     * @author yjh
     * @param $data
     * @param $user_info
     * @return array
     */
    public function edit( $data,$user_info)
    {
        $check = $this->checkEdit($data);
        if (!$check['code']) {
            return $this->failed($check['msg']);
        }
        $meter_arr = [
            "meter_no" => $data["meter_no"],
            "meter_status" => $data["meter_status"],
            "start_ton" => $data["start_ton"],
            "latest_record_time" => strtotime($data["latest_record_time"]),
            "remark"=>PsCommon::get($data,'remark'),
        ];
        $where['id'] = $data["meter_id"];
        PsElectricMeter::editData($meter_arr,$where);
        $operate=[
            "community_id"=>$data["community_id"],
            "operate_menu"=>"电表管理",
            "operate_type"=>"编辑电表",
            "operate_content"=>"表身号:".$data["meter_no"],
        ];
        OperateService::addComm($user_info,$operate);
        return $this->success("编辑成功");
    }


    public function import($data,$community_id,$user_info,$params){
        $uniqueDataValArr = $MeterArrInfo = $recordArrInfo =  [];
        $defeat_count = $error_count= $success_count = 0;

        //java 验证小区
        $commonService = new CommonService();
        $javaCommunityParams['community_id'] = $community_id;
        $javaCommunityParams['token'] = $params['token'];
        $communityName = $commonService->communityVerificationReturnName($javaCommunityParams);
        if(empty($communityName)){
            return $this->failed("未找到小区信息");
        }

        //查询java 所有房屋数据
        $alipayService = new AlipayCostService();
        $javaParams['token'] = $params['token'];
        $javaParams['community_id'] = $community_id;
        $javaRoomResult = $alipayService->getJavaRoomAll($javaParams);
        if(empty($javaRoomResult)){
            return $this->failed("该小区下，没有房屋信息");
        }

        for ($i = 3; $i <= count($data); $i++) {
            $val = $data[$i];
            if(empty($val["A"]) && empty($val["B"]) && empty($val["C"]) && empty($val["D"]) && empty($val["E"]) && empty($val["F"]) && empty($val["G"]) && empty($val["H"]) && $i==3){
                continue;
            }
            $val["G"]=$g = \PHPExcel_Shared_Date::ExcelToPHP($val["G"]);

            $roomKey = $communityName.trim($val["B"]).trim($val["C"]).trim($val["D"]).trim($val["E"]);
            $roomInfo = $javaRoomResult[$roomKey];
            if (empty($roomInfo)) {
                $error_count++;
                $defeat_count++;
                $errorCsv[$defeat_count] = [
                    "meter_no" => $val["A"],
                    "group" => $val["B"],
                    "building" => $val["C"],
                    "unit" => $val["D"],
                    "room" => $val["E"],
                    "meter_status" => $val["F"],
                    "start_ton" => $val["H"],
                    "remark" => (string) $val["I"],
                ];
                $errorCsv[$defeat_count]["error"] = "房屋未找到";
                continue;
            }

            $meter_arr = [
                "community_id" => $community_id,
                "meter_no" => $val["A"],
                "group_id" => $roomInfo['groupId'],
                "building_id" => $roomInfo["buildingId"],
                "unit_id" => $roomInfo["unitId"],
                "room_id" => $roomInfo["roomId"],
                "meter_status" => (string)$val["F"],
                "latest_record_time" => ($g > 0 ? gmdate("Y-m-d", $g) : ''),
                "start_ton" => (string)$val["H"],
                "remark" => (string)$val["I"],
            ];

            $valid = PsCommon::validParamArr(new PsWaterMeterFrom(), $meter_arr, 'import-post');
            $meter_arr['group'] = $val["B"];
            $meter_arr['building'] = $val["C"];
            $meter_arr['unit'] = $val["D"];
            $meter_arr['room'] = $val["E"];
            if (!$valid["status"]) {
                $error_count++;
                $defeat_count++;
                $errorCsv[$defeat_count] = $meter_arr;
                $errorCsv[$defeat_count]["error"] = $valid["errorMsg"];
                continue;
            }

            $uniqueDataVal = $meter_arr["room_id"];
            //excel表数据去重
            if (in_array($uniqueDataVal, $uniqueDataValArr)) {
                $error_count++;
                $defeat_count++;
                $errorCsv[$defeat_count] = $meter_arr;
                $errorCsv[$defeat_count]["error"] = "excel表中此条记录重复";
                continue;
            } else {
                array_push($uniqueDataValArr, $uniqueDataVal);
            }
//            //查找房屋信息是否存在
//            $ps_room = RoomService::service()->getRoom($meter_arr);
//            if (empty($ps_room)) {
//                $error_count++;
//                $defeat_count++;
//                $errorCsv[$defeat_count] = $meter_arr;
//                $errorCsv[$defeat_count]["error"] = "未找到系统内对应得小区的房屋信息";
//                continue;
//            }

            /*验证数据库中是否已存在*/
            $is_meter = Yii::$app->db->createCommand("select count(id) from ps_electric_meter where community_id=:community_id and room_id=:room_id", [":community_id" => $community_id, ":room_id" => $meter_arr["room_id"]])->queryScalar();
            if ($is_meter >= 1) {
                $error_count++;
                $defeat_count++;
                $errorCsv[$defeat_count] = $meter_arr;
                $errorCsv[$defeat_count]["error"] = "房号已绑定电表";
                continue;
            }
            $latest_record_time =  $meter_arr["latest_record_time"] ? strtotime($meter_arr['latest_record_time']) : strtotime(date('Ymd'));
            if ($latest_record_time <= 0) {
                $error_count++;
                $defeat_count++;
                $errorCsv[$defeat_count] = $meter_arr;
                $errorCsv[$defeat_count]["error"] = "上次抄表时间不正确";
                continue;
            }
            $meterArr = [
                "community_id" => $community_id,
                "meter_no" => $meter_arr["meter_no"],
                "meter_status" => array_search($meter_arr["meter_status"], Self::$meter_status),
                "group_id" => $roomInfo['groupId'],
                "building_id" => $roomInfo["buildingId"],
                "unit_id" => $roomInfo["unitId"],
                "room_id" => $roomInfo["roomId"],
                "address" => $roomInfo["home"],
                "start_ton" => $meter_arr["start_ton"],
                "latest_record_time" => $latest_record_time,
                "remark" => $meter_arr["remark"],
                "create_at" => time(),
            ];

            $recordArr = [
                "room_id"      => $roomInfo["roomId"],
                "status"      => $meterArr["meter_status"],
                "latest_ton"  => $meterArr["start_ton"],
                "use_ton"     => 0,
                "current_ton" => $meterArr["start_ton"],
//                "last_pay_day"=> $latest_record_time+$meterArr["payment_time"]*24*60*60,
                "period_start"=> $latest_record_time,
//                "period_end"=>$latest_record_time-1,
                "create_time"=>$latest_record_time,
                "operator_id"=>$user_info["id"],
                "operator_name"=>$user_info["truename"],
                "bill_type" =>"2"
            ];
            array_push($MeterArrInfo, $meterArr);
            array_push($recordArrInfo, $recordArr);
            $defeat_count++;
            $success_count++;
        }
        if($success_count>0) {
            //批量存入 ps_bill 表
            Yii::$app->db->createCommand()->batchInsert('ps_electric_meter',
                [
                    "community_id" ,
                    "meter_no" , "meter_status",
                    "group_id","building_id","unit_id","room_id","address",
                    "start_ton", "latest_record_time", "remark","create_at",
                ],
                $MeterArrInfo
            )->execute();
            //批量存入 ps_bill 表
            Yii::$app->db->createCommand()->batchInsert('ps_water_record',
                [
                    "room_id","status","latest_ton", "use_ton", "current_ton",
                    "period_start", "create_time", "operator_id", "operator_name", "bill_type"
                ],
                $recordArrInfo
            )->execute();
        }
        $error_url = "";
        if ($error_count > 0) {
            $error_url = $this->saveError($errorCsv);
        }
        return $this->success(['totals' => $success_count + $error_count, 'success' => $success_count, 'error_url' => $error_url]);
    }
    private  function saveError($data) {
        $config = [
            'A'=> ['title'=>'表身号','width'=>16, 'data_type'=>'str','field'=>'meter_no'],
            'B'=> ['title'=>'苑/期/区','width'=>16, 'data_type'=>'str','field'=>'group'],
            'C'=> ['title'=>'幢','width'=>30,'data_type'=>'str','field'=>'building'],
            'D'=> ['title'=>'单元','width'=>10,'data_type'=>'str','field'=>'unit'],
            'E'=> ['title'=>'室号','width'=>10,'data_type'=>'str','field'=>'room'],
            'F'=> ['title'=>'电表状态','width'=>16, 'data_type'=>'str','field'=>'meter_status'],
            'G'=> ['title'=>'上次抄表时间','width'=>30,'data_type'=>'str','field'=>'latest_record_time'],
            'H'=> ['title'=>'起始读数','width'=>10,'data_type'=>'str','field'=>'start_ton'],
            'I'=> ['title'=>'备注','width'=>10,'data_type'=>'str','field'=>'remark'],
            'j'=> ['title'=>'错误原因','width'=>10,'data_type'=>'str','field'=>'error'],
        ];
        $filename = CsvService::service()->saveTempFile(1, array_values($config), $data, 'Electric', 'error');
//        $filePath = F::originalFile().'error/'.$filename;
//        $fileRe = F::uploadFileToOss($filePath);
//        $downUrl = $fileRe['filepath'];


        $newFileName = explode('/',$filename);
        $savePath = Yii::$app->basePath . '/web/store/excel/error/'.$newFileName[0]."/";
        $downUrl = F::uploadExcelToOss($newFileName[1], $savePath);
        return $downUrl;
    }

    /**
     * 导出数据
     * @author yjh
     * @return string
     */
    public function export($where)
    {
        $field = 'id,meter_no,meter_status,latest_record_time,address,start_ton,latest_record_time,cycle_time,payment_time,remark';
        $result = $this->getElectrictData($where,$field);
        if(!empty($result['list'])){
            foreach ($result['list'] as $key => $model) {
                $result['list'][$key]["meter_status_desc"] = $model["meter_status"] ? self::$meter_status[$model["meter_status"]]:"";
                $result['list'][$key]["type"] ='电表';
            }
            $config = $this->exportConfig();
            $url = ExcelService::service()->export($result['list'], $config);
            return $url;
        }
    }

    /**
     * 导出配置
     * @author yjh
     * @return array
     */
    public function exportConfig()
    {
        $config["sheet_config"] = [
            'address' => ['title' => '表具类型', 'width' => 30],
            'meter_no' => ['title' => '表具编号', 'width' => 16],
            'latest_record_time' => ['title' => '上次抄表时间', 'width' => 18],
            'start_ton' => ['title' => '上次抄表读数', 'width' => 16],
            'meter_status_desc' => ['title' => '表具状态', 'width' => 16],
            'remark' => ['title' => '备注', 'width' => 16],
        ];
        $config["save"] = true;
        $config['path'] = 'temp/'.date('Y-m-d');
        $config['file_name'] = ExcelService::service()->generateFileName('dian');
        return $config;
    }

    /**
     * 获取数据
     * @author yjh
     * @param $data条件参数 $filed 获取字段
     * @return array
     */
    public function getElectrictData($data,$field,$page = false)
    {
        //条件处理
        $where['community_id'] = $data['community_id'];
        $where['room_id'] = !empty($data['room_id']) ? $data['room_id'] : null ;
        $where['group_id'] = !empty($data['group_id']) ? $data['group_id'] : null ;
        $where['building_id'] = !empty($data['building_id']) ? $data['building_id'] : null ;
        $where['unit_id'] = !empty($data['unit_id']) ? $data['unit_id'] : null ;
        $where['meter_status'] = !empty($data['meter_status']) ? $data['meter_status'] : null ;
        $where['meter_type'] = !empty($data['meter_type']) ? $data['meter_type'] : null ;
        $where = F::searchFilter($where);
        $like = !empty($data['meter_no']) ? ['like' , 'meter_no' , $data['meter_no']] : '1=1' ;
        //查询
        $data['where'] = $where;
        $data['like'] = $like;
        $result = PsElectricMeter::getData($data,$field,$page);
        return $result;
    }


}
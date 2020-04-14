<?php
namespace service\alipay;

use common\core\PsCommon;
use app\models\PsPrintModel;
use app\models\PsRoomUser;
use app\models\PsBill;
use service\BaseService;
use service\property_basic\JavaService;
use service\rbac\OperateService;
use Yii;
use yii\base\Exception;

class PrintService extends BaseService
{
    public static $pay_status = [
        "1" => "按单元缴费",
        "2" => "按户缴费"
    ];

    public static function lists($data)
    {
        $db = Yii::$app->db;
        $param = [":community_id" => $data["community_id"]];
        $sql = "select model_type,model_title,first_area,second_area from ps_print_model where community_id=:community_id ";
        $model = $db->createCommand($sql, $param)->queryAll();
        if (empty($model)) {
            $param = [":community_id" => 0];
            $sql = "select model_type,model_title,first_area,second_area from ps_print_model where community_id=:community_id ";
            $model = $db->createCommand($sql, $param)->queryAll();
        }
        return $model;
    }

    public static function show($data)
    {
        $db = Yii::$app->db;
        $param = [":community_id" => $data["community_id"], ":model_type" => $data["model_type"]];

        $model = $db->createCommand("SELECT model_title, first_area, second_area, remark 
            from ps_print_model 
            where community_id = :community_id and model_type = :model_type", $param)->queryOne();
        if (empty($model)) {
            $param = [":community_id" => 0, ":model_type" => $data["model_type"]];

            $model = $db->createCommand("SELECT model_title, first_area, second_area, remark 
                from ps_print_model 
                where community_id = :community_id and model_type = :model_type", $param)->queryOne();
        }
        //说明是收费通知单，则需要获取数量
        if($data["model_type"]==6){
            $sql = "select * from ps_print_number where company_id=:company_id and create_at=:create_at";
            $time=strtotime(date("Y-m-d",time()));
            $param = [":company_id" => $data["property_company_id"],':create_at' => $time];
            $number_model = $db->createCommand($sql, $param)->queryOne();
            if(empty($number_model)) {
                $new_arr = [
                    "company_id" => $data["property_company_id"],
                    "number" => 1,
                    "create_at" => $time
                ];
                Yii::$app->db->createCommand()->insert("ps_print_number", $new_arr)->execute();
            }
            $model['number']=!empty($number_model['number'])?$number_model['number']:1;
        }
        return $model?$model:'';
    }

    public static function add($data)
    {
        $db = Yii::$app->db;
        $param = [":community_id" => $data["community_id"], ":model_type" => $data["model_type"]];
        $sql = "select * from ps_print_model where community_id=:community_id and model_type=:model_type";
        $model = $db->createCommand($sql, $param)->queryOne();
        if (empty($model)) {
            $new_arr = [
                "community_id" => $data["community_id"],
                "model_type" => $data["model_type"],
                "model_title" => $data["model_title"],
                "first_area" => $data["first_area"],
                "second_area" => $data["second_area"],
                "remark" => $data["remark"],
                "create_at" => time(),
            ];
            Yii::$app->db->createCommand()->insert("ps_print_model", $new_arr)->execute();
        } else {
            $edit_arr = [
                "model_title" => $data["model_title"],
                "first_area" => $data["first_area"],
                "second_area" => $data["second_area"],
                "remark" => $data["remark"],
                "update_at" => time(),
            ];
            Yii::$app->db->createCommand()->update("ps_print_model", $edit_arr, 'id=' . $model["id"])->execute();
        }
    }
    //确认打印（用于编号自增）
    public static function chargeBill($data,$user_info)
    {
        $db = Yii::$app->db;
        $param = [":community_id" => $data["community_id"]];
        $sql = "select * from ps_print_model where community_id=:community_id and model_type=6";
        $model = $db->createCommand($sql, $param)->queryOne();
        if (empty($model)) {
            return ["status" => true];
            //return ["status" => false, "errorMsg" => "打印模板不存在"];
        } else {
            $sql = "select * from ps_print_number where company_id=:company_id and create_at=:create_at";
            $time=strtotime(date("Y-m-d",time()));
            $param = [":company_id" => $user_info["property_company_id"],':create_at' => $time];
            $number_model = $db->createCommand($sql, $param)->queryOne();
            if(empty($number_model)) {
                $new_arr = [
                    "company_id" => $user_info["property_company_id"],
                    "number" => 1,
                    "create_at" => $time
                ];
                Yii::$app->db->createCommand()->insert("ps_print_number", $new_arr)->execute();
            }else{
                $edit_arr = [
                    "number" => $number_model["number"]+1,
                ];
                Yii::$app->db->createCommand()->update("ps_print_number", $edit_arr, 'id=' . $number_model["id"])->execute();
            }
            return ["status" => true];
        }
    }

    // 物业后台-》计费管理-》催缴单打印 列表
    public  function billList($data)
    {

        $params = $arr = [];
        $where = " 1=1 ";
        if(!empty($data['communityList'])){
            $communityList = implode(",",$data['communityList']);
            $where .= " and community_id in (".$communityList.")";
        }

        if(!empty($data['community_id'])){
            $arr = [':community_id' => $data["community_id"]];
            $params = array_merge($params, $arr);
            $where .= " and community_id=:community_id ";
        }

        if(!empty($data['group_id'])){
            $arr = [':group_id' => $data["group_id"]];
            $params = array_merge($params, $arr);
            $where .= " AND `group_id`=:group_id";
        }

        if(!empty($data["building_id"])){
            $arr = [':building_id' => $data["building_id"]];
            $params = array_merge($params, $arr);
            $where .= " AND building_id=:building_id";
        }

        if(!empty($data["unit_id"])){
            $arr = [':unit_id' => $data["unit_id"]];
            $params = array_merge($params, $arr);
            $where .= " AND unit_id=:unit_id";
        }

        if(!empty($data["room_id"])){
            $arr = [':room_id' => $data["room_id"]];
            $params = array_merge($params, $arr);
            $where .= " AND room_id=:room_id";
        }

        $arr = [':is_del' => 1];
        $params = array_merge($params, $arr);
        $where .= " AND is_del=:is_del";

        /*账期开始时间*/
        if ($data["acct_period_start"]) {
            $acct_period_start = strtotime(date("Y-m-d 00:00:00", strtotime($data["acct_period_start"])));
            $arr = [":acct_period_start" => $acct_period_start];
            $params = array_merge($params, $arr);
            $where .= " And  acct_period_end>= :acct_period_start";
        }

        /*账期结束时间*/
        if ($data["acct_period_end"]) {
            $acct_period_end = strtotime(date("Y-m-d 23:59:59", strtotime($data["acct_period_end"])));
            $arr = [":acct_period_end" => $acct_period_end];
            $params = array_merge($params, $arr);
            $where .= " And  acct_period_start<= :acct_period_end";
        }
        if (!empty($data["cost_type"])) {
            $in = "";
            foreach ($data["cost_type"] as $i => $item) {
                $key = ":cost_type" . $i;
                $in .= "$key,";
                $in_params[$key] = $item;
            }
            $in = rtrim($in, ",");
            $params = array_merge($params, $in_params);
            $where .= " and  cost_id in ($in)";
        }
        $model = Yii::$app->db->createCommand("SELECT count(*) as total,sum(bill_entry_amount) as entry_amounts  FROM ps_bill WHERE   " . $where." and status=1  and trade_defend!=1 and trade_defend!=2 ", $params)
            ->queryOne();
        $sql = "select id,community_id,ifnull(out_room_id,'') as out_room_id,room_address,community_name,`group_id`,building_id,unit_id,room_id,cost_name,acct_period_start,acct_period_end,bill_entry_amount,cost_type from ps_bill where " . $where . " and status=1  and trade_defend!=1 and trade_defend!=2 order by `group_id`,building_id,unit_id,room_id asc";

        $is_down = !empty($data['is_down']) ? $data['is_down'] : 1;//1正常查询，2下载
        $offset = ($data['page']-1)*$data['pageSize'];
        $limit = $data['pageSize'];
        if ($is_down == 2) {//说明是下载，需要全部数据
            $offset = 0;
            $limit = intval($model["total"]);
        }
        $models = Yii::$app->db->createCommand($sql." LIMIT :offset,:limit", $params)->bindParam(':offset', $offset)->bindParam(':limit', $limit)->queryAll();
        $house = [];
        $nowTime = time();
        if (!empty($models)) {
            foreach ($models as $key => $val) {

                $val['overdue_day'] = '-'; //逾期天数
                if($nowTime>($val['acct_period_end']+86399)){
                    $val['overdue_day'] = floor(($nowTime-$val['acct_period_end'])/86400); //逾期天数
                }
                $val['acct_period_start'] = date("Y-m-d", $val["acct_period_start"]);
                $val['acct_period_end'] = date("Y-m-d", $val["acct_period_end"]);
                array_push($house, $val);
            }
        }
        $arr = ['totals' => $model["total"], "entry_amounts" => $model["entry_amounts"], 'list' => array_values($house)];
        return $this->success($arr);
    }
    
    // 物业后台-》计费管理-》催缴单打印 预览 新版
    public function billListNew($data)
    {
        // 账单可多选 查出对应哪些房屋 再循环查出每个房屋对应的催缴信息
        $rooms = PsBill::find()->select('room_id')
            ->where(['in', 'id', $data['ids']])
            ->groupBy('room_id')
            ->asArray()->all();

        if (!empty($rooms)) {
            foreach ($rooms as $k => $v) {
                $models = PsBill::find()->select('id, out_room_id, `group`, building, unit, room, cost_name, room_id, 
                  acct_period_start, acct_period_end, bill_entry_amount, cost_type, charge_area, community_name,
                  community_id, company_id')
                ->where(['in', 'id', $data['ids']])
                ->andWhere(['=', 'room_id', $v['room_id']])
                ->asArray()->all();
                $arrList = [];
                if (!empty($models)) {
                    $total_money = 0;
                    foreach ($models as $key => $val) {
                        // 如果是水费和电费则还需要查询使用量跟起始度数
                        if ($val['cost_type'] == 2 || $val['cost_type'] == 3) {
                            $water = Yii::$app->db->createCommand("SELECT use_ton, latest_ton,current_ton, formula from ps_water_record 
                                where bill_id = {$val['id']} ")->queryOne();
                            if (!empty($water)) {
                                $arr['use'] = $water['use_ton'];
                                $arr['end'] = $water['current_ton'];
                                $arr['start'] = $water['latest_ton'];
                                $arr['formula'] = $water['formula'];
                            } else {
                                $arr['use'] = '';
                                $arr['end'] = '';
                                $arr['start'] = '';
                                $arr['formula'] = '';
                            }
                        }
                        $arr['house_info'] = $val['group'].$val['building'].$val['unit'].$val['room'];
                        $arr['house_area'] = $val['charge_area'];
                        $arr['pay_item'] = $val['cost_name'];
                        $arr['start_at'] = date("Y-m-d", $val["acct_period_start"]);
                        $arr['end_at'] = date("Y-m-d", $val["acct_period_end"]);
                        $arr['bill_amount'] = $val['bill_entry_amount'];
                        $total_money += $val['bill_entry_amount'];
                        $arrList[] = $arr;
                    }
                    // 获取住户姓名 已迁入（迁入已认证+迁入未认证）
                    $names = PsRoomUser::find()->select('name')->where(['room_id' => $models[0]['room_id']])
                        ->andWhere(['in', 'status', [1,2]])->asArray()->all();
                    $true_name = '';
                    if (!empty($names)) {
                        foreach ($names as $key => $val) {
                            $true_name .= $val['name'] . ',';
                        }
                    }
                    $room_comm['print_date'] = date("Y-m-d H:i", time());
                    $room_comm['community_id'] = $models[0]['community_id'];
                    $room_comm['community_name'] = $models[0]['community_name'];
                    $room_comm['house_info'] = $arrList[0]['house_info'];
                    $room_comm['house_area'] = $models[0]['charge_area'];
                    $room_comm['true_name'] = !empty($true_name) ? $true_name : ''; // 住户姓名
                    $room_comm['total'] = sprintf("%.2f", $total_money);
                    $room_comm['company_id'] = $models[0]['company_id'];
                }

                $return_data[$v['room_id']]['bill_list'] = $arrList; // 账单信息
                $return_data[$v['room_id']]['room_data'] = $room_comm; // 模板信息+房屋信息
            }
        }

        return $this->success($return_data);
    }

    /**
     * 根据小区获取水表模版
     * @param $communityId
     */
    public function getWaterTemplateByCid($communityId)
    {
        $data = PsPrintModel::find()->where(['community_id'=>$communityId, 'model_type'=>[3, 4]])
            ->with(['adverts'])
            ->asArray()->one();
        if(!$data) {
            return [];
        }
        $data['adverts'] = $data['adverts'] ? $data['adverts'] : [];
        foreach($data['adverts'] as &$v) {
            if(!empty($v['images'])) {
                foreach($v['images'] as &$i) {
                    $i['image_url'] .= '?imageMogr2/thumbnail/384x';//给定图片强制宽度384px,并等比缩放
                }
            }
        }
        $data['first_area'] = str_replace("\r\n", "\n", $data['first_area']);
        return $data;
    }
    /*
     * 获取水费打印模板
     * */
    public function getWaterModelShow($communityId,$model_type) {
        $db = Yii::$app->db;
        $param = [":community_id" => $communityId, ":model_type" => $model_type];
        $sql = "select id,model_title,first_area,second_area from ps_print_model where community_id=:community_id and model_type=:model_type";
        $model = $db->createCommand($sql, $param)->queryOne();

        if (!empty($model)) {
            $adverts= $db->createCommand( "select  A.id,A.name,A.title,A.note,B.image_url from  ps_water_advert A  left join ps_water_advert_images B on B.wad_id=A.id where A.template_id=:template_id order by A.id asc", [":template_id"=>$model["id"]])->queryAll();
            $advert_arr =[];
            if(!empty( $adverts )) {
                foreach ($adverts as $advert) {
                    if($advert_arr[$advert["id"]]) {
                        $advert_arr[$advert["id"]]["image_url"][]=$advert["image_url"];
                    } else {
                        $advert_arr[$advert["id"]] =$advert;
                        unset($advert_arr[$advert["id"]]["image_url"]);
                        if($advert["image_url"]) {
                            $advert_arr[$advert["id"]]["image_url"][]=$advert["image_url"];
                        } else {
                            $advert_arr[$advert["id"]]["image_url"]=[];
                        }
                    }
                }
                $model["advert"] = array_values($advert_arr);
            }
        }
        return $model;
    }
    public function editWater($data,$user_info){
        $connection = Yii::$app->db;
        $param = [":community_id" => $data["community_id"], ":model_type" => $data["model_type"]];
        $model = $connection->createCommand("select * from ps_print_model where community_id=:community_id and model_type=:model_type", $param)->queryOne();
        if( !empty($data["adverts"]) ) {
            foreach ($data["adverts"] as $key=>$val ) {
                    $val = PsCommon::validParamArr(new PsPrintModel(),$val,"add-advert");
                    if(!$val["status"]) {
                        return ["status" => false, "errorMsg" => $val["errorMsg"]];
                    }
            }
        }
        $transaction = $connection->beginTransaction();
        try {
            if ( empty($model)) {
                $new_arr = [
                    "community_id" => $data["community_id"],
                    "model_type" => $data["model_type"],
                    "model_title" => $data["model_title"],
                    "first_area" => $data["first_area"],
                    "create_at" => time(),
                ];
                Yii::$app->db->createCommand()->insert("ps_print_model", $new_arr)->execute();
                $template_id =  $connection->getLastInsertID();
            } else {
                $edit_arr = [
                    "model_title" => $data["model_title"],
                    "first_area" => $data["first_area"],
                    "update_at" => time(),
                ];
                $connection->createCommand()->update("ps_print_model", $edit_arr, 'id=' . $model["id"])->execute();
                $template_id = $model["id"];
            }
            /*删除所有子集*/
            $connection->createCommand("delete A,B from  ps_water_advert A left join ps_water_advert_images B on A.id=B.wad_id where A.template_id=:template_id",[":template_id"=>$template_id])->execute();
            if( !empty($data["adverts"]) ) {
                foreach ($data["adverts"] as $value){
                    if( $value["name"] ||  $value["title"] || $value["note"] || !empty($value["images"]) ) {
                        $ad_arr = [
                            "template_id" => $template_id,
                            "name" => $value["name"],
                            "title" => $value["title"],
                            "note" => $value["note"],
                            "create_at" => time(),
                        ];
                        $connection->createCommand()->insert("ps_water_advert", $ad_arr)->execute();
                        $ar_id =  $connection->getLastInsertID();
                        if(!empty($value["images"])) {
                            $imageArr =[];

                            foreach ($value["images"] as $val) {
                                array_push($imageArr,[$ar_id,$val]);
                            }
                            $connection->createCommand()->batchInsert('ps_water_advert_images',
                                [
                                    'wad_id'     ,
                                    'image_url'    ,
                                ],
                                $imageArr
                            )->execute();
                        }
                    }
                }
            }
            $transaction->commit();
            $operate=[
                "community_id"=> $data["community_id"],
                "operate_menu"=>"打印模板",
                "operate_type"=>"水表模板编辑",
                "operate_content"=>"",
            ];
            OperateService::addComm($user_info,$operate);

            return ["status" => true,];
        } catch (Exception $e) {
            $transaction->rollBack();
            return ["status" => false, "errorMsg" => "编辑失败"];
        }

    }
}
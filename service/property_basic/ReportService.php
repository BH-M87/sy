<?php
namespace service\property_basic;

use Yii;
use service\BaseService;
use common\core\PsCommon;

class ReportService extends BaseService {

    public static $communit_type = [
        'house_status' => '房屋状态',
        'house_type'   => '房屋类型',
        'park_status'   => '车位状态',
    ];
    public static $pay_status = [
        "2"=>"线上已缴",
        "5"=>"线下已缴"

    ];

    public static  function getAmountInfo($data,$table){
        $params =[ ":community_id"=>$data["community_id"] ];
        $where = "community_id=:community_id";
        if ( $data["online"]) {
            $params = array_merge($params, [':online' => $data["online"]]);
            $where .= " AND online=:online";
        }
        if ( $data["type"]) {
            $params = array_merge($params, [':type' => $data["type"]]);
            $where .= " AND type=:type";
        }
        if ( $data["parent_id"]) {
            $params = array_merge($params, [':parent_id' => $data["parent_id"]]);
            $where .= " AND parent_id=:parent_id";
        }
        $sql = "select sum(amount) from ".$table. " where " .$where;
        $models =  Yii::$app->db->createCommand($sql,$params)->queryScalar();
        return $models;
    }
    public static function getAmountSacle( $data ) {
        $params =[ ":community_id"=>$data["community_id"] ];
        $where = "community_id=:community_id";

        if ( $data["paid_at_start"]) {
            $paid_at_start = strtotime(date("Y-m-d 00:00:00",strtotime($data["paid_at_start"])))-2;
            $params = array_merge($params, [':paid_at_start' => $paid_at_start]);
            $where .= " AND parent_time>=:paid_at_start";
        }
        if ( $data["paid_at_end"]) {
            $paid_at_end= strtotime(date("Y-m-d 23:59:59",strtotime($data["paid_at_end"])))+2;
            $params = array_merge($params, [':paid_at_end' => $paid_at_end]);
            $where .= " AND parent_time<=:paid_at_end";
        }

        $sql = "select sum(amount) as amount,type_name from ps_amount_day_report  where ".$where. " group by `type`";
        $models =  Yii::$app->db->createCommand($sql,$params)->queryAll();

        return $models;
    }
    public static  function getChannelInfo($data,$table){

        $params =[ ":community_id"=>$data["community_id"] ];
        $where = "community_id=:community_id";
        if ( $data["type"]) {
            $params = array_merge($params, [':type' => $data["type"]]);
            $where .= " AND type=:type";
        }
        if ( $data["parent_id"]) {
            $params = array_merge($params, [':parent_id' => $data["parent_id"]]);
            $where .= " AND parent_id=:parent_id";
        }

        $sql = "select amount,`type`,type_name from ".$table." where ".$where;
        $models =  Yii::$app->db->createCommand($sql,$params)->queryAll();
        return $models;
    }

    public static function getSacleMonths($data){
        $params =[ ":community_id"=>$data["community_id"] ];
        $where = "community_id=:community_id";
        if ( $data["cost_type"]) {
            $params = array_merge($params, [':cost_type' => $data["cost_type"]]);
            $where .= " AND cost_type=:cost_type";
        }
        if ( $data["paid_year"]) {
            $params = array_merge($params, [':year' => $data["paid_year"]]);
            $where .= " AND `year`=:year";
        }
        $sql = "select * from ps_bill_report where " .$where;
        $models =  Yii::$app->db->createCommand($sql,$params)->queryAll();
        return $models;
    }


    public static function getSacleMonth($data){
        $params =[ ":community_id"=>$data["community_id"] ];
        $where = "community_id=:community_id";
        if ( $data["cost_type"]) {
            $params = array_merge($params, [':cost_type' => $data["cost_type"]]);
            $where .= " AND cost_type=:cost_type";
        }
        if ( $data["paid_year"]) {
            $params = array_merge($params, [':year' => $data["paid_year"]]);
            $where .= " AND `year`=:year";
        }
        $sql = "select * from ps_bill_report where " .$where;
        $models =  Yii::$app->db->createCommand($sql,$params)->queryAll();
        return $models;
    }

    public static function  addCommunityReport($community,$data) {
        $db    =  \Yii::$app->db;
        $where = [":community_id"=>$community['id'],':type'=>$data["type"],":parent_type"=>$data["parent_type"]];
        $sql   =  "select * from ps_community_report where community_id=:community_id and `type`=:type and parent_type=:parent_type";
        $model = $db->createCommand($sql,$where)->queryOne();
        if( empty($model) ) {
            $model = [
                'community_id'=>$community['id'],
                'community_name'=>$community["name"],
                'type'=>$data["type"],
                'type_name'=> !empty($data['type_name']) ? $data['type_name'] : self::typeName($data["type"],$data["parent_type"]),
                'parent_type'=>$data["parent_type"],
                "parent_name"=>self::$communit_type[$data["parent_type"]],
                'total'=>$data["total"],
                'create_at'=>time()
            ];
            $db->createCommand()->insert('ps_community_report', $model)->execute();
        } else {
            $params = [
                'id'=>$model['id'],
                ':total'=> $data['total'],
                ':update_at'=>time(),
            ];
            $sql  = " update ps_community_report set total=:total,update_at=:update_at where id=:id";
            $db->createCommand($sql,$params)->execute();
        }
        return $model;
    }

    public static function addAmountReport($data,$table) {
        $db    =  \Yii::$app->db;
        $where = [":community_id"=>$data['community_id'],':type'=>$data['type'],":pid"=>$data['riqi'],":online"=>$data['online']];
        $sql   =  "select * from ".$table." where community_id=:community_id and `type`=:type and parent_id=:pid and online=:online";
        $model = $db->createCommand($sql,$where)->queryOne();
        if( empty($model) ) {
            $year = substr($data['riqi'],0,4);
            $month =  substr($data['riqi'],4,2) ? substr($data['riqi'],4,2) : "01";
            $day =  substr($data['riqi'],6,2) ? substr($data['riqi'],6,2) : "01";
            $time =$year."-". $month."-". $day;

            $model =  [
                'community_id'=> $data['community_id'],
                'community_name'=>$data['community_name'],
                'parent_id'=>$data['riqi'],
                'parent_time'=>strtotime($time),
                'type'=>$data['type'],
                'type_name'=>$data['type']==5?'其他费用':$data['type_name'],
                'amount'=>$data['amount'],
                'total'=>$data['total'],
                'online'=>$data['online'],
                'create_at'=>time(),
            ];
            $db->createCommand()->insert($table, $model)->execute();
        } else {
            $params = [
                ':amount'=> $data['amount'],
                ':total'=>$data['total'],
                ':id'=>$model['id'],
                ':update_at'=>time(),
            ];
            $sql  = " update ".$table." set amount=amount+:amount,total=total+:total,update_at=:update_at where id=:id";
            $db->createCommand($sql,$params)->execute();
        }
        return $model;
    }
    public static function addChannelReport($data,$table) {
        $db    =  \Yii::$app->db;
        $where = [":community_id"=>$data['community_id'],':type'=>$data['type'],":pid"=>$data['riqi'],":cost_id"=>$data['cost_id']];
        $sql   =  "select * from ".$table." where community_id=:community_id and `type`=:type and parent_id=:pid and cost_id=:cost_id";
        $model = $db->createCommand($sql,$where)->queryOne();

        if( empty($model) ) {
            $year = substr($data['riqi'],0,4);
            $month =  substr($data['riqi'],4,2) ? substr($data['riqi'],4,2) : "01";
            $day =  substr($data['riqi'],6,2) ? substr($data['riqi'],6,2) : "01";
            $time =$year."-". $month."-". $day;
            $model =  [
                'community_id'=> $data['community_id'],
                'community_name'=>$data['community_name'],
                'parent_id'=>$data['riqi'],
                'cost_id'=>$data['cost_id'],
                'parent_time'=>strtotime($time),
                'type'=>$data['type'],
                'type_name'=>$data['type']==5?'其他费用':$data['type_name'],
                'amount'=>$data['amount'],
                'total'=>$data['total'],
                'create_at'=>time(),
            ];
            $db->createCommand()->insert($table, $model)->execute();
        } else {
            $params = [
                ':amount'=> $data['amount'],
                ':total'=>$data['total'],
                ':id'=>$model['id'],
                ':update_at'=>time(),
            ];
            $sql  = " update ".$table." set amount=amount+:amount,total=total+:total,update_at=:update_at where id=:id";
            $db->createCommand($sql,$params)->execute();
        }

    }
    public static function addScaleReport($data) {
        $db    =  \Yii::$app->db;
        $where = [":community_id"=>$data['community_id'],':cost_type'=>$data['cost_type'],":month"=>$data["month"],":year"=>$data["year"]];
        $sql   =  "select * from ps_bill_report where community_id=:community_id and `cost_type`=:cost_type and `month`=:month and `year`=:year ";
        $model = $db->createCommand($sql,$where)->queryOne();
        if( empty($model) ) {
            $model =  [
                'community_id'=> $data['community_id'],
                'community_name'=>$data['community_name'],
                'cost_type'=>$data['cost_type'],
                'cost_name'=>$data['cost_name'],
                'totals'=>$data['totals']?$data['totals']:0,
                'amounts'=>$data['amounts']?$data['amounts']:0.00,
                'year'=>$data['year'],
                'month'=>$data['month'],
                'create_at'=>time()
            ];
            $db->createCommand()->insert('ps_bill_report', $model)->execute();
        } else {
            $params = [
                'id'=>$model['id'],
                ':totals'=>$data['totals']? $data['totals']:0,
                ':amounts'=>$data['amounts']? $data['amounts']:0,
                ':update_at'=>time(),
            ];
            $sql  = " update ps_bill_report set amounts=amounts+:amounts,totals=totals+:totals,update_at=:update_at where id=:id";
            $db->createCommand($sql,$params)->execute();
        }
    }

    public static function addScaleReports($data) {
        $data['cost_type'] = !empty($data['cost_type'])?$data['cost_type']:1;
        $db    =  \Yii::$app->db;
        $where = [":community_id"=>$data['community_id'],':cost_type'=>$data['cost_type'],":month"=>$data["month"]];
        $sql   =  "select id from ps_bill_report where community_id=:community_id and `cost_type`=:cost_type and `month`=:month ";
        $model = $db->createCommand($sql,$where)->queryOne();
        if( empty($model) ) {
            $model =  [
                'community_id'=> $data['community_id'],
                'community_name'=>$data['community_name'],
                'cost_type'=>$data['cost_type'],
                'cost_name'=>$data['cost_name'],
                'totals'=>$data['totals']?$data['totals']:0,
                'amounts'=>$data['amounts']?$data['amounts']:0.00,
                'month'=>$data['month'],
                'create_at'=>time()
            ];
            $db->createCommand()->insert('ps_bill_report', $model)->execute();
        } else {
            $params = [
                'id'=>$model['id'],
                ':totals'=>$data['totals']? $data['totals']:0,
                ':amounts'=>$data['amounts']? $data['amounts']:0,
                ':update_at'=>time(),
            ];
            $sql  = " update ps_bill_report set amounts=amounts+:amounts,totals=totals+:totals,update_at=:update_at where id=:id";
            $db->createCommand($sql,$params)->execute();
        }
    }

    public static function delScaleReport($data) {
        $db    =  \Yii::$app->db;
        $where = [":community_id"=>$data['community_id'],':cost_type'=>$data['cost_type'],":month"=>$data["month"],":year"=>$data["year"]];
        $sql   =  "select * from ps_bill_report where community_id=:community_id and `cost_type`=:cost_type and `month`=:month and `year`=:year ";
        $model = $db->createCommand($sql,$where)->queryOne();
        $params = [
            'id'=>$model['id'],
            ':totals'=>$data['totals'],
            ':amounts'=>$data['amounts'],
            ':update_at'=>time(),
        ];
        $sql  = " update ps_bill_report set amounts=amounts-:amounts,totals=totals-:totals,update_at=:update_at where id=:id";
        $db->createCommand($sql,$params)->execute();
    }
    public static function typeName($type,$parent_type){
        switch ( $parent_type) {
            case  "house_status":
                return PsCommon::houseStatus( $type);
                break;
            case  "house_type":
                return PsCommon::propertyType( $type);
                break;
            case  "park_status":
                return PsCommon::getParkStatus( $type);
                break;
            default:
                return "";
        }
    }

    //===================================2018-4-27 陈科浪 统计报表优化==================================================
    //统计小区下的数据
    public function statistical($start_time,$end_time){
        $db = \Yii::$app->db;
        $sql= "select count(bill.id) as totals,sum(bill.bill_entry_amount) as  amounts,der.product_type as cost_type,der.product_subject as cost_name,der.community_id from ps_bill as bill,ps_order as der  where bill.order_id=der.id and bill.id=der.bill_id and der.is_del=1 and der.status in(1,2,5,7,8) and der.create_at<=".$end_time." and der.create_at>=".$start_time." group by der.community_id,der.product_type";
        $bills = $db->createCommand($sql)->queryAll();
        if(!empty($bills) ) {
            foreach ($bills as $key => $val) {
                $val["community_name"]=CommunityService::service()->getShowCommunityInfo($val["community_id"])['name'];
                $val["month"] = 0;
                $this->addScaleReports($val);
            }
        }
        $sql= "select from_unixtime(der.pay_time,'%Y%m') as paid_time,count(der.id) as totals,sum(bill.paid_entry_amount) as  amounts,der.product_type as cost_type,der.product_subject as cost_name,der.community_id from ps_bill as bill,ps_order as der  where bill.order_id=der.id and bill.id=der.bill_id and (der.status=2 or der.status=7 or der.status=8) and der.is_del=1 and der.pay_time<=".$end_time." and der.pay_time>=".$start_time." group by der.community_id,der.product_type";
        $bills = $db->createCommand($sql)->queryAll();
        if(!empty($bills) ) {
            foreach ($bills as $key=>$val) {
                $val["community_name"]=CommunityService::service()->getShowCommunityInfo($val["community_id"])['name'];
                $val["month"]=$val["paid_time"];
                $this->addScaleReports($val);
            }
        }
    }
    //更新每日收费数据
    public function reportBillTotal($start_time,$end_time)
    {
        $db         = Yii::$app->db;
        $bill_sql= "select sum(bill.paid_entry_amount) as amount,count(der.id) as total, der.community_id,der.product_type as type,der.product_subject as type_name,der.status from ps_bill as bill,ps_order as der  where bill.order_id=der.id and bill.id=der.bill_id and (der.status=2 or der.status=7 or der.status=8) and der.is_del=1 and der.pay_status=1 and der.pay_time<=".$end_time." and der.pay_time>=".$start_time." group by der.community_id, der.status, der.product_type";
        $bill_offreports    = $db->createCommand($bill_sql)->queryAll();
        if(!empty($bill_offreports)) {
            foreach ($bill_offreports as $key => $val) {
                $val["community_name"]=CommunityService::service()->getShowCommunityInfo($val["community_id"])['name'];
                $val['online'] = $val['status']==7?2:1;
                $day = $month = $year = $val;
                $day["riqi"] = date("Ymd",$start_time);
                $month["riqi"] = date("Ym",$start_time);
                $year["riqi"] = date("Y",$start_time);
                $this->addAmountReport($day, "ps_amount_day_report");
                $this->addAmountReport($month, "ps_amount_month_report");
                $this->addAmountReport($year, "ps_amount_year_report");
            }
        }
    }
    //更新线下渠道收费数据
    public function reportBillChannel($start_time,$where = null)
    {
        if (!empty($where)) {
            $where1 = "and bill.community_id= {$where['community_id']} and bill.cost_id = {$where['cost_id']}";
        } else {
            $where1 = 'and 1=1';
        }
        if (is_array($start_time)) {
            $where1 .= "and pay_time >= {$start_time['start']} and pay_time <= {$start_time['end']}";
        }
        $db=Yii::$app->db;
        $bill_sql ="select  sum(bill.paid_entry_amount) as amount,count(der.id) as total,FROM_UNIXTIME(der.pay_time,'%Y%m%d') as riqi,bill.cost_id, der.community_id,der.pay_channel as type from ps_bill as bill,ps_order as der where bill.order_id=der.id and bill.id=der.bill_id and bill.trade_defend=0 and der.status=7 and der.is_del=1 and der.pay_status=1 $where1 group by  der.community_id,bill.cost_id,der.pay_channel,riqi";
        $bills = $db->createCommand($bill_sql)->queryAll();
        if(!empty($bills) ) {
            foreach ($bills as $key => $val) {
                $val["community_name"]=CommunityService::service()->getShowCommunityInfo($val["community_id"])['name'];
                $val["type_name"]=$val['type']?PsCommon::getPayChannel($val['type']):'未知';
                $day = $month = $year = $val;
                $this->addChannelReport($day, "ps_channel_day_report");

            }
        }
        $this->reportLineBillChannel($start_time,$where);
    }

    //更新线上渠道收费数据
    public function reportLineBillChannel($start_time,$where)
    {
        if (!empty($where)) {
            $where1 = "and bill.community_id= {$where['community_id']} and bill.cost_id = {$where['cost_id']}";
        } else {
            $where1 = 'and 1=1';
        }
        if (is_array($start_time)) {
            $where1 .= "and pay_time >= {$start_time['start']} and pay_time <= {$start_time['end']}";
        }
        $db=Yii::$app->db;
        $bill_sql ="select  sum(bill.paid_entry_amount) as amount,count(der.id) as total,FROM_UNIXTIME(der.pay_time,'%Y%m%d') as riqi,bill.cost_id, der.community_id,der.pay_channel as type from ps_bill as bill,ps_order as der where bill.order_id=der.id and bill.id=der.bill_id and bill.trade_defend=0 and der.status=2 and der.is_del=1 and der.pay_status=1  $where1 group by  der.community_id,bill.cost_id,riqi";
        $bills = $db->createCommand($bill_sql)->queryAll();
        if( empty(!$bills) ) {
            foreach ($bills as $key => $val) {
                $val["community_name"]=CommunityService::service()->getShowCommunityInfo($val["community_id"])['name'];
                $val['type'] = '9';
                $val["type_name"]= '线上收款';
                $day = $month = $year = $val;
                $this->addChannelReport($day, "ps_channel_day_report");
            }
        }
    }
}
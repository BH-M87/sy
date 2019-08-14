<?php
/**
 * 房屋信息service(原PsHouse)
 * @author shenyang
 * @date 2017-06-06
 */
namespace service\alipay;


use service\BaseService;
use Yii;
use yii\db\Query;

Class HouseService extends BaseService
{

    /*
 * 获取小区下房屋的总数量
 * */
    public  function  getRoomTotals($data) {
        $query = new Query();
        $query->from("ps_community_roominfo A")
            ->where(["A.community_id"=>$data["community_id"]]);
        return  $query->count();
    }


    public function  houseExcel( $data,$page,$rows,$type='data' ) {
        $params = $arr = [];
        $where  = " 1=1 ";
        if ($data["community_id"]) {
            $arr = [':community_id'=>$data["community_id"]];
            $params = array_merge ($params,$arr);
            $where .= " AND community_id=:community_id";
        }
        $total = Yii::$app->db->createCommand("SELECT count(*) from ps_community_roominfo where ".$where ,$params)
            ->queryScalar();
        $models =[];
        if($type=='data') {
            $page = $page < 1 ? 1 : $page;
            $page = $page > ceil($total / $rows) ? ceil($total / $rows) : $page;
            $limit = ($page - 1) * $rows;
            $sql =  "select `group`,building,unit,room,charge_area from ps_community_roominfo where ".$where." order by (`group`+0),`group`,(building+0),building,(unit+0),unit,(room+0),room asc limit $limit,$rows";
            $models = Yii::$app->db->createCommand($sql,$params)->queryAll();
        }
        $arr = ["total" => $total,"list" => $models];
        return $arr;
    }

}
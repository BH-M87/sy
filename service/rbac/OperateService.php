<?php
namespace service\rbac;

use Yii;
use yii\db\Query;
use common\core\PsCommon;
use service\BaseService;

class OperateService extends  BaseService {

    /*
     * 查询操作记录日志
     * $data 查询条件
     * $page  当前页
     * $rows 显示列数
     * */
    public static function lists( $data, $page, $rows,$user_info){

        $query = new Query();
        $query->from("ps_operate_log");
        if ( $data['name'] ) {
            $query->andWhere(['or', ['like', 'operate_mobile', $data["name"] ], ['like', 'operate_name', $data["name"] ]]);
        }

        if ( $data['content'] ) {
            $query->andWhere(['or', ['like', 'operate_menu', $data["content"] ], ['like', 'operate_type', $data["content"] ], ['like', 'operate_content', $data["content"] ]]);
        }

        if ( $data['operate_time_start'] ) {
            $operate_time_start = strtotime(date('Y-m-d 00:00:00', strtotime($data["operate_time_start"])));
            $query->andWhere(['>=', 'operate_time', $operate_time_start]);
        }
        if ( $data['operate_time_end'] ) {
            $operate_time_end = strtotime(date('Y-m-d 23:59:59', strtotime($data["operate_time_end"])));
            $query->andWhere(['<=', 'operate_time', $operate_time_end]);
        }

        if($user_info["id"] != 1){
            $query->andWhere(["operate_id" => $user_info["id"]]);
        }

        $re['totals'] = $query->count();
        $query->orderBy('operate_time desc');
        $offset = ($page-1) * $rows;
        $query->offset($offset)->limit($rows);
        $command    = $query->createCommand();
        $models = $command->queryAll();
        foreach ($models as $key => $model) {
            $models[$key]["operate_time"] =  $model["operate_time"]>0 ? date("Y-m-d H:i:s",$model["operate_time"]): "";
            $models[$key]["operate_content"] = !empty($model['operate_content']) ? $model['operate_content'] : $model['operate_menu'].'-'.$model['operate_type'];
        }
        $re['list']   = $models;
        return $re;
    }
    public   static  function commlists( $data, $page, $rows,$user_info,$type=''){

        $where = "  community_id=:community_id";
        $params =[ ":community_id"=>$data["community_id"] ];
        //不是管理员只查看自己的日志
        if($user_info["user_type"]!='admin'){
            $arr = [':operate_id'=>$user_info['id']];
            $params = array_merge ($params,$arr);
            $where .= " and  operate_id = :operate_id ";
        }
        if ( $data['name'] ) {
            $arr = [':name'=>'%'. $data["name"].'%'];
            $params = array_merge ($params,$arr);
            $where .= " and ( operate_mobile like :name or operate_name like :name ) ";
        }
        if ( $data['name'] ) {
            $arr = [':name'=>'%'. $data["name"].'%'];
            $params = array_merge ($params,$arr);
            $where .= " and ( operate_mobile like :name or operate_name like :name ) ";
        }
        if ( $data['content'] ) {
            $arr = [':content'=>'%'. $data["content"].'%'];
            $params = array_merge ($params,$arr);
            $where .= " and ( operate_menu like :content or operate_type like :content or operate_content like :content ) ";
        }
        if ( $data['operate_time_start'] ) {
            $operate_time_start = strtotime(date('Y-m-d 00:00:00', strtotime($data["operate_time_start"])));
            $arr = [":operate_time_start" => $operate_time_start];
            $params = array_merge($params, $arr);
            $where .= " And  operate_time>= :operate_time_start";
        }
        if ( $data['operate_time_end'] ) {
            $operate_time_end = strtotime(date('Y-m-d 23:59:59', strtotime($data["operate_time_end"])));
            $arr = [":operate_time_end" => $operate_time_end];
            $params = array_merge($params, $arr);
            $where .= " And  operate_time<= :operate_time_end";
        }

        $sql = "select count(id) from ps_community_operate_log where ".$where;

        $count = Yii::$app->db->createCommand($sql,$params)->queryScalar();

        $page = $page < 1 ? 1 : $page;
        if ($count == 0) {
            $arr1 = ['totals' => 0, 'list' => []];
            return $arr1;
        }
        $page = $page > ceil($count / $rows) ? ceil($count / $rows) : $page;
        $limit = ($page - 1) * $rows;
        if ($type == "all") {
            $limit = 0;
            $rows = $count;
        }
        $sql = "select * from ps_community_operate_log where ".$where." order by id desc limit $limit,$rows";
        $models = Yii::$app->db->createCommand($sql,$params)->queryAll();
        foreach ($models as $key=>$model) {
            $models[$key]["operate_time"] =  $model["operate_time"]>0 ? date("Y-m-d H:i:s",$model["operate_time"]): "";
        }
        return ["list" => $models, 'totals' => $count];
    }

    //运营的日志表
    public   static  function  add( $userinfo,$operate ){
        $connection = Yii::$app->db;
        $log_arr = [
//            "p_id"=>
            "operate_id" =>       $userinfo["id"],
            "operate_mobile" =>   $userinfo["mobile"],
            "operate_name" =>     $userinfo["truename"],
            "operate_time" =>     time(),
            "operate_menu" =>      $operate['operate_menu'],
            "operate_type" =>       $operate['operate_type'],
            "operate_content" =>   $operate['operate_content']
        ];
        $connection->createCommand()->insert('ps_operate_log', $log_arr)->execute();
        $result = ["status" => '20000',];
        return $result;
    }

    //物业的日志表
    public static function addComm($userInfo, $operate){
        $connection = Yii::$app->db;
        $log_arr = [
            "operate_id" =>       $userInfo["id"] ?? 0,
            "operate_mobile" =>   $userInfo["mobile"] ? $userInfo["mobile"] : '',
            "operate_name" =>     $userInfo["truename"] ?? "",
            "operate_time" =>     time(),
            "operate_menu" =>      $operate['operate_menu'],
            "community_id" =>      !empty($operate['community_id']) ? $operate['community_id'] : 0,
            "operate_type" =>      $operate['operate_type'],
            "operate_content" =>   $operate['operate_content']
        ];
        $connection->createCommand()->insert('ps_community_operate_log', $log_arr)->execute();
        $result = ["status" => '20000',];
        return $result;
    }
}
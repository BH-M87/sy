<?php
namespace service\manage;

use Yii;

use yii\db\Query;
use yii\base\Exception;

use service\BaseService;

class PackService extends BaseService 
{
    public static $_Type = ["1" => "运营", "2" => "邻易联"];
    
    // 获取套餐包及分类列表
    public function getList($reqArr) 
    {
        $class = $pack = [];
        $query = new Query();
        $query->select(["A.id","A.name","A.described"])->from("ps_pack_classify A");
        if($reqArr["name"]) {
            $query->andWhere(["like","A.name",$reqArr["name"]]);
        }
        $query->orderBy("created_at desc");
        $classModel = $query->all();
        foreach ($classModel as $c) {
            if(!isset($class[$c["id"]])) {
                $class[$c["id"]]["key"] = "1"."_".$c["id"];
                $class[$c["id"]]["id"] = $c["id"];
                $class[$c["id"]]["name"] = $c["name"];
                $class[$c["id"]]["parent_id"] =0;
                $class[$c["id"]]["parent_name"] ='';
                $class[$c["id"]]["described"] = $c["described"];
                $class[$c["id"]]["item_type"] = 1;
                $class[$c["id"]]["children"] = [];
            }
        }

        $query = new Query();
        $query->select(["A.id","A.name","A.type","A.described","A.class_id","B.name as class_name","B.described as class_described"])
            ->from("ps_pack A")
            ->leftJoin("ps_pack_classify B","A.class_id=B.id");
        if($reqArr["name"]) {
            $query->andWhere(["like","A.name",$reqArr["name"]]);
        }
        if($reqArr["type"]) {
            $query->andWhere(["type"=>$reqArr["type"]]);
        }
        $query->orderBy("A.created_at desc");
        $models = $query->all();
        foreach ($models as $key=> $model) {
            $arr = [
                "key"=> "2"."_".$model["id"],
                "id"=>$model["id"],
                "name"=>$model["name"],
                "described"=>$model["described"],
                "item_type"=>2,
                "type"=>$model['type'],
                "parent_id"=>$model["class_id"],
                "parent_name"=>$model["class_name"],
                "type_desc"=> isset(self::$_Type[$model['type']]) ? self::$_Type[$model['type']] : '未知',
            ];
            if($model["class_id"]!=0) {
              if(!isset($class[$model["class_id"]])) {
                    $class[$model["class_id"]]["key"] = "1"."_".$model["class_id"];
                    $class[$model["class_id"]]["id"] = $model["class_id"];
                    $class[$model["class_id"]]["name"] = $model["class_name"];
                    $class[$model["class_id"]]["parent_name"] ='';
                    $class[$model["class_id"]]["parent_id"] =0;
                    $class[$model["class_id"]]["described"] = $model["class_described"];
                    $class[$model["class_id"]]["item_type"] = 1;
                }
                $class[$model["class_id"]]["children"][]=$arr;
            } else {
                $pack[] = $arr;
            }
        }
        $result = array_merge($class,$pack);
        return $result;
    }

    // 获取套餐包及分类列表
    public function getPacks($reqArr) 
    {
        $query = new Query();
        $query->select(["A.id","A.name","A.type","A.class_id","B.name as class_name"])
            ->from("ps_pack A")
            ->leftJoin("ps_pack_classify B","A.class_id=B.id");
        if(!empty($reqArr["name"])) {
            $query->andWhere(["like","A.name",$reqArr["name"]]);
        }
        if(!empty($reqArr["type"])) {
            $query->andWhere(["type"=>$reqArr["type"]]);
        }
        $query->orderBy(" class_id desc,A.created_at desc");
        $models = $query->all();
        $class = $pack = [];
        foreach ($models as $key=> $model) {
            $arr = [
                "key"=> "2"."_".$model["id"],
                "id"=>$model["id"],
                "name"=>$model["name"],
                "item_type"=>2,
                "type"=>$model['type'],
                "parent_id"=>$model["class_id"],
                "parent_name"=>$model["class_name"],
                "type_desc"=> isset(self::$_Type[$model['type']]) ? self::$_Type[$model['type']] : '未知',
            ];
            if($model["class_id"]!=0) {
                if(isset($class[$model["class_id"]])) {
                    $class[$model["class_id"]]["children"][]=$arr;
                } else {
                    $class[$model["class_id"]]["key"] = "1"."_".$model["class_id"];
                    $class[$model["class_id"]]["id"] = $model["class_id"];
                    $class[$model["class_id"]]["name"] = $model["class_name"];
                    $class[$model["class_id"]]["parent_id"] =0;
                    $class[$model["class_id"]]["parent_name"] ='';
                    $class[$model["class_id"]]["item_type"] = 1;
                    $class[$model["class_id"]]["children"][]=$arr;
                }
            } else {
                $pack[] = $arr;
            }
        }
        $result = array_merge($class,$pack);
        return $result;
    }

    // 添加套餐包分类
    public function classifyAdd($reqArr) 
    {
        $db = Yii::$app->db;
        $total =  $db->createCommand("select count(id) from ps_pack_classify where name=:name",[":name"=>$reqArr["name"]])->queryScalar();
        if( $total > 0) {
            return $this->failed("名称重复");
        }
        $arr = [
            "name"=>$reqArr["name"],
            "described"=> $reqArr["described"] ?  $reqArr["described"] : "",
            "created_at"=> time(),
        ];
        $db->createCommand()->insert("ps_pack_classify",$arr)->execute();
        return $this->success();
    }

    // 套餐包分类编辑
    public function classifyEdit($reqArr) 
    {
        $db = Yii::$app->db;
        $total =  $db->createCommand("select count(id) from ps_pack_classify where name=:name and id<>:id",[":name"=>$reqArr["name"],":id"=>$reqArr["class_id"]])->queryScalar();
        if( $total > 0) {
            return $this->failed("名称重复");
        }
        $classify =  $db->createCommand("select * from ps_pack_classify where  id=:id",[":id"=>$reqArr["class_id"]])->queryOne();
        if(empty($classify)) {
            return $this->failed("套餐包分类不存在");
        }
        $arr = [
            "name"=>$reqArr["name"],
            "described"=> $reqArr["described"] ?  $reqArr["described"] : "",
        ];
        $db->createCommand()->update("ps_pack_classify",$arr,["id"=>$reqArr["class_id"]])->execute();
        return $this->success();
    }

    // 显示套餐包分类详情
    public function classifyShow( $classId) 
    {
        $db = Yii::$app->db;
        $model =  $db->createCommand( "select name,described from ps_pack_classify where id=:id",[":id"=>$classId])->queryOne();
        return $model ? $model : [];
    }

    // 删除套餐包分类
    public function classifyDelete($classId) 
    {
        $db = Yii::$app->db;
        $model =  $db->createCommand( "select name,described from ps_pack_classify where id=:id",[":id"=>$classId])->queryOne();
        if( empty( $model)) {
            return $this->failed("未找到数据");
        }
        $total = $db->createCommand("select count(id) from ps_pack where class_id=:class_id",["class_id"=>$classId])->queryScalar();
        if($total) {
            return $this->failed("请先移除分类下的套餐包");
        }
        $db->createCommand()->delete('ps_pack_classify',["id"=>$classId])->execute();
        return $this->success();
    }

    // 添加套餐包
    public function packAdd($reqArr) 
    {
        $db = Yii::$app->db;
        $name = $reqArr["name"];
        $classId=  $reqArr["class_id"]  ? $reqArr["class_id"] : 0;
        $classTotal =  $db->createCommand("select count(id) from ps_pack_classify where id=:id",[":id"=>$classId])->queryScalar();
        if($classTotal<1) {
           return $this->failed("套餐包分类不存在");
        }
        $total = $this->vaildName($name,$classId);
        if( $total > 0) {
            return $this->failed("同级名称不能重复");
        }
        $arr = [
            "name"=>$name,
            "class_id"=>$classId,
            "described"=> $reqArr["described"] ?  $reqArr["described"] : "",
            "type"=> $reqArr["type"] ?  $reqArr["type"] : 1,
            "created_at"=> time(),
        ];
        $db->createCommand()->insert("ps_pack",$arr)->execute();
        $packId =   $db->getLastInsertID();
        $itemArr = [];
        foreach ($reqArr["menus"] as $menu ) {
           $itemArr[] = ["menu_id"=>$menu,"pack_id"=>$packId];
        }

        $db->createCommand()->batchInsert('ps_menu_pack',
                ['menu_id','pack_id'],
                $itemArr
            )->execute();
        return $this->success();
    }

    // 编辑添加套餐包
    public function packEdit($reqArr) 
    {
        $db = Yii::$app->db;
        $packId = $reqArr["pack_id"];
        $name = $reqArr["name"];
        $classId=  $reqArr["class_id"]  ? $reqArr["class_id"] : 0;
        $classTotal =  $db->createCommand("select count(id) from ps_pack_classify where id=:id",[":id"=>$classId])->queryScalar();
        if($classTotal<1) {
            return $this->failed("套餐包分类不存在");
        }
        $total = $this->vaildName($name,$classId,$packId);
        if( $total > 0) {
            return $this->failed("同级名称不能重复");
        }
        $pack =  $db->createCommand("select * from ps_pack where  id=:id",[":id"=>$packId])->queryOne();
        if(empty($pack)) {
            return $this->failed("套餐包不存在");
        }

       /* if($pack["type"] !=  $reqArr["type"]) {
            $total = $db->createCommand("select count(id) from ps_group_pack where pack_id=:pack_id",[":pack_id"=>$packId])->queryScalar();
            if($total>0) {
                return $this->failed("套餐包已被用户使用,禁止切换所属");
            }
        }*/

        $arr = [
            "name"=>$name,
            "class_id"=>$classId,
            "type"=> $reqArr["type"] ?  $reqArr["type"] : 1,
            "described"=> $reqArr["described"] ?  $reqArr["described"] : "",
        ];
        $db->createCommand()->update("ps_pack",$arr,["id"=>$packId])->execute();
        $db->createCommand()->delete('ps_menu_pack',
            "pack_id=:pack_id",
            [":pack_id" => $packId ]
        )->execute();
        $itemArr = [];
        foreach ($reqArr["menus"] as $menu ) {
            array_push($itemArr,["menu_id"=>$menu,"pack_id"=>$packId]);
        }
        $db->createCommand()->batchInsert('ps_menu_pack',
            ['menu_id','pack_id'],
            $itemArr
        )->execute();
        $this->_resetCache($packId);
        return $this->success();
    }

    private function _resetCache($packId) 
    {
        $groupIds = Yii::$app->db->createCommand("select group_id from ps_group_pack where pack_id=:pack_id",[":pack_id"=>$packId])->queryColumn();
       if(!empty($groupIds)) {
           foreach ($groupIds as $groupId){
               GroupService::service()->delMenuCache($groupId);
           }
        }
    }

    // 查看套餐包
    public function packShow($packId)
    {
        $model =  Yii::$app->db->createCommand( "select name,class_id,type,created_at,described from ps_pack where id=:id",[":id"=>$packId])->queryOne();
        if(!empty($model)) {
            $model["menus"] = $this->getPackMenu($packId,0);
            $model["type_desc"] = $model["type"]==1 ? "代理商" : "邻易联";
            $model["created_at"] = date("Y-m-d",$model["created_at"]);
        }
        return !empty($model) ? $model : [] ;
    }

    // 套餐包删除
    public function packDelete($packId) 
    {
        $db = Yii::$app->db;
        $model =  $db->createCommand( "select name,described from ps_pack where id=:id",[":id"=>$packId])->queryOne();
        if( empty( $model)) {
            return $this->failed("未找到数据");
        }
        $total = $db->createCommand("select count(id) from ps_group_pack where pack_id=:pack_id",[":pack_id"=>$packId])->queryScalar();
        if($total>0) {
            return $this->failed("套餐包已被用户使用");
        }
        $db->createCommand()->delete('ps_pack',["id"=>$packId])->execute();
        $db->createCommand()->delete('ps_menu_pack',["pack_id"=>$packId])->execute();
        return $this->success();
    }

    // 验证套餐包名称，同一级下名称不能重复
    private function vaildName($name,$classId = 0, $packId = 0) 
    {
        $query = new Query();
         return $query->select(["count(id)"])->from("ps_pack")
            ->where(["name"=>$name])->andWhere(["class_id"=>$classId])
            ->andWhere(["<>","id",$packId])
            ->scalar();
    }

    // 获取所有套餐包分类
    public function getClassify() 
    {
        $query = new Query();
        $models = $query->select(["id","name"])
            ->from("ps_pack_classify")->all();
        return $models;
    }

    // 获取用户组下的所有套餐包
    public  function getGroupPack( $groupId) 
    {
        $query = new Query();
        $models = $query->select(["B.id","B.name"])
            ->from("ps_group_pack A")
            ->leftJoin("ps_pack B","A.pack_id=B.id")
            ->where(["A.group_id"=>$groupId])
            ->all();
        return !empty($models) ? $models : [];
    }

    public function getLevelGroupPack($groupId) 
    {
        $query = new Query();
        $models = $query->select(["A.id","A.name","A.type","A.class_id","B.name as class_name"])
            ->from("ps_group_pack C")
            ->leftJoin("ps_pack A","C.pack_id=A.id")
            ->leftJoin("ps_pack_classify B","A.class_id=B.id")
            ->where(["C.group_id"=>$groupId])->orderBy(" class_id desc,A.created_at desc")->all();
        $class = $pack = [];
        foreach ($models as $key=> $model) {
            $arr = [
                "key"=> "2"."_".$model["id"],
                "id"=>$model["id"],
                "name"=>$model["name"],
                "item_type"=>2,
                "type"=>$model['type'],
                "parent_id"=>$model["class_id"],
                "parent_name"=>$model["class_name"],
                "type_desc"=> isset(self::$_Type[$model['type']]) ? self::$_Type[$model['type']] : '未知',
            ];
            if($model["class_id"]!=0) {
                if(isset($class[$model["class_id"]])) {
                    $class[$model["class_id"]]["children"][]=$arr;
                } else {
                    $class[$model["class_id"]]["key"] = "1"."_".$model["class_id"];
                    $class[$model["class_id"]]["id"] = $model["class_id"];
                    $class[$model["class_id"]]["name"] = $model["class_name"];
                    $class[$model["class_id"]]["parent_id"] =0;
                    $class[$model["class_id"]]["parent_name"] ='';
                    $class[$model["class_id"]]["item_type"] = 1;
                    $class[$model["class_id"]]["children"][]=$arr;
                }
            } else {
                $pack[] = $arr;
            }
        }
        $result = array_merge($class,$pack);
        return $result;
    }

    // 获取套餐包下的菜单树状的结构
    public function getPackMenu($packId, $status = 0) 
    {
        $query = new Query();
        $query->select(["A.id","A.parent_id","A.level","A.name"])
            ->from("ps_menu_pack B")
            ->leftJoin("ps_menus A","A.id=B.menu_id")->where(["B.pack_id"=>$packId]);
        if($status==1) {
            $query->andWhere(["status"=>1]);
        }
        $models =  $query ->orderBy("parent_id asc,sort_num asc")->all();
        if(empty($models)) {
            return [];
        }
        $result = MenuService::service()->getMenuTree($models,1);
        return $result;
    }

    // 获取某个系统下菜单树壮结构图
    public function getSystemMenu($systemType) 
    {
        $query = new Query();
        $query->select(["A.id","A.parent_id","A.level","url","A.name","A.status","A.en_key", 'A.key'])
            ->from("ps_menus A")
            ->where(["status"=>1])
            ->where(["system_type"=>$systemType])
            ->orderBy("parent_id asc,sort_num asc");
        $models = $query->all();
        if(empty($models)) {
            return [];
        }
        $result = MenuService::service()->getMenuTree($models,1);
        return $result;
    }

    // 保存用户组和套餐包的关系
    public function _saveGroupPack($groupId, $packs) 
    {
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $connection->createCommand()->delete('ps_group_pack',["group_id"=>$groupId])->execute();
            $packIdArr =[];
            foreach ($packs as $packId) {
                if(!in_array($packId,$packIdArr)) {
                    $packArr[] = [$groupId,$packId];
                    $packIdArr[]=$packId;
                }
            }
            $connection->createCommand()->batchInsert('ps_group_pack',
                ['group_id','pack_id'],
                $packArr
            )->execute();
            GroupService::service()->delMenuCache($groupId);
            $transaction->commit();
            return $this->success();
        }   catch (Exception $e) {
            $transaction->rollBack();
            return $this->failed();
        }
    }
}
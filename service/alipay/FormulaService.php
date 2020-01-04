<?php
namespace service\alipay;

use Yii;
use yii\db\Query;
use yii\base\Exception;

use common\core\PsCommon;

use service\BaseService;

use service\manage\CommunityService;
use service\rbac\OperateService;
use service\property_basic\JavaService;

class FormulaService extends BaseService
{
    /*
     * 查看物业公司下用户列表
     * $data 查询条件
     * $page  当前页
     * $rows 显示列数
     * */
    public function lists($data, $page, $rows)
    {

        $params = [":community_id" => $data["community_id"]];
        $where = " community_id=:community_id";

        $sql = "select count(id) from ps_formula where " . $where;
        $count = Yii::$app->db->createCommand($sql, $params)->queryScalar();

        $page = $page < 1 ? 1 : $page;
        if ($count == 0) {
            $arr1 = ['totals' => 0, 'list' => []];
            return $arr1;
        }
        $page = $page > ceil($count / $rows) ? ceil($count / $rows) : $page;
        $limit = ($page - 1) * $rows;

        $sql = "select * from ps_formula where " . $where . " order by id desc limit $limit,$rows";
        $models = Yii::$app->db->createCommand($sql, $params)->queryAll();
        foreach ($models as $key => $model) {
            $models[$key]["create_at"] = $model["create_at"] > 0 ? date("Y-m-d H:i:s", $model["create_at"]) : "-";
            $models[$key]["update_at"] = $model["update_at"] > 0 ? date("Y-m-d H:i:s", $model["update_at"]) : "-";
            //计算公式
            foreach (PsCommon::getFormulaVar() as $k => $v) {
                $model["formula"] = str_replace($k, $v, $model["formula"]);
            }
            $models[$key]["formula"] = $model["formula"];           //计算公式
            $models[$key]["calcRule"] = PsCommon::getFormulaRule($model["calc_rule"]) . '/' . PsCommon::getFormulaWay($model['del_decimal_way']);           //计算规则
        }
        return ["list" => $models, 'totals' => $count];
    }


    public function show($formula_id)
    {

        $params = [":formula_id" => $formula_id];

        $sql = "select * from ps_formula where id=:formula_id";

        $model = Yii::$app->db->createCommand($sql, $params)->queryOne();
        if (empty($model)) {
            $result = ["status" => '20001', "errorMsg" => "未找到公式"];
            return $result;
        }
        $model["create_at"] = $model["create_at"] > 0 ? date("Y-m-d H:i:s", $model["create_at"]) : "-";
        $model["update_at"] = $model["update_at"] > 0 ? date("Y-m-d H:i:s", $model["update_at"]) : "-";
        $model["formula_val"] = $model["formula"];
        $model["calc_rule_msg"] = PsCommon::getFormulaRule($model["calc_rule"]) . '/' . PsCommon::getFormulaWay($model["del_decimal_way"]);
        foreach (PsCommon::getFormulaVar() as $k => $v) {
            $model["formula"] = str_replace($k, $v, $model["formula"]);
        }
        $result = ["status" => '20000', "list" => $model];
        return $result;
    }

    public function getFormula($formula_id)
    {
        $query = new Query();
        $model = $query->select(["formula", "calc_rule", "del_decimal_way"])
            ->from("ps_formula")
            ->where(["id" => $formula_id])
            ->one();
        return !empty($model) ? $model : [];
    }
    //根据小区id与类型获取计算公式:公式类型：1水费2电费3公摊水费4公摊电费
    public function getFormulaByCommunityId($community_id,$rule_type)
    {
        $query = new Query();
        $model = $query->select(["price"])
            ->from("ps_water_formula")
            ->where(["community_id" => $community_id,"rule_type"=>$rule_type])
            ->one();
        return !empty($model) ? $model : [];
    }

    /*
    * 新增用户
    * $data 单个业主新增
    * */
    public function add($data, $user_info)
    {

        $connection = Yii::$app->db;
        $params = [":community_id" => $data["community_id"], ":name" => $data["name"]];
        $is_formula = $connection->createCommand("select count(id) from ps_formula 
        where community_id=:community_id and name=:name",
            $params)->queryScalar();
        if ($is_formula >= 1) {
            $result = ["status" => '50001', "errorMsg" => "公式名称已存在"];
            return $result;
        }
        $park_arr = [
            "community_id" => $data["community_id"],
            "name" => $data["name"],
            "formula" => $data["formula"],
            "calc_rule" => $data["calc_rule"],
            "del_decimal_way" => $data["del_decimal_way"],
            "operator_id" => $data["operator_id"],
            "operator_name" => $data["operator_name"],
            "create_at" => time(),
        ];

        self::_logAdd($data['token'], "公式管理：新增公式，".$data["name"]);

        $connection->createCommand()->insert('ps_formula', $park_arr)->execute();
        $result = ["status" => '20000'];

        return $result;
    }

    public function edit($data, $user_info)
    {
        $connection = Yii::$app->db;
        $params = [":formula_id" => $data["formula_id"]];

        $formula = $connection->createCommand("select * from ps_formula where id=:formula_id ", $params)->queryOne();
        if (empty($formula)) {
            $result = ["status" => '50001', "errorMsg" => "未找到公式"];
            return $result;
        }

        $param = [":community_id" => $formula["community_id"], ":name" => $data["name"]];
        $is_formula = $connection->createCommand("select id from ps_formula 
        where community_id=:community_id and name=:name",
            $param)->queryScalar();
        if ($is_formula && $is_formula != $data["formula_id"]) {
            $result = ["status" => '50001', "errorMsg" => "公式名称已存在"];
            return $result;
        }
        $formula_arr = [
            "name" => $data["name"],
            "calc_rule" => $data["calc_rule"],
            "del_decimal_way" => $data["del_decimal_way"],
            "formula" => $data["formula"],
            "update" => time(),
        ];

        self::_logAdd($data['token'], "公式管理：编辑公式，".$formula["name"]);

        $connection->createCommand()->update('ps_formula',
            $formula_arr,
            "id=:id",
            [":id" => $data["formula_id"]]
        )->execute();
        $result = ["status" => '20000', "errorMsg" => "编辑成功"];
        return $result;
    }

    public function delete($data, $user_info)
    {
        $connection = Yii::$app->db;
        $params = [":formula_id" => $data["formula_id"]];
        $formula = $connection->createCommand("select * from ps_formula where id=:formula_id ", $params)->queryOne();
        if (empty($formula)) {
            $result = ["status" => '50001', "errorMsg" => "未找到公式"];
            return $result;
        }
        if ($formula["status"] == 2) {
            $result = ["status" => '20001', "errorMsg" => "使用中,请稍后删除"];
            return $result;
        }

        self::_logAdd($data['token'], "删除公式，".$formula["name"]);

        $connection->createCommand()->delete('ps_formula', "id=:formula_id", $params)->execute();
        $result = ["status" => '20000', "errorMsg" => "删除成功"];
        return $result;
    }

    /*查看水费列表*/
    public function waterList($community_id)
    {
        $param = [":community_id" => $community_id];
        $sql = "select * from ps_water_formula where community_id=:community_id and rule_type=1";
        $model = Yii::$app->db->createCommand($sql, $param)->queryOne();
        $result["formula_desc"] = "";
        if (!empty($model)) {
            if ($model['type'] == 1) {//按固定
                $result["price"] = $model["price"];
                $result["formula_desc"] = $model["price"] . "*用水量";
            } else {//安阶梯
                $sql = "select * from ps_phase_formula where community_id=:community_id  and rule_type=1";
                $phase_list = Yii::$app->db->createCommand($sql, $param)->queryAll();
                if ($phase_list) {
                    foreach ($phase_list as $key => $phase) {
                        $ton = $phase['ton'] == 0 ? "X" : $phase['ton'];
                        $msg = $ton . "元/立方米，单价:" . $phase["price"] . "*用水量<br>";
                        $result["formula_desc"] .= $msg;
                    }
                }
            }
            $result["type"] = $model["type"];
            $result["calcRule"] = PsCommon::getFormulaRule($model["calc_rule"]) . '/' . PsCommon::getFormulaWay($model['del_decimal_way']);           //计算规则
        }
        return $result;
    }

    public function waterShow($community_id)
    {
        $param = [":community_id" => $community_id];
        $sql = "select id,community_id,name,type,operator_id,operator_name,price,rule_type,calc_rule,del_decimal_way,create_at from ps_water_formula where community_id=:community_id and rule_type=1 ";
        $model = Yii::$app->db->createCommand($sql, $param)->queryOne();
        if (!empty($model)) {
            $model['phase_list'] = [];
            if ($model['type'] == 2) {//安阶梯
                $sql = "select ton,price from ps_phase_formula where community_id=:community_id  and rule_type=1";
                $phase_list = Yii::$app->db->createCommand($sql, $param)->queryAll();
                if ($phase_list) {
                    $model['phase_list'] = $phase_list;
                }
            }
            return $this->success($model);
        } else {
            return $this->failed("未找到计算公式");
        }
    }

    // 编辑固定水价
    public function editFixedWater($data, $user_info)
    {
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $param = [":community_id" => $data["community_id"]];
            $sql = "select * from ps_water_formula where community_id=:community_id  and rule_type=1";
            $model = $db->createCommand($sql, $param)->queryOne();
            if ($data['type'] == 1) {//按固定
                if (empty($model)) {//新增
                    $new_arr = [
                        "community_id" => $data["community_id"],
                        "name" => "固定水价",
                        "type" => "1",
                        "price" => $data["price"],
                        "rule_type" => 1,
                        "calc_rule" => $data['calc_rule'],
                        "del_decimal_way" => $data['del_decimal_way'],
                        "operator_id" => $user_info["id"],
                        "operator_name" => $user_info["truename"],
                        "create_at" => time(),
                    ];
                    $db->createCommand()->insert("ps_water_formula", $new_arr)->execute();
                } else {//编辑
                    $edit_arr = ["name" => "固定水价", "price" => $data["price"], "type" => "1", "calc_rule" => $data['calc_rule'], "del_decimal_way" => $data['del_decimal_way']];
                    Yii::$app->db->createCommand()->update("ps_water_formula", $edit_arr, 'rule_type=1 and community_id=' . $data["community_id"])->execute();
                }
            } else {//安阶梯
                if (!$data['phase_list']) {
                    return $this->failed("阶梯数据不存在");
                }
                if (!is_array($data['phase_list']) || count($data['phase_list']) != 3) {
                    return $this->failed("阶梯数据格式错误");
                }
                foreach ($data['phase_list'] as $key=>$phase) {
                    if (!is_numeric($phase['ton']) || !is_numeric($phase['price'])) {
                        return $this->failed("阶梯数据类型错误");
                    }
                    if($key==2 && $phase['ton']!=0){
                        return $this->failed("阶梯数据类型错误");
                    }
                }
                if (empty($model)) {//新增
                    $new_arr = [
                        "community_id" => $data["community_id"],
                        "name" => "阶梯水价",
                        "type" => "2",
                        "rule_type" => 1,
                        "calc_rule" => $data['calc_rule'],
                        "del_decimal_way" => $data['del_decimal_way'],
                        "operator_id" => $user_info["id"],
                        "operator_name" => $user_info["truename"],
                        "create_at" => time(),
                    ];
                    $db->createCommand()->insert("ps_water_formula", $new_arr)->execute();
                } else {//编辑
                    $edit_arr = ["name" => "阶梯水价", "price" => "0", "type" => "2", "calc_rule" => $data['calc_rule'], "del_decimal_way" => $data['del_decimal_way']];
                    Yii::$app->db->createCommand()->update("ps_water_formula", $edit_arr, 'rule_type=1 and community_id=' . $data["community_id"])->execute();
                }
                $db->createCommand()->delete("ps_phase_formula", ['community_id' => $data['community_id'], 'rule_type' => '1'])->execute();
                foreach ($data['phase_list'] as $phase) {
                    $phaseData = [
                        'community_id' => $data['community_id'],
                        'rule_type' => 1,
                        'ton' => $phase['ton'],
                        'price' => $phase['price'],
                        'create_at' => time()
                    ];
                    $db->createCommand()->insert("ps_phase_formula", $phaseData)->execute();
                }
            }
            $transaction->commit();

            self::_logAdd($data['token'], "水费编辑");
            return $this->success();
        } catch (Exception $e) {
            $transaction->rollBack();
            return ["status" => false, "errorMsg" => "编辑失败"];
        }
    }

    //================================================缴费3.5需求======================================================
    /*查看电费列表*/
    public function electricList($community_id)
    {
        $param = [":community_id" => $community_id];
        $sql = "select * from ps_water_formula where community_id=:community_id and rule_type=2";
        $model = Yii::$app->db->createCommand($sql, $param)->queryOne();
        $result["formula_desc"] = "";
        if (!empty($model)) {
            if ($model['type'] == 1) {//按固定
                $result["price"] = $model["price"];
                $result["formula_desc"] = $model["price"] . "*用电量";
                $result["type_msg"] = '固定价';
            } else {//安阶梯
                $sql = "select * from ps_phase_formula where community_id=:community_id  and rule_type=2";
                $phase_list = Yii::$app->db->createCommand($sql, $param)->queryAll();
                if ($phase_list) {
                    foreach ($phase_list as $key => $phase) {
                        $ton = $phase['ton'] == 0 ? "X" : $phase['ton'];
                        $msg = $ton . "度，单价:" . $phase["price"] . "*用电量<br>";
                        $result["formula_desc"] .= $msg;
                    }
                }
            }
            $result["type"] = $model["type"];
            $result["calcRule"] = PsCommon::getFormulaRule($model["calc_rule"]) . '/' . PsCommon::getFormulaWay($model['del_decimal_way']);           //计算规则
        }
        return $result;
    }

    /*新增编辑电价*/
    public function editElectric($data, $user_info)
    {
        if (empty($data['price']) && $data['type'] == 1) {
            return $this->failed("价格不存在");
        }
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $param = [":community_id" => $data["community_id"]];
            $sql = "select * from ps_water_formula where community_id=:community_id  and rule_type=2";
            $model = $db->createCommand($sql, $param)->queryOne();
            if ($data['type'] == 1) {//按固定
                if (empty($model)) {//新增
                    $new_arr = [
                        "community_id" => $data["community_id"],
                        "name" => "固定电价",
                        "type" => "1",
                        "price" => $data["price"],
                        "rule_type" => 2,
                        "calc_rule" => $data['calc_rule'],
                        "del_decimal_way" => $data['del_decimal_way'],
                        "operator_id" => $user_info["id"],
                        "operator_name" => $user_info["truename"],
                        "create_at" => time(),
                    ];
                    $db->createCommand()->insert("ps_water_formula", $new_arr)->execute();
                } else {//编辑
                    $edit_arr = ["name" => "固定电价", "price" => $data["price"], "type" => "1", "calc_rule" => $data['calc_rule'], "del_decimal_way" => $data['del_decimal_way']];
                    Yii::$app->db->createCommand()->update("ps_water_formula", $edit_arr, 'rule_type=2 and community_id=' . $data["community_id"])->execute();
                }
            } else {//安阶梯
                if (!$data['phase_list']) {
                    return $this->failed("阶梯数据不存在");
                }
                if (!is_array($data['phase_list']) || count($data['phase_list']) != 3) {
                    return $this->failed("阶梯数据格式错误");
                }
                foreach ($data['phase_list'] as $key=>$phase) {
                    if (!is_numeric($phase['ton']) || !is_numeric($phase['price'])) {
                        return $this->failed("阶梯数据类型错误");
                    }
                    if($key==2 && $phase['ton']!=0){
                        return $this->failed("阶梯数据类型错误");
                    }
                }
                if (empty($model)) {//新增
                    $new_arr = [
                        "community_id" => $data["community_id"],
                        "name" => "阶梯电价",
                        "type" => "2",
                        "rule_type" => 2,
                        "calc_rule" => $data['calc_rule'],
                        "del_decimal_way" => $data['del_decimal_way'],
                        "operator_id" => $user_info["id"],
                        "operator_name" => $user_info["truename"],
                        "create_at" => time(),
                    ];
                    $db->createCommand()->insert("ps_water_formula", $new_arr)->execute();
                } else {//编辑
                    $edit_arr = ["name" => "阶梯电价", "price" => "0", "type" => "2", "calc_rule" => $data['calc_rule'], "del_decimal_way" => $data['del_decimal_way']];
                    Yii::$app->db->createCommand()->update("ps_water_formula", $edit_arr, 'rule_type=2 and community_id=' . $data["community_id"])->execute();
                }
                $db->createCommand()->delete("ps_phase_formula", ['community_id' => $data['community_id'], 'rule_type' => '2'])->execute();
                foreach ($data['phase_list'] as $phase) {
                    $phaseData = [
                        'community_id' => $data['community_id'],
                        'rule_type' => 2,
                        'ton' => $phase['ton'],
                        'price' => $phase['price'],
                        'create_at' => time()
                    ];
                    $db->createCommand()->insert("ps_phase_formula", $phaseData)->execute();
                }
            }
            $transaction->commit();

            self::_logAdd($data['token'], "电费编辑");

            return $this->success();
        } catch (Exception $e) {
            $transaction->rollBack();
            return ["status" => false, "errorMsg" => "编辑失败"];
        }
    }

    //电费详情
    public function electricShow($data)
    {
        if (!$data['community_id']) {
            return $this->failed("小区id不能为空");
        }

        $param = [":community_id" => $data['community_id']];
        $sql = "select id,community_id,name,type,operator_id,operator_name,price,rule_type,calc_rule,del_decimal_way,create_at from ps_water_formula where community_id=:community_id and rule_type=2 ";
        $model = Yii::$app->db->createCommand($sql, $param)->queryOne();
        if (!empty($model)) {
            $model['phase_list'] = [];
            if ($model['type'] == 2) {//安阶梯
                $sql = "select ton,price from ps_phase_formula where community_id=:community_id  and rule_type=2";
                $phase_list = Yii::$app->db->createCommand($sql, $param)->queryAll();
                if ($phase_list) {
                    $model['phase_list'] = $phase_list;
                }
            }
            return $this->success($model);
        } else {
            return $this->failed("未找到计算公式");
        }
    }


    //================================================公摊3.6需求======================================================
    /*查看电费列表*/
    public function sharedElectricList($community_id)
    {
        $param = [":community_id" => $community_id];
        $sql = "select * from ps_water_formula where community_id=:community_id and rule_type=4";
        $model = Yii::$app->db->createCommand($sql, $param)->queryOne();
        $result["formula_desc"] = "";
        if (!empty($model)) {
            $result["type"] = $model["type"];
            $result["price"] = round($model["price"],2);
            $result["formula_desc"] = round($model["price"],2) . "*用电量";
        }
        return $result;
    }

    /*新增编辑电价*/
    public function sharedEditElectric($data, $user_info)
    {
        if (empty($data['price']) && $data['type'] == 1) {
            return $this->failed("价格不存在");
        }
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $param = [":community_id" => $data["community_id"]];
            $sql = "select * from ps_water_formula where community_id=:community_id  and rule_type=4";
            $model = $db->createCommand($sql, $param)->queryOne();
            if (empty($model)) {//新增
                $new_arr = [
                    "community_id" => $data["community_id"],
                    "name" => "固定电价",
                    "type" => "1",
                    "price" => $data["price"],
                    "rule_type" => 4,
                    "operator_id" => $user_info["id"],
                    "operator_name" => $user_info["truename"],
                    "create_at" => time(),
                ];
                $db->createCommand()->insert("ps_water_formula", $new_arr)->execute();
            } else {//编辑
                $edit_arr = ["name" => "固定电价", "price" => $data["price"], "type" => "1"];
                Yii::$app->db->createCommand()->update("ps_water_formula", $edit_arr, 'rule_type=4 and community_id=' . $data["community_id"])->execute();
            }
            $transaction->commit();

            self::_logAdd($data['token'], "电费编辑");

            return $this->success();
        } catch (Exception $e) {
            $transaction->rollBack();
            return ["status" => false, "errorMsg" => "编辑失败"];
        }
    }

    //电费详情
    public function sharedElectricShow($data)
    {
        if (!$data['community_id']) {
            return $this->failed("小区id不能为空");
        }

        $param = [":community_id" => $data['community_id']];
        $sql = "select id,community_id,name,operator_id,operator_name,price,create_at from ps_water_formula where community_id=:community_id and rule_type=4 ";
        $model = Yii::$app->db->createCommand($sql, $param)->queryOne();
        if (!empty($model)) {
            $model['price']=round($model["price"],2);
            return $this->success($model);
        } else {
            return $this->failed("未找到计算公式");
        }
    }

    /*查看电费列表*/
    public function sharedWaterList($community_id)
    {
        $param = [":community_id" => $community_id];
        $sql = "select * from ps_water_formula where community_id=:community_id and rule_type=3";
        $model = Yii::$app->db->createCommand($sql, $param)->queryOne();
        $result["formula_desc"] = "";
        if (!empty($model)) {
            $result["type"] = $model["type"];
            $result["price"] = round($model["price"],2);
            $result["formula_desc"] = round($model["price"],2) . "*用水量";
        }
        return $result;
    }

    /*新增编辑电价*/
    public function sharedWater($data, $user_info)
    {
        if (empty($data['price']) && $data['type'] == 1) {
            return $this->failed("价格不存在");
        }
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $param = [":community_id" => $data["community_id"]];
            $sql = "select * from ps_water_formula where community_id=:community_id  and rule_type=3";
            $model = $db->createCommand($sql, $param)->queryOne();
            if (empty($model)) {//新增
                $new_arr = [
                    "community_id" => $data["community_id"],
                    "name" => "固定水价",
                    "type" => "1",
                    "price" => $data["price"],
                    "rule_type" => 3,
                    "operator_id" => $user_info["id"],
                    "operator_name" => $user_info["truename"],
                    "create_at" => time(),
                ];
                $db->createCommand()->insert("ps_water_formula", $new_arr)->execute();
            } else {//编辑
                $edit_arr = ["name" => "固定水价", "price" => $data["price"], "type" => "1"];
                Yii::$app->db->createCommand()->update("ps_water_formula", $edit_arr, 'rule_type=3 and community_id=' . $data["community_id"])->execute();
            }
            $transaction->commit();

            self::_logAdd($data['token'], "水费编辑");

            return $this->success();
        } catch (Exception $e) {
            $transaction->rollBack();
            return ["status" => false, "errorMsg" => "编辑失败"];
        }
    }

    //水费详情
    public function sharedWaterShow($data)
    {
        if (!$data['community_id']) {
            return $this->failed("小区id不能为空");
        }

        $param = [":community_id" => $data['community_id']];
        $sql = "select id,community_id,name,operator_id,operator_name,price,create_at from ps_water_formula where community_id=:community_id and rule_type=3 ";
        $model = Yii::$app->db->createCommand($sql, $param)->queryOne();
        if (!empty($model)) {
            $model['price'] = round($model['price'],2);
            return $this->success($model);
        } else {
            return $this->failed("未找到计算公式");
        }
    }

    /*
    * 查看物业公司的计费公式
    * $data 查询条件
    * $page  当前页
    * $rows 显示列数
    * */
    public function propertyLists($data)
    {
        $params = [":community_id" => $data["community_id"]];
        $where = " community_id=:community_id";
        $sql = "select count(id) from ps_formula where " . $where;
        $count = Yii::$app->db->createCommand($sql, $params)->queryScalar();
        if ($count == 0) {
            $arr1 = ['totals' => 0, 'list' => []];
            return $arr1;
        }
        $sql = "select id,name,formula from ps_formula where " . $where . " order by id desc ";
        $models = Yii::$app->db->createCommand($sql, $params)->queryAll();
        return ["list" => $models, 'totals' => $count];
    }

     // 添加java日志
    public function _logAdd($token, $content)
    {
        $javaService = new JavaService();
        $javaParam = [
            'token' => $token,
            'moduleKey' => 'bill_module',
            'content' => $content,

        ];
        $javaService->logAdd($javaParam);
    }
}
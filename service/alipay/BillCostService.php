<?php
namespace service\alipay;

use app\models\PsBillCost;
use service\BaseService;
use Yii;
use service\rbac\OperateService;
use common\core\PsCommon;

class BillCostService extends BaseService
{
    //缴费项目列表
    public function getAll($params, $userinfo)
    {
        $requestArr['company_id'] = $userinfo['property_company_id'];
        $requestArr['name'] = !empty($params['name']) ? $params['name'] : '';              //缴费项目名称
        $requestArr['status'] = !empty($params['status']) ? $params['status'] : '';        //状态：1启用2禁用
        $page = (empty($params['page']) || $params['page'] < 1) ? 1 : $params['page'];
        $rows = !empty($params['rows']) ? $params['rows'] : 20;

        $db = Yii::$app->db;

        $where = "1=1 ";
        $params = [];
        if (!$requestArr['company_id']) {
            return $this->failed("物业公司不存在");
        }
        if ($requestArr['company_id']) {
            $where .= " AND ( company_id=:company_id or company_id=0) ";
            $params = array_merge($params, [':company_id' => $requestArr['company_id']]);
        }
        if ($requestArr['name']) {
            $where .= " AND name like :name";
            $params = array_merge($params, [':name' => '%' . $requestArr['name'] . '%']);
        }
        if ($requestArr['status']) {
            $where .= " AND status=:status";
            $params = array_merge($params, [':status' => $requestArr['status']]);
        }
        $total = $db->createCommand("select count(id) from ps_bill_cost where " . $where, $params)->queryScalar();
        if ($total == 0) {
            $data["totals"] = 0;
            $data["list"] = [];
            return $this->success($data);
        }
        $page = $page > ceil($total / $rows) ? ceil($total / $rows) : $page;
        $limit = ($page - 1) * $rows;
        $costList = $db->createCommand("select  *  from ps_bill_cost where " . $where . " order by  id desc limit $limit,$rows", $params)->queryAll();
        foreach ($costList as $key => $cost) {
            $arr[$key]['id'] = $cost['id'];
            $arr[$key]['company_id'] = $cost['company_id'];
            $arr[$key]['name'] = $cost['name'];
            $arr[$key]['describe'] = $cost['describe'];
            $arr[$key]['cost_type'] = $cost['cost_type'];
            $arr[$key]['status'] = $cost['status'];
            $arr[$key]['status_msg'] = $cost['status'] == 1 ? '启用' : '禁用';
            $arr[$key]['create_at'] = date('Y-m-d H:i:s', $cost['create_at']);
        }
        $data["totals"] = $total;
        $data['list'] = $arr;
        return $this->success($data);
    }

    //生成账单的缴费项目列表
    public function getAllByPay($userinfo)
    {
        $params=[];
        $requestArr['company_id'] = $userinfo['corpId'];
        $where = " 1=1 AND `status`=1 AND ( company_id=:company_id or company_id=0 )";
        $params = array_merge($params, [':company_id' => $requestArr['company_id']]);
        $result = Yii::$app->db->createCommand("select  id as `value`,id as `key`,`name` as label,cost_type  from ps_bill_cost where " . $where . " order by  cost_type asc,id desc ", $params)->queryAll();
        return $this->success($result);
    }
    //统计报表的缴费项目列表
    public function getAllByReport()
    {
        $result = Yii::$app->db->createCommand("select  id as `value`,id as `key`,`name` as label,cost_type  from ps_bill_cost where 1=1 AND `status`=1 AND  company_id=0  order by  cost_type asc,id desc ")->queryAll();
        return $this->success($result);
    }

    //新增缴费项
    public function addCost($params, $userinfo)
    {
        $params['company_id'] = $userinfo['property_company_id'];
        $cost = new PsBillCost();
        $cost->scenario = 'add';  # 设置数据验证场景为 新增
        $cost->load($params, '');   # 加载数据
        if ($cost->validate()) {  # 验证数据
            //查看缴费项名称是否重复,不能放model这，因为还需要根据物业公司来过滤
            $con=['or', ['company_id' => $params['company_id']], ['company_id' => 0]];
            $costInfo = PsBillCost::find()
                ->where($con)
                ->andFilterWhere([
                    'name'=>$params['name']
                ])->one();
            if ($costInfo) {
                return $this->failed('缴费项目不能重复');
            }
            if ($cost->save()) {  # 保存新增数据
                $content = "计费项目名称:" . $cost->name . ',';
                $content .= "计费项目描述:" . $cost->describe . ',';
                $content .= "状态:" . ($cost->status == 1 ? "启用" : "禁用") . ',';
                $operate = [
                    "community_id" => $params['community_id'],
                    "operate_menu" => "计费项目",
                    "operate_type" => "新增计费项目",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
            }
            return $this->success();
        }
        return $this->failed($cost->getErrors());
    }

    //编辑缴费项
    public function editCost($params, $userinfo)
    {
        if (!empty($params['id'])) {
            $cost = PsBillCost::findOne($params['id']);
            if (!$cost) {
                return '数据不存在';
            }
            //查看缴费项名称是否重复,不能放model这，因为还需要根据物业公司来过滤
            $con=['or', ['company_id' => $cost['company_id']], ['company_id' => 0]];
            $costInfo = PsBillCost::find()
                ->where($con)
                ->andFilterWhere([
                    'name'=>$params['name']
                ])->one();
            if ($costInfo && $costInfo->id!=$cost->id) {
                return $this->failed('缴费项目不能重复');
            }
            $cost->scenario = 'edit';  # 设置数据验证场景为 编辑
            $cost->load($params, '');   # 加载数据
            if ($cost->validate()) {  # 验证数据
                if ($cost->save()) {  # 保存新增数据
                    $content = "计费项目名称:" . $cost->name . ',';
                    $content .= "计费项目描述:" . $cost->describe . ',';
                    $content .= "状态:" . ($cost->status == 1 ? "启用" : "禁用") . ',';
                    $operate = [
                        "community_id" => $params['community_id'],
                        "operate_menu" => "计费项目管理",
                        "operate_type" => "编辑计费项目",
                        "operate_content" => $content,
                    ];
                    OperateService::addComm($userinfo, $operate);
                }
                return $this->success();
            }
            return $this->failed($cost->getErrors());
        }
        return $this->failed("收费项目id不能为空");
    }

    //编辑缴费项状态
    public function editCostStatus($params, $userinfo)
    {
        if (!empty($params['id'])) {
            if (!$params['status']) {
                return $this->failed("状态不存在");
            }
            $cost = PsBillCost::findOne($params['id']);
            if (!$cost) {
                return $this->failed('数据不存在');
            }
            $cost->status = $params['status'];
            if ($cost->save()) {  # 保存新增数据
                $content = "计费项目名称:" . $cost->name . ',';
                $content .= "计费项目描述:" . $cost->describe . ',';
                $content .= "状态:" . ($cost->status == 1 ? "启用" : "禁用") . ',';
                $operate = [
                    "community_id" => $params['community_id'],
                    "operate_menu" => "计费项目管理",
                    "operate_type" => "编辑计费项目",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
            } else {
                return $this->failed($cost->getErrors());
            }
            return $this->success();
        }
        return $this->failed('收费项目id不能为空');
    }

    //缴费项目详情
    public function getCostInfo($params)
    {
        if (!empty($params['id'])) {
            $result =  PsBillCost::find()->where(['id' => $params['id']])->asArray()->one();
            if($result){
                return $this->success($result);
            }else{
                return $this->failed('收费项目不存在');
            }
        }
        return $this->failed('收费项目id不能为空');
    }

    //缴费项目名称
    public function getCostName($id)
    {
        if (!empty($id)) {
            $result =  PsBillCost::find()->where(['id' => $id])->asArray()->one();
            if($result){
                return $this->success($result);
            }else{
                return $this->failed('收费项目不存在');
            }
        }
        return $this->failed('收费项目id不能为空');
    }

    //缴费项目详情根据条件来获取
    public function getCostInfoByData($params)
    {
        $requestArr['company_id'] = PsCommon::get($params, 'company_id');                   //物业公司
        $requestArr['name'] = !empty($params['name']) ? $params['name'] : '';              //缴费项目名称
        $requestArr['cost_type'] = !empty($params['cost_type']) ? $params['cost_type'] : '';              //缴费项目类型
        $requestArr['status'] = !empty($params['status']) ? $params['status'] : '1';        //状态：1启用2禁用
        $db = Yii::$app->db;
        $where = "1=1 ";
        $params = [];
        if ($requestArr['company_id']) {
            $where .= " AND ( company_id=:company_id or company_id=0)";
            $params = array_merge($params, [':company_id' => $requestArr['company_id']]);
        }
        if ($requestArr['name']) {
            $where .= " AND name like :name";
            $params = array_merge($params, [':name' => '%' . $requestArr['name'] . '%']);
        }
        if ($requestArr['status']) {
            $where .= " AND status=:status";
            $params = array_merge($params, [':status' => $requestArr['status']]);
        }
        if ($requestArr['cost_type']) {
            $where .= " AND cost_type=:cost_type";
            $params = array_merge($params, [':cost_type' => $requestArr['cost_type']]);
        }
        return $db->createCommand("select  *  from ps_bill_cost where " . $where, $params)->queryAll();
    }

    //删除项目
    public function delCost($params)
    {
        if (!empty($params['id'])) {
            $result =  PsBillCost::find()->where(['id' => $params['id']])->asArray()->one();
            if($result){
                PsBillCost::deleteAll(['id' => $params['id']]);
                return $this->success();
            }else{
                return $this->failed('收费项目不存在');
            }
        }
        return $this->failed('收费项目id不能为空');
    }

    /**
     * 根据ID获取缴费项目
     * @param $id
     * @param null $companyId
     */
    public function getById($id)
    {
        return PsBillCost::find()->where(['id' => $id])->asArray()->one();
    }
    /**
     * 根据ID（物业公司ID）获取缴费项目
     * @param $id
     * @param null $companyId
     */
    public function getCostByCompanyId($params)
    {
        $requestArr['company_id'] = PsCommon::get($params, 'company_id');                   //物业公司
        $requestArr['name'] = !empty($params['name']) ? $params['name'] : '';              //缴费项目名称
        $requestArr['cost_type'] = !empty($params['cost_type']) ? $params['cost_type'] : '';              //缴费项目类型
        $requestArr['status'] = !empty($params['status']) ? $params['status'] : '1';        //状态：1启用2禁用
        $db = Yii::$app->db;
        $where = "1=1 ";
        $params = [];
        if ($requestArr['company_id']) {
            $where .= " AND ( company_id=:company_id or company_id=0)";
            $params = array_merge($params, [':company_id' => $requestArr['company_id']]);
        }
        if ($requestArr['name']) {
            $where .= " AND name like :name";
            $params = array_merge($params, [':name' => '%' . $requestArr['name'] . '%']);
        }
        if ($requestArr['status']) {
            $where .= " AND status=:status";
            $params = array_merge($params, [':status' => $requestArr['status']]);
        }
        if ($requestArr['cost_type']) {
            $where .= " AND cost_type=:cost_type";
            $params = array_merge($params, [':cost_type' => $requestArr['cost_type']]);
        }
        return $db->createCommand("select  *  from ps_bill_cost where " . $where, $params)->queryOne();
    }
}
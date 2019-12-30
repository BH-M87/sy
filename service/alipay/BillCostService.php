<?php
namespace service\alipay;

use Yii;

use common\core\PsCommon;

use service\BaseService;
use service\rbac\OperateService;
use service\property_basic\JavaService;

use app\models\PsBillCost;

class BillCostService extends BaseService
{
    // 缴费项目列表
    public function getAll($params, $userinfo)
    {
        $requestArr['company_id'] = $userinfo['corpId'];
        $requestArr['name'] = !empty($params['name']) ? $params['name'] : '';              //缴费项目名称
        $requestArr['status'] = !empty($params['status']) ? $params['status'] : '';        //状态：1启用2禁用
        $page = (empty($params['page']) || $params['page'] < 1) ? 1 : $params['page'];
        $rows = !empty($params['rows']) ? $params['rows'] : 20;

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

        $total = Yii::$app->db->createCommand("select count(id) from ps_bill_cost where " . $where, $params)->queryScalar();
        if ($total == 0) {
            $data["totals"] = 0;
            $data["list"] = [];
            return $this->success($data);
        }

        $page = $page > ceil($total / $rows) ? ceil($total / $rows) : $page;
        $limit = ($page - 1) * $rows;
        $costList = Yii::$app->db->createCommand("SELECT  *  from ps_bill_cost where " . $where . " order by  id desc limit $limit,$rows", $params)->queryAll();

        foreach ($costList as $key => &$val) {
            $val['status_msg'] = $val['status'] == 1 ? '启用' : '禁用';
            $val['create_at'] = date('Y-m-d H:i:s', $val['create_at']);
        }

        return $this->success(['list' => $costList, 'totals' => $total]);
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

    // 新增缴费项
    public function addCost($p, $userinfo)
    {
        $p['company_id'] = $userinfo['corpId'];

        $m = new PsBillCost();
        $m->scenario = 'add';  # 设置数据验证场景为 新增
        $m->load($p, '');   # 加载数据
        
        if ($m->validate()) {  # 验证数据
            $cost = PsBillCost::find()
                ->where(['or', ['company_id' => $p['company_id']], ['company_id' => 0]])
                ->andFilterWhere(['name' => $p['name']])->one();
            
            if ($cost) { // 查看缴费项名称是否重复,不能放model这，因为还需要根据物业公司来过滤
                return $this->failed('缴费项目不能重复');
            }

            if ($m->save()) {  # 保存新增数据
                $content = "计费项目名称:" . $m->name . ',';
                $content .= "计费项目描述:" . $m->describe . ',';
                $content .= "状态:" . ($m->status == 1 ? "启用" : "禁用") . ',';

                self::_logAdd($p['token'], "新增计费项目，" . $content);
            }

            return $this->success();
        }

        return $this->failed(PsCommon::getModelError($m));
    }

    // 编辑缴费项
    public function editCost($p, $userinfo)
    {
        if (!empty($p['id'])) {
            $cost = PsBillCost::findOne($p['id']);
            if (!$cost) {
                return '数据不存在';
            }
            // 查看缴费项名称是否重复,不能放model这，因为还需要根据物业公司来过滤
            $costInfo = PsBillCost::find()
                ->where(['or', ['company_id' => $cost['company_id']], ['company_id' => 0]])
                ->andFilterWhere(['name' => $p['name']])->one();
            if ($costInfo && $costInfo->id != $cost->id) {
                return $this->failed('缴费项目不能重复');
            }

            $cost->scenario = 'edit';  # 设置数据验证场景为 编辑
            $cost->load($p, '');   # 加载数据
            if ($cost->validate()) {  # 验证数据
                if ($cost->save()) {  # 保存新增数据
                    $content = "计费项目名称:" . $cost->name . ',';
                    $content .= "计费项目描述:" . $cost->describe . ',';
                    $content .= "状态:" . ($cost->status == 1 ? "启用" : "禁用") . ',';

                    self::_logAdd($p['token'], "编辑计费项目" . $content);
                }
                return $this->success();
            }
            return $this->failed($cost->getErrors());
        }
        return $this->failed("收费项目id不能为空");
    }

    // 编辑缴费项状态
    public function editCostStatus($p, $userinfo)
    {
        if (!empty($p['id'])) {
            $cost = PsBillCost::findOne($p['id']);
            if (!$cost) {
                return $this->failed('数据不存在');
            }

            if ($cost->status == 1) {
                $cost->status = 2;
            } else {
                $cost->status = 1;
            }
            if ($cost->save()) {  # 保存新增数据
                $content = "计费项目名称:" . $cost->name . ',';
                $content .= "计费项目描述:" . $cost->describe . ',';
                $content .= "状态:" . ($cost->status == 1 ? "启用" : "禁用") . ',';

                self::_logAdd($p['token'], "编辑计费项目" . $content);
            } else {
                return $this->failed($cost->getErrors());
            }
            return $this->success();
        }
        return $this->failed('收费项目id不能为空');
    }

    // 缴费项目详情
    public function getCostInfo($p)
    {
        if (!empty($p['id'])) {
            $r =  PsBillCost::find()->where(['id' => $p['id']])->asArray()->one();
            if ($r) {
                return $this->success($r);
            } else {
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
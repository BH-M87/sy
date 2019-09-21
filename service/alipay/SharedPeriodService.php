<?php
/**
 * Created by PhpStorm.
 * User: chenkelang
 * Date: 2018/3/15
 * Time: 15:57
 */
namespace service\alipay;

use common\core\F;
use common\core\PsCommon;
use app\models\PsShared;
use app\models\PsSharedBill;
use app\models\PsSharedLiftRules;
use app\models\PsReceiptFrom;
use app\models\PsSharedPeriods;
use app\models\PsSharedRecords;
use app\models\PsWaterFormula;
use service\BaseService;
use service\common\CsvService;
use service\common\ExcelService;
use service\manage\CommunityService;
use service\rbac\OperateService;
use Yii;
use yii\db\Query;
use yii\helpers\StringHelper;

class SharedPeriodService extends BaseService
{
    //===============================================账期相关Start======================================================
    public $shared_type = [
        1 => ['id' => 1, 'name' => '电梯用电'],
        2 => ['id' => 2, 'name' => '楼道用电'],
        3 => ['id' => 3, 'name' => '整体用水用电']
    ];
    public $status = [
        1 => ['id' => 1, 'name' => '未发布账单'],
        2 => ['id' => 2, 'name' => '已发布账单']
    ];

    //公摊账期列表
    public function getList($params, $userinfo)
    {
        $requestArr['community_id'] = !empty($params['community_id']) ? $params['community_id'] : '';              //账期名称
        $page = (empty($params['page']) || $params['page'] < 1) ? 1 : $params['page'];
        $rows = !empty($params['rows']) ? $params['rows'] : 20;
        $db = Yii::$app->db;
        if (!$requestArr['community_id']) {
            return $this->failed("小区id不能为空");
        }
        $where = " community_id=:community_id ";
        $params = [':community_id' => $requestArr['community_id']];
        $total = $db->createCommand("select count(id) from ps_shared_periods where " . $where, $params)->queryScalar();
        if ($total == 0) {
            $data["totals"] = 0;
            $data["list"] = [];
            return $this->success($data);
        }
        $page = $page > ceil($total / $rows) ? ceil($total / $rows) : $page;
        $limit = ($page - 1) * $rows;
        $sharedList = $db->createCommand("select  *  from ps_shared_periods where " . $where . " order by  id desc limit $limit,$rows", $params)->queryAll();
        foreach ($sharedList as $key => $shared) {
            $arr[$key]['id'] = $shared['id'];
            $arr[$key]['community_id'] = $shared['community_id'];
            $arr[$key]['period_start'] = date("Y-m-d", $shared['period_start']);
            $arr[$key]['period_end'] = date("Y-m-d", $shared['period_end']);
            $arr[$key]['status'] = $shared['status']!=3?"1":"3";
            $arr[$key]['status_msg'] = $shared['status']!=3?"未发布账单":"已发布账单";
            $arr[$key]['create_at'] = date('Y-m-d H:i:s', $shared['create_at']);
        }
        $data["totals"] = $total;
        $data['list'] = $arr;
        return $this->success($data);
    }

    //新增账期
    public function add($params,$userinfo='')
    {
        $shared = new PsSharedPeriods();
        $shared->scenario = 'add';  # 设置数据验证场景为 新增
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            //查看是否重复
            $total = $this->verifyPeriod($params["community_id"], $params["period_start"], $params["period_end"]);
            if ($total) {
                return $this->failed("账期已存在");
            }
            if ($shared->save()) {  # 保存新增数据
                $operate = [
                    "community_id" =>$params['community_id'],
                    "operate_menu" => "抄表管理",
                    "operate_type" => "新增账期",
                    "operate_content" => "账期开始时间：".date('Y-m-d',$params["period_start"]).'-账期结束时间'. date("Y-m-d",$params["period_end"])
                ];
                OperateService::addComm($userinfo, $operate);
                return $this->success();
            }
        }
        return $this->failed($this->getError($shared));
    }

    //编辑账期
    public function edit($params,$userinfo)
    {
        if (empty($params['id'])) {
            return $this->failed("账期id不能为空");
        }
        $shared = PsSharedPeriods::findOne($params['id']);
        if (empty($shared)) {
            return $this->failed("账期不存在");
        }
        $shared->scenario = 'edit';  # 设置数据验证场景为 新增
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            //查看是否重复
            $total = $this->verifyPeriodEdit($params['id'], $params["community_id"], $params["period_start"], $params["period_end"]);
            if ($total) {
                return $this->failed("账期已存在");
            }
            if ($shared->save()) {  # 保存新增数据
                $operate = [
                    "community_id" =>$params['community_id'],
                    "operate_menu" => "抄表管理",
                    "operate_type" => "编辑账期",
                    "operate_content" => "账期开始时间：".date('Y-m-d',$params["period_start"]).'-账期结束时间'. date("Y-m-d",$params["period_end"])
                ];
                OperateService::addComm($userinfo, $operate);
                return $this->success();
            }
        }
        return $this->failed($this->getError($shared));
    }

    //账期详情
    public function getInfo($params)
    {
        if (!empty($params['id'])) {
            $result = PsSharedPeriods::find()->where(['id' => $params['id']])->asArray()->one();
            if ($result) {
                $result['period_start'] = date("Y-m-d", $result['period_start']);
                $result['period_end'] = date("Y-m-d", $result['period_end']);
                return $this->success($result);
            } else {
                return $this->failed('账期不存在');
            }
        }
        return $this->failed('账期id不能为空');
    }

    //删除账期
    public function del($params,$userinfo)
    {
        if (empty($params['id'])) {
            return $this->failed("账期id不能为空");
        }
        $shared = PsSharedPeriods::findOne($params['id']);
        if (empty($shared)) {
            return $this->failed("账期不存在");
        }
        if ($shared->status != 3) {
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "抄表管理",
                "operate_type" => "删除账期",
                "operate_content" => "账期开始时间：".date('Y-m-d',$shared->period_start).'-账期结束时间'. date("Y-m-d",$shared->period_end)
            ];
            OperateService::addComm($userinfo, $operate);
            PsSharedPeriods::deleteAll(['id' => $params['id']]);            //删除账期
            PsSharedRecords::deleteAll(['period_id' => $params['id']]);     //删除账期抄表
            PsSharedBill::deleteAll(['period_id' => $params['id']]);        //删除账期账单
            return $this->success();
        } else {
            return $this->failed('该账期已发布账单');

        }
    }

    //判断当前账期内是否存在
    public function verifyPeriod($community_id, $acctPeriodStart, $acctPeriodEnd)
    {
        $query = new Query();
        $totals = $query->from("ps_shared_periods ")
            ->where(["community_id" => $community_id])
            ->andWhere(["<=", "period_start", $acctPeriodEnd])
            ->andWhere([">=", "period_end", $acctPeriodStart])
            ->count();
        return $totals > 0 ? true : false;
    }

    //判断当前账期内是否存在，编辑才使用
    public function verifyPeriodEdit($id, $community_id, $acctPeriodStart, $acctPeriodEnd)
    {
        $query = new Query();
        $totals = $query->from("ps_shared_periods ")
            ->where(["community_id" => $community_id])
            ->andWhere(["!=", "id", $id])
            ->andWhere(["<=", "period_start", $acctPeriodStart])
            ->andWhere([">=", "period_end", $acctPeriodEnd])
            ->count();
        return $totals > 0 ? true : false;
    }
    //===============================================end账期相关======================================================

    //==================================================账期抄表玄关Start===============================================
    //抄表列表
    public function getRecordList($params)
    {
        $requestArr['period_id'] = !empty($params['period_id']) ? $params['period_id'] : '';              //账期id
        $page = (empty($params['page']) || $params['page'] < 1) ? 1 : $params['page'];
        $rows = !empty($params['rows']) ? $params['rows'] : 20;
        $db = Yii::$app->db;
        $where = "1=1 ";
        $params = [];
        if (!$requestArr['period_id']) {
            return $this->failed("账期id不能为空");
        }
        if ($requestArr['period_id']) {
            $where .= " AND period_id=:period_id ";
            $params = array_merge($params, [':period_id' => $requestArr['period_id']]);
        }
        $total = $db->createCommand("select count(id) from ps_shared_records where " . $where, $params)->queryScalar();
        if ($total == 0) {
            $data["totals"] = 0;
            $data["list"] = [];
            return $this->success($data);
        }
        $page = $page > ceil($total / $rows) ? ceil($total / $rows) : $page;
        $limit = ($page - 1) * $rows;
        $sharedList = $db->createCommand("select  *  from ps_shared_records where " . $where . " order by  id desc limit $limit,$rows", $params)->queryAll();
        foreach ($sharedList as $key => $shared) {
            $arr[$key]['id'] = $shared['id'];
            $arr[$key]['community_id'] = $shared['community_id'];
            $arr[$key]['period_id'] = $shared['period_id'];
            $arr[$key]['shared_type_msg'] = PsCommon::$sharedType[$shared['shared_type']];
            $arr[$key]['shared_name'] = SharedService::service()->getNameById($shared['shared_id']);
            $arr[$key]['latest_num'] = $shared['latest_num'];
            $arr[$key]['current_num'] = $shared['current_num'];
            $arr[$key]['amount'] = $shared['amount'];
            $arr[$key]['create_at'] = date('Y-m-d H:i:s', $shared['create_at']);
        }
        $data["totals"] = $total;
        $data['list'] = $arr;
        return $this->success($data);
    }

    //新增抄表数据
    public function addRecord($params,$user='')
    {
        $params['create_at'] = time();
        $shared = new PsSharedRecords();
        $shared->scenario = 'add';  # 设置数据验证场景为 新增
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            $sharedInfo = PsShared::findOne($params['shared_id']);
            if (empty($sharedInfo)) {
                return $this->failed("公摊项目不存在");
            }
            if ($sharedInfo['shared_type'] != $params['shared_type']) {
                return $this->failed("公摊项目类型与项目中的类型数据不一致");
            }
            if ($params['latest_num'] > $params['current_num']) {
                return $this->failed("上次读数不能大于本次读数");
            }
            //查看是否重复
            $total = $this->verifyRecord($params["period_id"], $params["shared_id"]);
            if ($total) {
                return $this->failed("该项目抄表数据已存在");
            }
            if ($shared->save()) {  # 保存新增数据
                $operate = [
                    "community_id" =>$shared->community_id,
                    "operate_menu" => "抄表管理",
                    "operate_type" => "新增抄表数据",
                    "operate_content" => "项目名称：".$sharedInfo->name.'-上次度数'.$params['latest_num'].'-本次度数'.$params['current_num'].'-金额'.$params['amount']
                ];
                OperateService::addComm($user, $operate);
                return $this->success();
            }
        }
        return $this->failed($this->getError($shared));
    }

    //编辑抄表数据
    public function editRecord($params,$user)
    {
        if (empty($params['id'])) {
            return $this->failed("抄表id不能为空");
        }
        $shared = PsSharedRecords::findOne($params['id']);
        if (empty($shared)) {
            return $this->failed("抄表不存在");
        }
        $shared->scenario = 'edit';  # 设置数据验证场景为 编辑
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            $sharedInfo = PsShared::findOne($params['shared_id']);
            if (empty($sharedInfo)) {
                return $this->failed("公摊项目不存在");
            }
            if ($sharedInfo['shared_type'] != $params['shared_type']) {
                return $this->failed("公摊项目类型与项目中的类型数据不一致");
            }
            if ($params['latest_num'] > $params['current_num']) {
                return $this->failed("上次读数不能大于本次读数");
            }
            //查看是否重复
            $total = $this->verifyRecordEdit($params["id"], $params["period_id"], $params["shared_id"]);
            if ($total) {
                return $this->failed("该项目抄表数据已存在");
            }
            if ($shared->save()) {  # 保存新增数据

                $operate = [
                    "community_id" =>$shared->community_id,
                    "operate_menu" => "抄表管理",
                    "operate_type" => "编辑抄表数据",
                    "operate_content" => "项目名称：".$sharedInfo->name.'-上次度数'.$params['latest_num'].'-本次度数'.$params['current_num'].'-金额'.$params['amount']
                ];
                OperateService::addComm($user, $operate);

                return $this->success();
            }
        }
        return $this->failed($this->getError($shared));
    }

    //验证抄表数据是否已存在
    public function verifyRecord($period_id, $shared_id)
    {
        $query = new Query();
        $totals = $query->from("ps_shared_records ")->where(["period_id" => $period_id, "shared_id" => $shared_id])->count();
        return $totals > 0 ? true : false;
    }

    //验证抄表数据是否已存在
    public function verifyRecordEdit($id, $period_id, $shared_id)
    {
        $query = new Query();
        $totals = $query->from("ps_shared_records ")
            ->where(["period_id" => $period_id, "shared_id" => $shared_id])
            ->andWhere(["!=", "id", $id])
            ->count();
        return $totals > 0 ? true : false;
    }

    //验证抄表数据是否已存在
    public function verifyRecordNumber($shared_id)
    {
        $query = new Query();
        $totals = $query->from("ps_shared_records ")->where(["shared_id" => $shared_id])->count();
        return $totals > 0 ? true : false;
    }

    //获取上次读数
    public function getRecordNumber($params)
    {
        if (empty($params['shared_id'])) {
            return $this->failed("公摊项目id不能为空");
        }
        $shared = PsShared::findOne($params['shared_id']);
        if (empty($shared)) {
            return $this->failed("公摊项目不存在");
        }
        $shared_id = $params['shared_id'];
        $total = $this->verifyRecordNumber($shared_id);
        if ($total) {//已存在则从账期抄表中获取上次读数
            $shared = PsSharedRecords::find()->where(['shared_id' => $shared_id])->orderBy('current_num desc')->asArray()->one();
            $latest_num = $shared['current_num'];
        } else {
            $resut = SharedService::service()->getStartNumById($shared_id);
            if (!empty($resut)) {
                $latest_num = $resut;
            } else {
                return $this->failed("该项目抄表数据已存在");
            }
        }
        return $this->success(['latest_num' => $latest_num]);
    }

    //获取对应金额
    public function getRecordMoney($params)
    {
        $shared = new PsSharedRecords();
        $shared->scenario = 'get-money';  # 设置数据验证场景为 获取对应金额
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            $sharedInfo = PsShared::findOne($params['shared_id']);
            if (empty($sharedInfo)) {
                return $this->failed("公摊项目不存在");
            }
            if ($sharedInfo->shared_type != $params['shared_type']) {
                return $this->failed("公摊项目类型与项目中的类型数据不一致");
            }
            if ($params['latest_num'] >= $params['current_num']) {
                return $this->failed("上次读数不能大于或等于本次读数");
            }
            $rule_type = 4;       //默认获取公摊电费
            if ($sharedInfo->shared_type == 3 && $sharedInfo->panel_type == 1) {//公摊类型为整体用水用电并且对于的是水表，则获取公摊水费公式
                $rule_type = 3;       //获取公摊水费
            }
            $formula = PsWaterFormula::find()->where(['community_id' => $sharedInfo->community_id, 'rule_type' => $rule_type])->asArray()->one();
            if (empty($formula)) {
                return $this->failed("该小区未设置公摊水电费的计算公式");
            }
            //获取金额
            $ton = $params['current_num'] - $params['latest_num'];
            $amount = round($formula['price'] * $ton, 2);
            return $this->success(['amount' => $amount]);
        }
        return $this->failed($this->getError($shared));
    }

    //账期抄表数据详情

    public function getRecordInfo($params)
    {
        if (!empty($params['id'])) {
            $result = PsSharedRecords::find()->where(['id' => $params['id']])->asArray()->one();
            if ($result) {
                return $this->success($result);
            } else {
                return $this->failed('账期抄表不存在');
            }
        }
        return $this->failed('账期抄表id不能为空');
    }

    //删除账期抄表数据
    public function delRecord($params,$user)
    {
        if (empty($params['id'])) {
            return $this->failed("抄表id不能为空");
        }
        $shared = PsSharedRecords::findOne($params['id']);
        if (empty($shared)) {
            return $this->failed("账期抄表数据不存在");
        }
        $sharedInfo = PsShared::findOne($shared->shared_id);
        $operate = [
            "community_id" =>$shared->community_id,
            "operate_menu" => "抄表管理",
            "operate_type" => "删除抄表数据",
            "operate_content" => "项目名称：".$sharedInfo->name.'-上次度数'.$shared->latest_num.'-本次度数'.$shared->current_num.'-金额'.$shared->amount
        ];
        OperateService::addComm($user, $operate);

        PsSharedRecords::deleteAll(['id' => $params['id']]);
        return $this->success();
    }

    //保存批量导入数据
    public function saveFromImport($params)
    {
        //================================================数据验证操作==================================================
        if ($params && !empty($params)) {
            //验证小区
            $community_info = CommunityService::service()->getCommunityInfo($params['community_id']);
            if (empty($community_info)) {
                return $this->failed("未找到小区信息");
            }
            //验证任务
            $task = ReceiptService::getReceiptTask($params["task_id"]);
            if (empty($task)) {
                return $this->failed("未找到上传任务");
            }

            $typefile = F::excelPath('receipt') . $task['next_name'];
            $PHPExcel = \PHPExcel_IOFactory::load($typefile);
            $currentSheet = $PHPExcel->getActiveSheet();
            $sheetData = $currentSheet->toArray(null, false, false, true);
            if (empty($sheetData)) {
                return $this->failed("表格里面为空");
            }
            ReceiptService::addReceiptTask($params);
        } else {
            return $this->failed("未接受到有效数据");
        }


        $defeat_count = $success_count = $error_count = 0;
        $receiptArr = [];
        $batchInfo = [];
        for ($i = 2; $i <= $task["totals"]; $i++) {
            $val = $sheetData[$i];
            $shared_type = $this->validSharedImport($val["A"]);
            if (empty($shared_type)) {
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "公摊所属类型错误";
                continue;
            }
            if (empty($val["B"])) {
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "公摊项目不能为空";
                continue;
            }
            $shared_id = SharedService::service()->getIdByNameType($val["B"], $shared_type,$params['community_id']);
            if (empty($shared_id)) {
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "公摊项目不存在";
                continue;
            }
            //验证度数
            if ((empty($val["C"]) && $val["C"] != 0) || !is_numeric($val["C"])) {
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "上次读数格式错误";
                continue;
            }
            if (!is_numeric($val["D"])) {
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "本次读数格式错误";
                continue;
            }
            if ($val["C"] > $val["D"]) {
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "上次读数不能大于本次读数";
                continue;
            }
            if (!is_numeric($val["E"])) {
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "对应金额格式错误";
                continue;
            }
            $receiptArr["PsReceiptFrom"]["community_id"] = $params["community_id"];
            $receiptArr["PsReceiptFrom"]["period_id"] = $params["period_id"];
            $receiptArr["PsReceiptFrom"]["shared_type"] = $shared_type;
            $receiptArr["PsReceiptFrom"]["shared_id"] = $shared_id;
            $receiptArr["PsReceiptFrom"]["latest_num"] = $val["C"];
            $receiptArr["PsReceiptFrom"]["current_num"] = $val["D"];
            $receiptArr["PsReceiptFrom"]["amount"] = $val["E"];
            $receiptArr["PsReceiptFrom"]["create_at"] = time();
            /*校验上传数据是否合法*/
            $model = new PsReceiptFrom();
            $model->setScenario('import-record');
            $model->load($receiptArr);
            if (!$model->validate()) {
                $errorMsg = array_values($model->errors);
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = $errorMsg[0][0];
                continue;
            }

            /*验证数据库中是否已存在*/
            $shared_params = [
                ":shared_type" => $receiptArr["PsReceiptFrom"]["shared_type"],
                ":shared_id" => $receiptArr["PsReceiptFrom"]["shared_id"],
                "period_id" => $params["period_id"]
            ];
            $shared = Yii::$app->db->createCommand("select id from ps_shared_records where  period_id=:period_id and shared_type=:shared_type and shared_id=:shared_id ", $shared_params)->queryOne();
            if (!empty($shared)) {
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "该抄表记录已存在";
                continue;
            }
            array_push($batchInfo, $receiptArr["PsReceiptFrom"]);
            $defeat_count++;
            $success_count++;
        }
        if ($success_count > 0) {
            //批量存入房屋数据
            Yii::$app->db->createCommand()->batchInsert('ps_shared_records',
                [
                    'community_id',
                    'period_id',
                    'shared_type',
                    'shared_id',
                    'latest_num',
                    'current_num',
                    'amount',
                    'create_at',
                ], $batchInfo
            )->execute();
        }
        $error_url = "";
        if ($error_count > 0) {
            $error_url = $this->savePayError($errorCsv);
        }
        $result = [
            'totals' => $success_count + $error_count,
            'success' => $success_count,
            'error_url' => $error_url,
        ];
        return $this->success($result);
    }

    // 添加错误至excel
    public function savePayError($data)
    {
        $config = [
            'A' => ['title' => '公摊项目类型', 'width' => 16, 'data_type' => 'str', 'field' => 'A'],
            'B' => ['title' => '公摊项目', 'width' => 18, 'data_type' => 'str', 'field' => 'B'],
            'C' => ['title' => '上次读数', 'width' => 15, 'data_type' => 'str', 'field' => 'C'],
            'D' => ['title' => '本次读数', 'width' => 15, 'data_type' => 'str', 'field' => 'D'],
            'E' => ['title' => '对应金额', 'width' => 15, 'data_type' => 'str', 'field' => 'E'],
            'F' => ['title' => '错误原因', 'width' => 35, 'data_type' => 'str', 'field' => 'error'],
        ];
        $filename = CsvService::service()->saveTempFile(1, array_values($config), $data, '', 'error');
        $filePath = F::originalFile().'error/'.$filename;
        $fileRe = F::uploadFileToOss($filePath);
        $downUrl = $fileRe['filepath'];
        return $downUrl;
    }

    ///验证导入的公摊类型，公式类型，状态是否正确并获取对应id
    public function validSharedImport($name)
    {
        foreach ($this->shared_type as $data) {
            if ($name == $data['name']) {
                return $data['id'];
            }
        }
        return false;
    }
    //==================================================Ends账期抄表玄关================================================

    //生成账单
    public function createBill($params)
    {
        $shared = new PsSharedRecords();
        $shared->scenario = 'create-bill';  # 设置数据验证场景为 新增
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            $periodInfo = PsSharedPeriods::find()->where(['id' => $params['period_id']])->asArray()->one();
            if ($periodInfo['status'] != 1) {
                return $this->failed("该账期已有账单");
            }
            //查询所有的抄表记录
            $recordList = PsSharedRecords::find()->where(['period_id' => $params['period_id']])->asArray()->all();
            if (empty($recordList)) {
                return $this->failed("该账期抄表记录不存在");
            }
            //获取小区下电梯的分摊规则：1按楼层， 2按面积， 3按楼层&面积
            $rule_type = SharedService::service()->getRuleTypeById($params['community_id']);
            if (empty($rule_type)) {
                return $this->failed("请设置公摊项目电梯的分摊规则");
            }
            $defeat_count = $success_count = 0;
            //增加mysql事务
            $connection = Yii::$app->db;
            $transaction = $connection->beginTransaction();
            try {
                //删除账单表中已存在该账期id的数据
                PsSharedBill::deleteAll(['community_id' => $params['community_id'], 'period_id' => $params['period_id']]);
                $list = [];
                //循环所有的抄表记录
                foreach ($recordList as $record) {
                    //根据抄表记录中的公摊项目id获取房屋总面积与总楼段系数
                    $roomData = $this->getHouseAreaFloor($params['community_id'], $record['shared_id'], $record['shared_type']);
                    if (empty($roomData['floorTotal']) && empty($roomData['areaTotal'])) {
                        return $this->failed("该账期公摊项目房屋记录不存在");
                    }
                    //根据抄表记录中的公摊项目id获取所有的房屋
                    $roomAll = $this->getHouseAll($record['amount'], $roomData, $params['community_id'], $record['shared_id'], $record['shared_type'], $rule_type);
                    if(!$roomAll['code']){
                        return $this->failed($roomAll['msg']);
                    }
                    foreach ($roomAll['data'] as $room) {
                        $data = [];
                        switch ($record['shared_type']) {
                            case 1://电梯
                                $data['elevator_total'] = $record['amount'];           //电梯用电总金额
                                $data['elevator_shared'] = $room['money'];             //电梯应分摊金额
                                break;
                            case 2://楼道
                                $data['corridor_total'] = $record['amount'];           //本楼道用电总金额
                                $data['corridor_shared'] = $room['money'];             //本楼道分摊金额
                                break;
                            case 3://整体
                                //多个整体公摊项目的情况，需金额需要累加
                                $total_money = !empty($list[$room['id']]['water_electricity_total']) ? $list[$room['id']]['water_electricity_total'] : 0;
                                $detail_money = !empty($list[$room['id']]['water_electricity_shared']) ? $list[$room['id']]['water_electricity_shared'] : 0;
                                $data['water_electricity_total'] = $record['amount'] + $total_money;  //小区整体用水用电总金额
                                $data['water_electricity_shared'] = $room['money'] + $detail_money;    //小区整体用水用电分摊金额
                                break;
                        }
                        $data['shared_id'] = $record['shared_id'];
                        $data['room_id'] = $room['id'];
                        $old_data = !empty($list[$room['id']]) ? $list[$room['id']] : [];
                        $list[$room['id']] = array_merge($old_data, $data);
                    }
                }
                if (empty($list)) {
                    return $this->failed("该账期房屋公摊记录不存在");
                }
                $bills = [];
                foreach ($list as $li) {
                    $datas = [];
                    $datas['community_id'] = $params['community_id'];
                    $datas['period_id'] = $params['period_id'];
                    $datas['room_id'] = !empty($li['room_id']) ? $li['room_id'] : 0;                         //房屋id
                    $datas['elevator_total'] = !empty($li['elevator_total']) ? $li['elevator_total'] : 0;                         //电梯用电总金额
                    $datas['elevator_shared'] = !empty($li['elevator_shared']) ? $li['elevator_shared'] : 0;                         //电梯应分摊金额
                    $datas['corridor_total'] = !empty($li['corridor_total']) ? $li['corridor_total'] : 0;                         //本楼道用电总金额
                    $datas['corridor_shared'] = !empty($li['corridor_shared']) ? $li['corridor_shared'] : 0;                         //本楼道分摊金额
                    $datas['water_electricity_total'] = !empty($li['water_electricity_total']) ? $li['water_electricity_total'] : 0;                         //小区整体用水用电总金额
                    $datas['water_electricity_shared'] = !empty($li['water_electricity_shared']) ? $li['water_electricity_shared'] : 0;                         //小区整体用水用电分摊金额
                    //分摊总金额
                    $shared_total = $datas['elevator_shared'] + $datas['corridor_shared'] + $datas['water_electricity_shared'];
                    $datas['shared_total'] = !empty($shared_total) ? $shared_total : 0;                         //应分摊总金额
                    $datas['create_at'] = time();
                    $bills[] = $datas;
                    $defeat_count++;
                    $success_count++;
                }
                //批量存入 ps_shared_bill  表
                Yii::$app->db->createCommand()->batchInsert('ps_shared_bill',
                    [
                        "community_id",
                        "period_id",
                        "room_id",
                        "elevator_total",
                        "elevator_shared",
                        "corridor_total",
                        "corridor_shared",
                        "water_electricity_total",
                        "water_electricity_shared",
                        "shared_total",
                        "create_at",
                    ], $bills)->execute();
                //修改账期表的状态
                PsSharedPeriods::updateAll(['status' => 2], "id={$periodInfo['id']}");
                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();
                return $this->failed($e->getMessage());
            }
            $result = ["success_totals" => $success_count, "defeated_totals" => $defeat_count];
            return $this->success($result);
        }
        return $this->failed($this->getError($shared));
    }

    //根据公摊项目id获取房屋的总系数与总面积
    public function getHouseAreaFloor($community_id, $shared_id, $shared_type)
    {
        $query = new Query();
        switch ($shared_type) {
            case 1://电梯用电
                $model = $query->select(["sum(floor_coe) as floorTotal", "sum(charge_area) as areaTotal"])
                    ->from("ps_community_roominfo")
                    ->where(["lift_shared_id" => $shared_id, "community_id" => $community_id, 'is_elevator' => 1])
                    ->one();
                break;
            case 2://楼道用电
                $model = $query->select(["sum(floor_coe) as floorTotal", "sum(charge_area) as areaTotal"])
                    ->from("ps_community_roominfo")
                    ->where(["floor_shared_id" => $shared_id, "community_id" => $community_id])
                    ->one();
                break;
            case 3://整体用水用电
                $model = $query->select(["sum(floor_coe) as floorTotal", "sum(charge_area) as areaTotal"])
                    ->from("ps_community_roominfo")
                    ->where(["community_id" => $community_id])
                    ->one();
                break;
        }
        return !empty($model) ? $model : [];
    }

    //根据公摊项目id获取房屋数据并且将金额计算出来
    public function getHouseAll($amount, $roomData, $community_id, $shared_id, $shared_type, $rule_type)
    {
        $area_money = $amount / $roomData['areaTotal']; //按面积分摊金额
        switch ($shared_type) {
            case 1://电梯用电
                switch ($rule_type) {
                    case 1://按系数
                        if(empty($roomData['floorTotal']) || $roomData['floorTotal']<1 ){
                            return $this->failed('该账期下的房屋没有楼段系数');
                        }
                        $floor_money = $amount / $roomData['floorTotal']; //按系数分摊金额
                        $sql = "select id,ROUND(floor_coe*$floor_money,2) as money from ps_community_roominfo where lift_shared_id={$shared_id} and community_id={$community_id} and is_elevator=1";
                        break;
                    case 2://按面积
                        $sql = "select id,ROUND(charge_area*$area_money,2) as money from ps_community_roominfo where lift_shared_id={$shared_id} and community_id={$community_id} and is_elevator=1";
                        break;
                    case 3://按系数+面积
                        if(empty($roomData['floorTotal']) || $roomData['floorTotal']<1){
                            return $this->failed('该账期下的房屋没有楼段系数');
                        }
                        $floor_money = $amount / $roomData['floorTotal']; //按系数分摊金额
                        $sql = "select id,ROUND(((floor_coe*$floor_money+charge_area*$area_money)/2),2) as money from ps_community_roominfo where lift_shared_id={$shared_id} and community_id={$community_id} and is_elevator=1";
                        break;
                }
                break;
            case 2://楼道用电
                $sql = "select id,ROUND(charge_area*$area_money,2) as money from ps_community_roominfo where floor_shared_id={$shared_id} and community_id={$community_id}";
                break;
            case 3://整体用水用电
                $sql = "select id,ROUND(charge_area*$area_money,2) as money from ps_community_roominfo where community_id={$community_id}";
                break;
        }
        $model = Yii::$app->db->createCommand($sql)->queryAll();
        return $this->success($model);
    }

    //账单列表
    public function billList($params)
    {
        $shared = new PsSharedRecords();
        $shared->scenario = 'create-bill';  # 设置数据验证场景为 新增
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            $requestArr['period_id'] = !empty($params['period_id']) ? $params['period_id'] : '';         //账期id
            $requestArr['community_id'] = !empty($params['community_id']) ? $params['community_id'] : '';//小区id
            $requestArr['group'] = !empty($params['group']) ? $params['group'] : '';           //苑期区
            $requestArr['building'] = !empty($params['building']) ? $params['building'] : '';  //幢
            $requestArr['unit'] = !empty($params['unit']) ? $params['unit'] : '';              //单元
            $requestArr['room'] = !empty($params['room']) ? $params['room'] : '';              //室
            $requestArr['is_down'] = !empty($params['is_down']) ? $params['is_down'] : '1';       //1正常页面2下载
            $page = (empty($params['page']) || $params['page'] < 1) ? 1 : $params['page'];
            $rows = !empty($params['rows']) ? $params['rows'] : 20;
            $db = Yii::$app->db;
            $where = " room.community_id=bill.community_id and room.id = bill.room_id";
            $params = [];
            if (!empty($requestArr['period_id'])) {
                $where .= " AND bill.period_id=:period_id ";
                $params = array_merge($params, [':period_id' => $requestArr['period_id']]);
            }
            if (!empty($requestArr['community_id'])) {
                $where .= " AND bill.community_id=:community_id ";
                $params = array_merge($params, [':community_id' => $requestArr['community_id']]);
            }
            if (!empty($requestArr['group'])) {
                $where .= " AND room.group=:group ";
                $params = array_merge($params, [':group' => $requestArr['group']]);
            }
            if (!empty($requestArr['building'])) {
                $where .= " AND room.building=:building ";
                $params = array_merge($params, [':building' => $requestArr['building']]);
            }
            if (!empty($requestArr['unit'])) {
                $where .= " AND room.unit=:unit ";
                $params = array_merge($params, [':unit' => $requestArr['unit']]);
            }
            if (!empty($requestArr['room'])) {
                $where .= " AND room.room=:room ";
                $params = array_merge($params, [':room' => $requestArr['room']]);
            }
            $total = $db->createCommand("select count(distinct bill.id) from ps_community_roominfo as room,ps_shared_bill as bill where " . $where, $params)->queryScalar();
            if ($total == 0) {
                $data["totals"] = 0;
                $data["list"] = [];
                return $this->success($data);
            }
            $page = $page > ceil($total / $rows) ? ceil($total / $rows) : $page;
            $limit = ($page - 1) * $rows;
            //说明是下载
            if ($requestArr['is_down'] == 2) {
                $limit = 0;
                $rows = $total;
            }
            $billList = $db->createCommand("select room.address,room.floor_coe,room.floor_shared_id,room.lift_shared_id,room.is_elevator,bill.* from ps_community_roominfo as room,ps_shared_bill as bill where " . $where . " order by  bill.id desc limit $limit,$rows", $params)->queryAll();
            foreach ($billList as $key => $bill) {
                $arr[$key]['id'] = $bill['id'];
                $arr[$key]['community_id'] = $bill['community_id'];
                $arr[$key]['address'] = $bill['address'];
                $arr[$key]['floor_coe'] = $bill['floor_coe'];               //系数
                $arr[$key]['floor_shared'] = !empty($bill['floor_shared_id']) ? SharedService::service()->getNameById($bill['floor_shared_id']) : 'X';      //楼道号
                $arr[$key]['lift_shared'] = !empty($bill['lift_shared_id']) ? SharedService::service()->getNameById($bill['lift_shared_id']) : 'X';      //电梯编号
                $arr[$key]['is_elevator'] = $bill['is_elevator'];           //是否需要电梯
                $arr[$key]['elevator_total'] = !empty($bill['elevator_total']) ? $bill['elevator_total'] : "X";     //电梯用电总金额
                $arr[$key]['elevator_shared'] = !empty($bill['elevator_shared']) ? $bill['elevator_shared'] : "X";   //电梯应分摊金额
                $arr[$key]['corridor_total'] = $bill['corridor_total'];     //本楼道用电总金额
                $arr[$key]['corridor_shared'] = $bill['corridor_shared'];   //本楼道用电分摊金额
                $arr[$key]['water_electricity_total'] = $bill['water_electricity_total'];   //小区整体用水用电总金额
                $arr[$key]['water_electricity_shared'] = $bill['water_electricity_shared']; //小区整体用水用电分摊金额
                $arr[$key]['shared_total'] = $bill['shared_total'];         //分摊总金额
            }
            $data["totals"] = $total;
            $data['list'] = $arr;
            return $this->success($data);
        }
        return $this->failed($this->getError($shared));
    }

    //推送账单
    public function pushBill($params, $userinfo)
    {
        $shared = new PsSharedRecords();
        $shared->scenario = 'create-bill';  # 设置数据验证场景为 新增
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            $requestArr['period_id'] = !empty($params['period_id']) ? $params['period_id'] : '';         //账期id
            $requestArr['community_id'] = !empty($params['community_id']) ? $params['community_id'] : '';//小区id
            $db = Yii::$app->db;
            $where = " room.community_id=bill.community_id and room.id = bill.room_id AND bill.community_id=:community_id AND bill.period_id=:period_id ";
            $where_params = [':community_id' => $requestArr['community_id'], ':period_id' => $requestArr['period_id']];
            //需要发布的房屋账单
            $billList = $db->createCommand("select bill.id as shared_id,room.id as room_id,room.out_room_id,room.group,room.building,room.unit,room.room,room.charge_area,room.status as room_status,room.property_type,bill.shared_total as bill_entry_amount from ps_community_roominfo as room,ps_shared_bill as bill where " . $where . " order by  bill.id desc ", $where_params)->queryAll();
            //获取账期数据
            $periodInfo = PsSharedPeriods::find()->where(['id' => $params['period_id']])->asArray()->one();
            if ($periodInfo['status'] == 2) {
                //c查询需要发布的房屋账单，是否有存在总金额为0的数据
                $billCount = $db->createCommand("select count(bill.id) as bill_money from ps_community_roominfo as room,ps_shared_bill as bill where " . $where . " and bill.shared_total<=0 order by  bill.id desc ", $where_params)->queryScalar();
                if($billCount>0){
                    return $this->failed("该账期的房屋应分摊总金额不能为零");
                }
                $requestArr['period_start'] = $periodInfo['period_start'];
                $requestArr['period_end'] = $periodInfo['period_end'];
                $result = AlipayCostService::service()->addSharedBatchBill($billList, $requestArr, $userinfo);
                if ($result['code']) {
                    //修改账期表的状态
                    PsSharedPeriods::updateAll(['status' => 3], "id={$periodInfo['id']}");
                    return $this->success($result['data']);
                } else {
                    return $this->failed($result['msg']);
                }
            } else {
                $msg = $periodInfo['status'] == 1 ? '该账期未生成账单' : '该账期账单已发布';
                return $this->failed($msg);
            }
        }
        return $this->failed($this->getError($shared));
    }

    //取消账单
    public function cancelBill($params)
    {
        $shared = new PsSharedRecords();
        $shared->scenario = 'create-bill';  # 设置数据验证场景为 新增
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            //获取账期数据
            $periodInfo = PsSharedPeriods::find()->where(['id' => $params['period_id']])->asArray()->one();
            if ($periodInfo['status'] == 2) {
                //删除账单表中已存在该账期id的数据
                PsSharedBill::deleteAll(['community_id' => $params['community_id'], 'period_id' => $params['period_id']]);
                //修改账期表的状态
                PsSharedPeriods::updateAll(['status' => 1], "id={$params['period_id']}");
                return $this->success();
            } else {
                $msg = $periodInfo['status'] == 1 ? '该账期未生成账单' : '该账期账单已发布';
                return $this->failed($msg);
            }
        }
        return $this->failed($this->getError($shared));
    }

}

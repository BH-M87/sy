<?php
/**
 * Created by PhpStorm.
 * User: chenkelang
 * Date: 2018/3/15
 * Time: 15:57
 */
namespace service\alipay;

use app\models\PsWaterMeterFrom;
use common\core\F;
use common\core\PsCommon;
use app\models\PsElectricMeter;
use app\models\PsShared;
use app\models\PsSharedLiftRules;
use app\models\PsReceiptFrom;
use app\models\PsSharedRecords;
use app\models\PsWaterMeter;
use service\BaseService;
use service\common\CsvService;
use service\common\ExcelService;
use service\property_basic\CommonService;
use Yii;
use service\manage\CommunityService;
use service\rbac\OperateService;


class SharedService extends BaseService
{
    public $shared_type = [
        1 => ['id' => 1, 'name' => '电梯用电'],
        2 => ['id' => 2, 'name' => '楼道用电'],
        3 => ['id' => 3, 'name' => '整体用水用电']
    ];
    public $panel_type = [
        1 => ['id' => 1, 'name' => '水表'],
        2 => ['id' => 2, 'name' => '电表'],
    ];
    public $panel_status = [
        1 => ['id' => 1, 'name' => '正常'],
        2 => ['id' => 2, 'name' => '异常'],
    ];

    //公摊项目列表
    public function getList($params, $userinfo)
    {
        $shared = new PsShared();
        $shared->scenario = 'search';  # 设置数据验证场景为
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            $requestArr['community_id'] = !empty($params['community_id']) ? $params['community_id'] : '';              //项目名称
            $requestArr['name'] = !empty($params['name']) ? $params['name'] : '';              //项目名称
            $requestArr['panel_status'] = !empty($params['panel_status']) ? $params['panel_status'] : '';        //状态：1启用2禁用
            $requestArr['panel_type'] = !empty($params['panel_type']) ? $params['panel_type'] : '';        //仪表类型：1水表2电表
            $requestArr['shared_type'] = !empty($params['shared_type']) ? $params['shared_type'] : '';        //公摊类型
            $page = (empty($params['page']) || $params['page'] < 1) ? 1 : $params['page'];
            $rows = !empty($params['rows']) ? $params['rows'] : 20;
            $db = Yii::$app->db;
            $where = " community_id=:community_id  AND shared_type=:shared_type ";
            $params = [':community_id' => $requestArr['community_id'], ':shared_type' => $requestArr['shared_type']];
            if ($requestArr['name']) {
                $where .= " AND name like :name";
                $params = array_merge($params, [':name' => '%' . $requestArr['name'] . '%']);
            }
            if ($requestArr['panel_status']) {
                $where .= " AND panel_status=:panel_status";
                $params = array_merge($params, [':panel_status' => $requestArr['panel_status']]);
            }
            if ($requestArr['panel_type']) {
                $where .= " AND panel_type=:panel_type";
                $params = array_merge($params, [':panel_type' => $requestArr['panel_type']]);
            }
            $total = $db->createCommand("select count(id) from ps_shared where " . $where, $params)->queryScalar();
            if ($total == 0) {
                $data["totals"] = 0;
                $data["list"] = [];
                return $this->success($data);
            }
            $page = $page > ceil($total / $rows) ? ceil($total / $rows) : $page;
            $limit = ($page - 1) * $rows;
            $sharedList = $db->createCommand("select  *  from ps_shared where " . $where . " order by  id desc limit $limit,$rows", $params)->queryAll();
            foreach ($sharedList as $key => $shared) {
                $arr[$key]['id'] = $shared['id'];
                $arr[$key]['community_id'] = $shared['community_id'];
                $arr[$key]['name'] = $shared['name'];
                $arr[$key]['start_num'] = $shared['start_num'];
                $arr[$key]['remark'] = $shared['remark'];
                $arr[$key]['shared_type'] = PsCommon::$sharedType[$shared['shared_type']];
                $arr[$key]['panel_type'] = $shared['panel_type'] == 1 ? '水表' : '电表';
                $arr[$key]['panel_status'] = $shared['panel_status'];
                $arr[$key]['panel_status_msg'] = $shared['panel_status'] == 1 ? '正常' : '异常';
                $arr[$key]['create_at'] = date('Y-m-d H:i:s', $shared['create_at']);
            }
            $data["totals"] = $total;
            $data['list'] = $arr;
            $data['calc_msg'] = $this->getCalcMsg($requestArr);
            return $this->success($data);
        }
        return $this->failed($this->getError($shared));
    }

    //获取计算规则
    public function getCalcMsg($requestArr)
    {
        $msg = '';
        switch ($requestArr['shared_type']) {
            case 1://电梯用电
                $ralc = $this->getSharedLift($requestArr);
                if ($ralc['code']) {
                    //分摊规则类型：1按楼层， 2按面积， 3按楼层&面积
                    switch ($ralc['data']['rule_type']) {
                        case 1://按楼层
                            $msg = "每户按楼层分摊金额 = 电梯用电金额  *  楼层分摊系数 （每户房屋楼段系数／ 单元或者栋总的楼段系数）";
                            break;
                        case 2://按面积
                            $msg = "每户按面积分摊金额 = 电梯用电金额  *  房屋面积分摊系数 （每户房屋面积／ 单元或者栋总的房屋面积）";
                            break;
                        case 3://按楼层&面积
                            $msg = "每户楼层＆面积相结合应分摊金额 (电梯户)  =  按楼层分摊金额 +  按面积分摊金额 ／  2";
                            break;
                    }
                }
                break;
            case 2://楼道用电
                $msg = "每户楼道用电应分摊金额  （楼梯户）= 楼道用电总金额 ／ 楼道总面积  * 每户房屋面积";
                break;
            case 3:
                //整体用水用电
                $msg = "每户整体用水用电金额= 小区整体用水用电总金额 ／ 小区房屋总面积 * 每户房屋面积";
                break;
        }
        return $msg;
    }

    //根据小区id获取名下的公摊项目
    public function getSharedList($data)
    {
        $communityId = PsCommon::get($data, "community_id");  //小区id
        $where = " 1=1 AND `panel_status`=1 AND community_id=:community_id ";
        $params = [':community_id' => $communityId];
        $result = Yii::$app->db->createCommand("select  id ,`name`,shared_type  from ps_shared where " . $where . " order by id desc ", $params)->queryAll();
        if (!empty($result)) {
            $arrList = [];
            foreach ($result as $shared) {
                switch ($shared['shared_type']) {
                    case 1://电梯用电
                        $arr['id'] = $shared['id'];
                        $arr['name'] = $shared['name'];
                        $arrList['liftList'][] = $arr;
                        break;
                    case 2://楼道用电
                        $arr['id'] = $shared['id'];
                        $arr['name'] = $shared['name'];
                        $arrList['floorList'][] = $arr;
                        break;
                    case 3://整体用水用电
                        $arr['id'] = $shared['id'];
                        $arr['name'] = $shared['name'];
                        $arrList['sharedList'][] = $arr;
                        break;
                }
            }
            return $this->success($arrList);
        } else {
            return $this->failed('暂无公摊项目');
        }
    }

    //获取公摊项目模糊查找
    public function getSharedSearchList($data)
    {
        $shared_type = PsCommon::get($data, "shared_type");  //公摊类型
        $communityId = PsCommon::get($data, "community_id");  //小区id
        $name = PsCommon::get($data, "name");  //小区id
        $where = " 1=1 AND `panel_status`=1 AND community_id=:community_id and shared_type=:shared_type ";
        $params = [':community_id' => $communityId, ":shared_type" => $shared_type];
        if (!empty($name)) {
            $where .= " AND name like :name";
            $params = array_merge($params, [':name' => '%' . $name . '%']);
        }
        $result = Yii::$app->db->createCommand("select  id ,`name`  from ps_shared where " . $where . " order by id desc ", $params)->queryAll();
        $result = !empty($result) ? $result : [];
        return $this->success(['list' => $result]);
    }

    //新增项目
    public function add($params, $userinfo)
    {
        $shared = new PsShared();
        if ($params['shared_type'] != 3) {
            //只要不是整体用水用电表盘类型都是电表
            $params['panel_type'] = 2;
        }
        $shared->scenario = 'add';  # 设置数据验证场景为 新增
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            //查看名称是否重复
            $sharedInfo = PsShared::find()
                ->where(['shared_type' => $params['shared_type']])
                ->andWhere(['community_id' => $params['community_id']])
                ->andFilterWhere(['name' => $params['name']])
                ->one();
            if ($sharedInfo) {
                return $this->failed('项目不能重复');
            }
            if ($shared->save()) {  # 保存新增数据
                $content = "项目名称:" . $shared->name;
                $operate = [
                    "community_id" =>$params['community_id'],
                    "operate_menu" => "仪表信息",
                    "operate_type" => "新增项目",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
            }
            return $this->success();
        }
        return $this->failed($shared->getErrors());
    }

    //编辑缴费项
    public function edit($params, $userinfo)
    {
        $shared = new PsShared();
        $shared->scenario = 'edit';  # 设置数据验证场景为 编辑
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            $sharedInfo = PsShared::findOne($params['id']);
            if (empty($sharedInfo)) {
                return $this->failed('数据不存在');
            }
            //查看名称是否重复
            $sharedData = PsShared::find()
                ->where(['community_id' => $params['community_id'], 'shared_type' => $params['shared_type']])
                ->andWhere(['!=', 'id', $params['id']])
                ->andFilterWhere(['name' => $params['name']])
                ->one();
            if ($sharedData) {
                return $this->failed('项目不能重复');
            }
            if ($params['shared_type'] != 3) {
                //只要不是整体用水用电表盘类型都是电表
                $params['panel_type'] = 2;
            }
            $sharedInfo->load($params, '');   # 加载数据
            if ($sharedInfo->save()) {  # 保存新增数据
                $content = "项目名称:" . $shared->name . ',';
                $content .= "项目id:" . $shared->id ;
                $operate = [
                    "community_id" =>$params['community_id'],
                    "operate_menu" => "仪表信息",
                    "operate_type" => "编辑项目",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
            }
            return $this->success();
        }
        return $this->failed($this->getError($shared));
    }

    //项目详情
    public function getInfo($params)
    {
        $shared = new PsShared();
        $shared->scenario = 'show';  # 设置数据验证场景为 编辑
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            $result = PsShared::find()->where(['id' => $params['id']])->asArray()->one();
            if ($result) {
                return $this->success($result);
            } else {
                return $this->failed('项目不存在');
            }
        }
        return $this->failed($this->getError($shared));
    }

    //删除项目
    public function del($params,$userinfo='')
    {
        $shared = new PsShared();
        $shared->scenario = 'show';  # 设置数据验证场景为 编辑
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            $result = PsShared::find()->where(['id' => $params['id']])->asArray()->one();
            if (!empty($result)) {
                //验证是否有抄表数据，有的话不能删除
                $record = PsSharedRecords::find()->where(['shared_id' => $params['id']])->asArray()->one();
                if (!empty($record)) {
                    return $this->failed('该项目已有抄表数据');
                } else {
                    //保存日志
                    $log = [
                        "community_id" => $result['community_id'],
                        "operate_menu" => "仪表信息",
                        "operate_type" => "删除仪表",
                        "operate_content" => "项目名称：".$result['name']
                    ];
                    OperateService::addComm($userinfo, $log);
                    PsShared::deleteAll(['id' => $params['id']]);
                    return $this->success();
                }
            } else {
                return $this->failed('项目不存在');
            }
        }
        return $this->failed($this->getError($shared));
    }

    //配置公摊项目的电梯分摊规则
    public function setSharedLift($params, $userinfo)
    {
        $shared = new PsSharedLiftRules();
        $params['create_at'] = time();
        $shared->scenario = 'add';  # 设置数据验证场景为 新增
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            $sharedInfo = PsSharedLiftRules::find()->where(['community_id' => $params['community_id']])->one();
            if ($sharedInfo) {
                $sharedInfo::updateAll(['rule_type' => $params['rule_type']], ['community_id' => $params['community_id']]);
            } else {
                if ($shared->save()) {  # 保存新增数据
                    $content = "小区id:" . $shared->community_id;
                    $operate = [
                        "community_id" =>$params['community_id'],
                        "operate_menu" => "仪表信息",
                        "operate_type" => "配置电梯的分摊规则",
                        "operate_content" => $content,
                    ];
                    OperateService::addComm($userinfo, $operate);
                }
            }
            return $this->success();
        }
        return $this->failed($this->getError($shared));
    }

    //获取公摊项目的电梯分摊规则
    public function getSharedLift($params)
    {
        $shared = new PsSharedLiftRules();
        $shared->scenario = 'search';  # 设置数据验证场景为 新增
        $shared->load($params, '');   # 加载数据
        if ($shared->validate()) {  # 验证数据
            $sharedInfo = PsSharedLiftRules::find()->where(['community_id' => $params['community_id']])->asArray()->one();
            if ($sharedInfo) {
                return $this->success($sharedInfo);
            } else {
                return $this->failed('未配置分摊规则');
            }
        }
        return $this->failed($this->getError($shared));
    }

    //通过id获取公摊项目名称
    public function getNameById($id)
    {
        $shared = PsShared::findOne($id);
        if (!empty($shared)) {
            return $shared->name;
        }
    }

    //通过id获取公摊项目起始度数
    public function getStartNumById($id)
    {
        $shared = PsShared::findOne($id);
        if (!empty($shared)) {
            return $shared->start_num;
        }
    }

    //通过id获取公摊项目表盘类型
    public function getPanelTypeById($id)
    {
        $shared = PsShared::findOne($id);
        if (!empty($shared)) {
            return $shared->panel_type;
        }
    }

    //通过小区id获取公摊项目电梯的分摊规则
    public function getRuleTypeById($community_id)
    {
        $shared = PsSharedLiftRules::find()->where(['community_id' => $community_id])->asArray()->one();
        if (!empty($shared)) {
            return $shared['rule_type'];//分摊规则类型：1按楼层， 2按面积， 3按楼层&面积
        }
    }

    //通过name获取公摊项目id
    public function getIdByName($name, $community_id)
    {
        $shared = PsShared::find()->where(['name' => $name, 'community_id' => $community_id])->asArray()->one();
        if (!empty($shared)) {
            return $shared['id'];
        } else {
            return '0';
        }
    }

    //通过name获取公摊项目id
    public function getIdByNameType($name, $shared_type,$community_id)
    {
        $shared = PsShared::find()->where(['name' => $name, 'shared_type' => $shared_type,'community_id'=>$community_id])->asArray()->one();
        if (!empty($shared)) {
            return $shared['id'];
        } else {
            return '0';
        }
    }

    //保存批量导入数据
    public function saveFromImport($params)
    {
        //================================================数据验证操作==================================================
        if ($params && !empty($params)) {
            //验证小区
//            $community_info = CommunityService::service()->getCommunityInfo($params['community_id']);
//            if (empty($community_info)) {
//                return $this->failed("未找到小区信息");
//            }

            if(!empty($params['communityList'])){
                if(!in_array($params['community_id'],$params['communityList'])){
                    return $this->failed("没有该小区权限");
                }
            }

            //java 验证小区
            $commonService = new CommonService();
            $javaCommunityParams['community_id'] = $params['community_id'];
            $javaCommunityParams['token'] = $params['token'];
            $communityName = $commonService->communityVerificationReturnName($javaCommunityParams);
            if(empty($communityName)){
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
            //验证度数
            if (!is_numeric($val["E"])) {
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "起始度数错误";
                continue;
            }
            if (!$this->validSharedImport(1, $val["B"])) {
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "公摊所属类型错误";
                continue;
            }
            if (!$this->validSharedImport(2, $val["C"])) {
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "公摊仪表类型错误";
                continue;
            }
            if (!$this->validSharedImport(3, $val["D"])) {
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "公摊表盘错误";
                continue;
            }
            if ((empty($val["E"]) && $val["E"] != 0) || !is_numeric($val["E"])) {
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "起始读数错误";
                continue;
            }
            $receiptArr["PsReceiptFrom"]["community_id"] = (string)$params["community_id"];
            $receiptArr["PsReceiptFrom"]["name"] = $val["A"];
            $receiptArr["PsReceiptFrom"]["shared_type"] = $this->validSharedImport(1, $val["B"]);
            $receiptArr["PsReceiptFrom"]["panel_type"] = $this->validSharedImport(2, $val["C"]);
            $receiptArr["PsReceiptFrom"]["panel_status"] = $this->validSharedImport(3, $val["D"]);
            $receiptArr["PsReceiptFrom"]["start_num"] = (string)$val["E"];
            $receiptArr["PsReceiptFrom"]["remark"] = (string)$val["F"];
            $receiptArr["PsReceiptFrom"]["create_at"] = time();
            //不是整体用水用电，需要验证仪表类型是不是电表
            if($receiptArr["PsReceiptFrom"]["shared_type"]!=3 && $receiptArr["PsReceiptFrom"]["panel_type"]!=2){
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "仪表类型错误";
                continue;
            }
            /*校验上传数据是否合法*/
            $model = new PsReceiptFrom();
            $model->setScenario('shared-import');
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
                ":name" => $val["A"],
                ":shared_type" => $receiptArr["PsReceiptFrom"]["shared_type"],
                "community_id" => $params["community_id"]
            ];
            $shared = Yii::$app->db->createCommand("select id from ps_shared where  community_id=:community_id and shared_type=:shared_type and name=:name ", $shared_params)->queryOne();
            if (!empty($shared)) {
                $defeat_count++;
                $error_count++;
                $errorCsv[$defeat_count] = $val;
                $errorCsv[$defeat_count]["error"] = "该公摊项目已存在";
                continue;
            }
            array_push($batchInfo, $receiptArr["PsReceiptFrom"]);
            $defeat_count++;
            $success_count++;
        }
        if ($success_count > 0) {
            //批量存入房屋数据
            Yii::$app->db->createCommand()->batchInsert('ps_shared',
                [
                    'community_id',
                    'name',
                    'shared_type',
                    'panel_type',
                    'panel_status',
                    'start_num',
                    'remark',
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
            'A' => ['title' => '项目名称', 'width' => 16, 'data_type' => 'str', 'field' => 'A'],
            'B' => ['title' => '所属类型', 'width' => 16, 'data_type' => 'str', 'field' => 'B'],
            'C' => ['title' => '仪表类型', 'width' => 30, 'data_type' => 'str', 'field' => 'C'],
            'D' => ['title' => '表盘状态', 'width' => 10, 'data_type' => 'str', 'field' => 'D'],
            'E' => ['title' => '起始读数', 'width' => 10, 'data_type' => 'str', 'field' => 'E'],
            'F' => ['title' => '备注', 'width' => 10, 'data_type' => 'str', 'field' => 'F'],
            'G' => ['title' => '错误原因', 'width' => 10, 'data_type' => 'str', 'field' => 'error'],
        ];
        $filename = CsvService::service()->saveTempFile(1, array_values($config), $data, '', 'error');
//        $filePath = F::originalFile().'error/'.$filename;
//        $fileRe = F::uploadFileToOss($filePath);
//        $downUrl = $fileRe['filepath'];
        $newFileName = explode('/',$filename);
        $savePath = Yii::$app->basePath . '/web/store/excel/error/'.$newFileName[0]."/";
        $downUrl = F::uploadExcelToOss($newFileName[1], $savePath);
        return $downUrl;
    }

    ///验证导入的公摊类型，公式类型，状态是否正确并获取对应id
    public function validSharedImport($type, $name)
    {
        switch ($type) {
            case 1://验证公摊类型
                $validData = $this->shared_type;
                break;
            case 2://验证水表电表
                $validData = $this->panel_type;
                break;
            case 3://验证状态
                $validData = $this->panel_status;
                break;
        }
        foreach ($validData as $data) {
            if ($name == $data['name']) {
                return $data['id'];
            }
        }
        return false;
    }

    /**
     * 删除仪表数据
     * @author yjh
     * @return array
     */
    public function delete($data,$userinfo='')
    {
        if (empty($data['id']) || empty($data['type'])) {
            return $this->failed('参数错误');
        }
        switch ($data['type']) {
            //水表
            case 1:
                PsWaterMeter::deleteData($data['id'],$userinfo);
                break;
            //电表
            case 2:
                PsElectricMeter::deleteData($data['id'],$userinfo);
                break;
            //公共表
            case 3:
                PsShared::deleteData($data['id'],$userinfo);
                break;
            default:
               return $this->failed('类型错误');
        }
        return $this->success();
    }

    /*
     * 仪表数据详情
     */
    public function show($data){
        if (empty($data['id']) || empty($data['type'])) {
            return $this->failed('参数错误');
        }
        switch ($data['type']) {
            //水表
            case 1:
                $result = WaterMeterService::service()->show($data["id"]);
                break;
            //电表
            case 2:
                $result = ElectrictMeterService::service()->show($data["id"]);
                break;
            default:
                return $this->failed('类型错误');
        }
        return $result;
    }

    /**
     * 导出数据(未测demo)
     * @author yjh
     * @return array
     */
    public function export($where)
    {
        $result = $this->getWaterData($where);
        $config = $this->exportConfig();
        if(!empty($result['list'])){
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
            'group' => ['title' => '苑期区', 'width' => 16],
            'building' => ['title' => '幢', 'width' => 16],
            'unit' => ['title' => '单元', 'width' => 16],
            'room' => ['title' => '室', 'width' => 16],
            'type' => ['title' => '表具类型', 'width' => 16],
            'latest_record_time' => ['title' => '上次抄表时间', 'width' => 18],
            'start_ton' => ['title' => '上次抄表读数', 'width' => 16],
            'meter_status_desc' => ['title' => '表具状态', 'width' => 16],
            'remark' => ['title' => '备注', 'width' => 16],
        ];
        $config["save"] = true;
        $config['path'] = 'temp/'.date('Y-m-d');
        $config['file_name'] = ExcelService::service()->generateFileName('shared');
        return $config;
    }

    /**
     * 获取数据
     * @author yjh
     * @return array
     */
    public function getSharedData($data)
    {
        $valid = PsCommon::validParamArr(new PsShared(), $data, 'add-search');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        //条件处理
        $where['community_id'] = !empty($params['community_id']) ? $params['community_id'] : null;
        $where['panel_status'] = !empty($params['panel_status']) ? $params['panel_status'] : null;
        $where['panel_type'] = !empty($params['panel_type']) ? $params['panel_type'] : null;
        $where['shared_type'] = !empty($params['shared_type']) ? $params['shared_type'] : null;
        $where = F::searchFilter($where);
        $like = !empty($data['name']) ? ['like' , 'name' , $data['name']] : '1=1' ;
        //查询
        $data['where'] = $where;
        $data['like'] = $like;
        $result = PsShared::getData($data,false);
        $result['calc_msg'] = $this->getCalcMsg($where);
        return $result;
    }
}

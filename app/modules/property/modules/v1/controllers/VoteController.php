<?php
namespace app\modules\property\modules\v1\controllers;

use service\property_basic\JavaService;
use Yii;

use app\modules\property\controllers\BaseController;

use common\core\F;
use common\core\PsCommon;

use service\common\CsvService;
use service\common\ExcelService;
use service\rbac\OperateService;
use service\property_basic\VoteService;

use app\models\PsVote;
use app\models\PsVoteProblem;
use app\models\PsVoteProblemOption;

//require dirname(dirname(__DIR__)) . '/../common/PhpExcel/PHPExcel.php';

class VoteController extends BaseController
{
    public $repeatAction = ['add'];

    public function actionGetComm()
    {
        $result["vote_type"] = PsCommon::returnKeyValue(VoteService::$Vote_Type);
        $result["permission_type"] = PsCommon::returnKeyValue(VoteService::$Permission_Type);
        $result["option_type"] = PsCommon::returnKeyValue(VoteService::$Option_Type);
        $result["status"] = PsCommon::returnKeyValue(VoteService::$status);
        $result["vote_channel"] = PsCommon::returnKeyValue(VoteService::$Vote_Channel);
        
        return PsCommon::responseSuccess($result);
    }

    public function actionList()
    {
//        $communityId = PsCommon::get($this->request_params, 'community_id');
//        if (!$communityId) {
//            return PsCommon::responseFailed('小区不能为空！');
//        }
        $result = VoteService::service()->voteList($this->request_params);
        
        return PsCommon::responseSuccess($result);
    }

    public function actionShow()
    {
        $voteId = PsCommon::get($this->request_params, 'vote_id');
        if (!$voteId) {
            return PsCommon::responseFailed('投票活动id不能为空！');
        }
        $result = VoteService::service()->voteDetail($voteId);
        if ($result["code"]) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionOnOff()
    {
        $valid = PsCommon::validParamArr(new PsVote(), $this->request_params, 'on-off');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = VoteService::service()->onOffVote($this->request_params, $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //投票状态下拉
    public function actionGetStatusDrop(){
        $drop = VoteService::service()->getStatusList();
        return PsCommon::responseSuccess($drop);
    }

    public function actionAdd()
    {
        $data = $this->request_params;
        $this->request_params['vote_status'] = 1; //投票状态
        $valid = PsCommon::validParamArr(new PsVote(), $this->request_params, 'add');

        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }

        if (empty($data["problems"]) || count($data["problems"]) > 10) {
            return PsCommon::responseFailed('投票问题数最少一个最多十个');
        }

        if (strtotime($data["end_time"]) <= time()) {
            return PsCommon::responseFailed('截止时间必须大于当前时间');
        }

        foreach ($data["problems"] as $problem) {
            $valid = PsCommon::validParamArr(new PsVoteProblem(), $problem, 'add');
            if (!$valid["status"]) {
                return PsCommon::responseFailed($valid["errorMsg"]);
            }

            if (empty($problem["options"]) || count($problem["options"]) > 30 || count($problem["options"]) < 2) {
                return PsCommon::responseFailed('选项个数最少2个最多30个');
            }

            foreach ($problem['options'] as $option){
                $valid = PsCommon::validParamArr(new PsVoteProblemOption(), $option, 'add2');
                if (!$valid["status"]) {
                    return PsCommon::responseFailed($valid["errorMsg"]);
                }
            }
        }


        if ($data["permission_type"] == 3) {
            if (empty($data["appoint_members"])) {
                return PsCommon::responseFailed('指定业主不能为空');
            }
            $arr = [];
            foreach ($data["appoint_members"] as $key => $member) {
                if (!empty($member["room_id"]) && !empty($member["member_id"])) {
                    $key_str = $member["room_id"] . '_' . $member["member_id"];
                    if (in_array($key_str, $arr)) {
                        unset($data["appoint_members"][$key]);
                    } else {
                        array_push($arr, $key_str);
                    }
                } else {
                    return PsCommon::responseFailed('业主房屋不能为空');
                }
            }
        }
        $result = VoteService::service()->addVote($data, $this->user_info);
        if ($result["code"]) {
            // 添加到redis中，处理发送消息
            $voteId = $result['data']['vote_id'];
            return PsCommon::responseSuccess(['id'=>$voteId]);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionEndTime()
    {
        $valid = PsCommon::validParamArr(new PsVote(), $this->request_params, 'end-time');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = VoteService::service()->editEndTime($this->request_params, $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionDelete()
    {
        $voteId = PsCommon::get($this->request_params, 'vote_id');
        if (!$voteId) {
            return PsCommon::responseFailed('投票活动id不能为空！');
        }
        $result = VoteService::service()->deleteVote($voteId,$this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //投票人员列表
    public function actionVoteMemberList(){
        $voteId = PsCommon::get($this->request_params, 'vote_id');
        if (!$voteId) {
            return PsCommon::responseFailed('投票活动id不能为空！');
        }
        $communityId = PsCommon::get($this->request_params, 'community_id');
        if (!$communityId) {
            return PsCommon::responseFailed('小区id不能为空！');
        }

        $result = VoteService::service()->voteMemberList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    public function actionShowMember()
    {
        $voteId = PsCommon::get($this->request_params, 'vote_id');
        if (!$voteId) {
            return PsCommon::responseFailed('投票活动id不能为空！');
        }
        $result = VoteService::service()->showMember($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //投票列表详情
    public function actionVoteListDetail(){
        $voteId = PsCommon::get($this->request_params, 'vote_id');
        if (!$voteId) {
            return PsCommon::responseFailed('投票活动id不能为空！');
        }

        $memberId = PsCommon::get($this->request_params, 'member_id');
        if(!$memberId){
            return PsCommon::responseFailed('用户id不能为空！');
        }


        $roomId = PsCommon::get($this->request_params, 'room_id');
        if(!$roomId){
            return PsCommon::responseFailed('房屋id不能为空！');
        }

        $result = VoteService::service()->voteListDetail($voteId, $memberId, $roomId);
        if ($result["code"]) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }


    public function actionShowMemberDet()
    {
        $voteId = PsCommon::get($this->request_params, 'vote_id');
        if (!$voteId) {
            return PsCommon::responseFailed('投票活动id不能为空！');
        }
        $memberId = PsCommon::get($this->request_params, 'member_id', 0);
        $roomId = PsCommon::get($this->request_params, 'room_id', 0);

        $result = VoteService::service()->showMemberDet($voteId, $memberId, $roomId);
        return PsCommon::responseSuccess($result);
    }

    public function actionExportVote()
    {
        $voteId = PsCommon::get($this->request_params, 'vote_id');
        if (!$voteId) {
            return PsCommon::responseFailed('投票活动id不能为空！');
        }
        $models = VoteService::service()->exportVote($this->request_params);

        $config = [
            ['title' => '房屋信息', 'data_type' => 'join', 'field' => ['group', 'building', 'unit', 'room'], 'default' => '-'],
            ['title' => '业主姓名', 'field' => 'name'],
            ['title' => '业主手机号', 'field' => 'mobile'],
            ['title' => '业主类型', 'field' => 'identity_type_desc'],
            ['title' => '是否投票', 'field' => 'is_vote'],
            ['title' => '投票时间', 'field' => 'vote_time', 'default' => '-'],
            ['title' => '投票渠道', 'field' => 'vote_channel_desc', 'default' => '-'],
        ];

        $filename = CsvService::service()->saveTempFile(1, $config, $models['list'], 'TouPiao');
        $filePath = F::originalFile().'temp/'.$filename;
        $fileRe = F::uploadFileToOss($filePath);
        $downUrl = $fileRe['filepath'];
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    public function actionShowTime()
    {
        $valid = PsCommon::validParamArr(new PsVote(), $this->request_params, 'show-time');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = VoteService::service()->editShowTime($this->request_params,$this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionEditResult()
    {
        $valid = PsCommon::validParamArr(new PsVote(), $this->request_params, 'edit-result');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }

        $result = VoteService::service()->editResult($this->request_params);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionShowResult()
    {
        $voteId = PsCommon::get($this->request_params, 'vote_id');
        if (!$voteId) {
            return PsCommon::responseFailed('投票活动id不能为空！');
        }
        $result = VoteService::service()->showResult($voteId);
        return PsCommon::responseSuccess($result);
    }

    public function actionDeleteResult()
    {
        $voteId = PsCommon::get($this->request_params, 'vote_id');
        if (!$voteId) {
            return PsCommon::responseFailed('投票活动id不能为空！');
        }
        $result = VoteService::service()->deleteResult($voteId);
        return PsCommon::responseSuccess($result);
    }

    public function actionDoVote()
    {
        $memberId = PsCommon::get($this->request_params, 'member_id');
        $token = PsCommon::get($this->request_params, 'token');
        $roomId = PsCommon::get($this->request_params, 'room_id');
        $voteId = PsCommon::get($this->request_params, 'vote_id');
        $voteDetail = PsCommon::get($this->request_params, 'vote_det');
        if (!$voteId) {
            return PsCommon::responseFailed('投票id不能为空');
        }
        if (!$roomId) {
            return PsCommon::responseFailed('房间号id不能为空');
        }
        if (!$memberId) {
            return PsCommon::responseFailed('业主id不能为空');
        }
        if (empty($voteDetail)) {
            return PsCommon::responseFailed('投票明细不能为空');
        }
        $problems = Yii::$app->db->createCommand("select id,option_type from ps_vote_problem where vote_id=:vote_id", [":vote_id" => $voteId])->queryAll();
        $problem_type = array_column($problems, 'option_type', 'id');
        $problem_ids = array_column($problems, 'id');

        foreach ($voteDetail as $key => $det) {
            if (!empty($det["problem_id"]) && in_array($det["problem_id"], $problem_ids)) {
                if (empty($det["options"])) {
                    return PsCommon::responseFailed('选项不能为空');
                }
                if (!$problem_type[$det["problem_id"]]) {
                    return PsCommon::responseFailed('重复提交');
                }
                if (count($det["options"]) > 1 && $problem_type[$det["problem_id"]] == 1) {
                    return PsCommon::responseFailed('单选问题答案不能多余1个');
                }
                unset($problem_type[$det["problem_id"]]);
            } else {
                return PsCommon::responseFailed('问题未找到');
            }
        }
        if (!empty($problem_type)) {
            return PsCommon::responseFailed('问题未添加选项！');
        }

        $javaService = new JavaService();
        $javaParams['id'] = $memberId;
        $javaParams['token'] = $token;
        $javaResult = $javaService->residentDetail($javaParams);
        if(empty($javaResult)){
            return PsCommon::responseFailed('用户不存在');
        }

        if(!empty($javaResult['roomId'])&&$javaResult['roomId']!=$roomId){
            return PsCommon::responseFailed('用户不存在');
        }

        $doVote = VoteService::service()->doVote($voteId, $javaResult['residentId'], $javaResult['memberName'], $voteDetail, $javaResult['communityId'], 'off', $roomId);
        if ($doVote === true) {
            return PsCommon::responseSuccess();
        } elseif ($doVote === false) {
            return PsCommon::responseFailed('投票失败');
        } else {
            return PsCommon::responseFailed($doVote);
        }
    }

    public function actionDataExport()
    {
        $community_id = PsCommon::get($this->request_params, 'community_id');
        $voteId = PsCommon::get($this->request_params, 'vote_id');
        $dataType = PsCommon::get($this->request_params, 'data_type');
        if (!$voteId) {
            return PsCommon::responseFailed('投票id不能为空');
        }
        $result = VoteService::service()->exportData($voteId);
        if ($dataType == 'online') {
            $models = $result["online_data_show"];
            $prefix = "XianShang";
            $operate_type='线上统计结果';
        } elseif ($dataType == 'offline') {
            $prefix = "XianXia";
            $models = $result["underline_data_show"];
            $operate_type='线下统计结果';
        } else {
            $prefix = "JieGuo";
            $models = $result["total_data_show"];
            $operate_type='统计结果汇总';
        }
        $operate = [
            "community_id" =>$community_id,
            "operate_menu" => "小区投票",
            "operate_type" => '小区详情-导出',
            "operate_content" => $operate_type
        ];
        OperateService::addComm($this->user_info, $operate);
        $maxTd = Yii::$app->db->createCommand("select max(D.total) from (select count(A.id) as total, A.problem_id from ps_vote_problem_option A 
          left join ps_vote_problem B on   A.problem_id=B.id where B.vote_id=:vote_id group by A.problem_id) D", [":vote_id" => $voteId])->queryScalar();

        $header[] = "";//第一列空白
        for ($i = 1; $i <= $maxTd; $i++) {
            $header[] = "选项" . $i;
        }
        $data = [];
        foreach ($models as $key => $model) {
            $tmp = [];
            $tmp[] = "题目" . ($key + 1);
            foreach ($model['options'] as $k => $v) {
                $tmp[] = $v;
            }
            $data[] = $tmp;
        }
        $filename = CsvService::service()->saveTempFile(0, $header, $data, $prefix);
        $filePath = F::originalFile().'temp/'.$filename;
        $fileRe = F::uploadFileToOss($filePath);
        $downUrl = $fileRe['filepath'];
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    public function actionExportDet()
    {
        $voteId = PsCommon::get($this->request_params, 'vote_id');
        if (!$voteId) {
            return PsCommon::responseFailed('投票id不能为空！');
        }
        $vote = Yii::$app->db->createCommand("select * from ps_vote where id=:vote_id", [":vote_id" => $voteId])->queryOne();
        if (empty($vote)) {
            return PsCommon::responseFailed('未找到投票！');
        }
        $problems = VoteService::service()->getProblems($voteId);

        $objPHPExcel = new \PHPExcel();
        $operate = [
            "community_id" =>$vote['community_id'],
            "operate_menu" => "小区投票",
            "operate_type" => '导出投票详情',
            "operate_content" => '投票标题'.$vote["vote_name"]
        ];
        OperateService::addComm($this->user_info, $operate);
        $html = "投票标题：" . $vote["vote_name"] . "\r\n";
        $now_col = 1;
        $cols = [];
        foreach ($problems as $key => $problem) {
            $html .= "题目" . ($key + 1) . "[" . $problem["option_type_desc"] . "]" . " : " . $problem["title"] . "\r\n";
            $now_col++;
            foreach ($problem["options"] as $k => $v) {
                $html .= ($k + 1) . ":" . $v . " ";
            }
            $html .= "\r\n";
            $now_col++;
            array_push($cols, ["problem_id" => $problem["problem_id"], "item" => \PHPExcel_Cell::stringFromColumnIndex((8 + $key))]);
        }
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setWrapText(true);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $html);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:H' . $now_col);      //合并

        $now_col = $now_col + 2;

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(16);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(30);
        $objPHPExcel->getActiveSheet()->setCellValue("A" . $now_col, "房屋信息");
        $objPHPExcel->getActiveSheet()->setCellValue("B" . $now_col, "房屋面积(㎡)");
        $objPHPExcel->getActiveSheet()->setCellValue("C" . $now_col, "姓名");
        $objPHPExcel->getActiveSheet()->setCellValue("D" . $now_col, "手机号码");
        $objPHPExcel->getActiveSheet()->setCellValue("E" . $now_col, "业主类型");
        $objPHPExcel->getActiveSheet()->setCellValue("F" . $now_col, "是否投票");
        $objPHPExcel->getActiveSheet()->setCellValue("G" . $now_col, "投票渠道");
        $objPHPExcel->getActiveSheet()->setCellValue("H" . $now_col, "投票时间");
        foreach ($cols as $k => $c) {
            $objPHPExcel->getActiveSheet()->setCellValue($c["item"] . $now_col, "题目" . ($k + 1));
        }
        $models = VoteService::service()->exportVote(["vote_id" => $voteId]);
        $member_options = VoteService::service()->getMemberOption($voteId, $vote["permission_type"]);
        foreach ($models["list"] as $model) {
            $now_col++;
            $roominfo = $model["group"] . $model["building"] . $model["unit"] . $model["room"];
            $objPHPExcel->getActiveSheet()->setCellValueExplicit("A" . $now_col, "" . $roominfo . "");
            $objPHPExcel->getActiveSheet()->setCellValueExplicit("B" . $now_col, $model["charge_area"] > 0 ? $model["charge_area"] : '-', 'str');
            $objPHPExcel->getActiveSheet()->setCellValueExplicit("C" . $now_col, !empty($model["name"]) ? $model["name"] : '-', 'str');
            $objPHPExcel->getActiveSheet()->setCellValueExplicit("D" . $now_col, !empty($model["mobile"]) ? $model["mobile"] : '-', 'str');
            $objPHPExcel->getActiveSheet()->setCellValueExplicit("E" . $now_col, !empty($model["identity_type_desc"]) ? $model["identity_type_desc"] : '-', 'str');
            $objPHPExcel->getActiveSheet()->setCellValueExplicit("F" . $now_col, $model["is_vote"], 'str');
            $objPHPExcel->getActiveSheet()->setCellValueExplicit("G" . $now_col, !empty($model["vote_channel_desc"]) ? $model["vote_channel_desc"] : '-', 'str');
            $objPHPExcel->getActiveSheet()->setCellValueExplicit("H" . $now_col, !empty($model["vote_time"]) ? $model["vote_time"] : '-', 'str');
            if (!empty($model["vote_channel"])) {
                $member_option = $vote["permission_type"] == 1 ? $member_options[$model['room_id']] : $member_options[$model['member_id']];
                foreach ($cols as $c) {
                    $options = $member_option[$c["problem_id"]];
                    $options = $options != '' ? substr($options, 0, -1) : "";
                    $objPHPExcel->getActiveSheet()->setCellValueExplicit($c["item"] . $now_col, $options, 'str');
                }
            }
        }
        $excelConfig = ['path' => 'temp/' . date('Y-m-d'), 'file_name' => ExcelService::service()->generateFileName('TouPiaoMingXi')];
        $url = ExcelService::service()->saveExcel($objPHPExcel, $excelConfig);
        $fileName = pathinfo($url, PATHINFO_BASENAME);
        $downUrl = F::downloadUrl($this->systemType, date('Y-m-d') . '/' . $fileName, 'temp', 'TouPiaoMingXi.xlsx');
        return PsCommon::responseSuccess(["down_url" => $downUrl]);
    }
}
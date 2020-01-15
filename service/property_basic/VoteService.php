<?php
namespace service\property_basic;

use Yii;
use yii\db\Query;
use yii\base\Exception;

use common\core\PsCommon;

use service\BaseService;
use service\rbac\OperateService;
use service\message\MessageService;

use app\models\PsCommunityModel;
use app\models\PsVote;
use app\models\PsVoteMemberAppoint;
use app\models\PsVoteMemberDet;
use app\models\PsVoteProblem;
use app\models\PsVoteProblemOption;
use app\models\PsVoteResult;

class VoteService extends BaseService
{
    public static $Vote_Type = ['1' => '文字投票', '2'  => '图文投票'];
    public static $Permission_Type = ['1'  => '每户一票', '2'  => '每人一票', '3'  => '指定业主投票'];
    public static $Option_Type = ['1' => '单选', '2'  => '多选'];
    public static $status = ['1' => '已上架', '2'  => '已下架'];
    public static $Vote_Channel = ['1' => '线上投票', '2'  => '线下录入'];
    public static $Vote_Status = ['0' => '全部', '1' => '进行中', '2'  => '已结束'];
    public static $vote_status = ['1'=>'未开始', '2'=>'投票中', '3'=>'投票结束', '4'=>'已公示'];        //投票状态

    // 查询所有的小区列表
    public function getAllCommunitys($name)
    {
        $model = PsCommunityModel::find()->where(['comm_type' => 1, 'status' => 1])->orderBy('id desc');
        if ($name) {
            $model->andWhere(['like', 'name', $name]);
        }
        $comms = $model->select(['id', 'name', 'address'])->asArray()->all();

        return $comms;
    }

    public function getStatusList(){
        return [
            'list'=>[
                ['key'=>1,'name'=>'未开始'],
                ['key'=>2,'name'=>'投票中'],
                ['key'=>3,'name'=>'投票结束'],
                ['key'=>4,'name'=>'已公示'],
            ]
        ];
    }

    //投票权限类型下拉
    public function getPermissionType(){
        return [
            'list'=>[
                ['key'=>1,'name'=>'每户一票'],
                ['key'=>2,'name'=>'每人一票'],
                ['key'=>3,'name'=>'指定业主投票'],
            ]
        ];
    }

    // 获取投票列表
    public function voteList($reqArr)
    {
        $communityId = !empty($reqArr['community_id']) ? $reqArr['community_id'] : '';
        $voteName = !empty($reqArr['vote_name']) ? $reqArr['vote_name'] : '';
        $voteStatus = !empty($reqArr['vote_status']) ? $reqArr['vote_status'] : '';
        $page = !empty($reqArr['page']) ? $reqArr['page'] : 1;
        $rows = !empty($reqArr['rows']) ? $reqArr['rows'] : Yii::$app->params['list_rows'];

        // 获得所有小区
        $javaService = new JavaService();
        $javaParam['token'] = $reqArr['token'];
        $javaResult = $javaService->communityNameList($javaParam);
        $communityIds = !empty($javaResult['list'])?array_column($javaResult['list'],'key'):[];
        $javaResult = !empty($javaResult['list'])?array_column($javaResult['list'],'name','key'):[];
        $query = new Query();
        $query->from('ps_vote') ->where("1=1");

        if ($communityId) {
            $query->andWhere(['community_id' => $communityId]);
        }else{
            if(!empty($communityIds)){
                $query->andWhere(['community_id' => $communityIds]);
            }
        }

        if ($voteName) {
            $query->andWhere(['like', 'vote_name', $voteName]);
        }

        if ($voteStatus){
            $query->andWhere(['=', 'vote_status', $voteStatus]);
        }

        $totals = $query->count();
        if($totals == 0 ) {
            return [ "totals" => 0, 'list' => []];
        }
        $fields = ['id','community_id','vote_name','start_time','end_time','vote_status','status','totals'];
        $query->select($fields);
        $query->orderBy('created_at desc');
        $re['totals'] = $totals;
        $offset = ($page-1) * $rows;
        $query->offset($offset)->limit($rows);

        $command = $query->createCommand();
        $models = $command->queryAll();

        foreach ($models as $key=>$val) {
//            $models[$key]['voted_totals'] = Yii::$app->db->createCommand("SELECT count(distinct member_id ) as total from ps_vote_member_det where vote_id=:vote_id and vote_channel=1", [":vote_id" => $val["id"]])->queryScalar();
            $models[$key]['voted_totals'] = $val['totals'];
            $models[$key]['start_time'] = date("Y-m-d H:i", $val['start_time']);
            $models[$key]['end_time'] = date("Y-m-d H:i", $val['end_time']);
            $models[$key]['vote_status_msg'] = !empty($val['vote_status'])?self::$vote_status[$val['vote_status']]:'';
            $models[$key]['community_name'] = !empty($val['community_id'])?$javaResult[$val['community_id']]:'';



//            $models[$key]['totals'] = Yii::$app->db->createCommand("SELECT count(distinct member_id ) as total from ps_vote_member_det where vote_id=:vote_id and vote_channel=1", [":vote_id" => $val["id"]])->queryScalar();
//            $models[$key]['start_time'] = date("Y-m-d H:i", $val['start_time']);
//            $models[$key]['end_time'] = date("Y-m-d H:i", $val['end_time']);
//            $models[$key]["vote_status"] = $val['end_time'] > time() ? "进行中" : "已结束";
//            $models[$key]["status_desc"] = isset( self::$status[$val['status']]) ? self::$status[$val['status']] : '未知';
//            $models[$key]['created_at'] = date("Y-m-d H:i:s", $val['created_at']);
//            $models[$key]['permission_type_desc'] = isset( self::$Permission_Type[$val['permission_type']]) ? self::$Permission_Type[$val['permission_type']] : '未知';
//            $models[$key]['vote_type_desc'] = isset( self::$Vote_Type[$val['vote_type']]) ? self::$Vote_Type[$val['vote_type']] : '未知';
        }

        return ["totals" => $totals, 'list' => $models];
    }

    // 街道办后台获取投票列表
    public function streetVoteList($reqArr)
    {
        unset($reqArr['community_name']);

        $communityId = !empty($reqArr['community_id']) ? $reqArr['community_id'] : [];
        $voteName = !empty($reqArr['vote_name']) ? $reqArr['vote_name'] : '';
        $page = !empty($reqArr['page']) ? $reqArr['page'] : 1;
        $rows = !empty($reqArr['rows']) ? $reqArr['rows'] : Yii::$app->params['list_rows'];
        $voteStatus = !empty($reqArr['vote_status']) ? $reqArr['vote_status'] : 0;

        $query = new Query();
        if ($voteStatus == 1) { // 进行中
            $query->from('ps_vote') ->where("status=1 and start_time <".time()." and end_time >".time());
        } else if ($voteStatus == 2) { // 已结束
            $query->from('ps_vote') ->where("status=1 and end_time <".time());
        } else { // 全部
            $query->from('ps_vote') ->where("status=1");
        }

        if ($communityId) {
            $query->andWhere(['in', 'community_id', $communityId]);
        }

        if ($voteName) {
            $query->andWhere(['like', 'vote_name', $voteName]);
        }

        $totals = $query->count();
        if($totals == 0 ) {
            return [ "totals" => 0, 'list' => [] ];
        }

        $query->select(['*']);
        $query->orderBy('created_at desc');
        $re['totals'] = $totals;
        $offset = ($page-1) * $rows;
        $query->offset($offset)->limit($rows);
        $command = $query->createCommand();
        $models = $command->queryAll();
        // 获取小区名称
        $cnames = array();
        if (!empty($communityId)) {
            $cmodel = new PsCommunityModel();
            $cnames = $cmodel->getCommunityName($communityId);
        }

        foreach ($models as $key => $val) {
            $models[$key]['start_time'] = date("Y-m-d H:i", $val['start_time']);
            $models[$key]['end_time'] = date("Y-m-d H:i", $val['end_time']);
            $models[$key]["vote_status"] = $val['end_time'] > time() ? "进行中" : "已结束";
            $models[$key]["status_desc"] = isset( self::$status[$val['status']]) ? self::$status[$val['status']] : '未知';
            $models[$key]['created_at'] = date("Y-m-d H:i:s", $val['created_at']);
            $models[$key]['permission_type_desc'] = isset( self::$Permission_Type[$val['permission_type']]) ? self::$Permission_Type[$val['permission_type']] : '未知';
            $models[$key]['vote_type_desc'] = isset( self::$Vote_Type[$val['vote_type']]) ? self::$Vote_Type[$val['vote_type']] : '未知';
            $models[$key]['data_count'] = $this->getCountTotal($val);
            $models[$key]['community_name'] = '';

            if (!empty($cnames)) {
                foreach ($cnames  as $k => $v) {
                    if ($models[$key]['community_id'] == $v['id']) {
                        $models[$key]['community_name'] = $v['name'];
                    }
                }
            }
        }

        return ["totals" => $totals, 'list' => $models];
    }

    // 精简的投票列表，用于生活号上展示，主要为了C端查询速度不用后台使用同一个方法 显示当前用户能看到的数据
    public function voteListOfC($params)
    {
        $params['status'] = 1;

        $fields = ['id','vote_name','community_id','start_time','end_time','vote_desc','permission_type','vote_status','status'];
        $model = PsVote::find()->select($fields)->andWhere(['in','permission_type',[1,2]]);

        $fields1 = ['v.id','v.vote_name','v.community_id','v.start_time','v.end_time','v.vote_desc','v.permission_type','v.vote_status','v.status'];
        $model1 = PsVote::find()->alias('v')->select($fields1)
            ->leftJoin(['a'=>PsVoteMemberAppoint::tableName()],'a.vote_id=v.id')
            ->andWhere(['=','v.permission_type',3]);

        if(!empty($params['community_id'])){
            $model->andWhere(['=','community_id',$params['community_id']]);
            $model1->andWhere(['=','v.community_id',$params['community_id']]);
        }

        if(!empty($params['status'])){
            $model->andWhere(['=','status',$params['status']]);
            $model1->andWhere(['=','v.status',$params['status']]);
        }


        if(!empty($params['member_id'])){
            $model1->andWhere(['=','a.member_id',$params['member_id']]);
        }

        if(!empty($params['room_id'])){
            $model1->andWhere(['=','a.room_id',$params['room_id']]);
        }

        $modelQuery = (new Query())->from(['tmpA' => $model->union($model1)]);

        $count = $modelQuery->count();
        $modelQuery->offset(($params['page']-1)*$params['rows'])->limit($params['rows']);
        $modelQuery->orderBy(["id"=>SORT_DESC]);
        $result = $modelQuery->all();


        if($count==0){
            return ["totals" => 0, 'list' => []];
        }

        $data = [];
        foreach ($result as $key => $value) {
            $element = [];
            $element['id'] = !empty($value['id'])?$value['id']:'';
            $element['vote_name'] = !empty($value['vote_name'])?$value['vote_name']:'';
            $element['vote_status_msg'] = !empty($value['vote_status'])?self::$vote_status[$value['vote_status']]:'';
            $element['end_time_msg'] = !empty($value['end_time'])?date('Y年m月d日',$value['end_time']):'';
            $element['end_minute_msg'] = !empty($value['end_time'])?date('H:i',$value['end_time']):'';
            $data[] = $element;
        }

        return ["totals" => $count, 'list' => $data];
    }

    // 精简的投票列表，用于生活号上展示，主要为了C端查询速度不用后台使用同一个方法
    public function simpleVoteList($reqArr)
    {
        $communityId = !empty($reqArr['community_id']) ? $reqArr['community_id'] : 0;
        $votes = PsVote::find()
            ->select(['id', 'vote_name', 'start_time', 'end_time', 'show_at', 'vote_desc', 'vote_type', 'permission_type', 'totals', 'status'])
            ->where(['community_id' => $communityId, 'status' => 1])
            ->orderBy('id desc')->asArray()->all();

        foreach ($votes as $k => $v) {
            $votes[$k]['end_time'] = date("m月d日 H:i", $v['end_time']);
            $votes[$k]['permission_type_desc'] = isset(self::$Permission_Type[$v['permission_type']]) ? self::$Permission_Type[$v['permission_type']] : '未知';
            $votes[$k]["vote_status"] = $v['end_time'] > time() ? "进行中" : "已结束";

            // 是否展示投票结果
            $votes[$k]['show_vote_det'] = 0;
            if (time() >= $v['show_at']) {
                $votes[$k]['show_vote_det'] = 1;
            }

            // 查询公告结果配置
            $psVoteRe = PsVoteResult::find()->where(['vote_id' => $v['id']])->asArray()->one();
            $votes[$k]['result_config'] = $psVoteRe;
        }

        return $votes;
    }

    //投票详情
    public function voteDetail($vote_id){
        $params['id'] = $vote_id;
        $model = new PsVote(['scenario'=>'detail']);
        if($model->load($params,"") && $model->validate()){
            $detail = $model->getDetail($params);
            $result = self::doVoteDetail($detail);
            return $this->success($result);
        }else{
            return $this->failed($this->getError($model));
        }
    }

    //投票详情数据
    public function doVoteDetail($params){
        $element = [];
        $element['id'] = !empty($params['id'])?$params['id']:'';
        $element['vote_name'] = !empty($params['vote_name'])?$params['vote_name']:'';
        $element['community_id'] = !empty($params['community_id'])?$params['community_id']:'';
        $element['start_time_msg'] = !empty($params['start_time'])?date('Y-m-d H:i',$params['start_time']):'';
        $element['end_time_msg'] = !empty($params['end_time'])?date('Y-m-d H:i',$params['end_time']):'';
        $element['vote_desc'] = !empty($params['vote_desc'])?$params['vote_desc']:'';
        $element['permission_type'] = !empty($params['permission_type'])?$params['permission_type']:'';
        $element['permission_type_msg'] = !empty($params['permission_type'])?self::$Permission_Type[$params['permission_type']]:'';
        //已投票数
        $element['totals'] = !empty($params['totals'])?$params['totals']:0;
        //投票问题
        $element['problem'] = [];
        if(!empty($params['problem'])){
            foreach($params['problem'] as $key=>$value){
                $problemEle = [];
                $problemEle['title'] = !empty($value['title'])?$value['title']:'';
                $problemEle['option_type'] = !empty($value['option_type'])?$value['option_type']:'';
                $problemEle['totals'] = !empty($value['totals'])?$value['totals']:0;
                $problemEle['option_type_msg'] = !empty($value['option_type'])?self::$Option_Type[$value['option_type']]:'';
                $problemEle['option'] = [];
                if(!empty($value['option'])){
                    foreach($value['option'] as $k=>$v){
                        $optionEle = [];
                        $optionEle['title'] = !empty($v['title'])?$v['title']:'';
                        $optionEle['image_url'] = !empty($v['image_url'])?$v['image_url']:'';
                        $optionEle['option_desc'] = !empty($v['option_desc'])?$v['option_desc']:'';
                        $optionEle['totals'] = !empty($v['totals'])?$v['totals']:0;
                        $optionEle['rate'] = !empty($problemEle['totals'])?round(sprintf("%.3f",$v['totals']/$problemEle['totals'])*100,2):0;
                        $problemEle['option'][] = $optionEle;
                    }
                }
                $element['problem'][] = $problemEle;
            }
        }
        return $element;
    }


    // 获取投票详情
    public function showVote($vote_id, $member_id = 0, $roomId = 0)
    {
        $vote = Yii::$app->db->createCommand("select * from ps_vote where id=:vote_id", [":vote_id" => $vote_id])->queryOne();
        if (!empty($vote)) {
            $vote["appoint_members"] = [];
            if ($vote["permission_type"] == 3) {
                $vote["appoint_members"] = $this->getMemberAppoint($vote_id, $vote["community_id"]);
            } else if ($vote["permission_type"] == 2) {
                $roomId = 0;
            }

            $vote["is_show_at"] = $vote['show_at'] > time() ? 0 : 1;
            $vote["is_end_time"] = $vote['end_time'] > time() ? 0 : 1;
            $vote["vote_status"] = $vote['end_time'] > time() ? "进行中" : "已结束";
            $vote['start_time'] = date("Y-m-d H:i", $vote['start_time']);
            $vote['end_time'] = date("Y-m-d H:i", $vote['end_time']);
            $vote["status_desc"] = isset( self::$status[$vote['status']]) ? self::$status[$vote['status']] : '未知';
            $vote['created_at'] = date("Y-m-d H:i", $vote['created_at']);
            $vote['permission_type_desc'] = isset( self::$Permission_Type[$vote['permission_type']]) ? self::$Permission_Type[$vote['permission_type']] : '未知';
            $vote['vote_type_desc'] = isset( self::$Vote_Type[$vote['vote_type']]) ? self::$Vote_Type[$vote['vote_type']] : '未知';
            //$vote["appoint_members"] = $this->getMemberAppoint($vote_id,$vote["community_id"]);
            $vote["problems"] = $this->getVoteProblem($vote_id, $member_id, $roomId);
            $vote_result = VoteService::service()->showResult($vote_id);
            $vote["vote_result"] = !empty($vote_result) ? 1 : 0;
            // 查询问题
            $problems = PsVoteProblem::find()->where(['vote_id' => $vote_id])->asArray()->all();
            $vote['data_show'] = $this->_getOptionData($problems);
            $vote["data_count"] = $this->getCountTotal($vote);

            // 查询所有问题 判断用户是否可以投票
            $vote['do_voting'] = [];
            if ($member_id) {
                $vote['do_voting'] = $this->canVoting($member_id, $vote_id, 'on', $roomId);
                if ($vote['do_voting']['voting_status'] == 1) {
                    $vote['do_voting']['voting_value'] = "请投出您宝贵的一票";
                }
            }
            // 是否展示投票结果
            $vote['show_vote_det'] = 0;
            if (time() >= $vote['show_at']) {
                $vote['show_vote_det'] = 1;
            }

            $vote['show_at'] = $vote['show_at'] ? date("Y-m-d H:i",$vote['show_at']) : '';

            // 查询公告结果配置
            $psVoteRe = PsVoteResult::find()->where(['vote_id' => $vote_id])->asArray()->one();
            $vote['result_config'] = $psVoteRe;

        }

        return $vote;
    }

    // 查看线下统计结果
    private function _getOptionData($problemArr)
    {
        $reArr = [];
        foreach ($problemArr as $k => $v) {
            $dataArr['title'] = $v['title'];
            $dataArr['problem_id'] = $v['id'];
            // 查询选项
            $options = PsVoteProblemOption::find()->where(['problem_id' => $v['id']])->orderBy('id asc')->asArray()->all();
            // 查询题目的总投票数
            $totalProblemNum  = $v['totals'];
            $onlineProblem = PsVoteMemberDet::find()->select(['id'])->where(['vote_id' => $v['vote_id']])
                ->andWhere(['problem_id' => $v['id']])->andWhere(['vote_channel' => 1])
                ->groupBy('member_id')->asArray()->all();

            $underlineProblem = PsVoteMemberDet::find()->select(['id'])->where(['vote_id' => $v['vote_id']])
                ->andWhere(['problem_id' => $v['id']])->andWhere(['vote_channel' => 2])
                ->groupBy('member_id')->asArray()->all();
            $onlineProblemNum = count($onlineProblem);
            $unlineProblemNum = count($underlineProblem);
            // 线下数据组合
            $dataArr['options_online'] = [];
            // 线下数据组合
            $dataArr['options_underline'] = [];
            // 总数组合
            $dataArr['options_total'] = [];
            foreach ($options as $key => $option) {
                // 线上数量统计
                $tmpData  = PsVoteMemberDet::find()->select(['count(id) as num'])
                    ->where(['vote_id' => $v['vote_id']])
                    ->andWhere(['problem_id' => $v['id'], 'option_id' => $option['id']])
                    ->andWhere(['vote_channel' => 1])
                    ->asArray()->one();
                // 计算比例
                $tmpPercent = $onlineProblemNum == 0 ? '0%' : round(($tmpData['num'] / $onlineProblemNum) * 100) . '%';
                $singleData = $tmpData['num'] . "票" . "，" . $tmpPercent;
                array_push($dataArr['options_online'], $singleData);

                // 线下数量统计
                $tmpData  = PsVoteMemberDet::find()->select(['count(id) as num'])
                    ->where(['vote_id' => $v['vote_id']])
                    ->andWhere(['problem_id' => $v['id'], 'option_id' => $option['id']])
                    ->andWhere(['vote_channel' => 2])
                    ->asArray()->one();
                // 计算比例
                $tmpPercent = $unlineProblemNum == 0 ? '0%' : round(($tmpData['num'] / $unlineProblemNum) * 100) . '%';
                $singleData = $tmpData['num']."票"."，".$tmpPercent;
                array_push($dataArr['options_underline'], $singleData);

                // 总数统计
                $tmpPercent = $totalProblemNum == 0 ? '0%' : round(($option['totals'] / $totalProblemNum) * 100) . '%';
                $singleData = $option['totals'] . "票" . "，" . $tmpPercent;
                array_push($dataArr['options_total'], $singleData);
            }
            array_push($reArr, $dataArr);
        }

        $onlineDataShow = [];
        $underLineDataShow = [];
        $totalDataShow = [];
        foreach ($reArr as $dataShow) {
            $tmpOnline['id'] = $tmpUnderLine['id'] = $tmpTotal['id'] = $dataShow['problem_id'];
            $tmpOnline['key'] = $tmpUnderLine['key'] = $tmpTotal['key'] = $dataShow['title'];
            $tmpOnline['options'] = $dataShow['options_online'];
            $tmpUnderLine['options'] = $dataShow['options_underline'];
            $tmpTotal['options'] = $dataShow['options_total'];
            array_push($onlineDataShow, $tmpOnline);
            array_push($underLineDataShow, $tmpUnderLine);
            array_push($totalDataShow, $tmpTotal);
        }
        $re['online_data_show'] = $onlineDataShow;
        $re['underline_data_show'] = $underLineDataShow;
        $re['total_data_show'] = $totalDataShow;

        return $re;
    }

    /**
     * 生活号业主投票
     * @param $voteId 投票id
     * @param $memberId 会员id
     * @param $memberName 会员名称
     * @param $voteDetail 投票详情
     * @param $communityId 小区id
     * @author wenchao.feng
     * @return bool
     */
    public function doVote($voteId, $memberId, $memberName, $voteDetail, $communityId, $voteChannel = 'on', $room_id=0,$userId='')
    {

        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try{

            $canVote = $this->doCanVoting($memberId, $voteId, $voteChannel, $room_id);
            if (!empty($canVote['voting_status']) && $canVote['voting_status'] == 1) {
                // 可投票
                $voteDetArr = $voteDetail;//json_decode($voteDetail, true);
                // 数据检查
                foreach ($voteDetArr as $vote) {
                    if (empty($vote['problem_id'])) {
                        return '投票项不存在！';
                    }

                    if (empty($vote['options'])) {
                        return '您还有投票项未选择!';
                    }
                }
                // 进行投票 查询会员的房屋信息
//                if ($room_id > 0 ) {
//                    $room_id = $room_id;
//                } else {
//                    $memberRoomIds = MemberService::service()->getRommIdsByMemberId($memberId, $communityId);
//                    $room_id = $memberRoomIds[0] ? $memberRoomIds[0] : 0 ;
//                }

                $doVoteSuc = 0;
                foreach ($voteDetArr as $vote) {
                    $suc = 0;
                    foreach ($vote['options'] as $k => $v) {
                        $model = new PsVoteMemberDet(['scenario' => 'add']);
                        $params['vote_id'] = $voteId;
                        $params['problem_id'] = $vote['problem_id'];
                        $params['room_id'] = $room_id;
                        $params['option_id'] = $v['option_id'];
                        $params['member_id'] = $memberId;
                        $params['member_name'] = $memberName;
                        $params['user_id'] = $userId;
                        $params['vote_channel'] = $voteChannel == 'off' ? 2 : 1;

                        if ($model->load($params, '') && $model->validate() && $model->saveData()) {
                            $suc++;
                            $optionModel = PsVoteProblemOption::findOne($v['option_id']);
                            $optionModel->totals = $optionModel->totals + 1;
                            $optionModel->save();
                        }else{
                            //验证不通过
                            throw new Exception(array_values($model->errors)[0][0]);
                        }
//                        $model->vote_id     = $voteId;
//                        $model->problem_id  = $vote['problem_id'];
//                        $model->room_id     = $room_id ;
//                        $model->option_id   = $v['option_id'];
//                        $model->member_id   = $memberId;
//                        $model->member_name = $memberName;
//                        $model->vote_channel = $voteChannel == 'off' ? 2 : 1;
//                        $model->created_at  = time();
//                        if ($model->save()) {
                        // 给选项增加投票数量
//                            $suc++;
//                            $optionModel = PsVoteProblemOption::findOne($v['option_id']);
//                            $optionModel->totals = $optionModel->totals + 1;
//                            $optionModel->save();
//                        }
                    }

                    // 给问题增加投票数量
                    $problemModel = PsVoteProblem::findOne($vote['problem_id']);
                    if ($problemModel && $suc) {
                        $problemModel->totals = $problemModel->totals + 1;
                        $problemModel->save();
                        $doVoteSuc++;
                    }
                }
                if ($doVoteSuc) {
                    // 修改投票计数
                    $voteModel = PsVote::findOne($voteId);
                    $voteModel->totals = $voteModel->totals + 1;
                    $voteModel->save();
                    $transaction->commit();
                    return true;
                }

            } else {

                if ($canVote['voting_status'] == 2) {
                    if($voteChannel == 'off') {
                        return '该业主已参与投票，无法重复投票';
                    } else {
                        //已投票
                        return '已投票';
                    }
                }
                return !empty($canVote['voting_value']) ? $canVote['voting_value'] : '投票失败！';
            }

            return false;
        }catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /**
     * 小程序查询是否可以投票
     * @param $memberId
     * @param $voteId
     * @author wenchao.feng
     * @return array
     */
    private function doCanVoting($memberId, $voteId, $voteChannel = 'on', $roomId = 0)
    {
        $data = [
            'voting_status' => 0,
            'voting_value'  => '投票结果查询错误'
        ];
        $vote = PsVote::find()
            ->select(['community_id', 'permission_type', 'start_time', 'end_time','show_at'])
            ->where(['id' => $voteId])->asArray()->one();
        if ($vote) {
            $data = [
                'voting_status' => 1,
                'voting_value'  => ''
            ];
            //查询当前用户是否已投 权限类型 1每户一票 2每人一票 3指定业主投票
            if ($vote['permission_type'] ==  1) {//每户一票根据 投票id 房号id查询该房号是否投票
//                $voteDet = PsVoteMemberDet::find()
//                    ->where(['vote_id' => $voteId, 'member_id' => $memberId, 'room_id' => $roomId])
//                    ->asArray()
//                    ->one();
                $voteDet = PsVoteMemberDet::find()
                    ->where(['vote_id' => $voteId, 'room_id' => $roomId])
                    ->asArray()
                    ->one();
            } else if ($vote['permission_type'] ==  2) {//每人一票 根据投票id 用户id查询是否投票
                $voteDet = PsVoteMemberDet::find()
                    ->where(['vote_id' => $voteId, 'member_id' => $memberId])
                    ->asArray()
                    ->one();
            } else if ($vote['permission_type'] ==  3) {//指定业主  根据投票id 业主id 房号id 查询是否投票
                $voteDet = PsVoteMemberDet::find()
                    ->where(['vote_id' => $voteId, 'member_id' => $memberId, 'room_id' => $roomId])
                    ->asArray()
                    ->one();
            }
            if (!empty($voteDet)) {
                if($voteChannel=='off') {
                    //已投
                    $data = [
                        'voting_status' => 2,
                        'voting_value' => ''
                    ];
                } else {
                    if ($vote['end_time'] <= time()) {
                        //投票已结束
                        $data = [
                            'voting_status' => 5,
                            'voting_value'  => '当前投票活动已截止'
                        ];
                    } else {
                        //已投
                        $data = [
                            'voting_status' => 2,
                            'voting_value' => '投票成功，感谢您的参与'
                        ];
                    }
                }
            } else {
                //未投
//                if( $voteChannel == 'off' &&  ( $vote['end_time'] >= time() || $vote['show_at'] <= time())) {
                if( $voteChannel == 'off' &&  $vote['end_time'] <= time() ) {
                    //投票已结束
                    $data = [
                        'voting_status' => 5,
                        'voting_value'  => '当前投票活动已截止'
                    ];
                } elseif ( $voteChannel == 'on' && $vote['end_time'] <= time() ) {
                    //投票已结束
                    $data = [
                        'voting_status' => 5,
                        'voting_value'  => '当前投票活动已截止'
                    ];
                }  else {
                    //投票未结束
                    if ($vote['permission_type'] ==  1) {
                        //每户一票
//                        if($roomId == 0 ) {
//                            $roomIds = MemberService::service()->getRommIdsByMemberId($memberId, $vote['community_id']);
//                        }else {
//                            $roomIds = $roomId;
//                        }
                        $roomIds = $roomId;
                        $voteStatus = $this->roomIsVoted($voteId, $roomIds);
                        if ($voteStatus) {
                            $data = [
                                'voting_status' => 3,
                                'voting_value'  => '每户可投一票，您无权限参加'
                            ];
                        }
                    } else if ($vote['permission_type'] ==  3) {
                        $appointMembers = $this->getMemberIdsAppoint($voteId);
                        if (!in_array($memberId, $appointMembers)) {
                            $data = [
                                'voting_status' => 4,
                                'voting_value'  => '指定业主投票，您无权限参加'
                            ];
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 查询是否可以投票
     * @param $memberId
     * @param $voteId
     * @author wenchao.feng
     * @return array
     */
    private function canVoting($memberId, $voteId, $voteChannel = 'on', $roomId = 0)
    {
        $data = [
            'voting_status' => 0,
            'voting_value'  => '数据查询错误'
        ];
        $vote = PsVote::find()
            ->select(['community_id', 'permission_type', 'start_time', 'end_time','show_at'])
            ->where(['id' => $voteId])
            ->asArray()
            ->one();
        if ($vote) {
            $data = [
                'voting_status' => 1,
                'voting_value'  => ''
            ];
            //查询当前用户是否已投
            if (!empty($roomId)) {
                $voteDet = PsVoteMemberDet::find()
                    ->where(['vote_id' => $voteId, 'member_id' => $memberId, 'room_id' => $roomId])
                    ->asArray()
                    ->one();
            } else {
                $voteDet = PsVoteMemberDet::find()
                    ->where(['vote_id' => $voteId, 'member_id' => $memberId])
                    ->asArray()
                    ->one();
            }
            if ($voteDet) {
                if($voteChannel=='off') {
                    //已投
                    $data = [
                        'voting_status' => 2,
                        'voting_value' => ''
                    ];
                } else {
                    if ($vote['end_time'] <= time()) {
                        //投票已结束
                        $data = [
                            'voting_status' => 5,
                            'voting_value'  => '当前投票活动已截止'
                        ];
                    } else {
                        //已投
                        $data = [
                            'voting_status' => 2,
                            'voting_value' => '投票成功，感谢您的参与'
                        ];
                    }
                }
            } else {
                //未投
                if( $voteChannel == 'off' &&  ( $vote['end_time'] >= time() || $vote['show_at'] <= time())) {
                    //投票已结束
                    $data = [
                        'voting_status' => 5,
                        'voting_value'  => '投票必须在活动结束后未到公示时间'
                    ];
                } elseif ( $voteChannel == 'on' && $vote['end_time'] <= time() ) {
                    //投票已结束
                    $data = [
                        'voting_status' => 5,
                        'voting_value'  => '当前投票活动已截止'
                    ];
                }  else {
                    //投票未结束
                    if ($vote['permission_type'] ==  1) {
                        //每户一票
                        if($roomId == 0 ) {
                            $roomIds = MemberService::service()->getRommIdsByMemberId($memberId, $vote['community_id']);
                        }else {
                            $roomIds = $roomId;
                        }
                        $voteStatus = $this->roomIsVoted($voteId, $roomIds);
                        if ($voteStatus) {
                            $data = [
                                'voting_status' => 3,
                                'voting_value'  => '每户可投一票，您无权限参加'
                            ];
                        }
                    } elseif ($vote['permission_type'] ==  3) {
                        $appointMembers = $this->getMemberIdsAppoint($voteId, $roomId);
                        if (!in_array($memberId, $appointMembers)) {
                            $data = [
                                'voting_status' => 4,
                                'voting_value'  => '指定业主投票，您无权限参加'
                            ];
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * 查询指定会员的会员id组
     * @param $vote_id
     * @param int $room_id
     * @return array
     */
    private function getMemberIdsAppoint($vote_id, $room_id = 0)
    {
        $result = PsVoteMemberAppoint::find()->select(['member_id'])->where(['vote_id' => $vote_id]);
        if ($room_id) {
            $result->andWhere(['room_id' => $room_id]);
        }

        return $result->asArray()->column();
    }

    /**
     * 查询某户是否已经参与过某投票活动
     * @param $voteId
     * @param $roomIds
     * @return bool
     */
    private function roomIsVoted($voteId, $roomIds)
    {
        $memberDet = PsVoteMemberDet::find()->select(['id'])
            ->where(['vote_id' => $voteId, 'room_id' => $roomIds])
            ->asArray()->one();
        if ($memberDet) {
            return true;
        }

        return false;
    }

    private function getMemberAppoint($vote_id, $community_id)
    {
        $query = new Query();

        $models = $query->select([ 'B.name','B.mobile','B.sex','R.address','B.identity_type','B.`status`'])
            ->from(' ps_vote_member_appoint A')
            ->leftJoin('ps_room_user B','A.member_id=B.member_id  and B.room_id=A.room_id')
            ->leftJoin('ps_community_roominfo R','R.id=B.room_id')
            ->where(["A.vote_id"=>$vote_id])
            ->andWhere(["B.community_id"=>$community_id])
            ->all();

        if(!empty($models)) {
            foreach ($models as $key => $model) {
                $models[$key]['sex_desc'] = $model["sex"] == 1 ? "男" :( $model["sex"]==2 ?  "女" :"未设置");
                $models[$key]['identity_type_desc'] =  $model['identity_type']? PsCommon::getIdentityType($model['identity_type'],'key'):"-";
                $models[$key]['status_desc'] = $model['status']? PsCommon::getIdentityStatus($model['status']):"-";
                $models[$key]['mobile'] = PsCommon::isVirtualPhone($model['mobile']) === true ? '' : $model['mobile'];
            }
        }
        return !empty($models) ? $models : [];
    }

    private function getVoteProblem( $vote_id, $membeId = 0, $roomId = 0)
    {
        $query = new Query();
        $problems =  $query->select([ 'B.id as problem_id','B.option_type','B.title','B.vote_type','B.totals as problem_total',
            'A.title as option_title', 'A.image_url','A.option_desc','A.totals as option_total', 'A.id as option_id'])
            ->from(' ps_vote_problem_option A')
            ->leftJoin('ps_vote_problem B',' B.id=A.problem_id')
            ->where(["B.vote_id"=>$vote_id])
            ->all();
        $arr = [];
        if(!empty($problems) ) {
            $serial = 1;
            foreach ($problems as $problem) {
                $hasChecked = 0;
                $problemHasChecked = 0;
                unset($memberDetModel);
                if ($membeId) {
                    //查询此会员此问题是否参与投票
                    $memberDetModel = PsVoteMemberDet::find()
                        ->select(['id'])
                        ->where(['problem_id' => $problem['problem_id']])
                        ->andWhere(['member_id' => $membeId]);
                    if (!empty($roomId)) {
                        $memberDetModel->andWhere(['room_id' => $roomId]);
                    }
                    $res = $memberDetModel->asArray()->one();
                    if ($res) {
                        $problemHasChecked = 1;
                    }

                    //查询此会员已投的选项
                    $memberDetModel = PsVoteMemberDet::find()
                        ->select(['id'])
                        ->where(['problem_id' => $problem['problem_id']])
                        ->andWhere(['option_id' => $problem['option_id'], 'member_id' => $membeId]);
                    if (!empty($roomId)) {
                        $memberDetModel->andWhere(['room_id' => $roomId]);
                    }
                    $result = $memberDetModel->asArray()->one();
                    if ($result) {
                        $hasChecked = 1;
                    }
                }

                if( isset( $arr[$problem["problem_id"]])) {
                    $option =    [
                        "option_id" => $problem["option_id"],
                        "title"=>$problem["option_title"],
                        "image_url"=>$problem["image_url"],
                        "option_desc"=>$problem["option_desc"],
                        "total"=>$problem["option_total"],
                        "totals"=>$problem["problem_total"],
                        "scale"=> $problem["problem_total"]==0 ? '0%' :round(($problem["option_total"]/$problem["problem_total"])*100).'%',
                        'is_checked' => $hasChecked
                    ];
                    array_push( $arr[$problem["problem_id"]]["options"],$option);
                } else {
                    $arr[$problem["problem_id"]]["problem_has_checked"] = $problemHasChecked;
                    $arr[$problem["problem_id"]]["serial_no"]        = $serial;
                    $arr[$problem["problem_id"]]["problem_id"]       = $problem["problem_id"];
                    $arr[$problem["problem_id"]]["option_type"]      = $problem["option_type"];
                    $arr[$problem["problem_id"]]["option_type_desc"] = self::$Option_Type[$problem["option_type"]];

                    $arr[$problem["problem_id"]]["vote_type"]= $problem["vote_type"];
                    $arr[$problem["problem_id"]]["vote_type_desc"]= self::$Vote_Type[$problem["vote_type"]];
                    $arr[$problem["problem_id"]]["title"] = $problem["title"];
                    $arr[$problem["problem_id"]]["options"]  = [];
                    $arr[$problem["problem_id"]]["option_sort"] = [];
                    $option =  [
                        "option_id" => $problem["option_id"],
                        "title"=>$problem["option_title"],
                        "image_url"=>$problem["image_url"],
                        "option_desc"=>$problem["option_desc"],
                        "total"=>$problem["option_total"],
                        "totals"=>$problem["problem_total"],
                        "scale"=> $problem["problem_total"]==0 ? '0%' :round(($problem["option_total"]/$problem["problem_total"])*100).'%',
                        'is_checked' => $hasChecked
                    ];

                    array_push( $arr[$problem["problem_id"]]["options"],$option);
                    $serial++;
                }

            }
        }
        return !empty($arr) ? array_values($arr) : [];
    }

    // 新增投票
    public function addVote( $data, $userinfo = '')
    {
        $now_time = time();
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        $vote_status = 1;
//        $totals = 0;
        try {

            $start_time = !empty($data["start_time"]) ? strtotime($data["start_time"].":00")  : strtotime(date("Y-m-d H:i:00",$now_time));
            $end_time = strtotime($data["end_time"].":59");
            if($now_time<$start_time){
                $vote_status = 1;
            }
            if($now_time>$start_time && $now_time<$end_time){
                $vote_status = 2;
            }
            if($now_time>$end_time){
                $vote_status = 3;
            }
//            //计算应该有多少投票
//            switch($data["permission_type"]){
//                case 1: //每户一票
//                    $javaService = new JavaService();
//                    $javaParams['token'] = $data['token'];
//                    $javaParams['communityId'] = $data['community_id'];
//                    $javaResult = $javaService->roomList($javaParams);
//                    $totals = !empty($javaResult['totalSize'])?$javaResult['totalSize']:'';
//                    break;
//                case 2: //每人一票
//                    $javaService = new JavaService();
//                    $javaParams['token'] = $data['token'];
//                    $javaParams['communityId'] = $data['community_id'];
//                    $javaResult = $javaService->residentList($javaParams);
//                    $totals = !empty($javaResult['totalSize'])?$javaResult['totalSize']:'';
//                    break;
//                case 3: //指定业主投票
//                    $totals = count($data['appoint_members']);
//                    break;
//            }
            // 添加投票主题
            $voteArr = [
                "community_id" => $data["community_id"],
                "vote_name" => $data["vote_name"],
                "start_time" => $start_time,
                "end_time" => $end_time,
                "vote_desc" => $data["vote_desc"],
                "vote_status" => $vote_status,
//                "totals" => $totals,
//                "vote_type" => $data["vote_type"],
                "permission_type" => $data["permission_type"],
//                "show_at" => strtotime($data["show_at"].":59"),
                "status" => 1,
                "created_at" => $now_time
            ];
            $connection->createCommand()->insert('ps_vote', $voteArr)->execute();
            $voteId = $connection->getLastInsertID();
            /*投票类型 为指定类型的时候 需要添加指定人员*/
            if( $voteArr["permission_type"] == 3) {
                $memberArr = [];
                foreach ( $data["appoint_members"] as $member) {
                    $member['user_id'] = !empty($member['user_id'])?$member['user_id']:'';
                    array_push($memberArr, ["vote_id" => $voteId, "member_id" => $member["member_id"], 'room_id' => $member["room_id"],'user_id'=> $member['user_id'], "created_at" => $now_time]);
                }
                $connection->createCommand()->batchInsert('ps_vote_member_appoint',
                    ['vote_id', 'member_id', 'room_id', 'user_id','created_at'],
                    $memberArr
                )->execute();
            }

            foreach ( $data["problems"] as $problem) {
                /*添加投票问题*/
                $problemArr = [
                    "vote_id" => $voteId,
                    "option_type" => $problem["option_type"],
                    "option_num" => !empty($problem["option_num"]) ? $problem["option_num"] : 0,
                    "title" => $problem["title"],
//                    "vote_type" => $voteArr["vote_type"],
                    "created_at" => $now_time
                ];
                $connection->createCommand()->insert('ps_vote_problem', $problemArr)->execute();
                $problemId = $connection->getLastInsertID();
                $optionArrs = [];
                foreach ( $problem["options"] as $option) {
                    /*问题选项添加*/
                    $optionArr = [
                        "problem_id" => $problemId,
                        "title" => $option["title"],
                        "image_url" => !empty($option["image_url"]) ? $option["image_url"] : "",
                        "option_desc" => !empty($option["option_desc"]) ? $option["option_desc"] : "",
                    ];
                    array_push($optionArrs, $optionArr);
                }
                $connection->createCommand()->batchInsert('ps_vote_problem_option',
                    ['problem_id','title','image_url','option_desc'],
                    $optionArrs
                )->execute();
            }
            $transaction->commit();
            $re['vote_id'] = $voteId;
            $operate = [
                "community_id" =>$data['community_id'],
                "operate_menu" => "小区投票",
                "operate_type" => "新增投票",
                "operate_content" => '投票标题'.$data["vote_name"]
            ];
            OperateService::addComm($userinfo, $operate);
            //发送消息通知
            $sendParams['token'] = $data['token'];
            $sendParams['vote_id'] = $voteId;
            $sendParams['sendType'] = 1;
            $sendParams['trueName'] = $userinfo['trueName'];
            $sendParams['corpId'] = $userinfo['corpId'];
            self::sendMessage($sendParams);

            //java日志
            $javaService = new JavaService();
            $javaParam['moduleKey'] = "vote_module";
            $javaParam['token'] = $data['token'];
            $javaParam['content'] = "新增投票:".$data["vote_name"];
            $javaService->logAdd($javaParam);
            return $this->success($re);
        }catch (Exception $e) {
            return $this->failed('系统错误');
        }
    }

    /*
     *  发送消息通知 调用java接口
     *  input: vote_id token sendType(1预发布 2已公布) corpId trueName
     */
    public function sendMessage($params){

        if(empty($params['vote_id'])){
            return $this->failed('投票id必传');
        }
        $service = new JavaService();
        $model = PsVote::find()->select(['id','vote_name','permission_type','community_id'])->where(['=','id',$params['vote_id']])->asArray()->one();
        if( empty( $model)) {
            return $this->failed('未找到投票');
        }
        $userIdList = self::getSendMessageUser($model,$params);
        if(!empty($userIdList)){
            //发送消息
            switch($params['sendType']){
                case 1: //预发布
                    $content = '"'.$model['vote_name'].'"问卷投票已发布，期待您的参与';
                    $pushTime = date('Y-m-d H:i');
                    break;
                case 2: //已公布
                    $content = '"'.$model['vote_name'].'"问卷投票已发布公告，请您查看';
                    $pushTime = date('Y-m-d H:i');
                    break;
            }
            $sendParams['createPeople'] = $params['trueName'];
            $sendParams['appletFlag'] = true;
            $sendParams['corpId'] = $params['corpId'];
            $sendParams['appFlag'] = true;
            $sendParams['pushTime'] = $pushTime;
            $sendParams['tmallFlag'] = true;
            $sendParams['timestamp'] = time();
            $sendParams['token'] = $params['token'];
            $sendParams['userIdList'] = $userIdList;
            $sendParams['bizType'] = 'vote';
            $sendParams['bizId'] = $model['id'];
            $sendParams['title'] = "投票通知";
            $sendParams['content'] = $content;
            $sendResult = $service->messageInsert($sendParams);
        }
    }

    // 获得消息推送成员列表
    public function getSendMessageUser($model,$params){

        //获得人员
        $userIdList = [];
        if($params['sendType']==1){
            if($model['permission_type']==3){
                $appointResult = PsVoteMemberAppoint::find()->select(['user_id'])
                    ->where(['=','vote_id',$model['id']])
                    ->andWhere(['!=','user_id',''])
                    ->asArray()->all();
                if(!empty($appointResult)){
                    $userIdList = array_column($appointResult,'user_id');
                }
            }else{
                //获得java数据
                $service = new JavaService();
                $javaParams['token'] = $params['token'];
                $javaParams['communityId'] = $model['community_id'];
                $result = $service->residentSelectAllByCommunityId($javaParams);
                if(!empty($result['list'])){
                    $userIdList = array_column($result['list'],'memberId');
                }
            }
        }else{
            //投过票人员
            $voteMemberResult = PsVoteMemberDet::find()->select(['user_id'])->distinct()
                ->where(['=','vote_id',$model['id']])
                ->andWhere(['!=','user_id',''])
                ->asArray()->all();
            if(!empty($voteMemberResult)){
                $userIdList = array_column($voteMemberResult,'user_id');
            }

        }
        return $userIdList;
    }

    // 编辑投票结束时间
    public function editEndTime($data, $userinfo = '')
    {
        $connection = Yii::$app->db;
        /*上架或者进行中*/
        $model = $connection->createCommand("select * from ps_vote where id=:id",[":id"=>$data["vote_id"]])->queryOne();
        if( empty( $model)) {
            return $this->failed('未找到投票');
        }
        $end_time = strtotime($data["end_time"].":59");

        if( $model["end_time"] <= time()) {
            return $this->failed('已结束投票禁止修改时间');
        }
        if( $end_time >= $model["show_at"]) {
            return $this->failed('投票结束时间不能大于公示时间');
        }
        if( $end_time <= time()) {
            return $this->failed('投票结束时间不能小于当前时间');
        }
        $connection->createCommand()->update('ps_vote',
            ["end_time" => $end_time],
            "id=:id",
            [":id" => $data["vote_id"]]
        )->execute();
        $operate = [
            "community_id" =>$model['community_id'],
            "operate_menu" => "小区投票",
            "operate_type" => '修改截止时间',
            "operate_content" => '投票标题'.$model["vote_name"].'-截止时间:'.date('Y-m-d H:i',$end_time)
        ];
        OperateService::addComm($userinfo, $operate);
        return $this->success();

    }

    // 编辑投票结果
    public function editResult($data,$userinfo)
    {
        $connection = Yii::$app->db;
        /*上架或者进行中*/
        $model = $connection->createCommand("select * from ps_vote where id=:id",[":id"=>$data["vote_id"]])->queryOne();
        if( empty( $model)) {
            return $this->failed('未找到投票');
        }
        if($model["end_time"]>time()) {
            return $this->failed('投票未结束,暂不能编辑投票结果');
        }
//        if( $model["show_at"] < time()) {
//            return $this->failed('已过公示时间,不能编辑投票结果');
//        }
        $result =  $connection->createCommand("select id from ps_vote_result where vote_id=:vote_id",[":vote_id"=>$data["vote_id"]])->queryOne();
        if( empty( $result) ) {
            $connection->createCommand()->insert('ps_vote_result',
                [
                    "vote_id" => $data["vote_id"],
//                    "result_title" => $data["result_title"],
                    "result_content" => $data["result_content"],
                    "created_at" =>time(),
                ]
            )->execute();
            //发送消息通知
            $sendParams['token'] = $data['token'];
            $sendParams['vote_id'] = $data["vote_id"];
            $sendParams['sendType'] = 2;
            $sendParams['trueName'] = $userinfo['trueName'];
            $sendParams['corpId'] = $userinfo['corpId'];
            self::sendMessage($sendParams);
        } else {
            $connection->createCommand()->update('ps_vote_result',
                [
//                    "result_title" => $data["result_title"],
                    "result_content"=>$data["result_content"]],
                "vote_id=:vote_id",
                [":vote_id" => $data["vote_id"]]
            )->execute();
        }
        //修改投票状态为已公式
        $connection->createCommand()->update('ps_vote',
            [
                "vote_status"=>4
            ],
            "id=:vote_id",[":vote_id" => $data["vote_id"]
            ]
        )->execute();
        return $this->success();
    }

    // 查看投票结果
    public function showResult( $vote_id)
    {
        $result =  Yii::$app->db->createCommand("select id,vote_id,result_content,created_at from ps_vote_result where vote_id=:vote_id",[":vote_id"=>$vote_id])->queryOne();
        if( !empty( $result)) {
            $result["created_at"]= date("Y-m-d H:i:s",$result["created_at"]);
        }
        return empty($result) ? [] :$result ;
    }

    public function deleteResult($vote_id)
    {
        $connection = Yii::$app->db;
        /*上架或者进行中*/
        $model = $connection->createCommand("select * from ps_vote where id=:id",[":id"=>$vote_id])->queryOne();
        if( empty( $model)) {
            return $this->failed('未找到投票');
        }
        if($model["end_time"]>time()) {
            return $this->failed('投票未结束,不能删除投票结果');
        }
        if( $model["show_at"] < time()) {
            return $this->failed('已过公示时间,不能删除投票结果');
        }
        $connection->createCommand()->delete('ps_vote_result', "vote_id=:vote_id", [":vote_id" =>$vote_id])->execute();
        return $this->success();
    }

    // 编辑投票展示时间
    public function editShowTime($data, $userinfo = '')
    {
        $connection = Yii::$app->db;
        /*上架或者进行中*/
        $model = $connection->createCommand("select * from ps_vote where id=:id",[":id"=>$data["vote_id"]])->queryOne();
        if( empty( $model)) {
            return $this->failed('未找到投票');
        }
        $show_at = strtotime($data["show_at"].":59");

        if( $show_at <= time()) {
            return $this->failed('当前无法修改');
        }

        if( $model["end_time"] >= $show_at) {
            return $this->failed('公示时间必须大于结束时间');
        }
        $connection->createCommand()->update('ps_vote',
            ["show_at" => $show_at],
            "id=:id",
            [":id" => $data["vote_id"]]
        )->execute();
        $operate = [
            "community_id" =>$model['community_id'],
            "operate_menu" => "小区投票",
            "operate_type" => '修改投票公示时间',
            "operate_content" => '投票标题'.$model["vote_name"].'-公示时间:'.date('Y-m-d H:i',$show_at)
        ];
        OperateService::addComm($userinfo, $operate);
        return $this->success();
    }

    // 删除投票
    public function deleteVote($data, $userinfo = '')
    {
        $connection = Yii::$app->db;
        $vote_id = $data['vote_id'];
        /*上架或者进行中*/
        $model = $connection->createCommand("select * from ps_vote where id=:id",[":id" => $vote_id])->queryOne();
        if( empty( $model)) {
            return $this->failed('未找到投票');
        }

//        if( $model["status"]==1 ) {
//            return $this->failed('显示的投票 才能删除');
//        }

        $transaction = $connection->beginTransaction();
        try {
            $operate = [
                "community_id" => $model['community_id'],
                "operate_menu" => "小区投票",
                "operate_type" => '删除投票',
                "operate_content" => '投票标题'.$model["vote_name"]
            ];
            OperateService::addComm($userinfo, $operate);
            // 删除 限定人员
            $connection->createCommand()->delete('ps_vote_member_appoint',["vote_id"=>$vote_id])->execute();
            // 删除投票记录
            $connection->createCommand()->delete('ps_vote_member_det',["vote_id"=>$vote_id])->execute();
            //删除公式
            $connection->createCommand()->delete('ps_vote_result',["vote_id"=>$vote_id])->execute();
            // 删除问题//删除问题选项
            $connection->createCommand("delete A,B from ps_vote_problem A,ps_vote_problem_option B 
              where A.id=B.problem_id  and A.vote_id=:vote_id",[":vote_id"=>$vote_id])->execute();
            // 删除投票
            $connection->createCommand()->delete('ps_vote',["id"=>$vote_id])->execute();
            //添加java日志
            $javaService = new JavaService();
            $javaParam['moduleKey'] = "vote_module";
            $javaParam['token'] = $data['token'];
            $javaParam['content'] = "删除投票:".$model["vote_name"];
            $javaService->logAdd($javaParam);
            $transaction->commit();
            return $this->success();
        } catch (Exception $e) {
            return $this->failed('删除失败！');
        }
    }

    // 上/下架投票
    public function onOffVote($data, $userinfo = '')
    {
        $connection = Yii::$app->db;
        $model = $connection->createCommand("select * from ps_vote where id=:id",[":id"=>$data["vote_id"]])->queryOne();
        if( empty( $model)) {
            return $this->failed('未找到投票');
        }
        if ($model["status"] == $data["status"] ) {
            if($data["status"] == 1) {
                return $this->failed('当前投票已上架');
            } else {
                return $this->failed('当前投票已下架');
            }
        } else {
            $connection->createCommand()->update('ps_vote',
                ["status" => $data["status"]],
                "id=:id",
                [":id" => $data["vote_id"]]
            )->execute();
            $operate = [
                "community_id" =>$model['community_id'],
                "operate_menu" => "小区投票",
                "operate_type" => $data["status"]==1?'显示投票':'隐藏投票',
                "operate_content" => '投票标题'.$model["vote_name"]
            ];
            OperateService::addComm($userinfo, $operate);
            return $this->success(['id'=>$data['vote_id']]);
        }
    }

    //投票人员列表
    public function voteMemberList($data){

        $page = !empty($data["page"]) ?  $data["page"] : 1;
        $page = $page < 1 ? 1 : $page;
        $rows = !empty($data["rows"]) ?  $data["rows"] : Yii::$app->params['list_rows'];
        $data['pageNum'] = $page;
        $data['pageSize'] = $rows;
        $data["communityId"] = $data['community_id'];
        $result = ['totals'=>0,'list'=>[]];
        $vote = PsVote::find()->select(['permission_type','id'])->where(['=','id',$data['vote_id']])->andWhere(['=','community_id',$data['community_id']])->asArray()->one();
        if(!empty($vote)){
            $javaParams = $this->doJavaListParams($data,$vote);
            if($javaParams['transfer']){
                //获得java数据
                $javaService = new JavaService();
                $javaData = $javaService->residentList($javaParams);

                if(!empty($javaData['list'])){
                    $result = $this->doVoteMemberListData($javaData,$data);
                }
            }
        }
        return $result;
    }

    //投票用户列表 is_vote 是否投票 1已投票 2未投票
    public function doJavaListParams($data,$vote){
        $data['transfer'] = false;  //默认不调用java接口
        switch($vote['permission_type']){
            case 1:
            case 2: //非指定用户
                $data = self::doUnSpecifyJavaListParams($data,$vote);
                break;
            case 3: //指定用户
                $data = self::doSpecifyJavaListParams($data,$vote);
                break;
        }
        return $data;
    }

    //非指定业主参数
    public function doUnSpecifyJavaListParams($data,$vote){
        if(!empty($data['is_vote'])){
            if($data['is_vote']==1) {    //已投票
                //获得所有已经投票用户id
                $votedModel = PsVoteMemberDet::find()->select(['member_id','vote_channel'])->where(['=','vote_id',$vote['id']]);
                if(!empty($data['vote_channel'])){
                    $votedModel->andWhere(['=','vote_channel',$data['vote_channel']]);
                }
                if(!empty($data['start_time'])){
                    $votedModel->andWhere(['>=','created_at',strtotime($data['start_time'])]);
                }
                if(!empty($data['end_time'])){
                    $votedModel->andWhere(['<=','created_at',strtotime($data['end_time'])]);
                }
                $votedResult = $votedModel->asArray()->all();
                if(!empty($votedResult)){
                    $data['transfer'] = true;
                    $data['votedIds'] = array_column($votedResult,'member_id');
                }
            }else{
                //未投票
                $data['transfer'] = true;
                $votedModel = PsVoteMemberDet::find()->select(['member_id','vote_channel'])->where(['=','vote_id',$vote['id']]);
                $votedResult = $votedModel->asArray()->all();
                if(!empty($votedResult)){
                    $data['neVotedIds'] = array_column($votedResult,'member_id');
                }
            }
        }else{
            $data['transfer'] = true;
        }
        return $data;
    }

    //指定业主参数
    public function doSpecifyJavaListParams($data,$vote){
        // 获得已投票人员
        $data['votedIds'] = [];
        if(!empty($data['is_vote'])){
            if($data['is_vote']==1){    //已投票
                //获得所有已经投票用户id
                $votedModel = PsVoteMemberDet::find()->select(['member_id','vote_channel'])->where(['=','vote_id',$vote['id']]);
                if(!empty($data['vote_channel'])){
                    $votedModel->andWhere(['=','vote_channel',$data['vote_channel']]);
                }
                if(!empty($data['start_time'])){
                    $votedModel->andWhere(['>=','created_at',strtotime($data['start_time'])]);
                }
                if(!empty($data['end_time'])){
                    $votedModel->andWhere(['<=','created_at',strtotime($data['end_time'])]);
                }
                $votedResult = $votedModel->asArray()->all();
                if(!empty($votedResult)){
                    $data['transfer'] = true;
                    $data['votedIds'] = array_column($votedResult,'member_id');
                }
            }else{
                //未投票
                $votedModel = PsVoteMemberDet::find()->select(['member_id','vote_channel'])->where(['=','vote_id',$vote['id']]);
                $votedResult = $votedModel->asArray()->all();
                $memberIds = [];
                if(!empty($votedResult)){
                    $memberIds = array_column($votedResult,'member_id');
                }
                $query = new Query();
                $query->from('ps_vote_member_appoint')->select(['room_id','member_id'])->where(['=','vote_id',$vote['id']]);
                if(!empty($memberIds)){
                    $query->andWhere(['not in','member_id',$memberIds]);
                }
                $queryResult = $query->all();
                if(!empty($queryResult)){
                    $data['transfer'] = true;
                    $data['votedIds'] = array_column($queryResult,'member_id');
                }
            }
        }else{
            //获得指定人员（全部）
            $specifyResult = PsVoteMemberAppoint::find()->select(['member_id','room_id'])->where(['=','vote_id',$vote['id']])->asArray()->all();
            if(!empty($specifyResult)){
                $data['transfer'] = true;
                $data['votedIds'] = array_column($specifyResult,'member_id');
            }
        }
        return $data;
    }

    /*
     *  获得java数据 做自己显示数据
     *   javaData   java返回数据
     *   params     输入参数 ces
     */
    public function doVoteMemberListData($javaData,$params){
        $list = $voteChannel = $voteCreate = [];
        $totals = $javaData['totalSize'];
        $voteModel = PsVote::find()->select(['vote_status'])->where(['=','id',$params['vote_id']])->asArray()->one();
        if(!empty($params['is_vote'])){
            if($params['is_vote']==1){
                //已投票
                //获得投票数据
                $memberIds = array_column($javaData['list'],'residentId');
                $votedResult = PsVoteMemberDet::find()->select(['member_id','vote_channel','created_at'])
                    ->where(['=','vote_id',$params['vote_id']])->andWhere(['in','member_id',$memberIds])
                    ->asArray()->all();
                $voteChannel = array_column($votedResult,'vote_channel','member_id');
                $voteCreate = array_column($votedResult,'created_at','member_id');
                foreach($javaData['list'] as $key=>$value){
                    $element = [];
                    $element['home'] = !empty($value['home'])?$value['home']:'';
                    $element['member_id'] = !empty($value['residentId'])?$value['residentId']:'';
                    $element['user_id'] = !empty($value['memberId'])?$value['memberId']:'';
                    $element['room_id'] = !empty($value['roomId'])?$value['roomId']:'';
                    $element['name'] = !empty($value['name'])?$value['name']:'';
                    $element['mobile'] = !empty($value['mobile'])?$value['mobile']:'';
                    $element['memberTypeVal'] = !empty($value['memberTypeVal'])?$value['memberTypeVal']:'';
                    $element['vote_id'] = !empty($params['vote_id'])?$params['vote_id']:'';
                    $element['is_vote'] = 1;
                    $element['is_vote_msg'] = '是';
                    $element['vote_status'] = $voteModel['vote_status'];
                    $element['vote_channel_msg'] = !empty($voteChannel[$value['residentId']])?self::$Vote_Channel[$voteChannel[$value['residentId']]]:'';
                    $element['vote_create_msg'] = !empty($voteCreate[$value['residentId']])?date('Y/m/d H:i:s',$voteCreate[$value['residentId']]):'';
                    $list[] = $element;
                }
            }else{
                //未投票
                foreach($javaData['list'] as $key=>$value){
                    $element = [];
                    $element['home'] = !empty($value['home'])?$value['home']:'';
                    $element['member_id'] = !empty($value['residentId'])?$value['residentId']:'';
                    $element['user_id'] = !empty($value['memberId'])?$value['memberId']:'';
                    $element['room_id'] = !empty($value['roomId'])?$value['roomId']:'';
                    $element['name'] = !empty($value['name'])?$value['name']:'';
                    $element['mobile'] = !empty($value['mobile'])?$value['mobile']:'';
                    $element['memberTypeVal'] = !empty($value['memberTypeVal'])?$value['memberTypeVal']:'';
                    $element['vote_id'] = !empty($params['vote_id'])?$params['vote_id']:'';
                    $element['is_vote'] = 2;
                    $element['is_vote_msg'] = '否';
                    $element['vote_status'] = $voteModel['vote_status'];
                    $element['vote_channel_msg'] = '';
                    $element['vote_create_msg'] = '';
                    $list[] = $element;
                }
            }
        }else{

            //获得投票数据
            $memberIds = array_column($javaData['list'],'residentId');
            $votedResult = PsVoteMemberDet::find()->select(['member_id','vote_channel','created_at'])
                ->where(['=','vote_id',$params['vote_id']])->andWhere(['in','member_id',$memberIds])
                ->asArray()->all();
            if(!empty($votedResult)){
                $voteChannel = array_column($votedResult,'vote_channel','member_id');
                $voteCreate = array_column($votedResult,'created_at','member_id');
            }
            foreach($javaData['list'] as $key=>$value){
                $element = [];
                $element['home'] = !empty($value['home'])?$value['home']:'';
                $element['member_id'] = !empty($value['residentId'])?$value['residentId']:'';
                $element['user_id'] = !empty($value['memberId'])?$value['memberId']:'';
                $element['room_id'] = !empty($value['roomId'])?$value['roomId']:'';
                $element['name'] = !empty($value['name'])?$value['name']:'';
                $element['mobile'] = !empty($value['mobile'])?$value['mobile']:'';
                $element['memberTypeVal'] = !empty($value['memberTypeVal'])?$value['memberTypeVal']:'';
                $element['vote_id'] = !empty($params['vote_id'])?$params['vote_id']:'';
                $element['is_vote'] = !empty($voteCreate[$value['residentId']])?1:2;
                $element['is_vote_msg'] = !empty($voteCreate[$value['residentId']])?"是":'否';
                $element['vote_status'] = $voteModel['vote_status'];
                $element['vote_channel_msg'] = !empty($voteChannel[$value['residentId']])?self::$Vote_Channel[$voteChannel[$value['residentId']]]:'';
                $element['vote_create_msg'] = !empty($voteCreate[$value['residentId']])?date('Y/m/d H:i:s',$voteCreate[$value['residentId']]):'';
                $list[] = $element;
            }
        }
        return ['totals'=>$totals,'list'=>$list];
    }

    public function showMember($data)
    {
        $page = !empty($data["page"]) ?  $data["page"] : 1;
        $page = $page < 1 ? 1 : $page;
        $rows = !empty($data["rows"]) ?  $data["rows"] : Yii::$app->params['list_rows'];
        $db = Yii::$app->db;
        $result = ['all_total'=>0,'vote_total'=>0,'totals'=>0,'list'=>[]];

        $vote = $db->createCommand("select id,community_id,permission_type from ps_vote where id=:id",[":id"=>$data["vote_id"]])->queryOne();
        if( $vote["permission_type"]==2 ) {
            $result = $this->getMemberVote($data,$vote,$page,$rows,'');
        } elseif( $vote["permission_type"]==1 ) {
            $result = $this->getHouseVote($data,$vote,$page,$rows,'');
        } elseif ( $vote["permission_type"]==3 ) {
            $result = $this->getAppiontVote($data,$vote,$page,$rows,'');
        }
        return $result;
    }

    private function getAppiontVote($data, $vote, $page = 1, $rows, $type = '')
    {
        $where = " 1=1 ";
        $param = [];

        if (!empty($data["group"])) {
            $where .= " and r.`group`=:group";
            $param = array_merge($param,[":group"=>$data["group"]]);
        }

        if (!empty($data["building"])) {
            $where .= " and r.`building`=:building";
            $param = array_merge($param,[":building"=>$data["building"]]);
        }

        if (!empty($data["room"])) {
            $where .= " and r.`room`=:room";
            $param = array_merge($param,[":room"=>$data["room"]]);
        }

        if (!empty($data["unit"])) {
            $where .= " and  r.`unit`=:unit";
            $param = array_merge($param,[":unit"=>$data["unit"]]);
        }

        if (!empty($data["member_name"])) {
            $where .= " and M.`name` like :name";
            $param = array_merge($param,[":name"=>'%'.$data["member_name"].'%']);
        }

        if (!empty($data["member_mobile"])) {
            $where .= " and  M.`mobile` like :mobile";
            $param = array_merge($param,[":mobile"=>'%'.$data["member_mobile"].'%']);
        }

        if (!empty($data["vote_time_start"])) {
            $start_time = strtotime(date("Y-m-d H:i:00",strtotime($data["vote_time_start"])));
            $where .= " and  B.vote_time >= :vote_time_start";
            $param = array_merge($param,[":vote_time_start"=>$start_time]);
        }

        if (!empty($data["vote_time_end"])) {
            $end_time = strtotime(date("Y-m-d H:i:59",strtotime($data["vote_time_end"])));
            $where .= " and  B.vote_time <= :vote_time_end";
            $param = array_merge($param,[":vote_time_end"=>$end_time]);
        }

        $param = array_merge($param,[":vote_id"=>$vote["id"]]);
        $having = '';
        if (!empty($data["is_vote"])) {
            if($data["is_vote"] == 1  ) {
                $having = ' having is_vote >0';
            }elseif ($data["is_vote"] == 2) {
                $having = ' having is_vote is null';
            }
        }

        if ($data["vote_channel"] == 1 || $data["vote_channel"] == 2 ) {
            if($having) {
                $having .= ' and  vote_channel='.$data["vote_channel"];
            } else {
                $having .= ' having  vote_channel='.$data["vote_channel"];
            }
        }

        $sql = " select count(D.member_id)  from (select A.member_id, B.member_id as is_vote,B.vote_channel   from
        (select member_id,room_id from ps_vote_member_appoint where vote_id=:vote_id  group by member_id) A
        left join (select member_id,created_at as vote_time,vote_channel from ps_vote_member_det where vote_id=:vote_id  group by member_id) B  on A.member_id = B.member_id
        left join  ps_community_roominfo  r on r.id=  A.room_id
        left join ps_room_user M on M.room_id=A.room_id and A.member_id=M.member_id where ".$where.$having.") D";
        $count = Yii::$app->db->createCommand($sql,$param)->queryScalar();
        if ($count == 0 ) {
            return  array_merge( ["list" => [], 'totals' => $count],$this->getCountTotal($vote));
        }

        $page = $page > ceil($count / $rows) ? ceil($count / $rows) : $page;
        $limit = ($page - 1) * $rows;

        if ($type == 'all') {
            $limit = 0;
            $rows = $count;
        }

        $sql = "select A.member_id,A.room_id,r.`group`,r.building,r.unit,r.room,r.charge_area,M.mobile,M.name,M.identity_type,
          M.`status`,B.member_id as is_vote,B.vote_time,B.vote_channel from
        (select member_id,room_id from ps_vote_member_appoint where vote_id=:vote_id  group by member_id) A
        left join (select member_id,created_at as vote_time ,vote_channel from ps_vote_member_det where vote_id=:vote_id  group by member_id) B  on A.member_id = B.member_id
        left join  ps_community_roominfo  r on r.id=  A.room_id
           left join ps_room_user M on M.room_id=A.room_id and A.member_id=M.member_id  where ".$where.$having." limit $limit,$rows";;
        $models = Yii::$app->db->createCommand($sql,$param)->queryAll();
        if (! empty($models)) {
            foreach ($models as $key => $model) {
                $models[$key]["identity_type_desc"] = $model["identity_type"] ? PsCommon::getIdentityType($model["identity_type"], 'key') : "";
                $models[$key]["vote_time"] = $model["vote_time"] > 0 ? date("Y-m-d H:i:s", $model["vote_time"]) : "";
                $models[$key]["is_vote"] = $model["is_vote"] > 0 ? "是" : "否";
                $models[$key]["vote_channel_desc"] =  isset( self::$Vote_Channel[$model['vote_channel']]) ? self::$Vote_Channel[$model['vote_channel']] : '';
                $models[$key]['mobile'] = PsCommon::isVirtualPhone($model['mobile']) === true ? '' : $model['mobile'];
            }
        }
        return  array_merge( ["list" => $models, 'totals' => $count],$this->getCountTotal($vote));
    }

    private function  getHouseVote($data, $vote, $page, $rows, $type = '')
    {
        $where = " 1=1 ";
        $param = [];
        if ($data["group"]) {
            $where .= " and r.`group`=:group";
            $param = array_merge($param,[":group"=>$data["group"]]);
        }

        if ($data["building"]) {
            $where .= " and r.`building`=:building";
            $param = array_merge($param,[":building"=>$data["building"]]);
        }

        if ($data["room"]) {
            $where .= " and r.`room`=:room";
            $param = array_merge($param,[":room"=>$data["room"]]);
        }

        if ($data["unit"]) {
            $where .= " and r.`unit`=:unit";
            $param = array_merge($param,[":unit"=>$data["unit"]]);
        }

        if ($data["vote_time_start"]) {
            $start_time = strtotime(date("Y-m-d H:i:00",strtotime($data["vote_time_start"])));
            $where .= " and B.vote_time >= :vote_time_start";
            $param = array_merge($param,[":vote_time_start"=>$start_time]);
        }

        if ($data["vote_time_end"] ) {
            $end_time = strtotime(date("Y-m-d H:i:59",strtotime($data["vote_time_end"])));
            $where .= " and B.vote_time <= :vote_time_end";
            $param = array_merge($param,[":vote_time_end"=>$end_time]);
        }

        $param = array_merge($param,[":vote_id"=>$vote["id"]]);
        $param = array_merge($param,[":community_id"=>$vote["community_id"]]);
        $having = '';
        if ($data["is_vote"]) {
            if($data["is_vote"] ==1  ) {
                $having = ' having is_vote >0';
            }elseif ($data["is_vote"]==2) {
                $having = ' having is_vote is null';
            }
        }

        if ($data["vote_channel"] == 1 || $data["vote_channel"] == 2) {
            if ($having) {
                $having .= ' and  vote_channel='.$data["vote_channel"];
            } else {
                $having .= ' having  vote_channel='.$data["vote_channel"];
            }
        }

        $sql = " select count(D.id) from (select r.id,B.is_vote,B.vote_channel from
                  (select id,`group`,building,room,unit,charge_area from ps_community_roominfo  where community_id=:community_id) r
                  left join (  
                  select md.room_id,md.member_id as is_vote,md.created_at as vote_time, m.mobile,m.name,m.identity_type,md.vote_channel
                   from ps_vote_member_det md  left join ps_room_user m on m.member_id=md.member_id and m.room_id=md.room_id
	                            where md.vote_id=:vote_id group by md.room_id ) B on r.id=B.room_id  where ".$where.$having." ) D";

        $count = Yii::$app->db->createCommand($sql, $param)->queryScalar();
        if ($count == 0 ) {
            return  array_merge( ["list" => [], 'totals' => $count], $this->getCountTotal($vote));
        }
        $page = $page > ceil($count / $rows) ? ceil($count / $rows) : $page;
        $limit = ($page - 1) * $rows;
        if ($type == 'all') {
            $limit = 0;
            $rows = $count;
        }
        $sql = " select r.id as room_id,r.group,r.building,r.unit,r.room,B.is_vote,B.vote_time,B.member_id,B.mobile,B.name,B.identity_type,r.charge_area,B.vote_channel from
                (select id,`group`,building,room,unit,charge_area from ps_community_roominfo  where community_id=:community_id) r
                left join (  select  md.member_id as member_id,md.room_id,md.member_id as is_vote,md.created_at as vote_time,m.mobile,m.name,m.identity_type,md.vote_channel from ps_vote_member_det md
	            left join ps_room_user m on m.room_id=md.room_id and m.member_id=m.member_id where md.vote_id=:vote_id  group by md.room_id
	          ) B on r.id=B.room_id where ".$where.$having." limit $limit,$rows";
        $models = Yii::$app->db->createCommand($sql,$param)->queryAll();
        if (! empty($models)) {
            //zhd 因为查询每户一票的时候结果查错了姓名手机号  为了不更改上面复杂的sql 在结果集上面根据用户Id再查询用户表来对名字手机做更正
            $member_ids = '';
            $mmodels = [];
            $j = 0;
            foreach ($models as $k => $v) {
                if (!empty($v['member_id'])) {
                    $member_ids.= $j == 0 ? $v['member_id'] : ','.$v['member_id'];
                    $j++;
                }
            }
            if (!empty($member_ids)) {
                $msql = "SELECT id,name,mobile FROM `ps_member` where id in (".$member_ids.")";
                $mmodels = Yii::$app->db->createCommand($msql)->queryAll();
            }
            foreach ($models as $key => $model) {
                $models[$key]["name"] = $model["name"] ? $model["name"] : "";
                $models[$key]["mobile"] = $model["mobile"] ? $model["mobile"] : "";
                if (!empty($mmodels)) {foreach ($mmodels as $k => $v) {
                    if ($v['id'] == $model["member_id"]) {
                        $models[$key]["name"] = $v["name"] ? $v["name"] : "";
                        $models[$key]["mobile"] = $v["mobile"] ? $v["mobile"] : "";
                    }
                }}
                $models[$key]["mobile"] = PsCommon::isVirtualPhone($models[$key]["mobile"]) === true ? '' : $models[$key]["mobile"];
                $models[$key]["identity_type_desc"] = $model["identity_type"] ? PsCommon::getIdentityType($model["identity_type"], 'key') : "";
                $models[$key]["vote_time"] = $model["vote_time"] > 0 ? date("Y-m-d H:i:s", $model["vote_time"]) : "";
                $models[$key]["is_vote"] = $model["is_vote"] > 0 ? "是" : "否";
                $models[$key]["vote_channel_desc"] =  isset( self::$Vote_Channel[$model['vote_channel']]) ? self::$Vote_Channel[$model['vote_channel']] : '';
            }
        }
        return  array_merge(["list" => $models, 'totals' => $count], $this->getCountTotal($vote));
    }

    private function getMemberVote($data, $vote, $page, $rows, $type)
    {
        $where = "1=1";
        $param = [];
        if($data["group"]) {
            $where .= " and r.`group`=:group";
            $param = array_merge($param,[":group"=>$data["group"]]);
        }
        if($data["building"]) {
            $where .= " and  r.`building`=:building";
            $param = array_merge($param,[":building"=>$data["building"]]);
        }
        if($data["room"]) {
            $where .= " and r.`room`=:room";
            $param = array_merge($param,[":room"=>$data["room"]]);
        }
        if($data["unit"]) {
            $where .= " and  r.`unit`=:unit";
            $param = array_merge($param,[":unit"=>$data["unit"]]);
        }
        if($data["member_name"]) {
            $where .= " and m.`name` like :name";
            $param = array_merge($param,[":name"=>'%'.$data["member_name"].'%']);
        }
        if( $data["member_mobile"] ) {
            $where .= " and m.`mobile` like :mobile";
            $param = array_merge($param,[":mobile"=>'%'.$data["member_mobile"].'%']);
        }
        if( $data["vote_time_start"] ) {
            $start_time = strtotime(date("Y-m-d H:i:00",strtotime($data["vote_time_start"])));
            $where .= " and B.vote_time >= :vote_time_start";
            $param = array_merge($param,[":vote_time_start"=>$start_time]);
        }
        if( $data["vote_time_end"] ) {
            $end_time = strtotime(date("Y-m-d H:i:59",strtotime($data["vote_time_end"])));
            $where .= " and B.vote_time <= :vote_time_end";
            $param = array_merge($param,[":vote_time_end"=>$end_time]);
        }
        $param = array_merge($param,[":vote_id"=>$vote["id"]]);
        $having = '';
        if( $data["is_vote"] ) {
            if($data["is_vote"] ==1  ) {
                $having = ' having is_vote >0';
            }elseif ($data["is_vote"]==2) {
                $having = ' having is_vote is null';
            }
        }
        if( $data["vote_channel"]==1 || $data["vote_channel"]==2 ) {
            if($having) {
                $having .= ' and  vote_channel='.$data["vote_channel"];
            } else {
                $having .= ' having  vote_channel='.$data["vote_channel"];
            }
        }

        $param = array_merge($param,[":community_id"=>$vote["community_id"]]);
        $sql = " select count(D.member_id)  from (select mr.member_id,B.member_id as is_vote,B.vote_channel from
           (select * from ps_room_user  where community_id=:community_id and status=2 group by member_id) mr
          left join (select  member_id,created_at as vote_time,vote_channel from ps_vote_member_det where vote_id=:vote_id  group by member_id) B on mr.member_id=B.member_id
          left join ps_member m on mr.member_id=m.id
          left join ps_community_roominfo r on  r.id=mr.room_id where ".$where.$having.") D";
        $count = Yii::$app->db->createCommand($sql,$param)->queryScalar();
        if($count == 0 ) {
            return  array_merge( ["list" => [], 'totals' => $count],$this->getCountTotal($vote));
        }
        $page = $page > ceil($count / $rows) ? ceil($count / $rows) : $page;
        $limit = ($page - 1) * $rows;
        if($type=='all') {
            $limit = 0;
            $rows = $count;
        }
        $sql = "select mr.member_id,mr.room_id,r.`group`,r.building,r.unit,r.room,m.mobile,mr.name,mr.identity_type,mr.`status`,B.member_id as is_vote,B.vote_time,B.vote_channel from 
          (select * from ps_room_user  where community_id=:community_id and status=2  group by member_id) mr
            left join (select   member_id,created_at as vote_time,vote_channel from ps_vote_member_det where vote_id=:vote_id  group by member_id) B on mr.member_id=B.member_id
            left join ps_member m on mr.member_id=m.id
            left join ps_community_roominfo r on  r.id=mr.room_id where ".$where.$having." limit $limit,$rows";
        $models = Yii::$app->db->createCommand($sql,$param)->queryAll();
        if(  ! empty($models)) {
            foreach ($models as $key=>$model) {
                $models[$key]["identity_type_desc"] =  $model["identity_type"] ? PsCommon::getIdentityType($model["identity_type"],'key') : "";
                $models[$key]["vote_time"] =  $model["vote_time"]>0 ? date("Y-m-d H:i:s",$model["vote_time"]): "";
                $models[$key]["is_vote"] =  $model["is_vote"]>0 ? "是": "否";
                $models[$key]["vote_channel_desc"] =  isset( self::$Vote_Channel[$model['vote_channel']]) ? self::$Vote_Channel[$model['vote_channel']] : '';
                $models[$key]["mobile"] = PsCommon::isVirtualPhone($model['mobile']) ? '' : $model['mobile'];
            }
        }
        return  array_merge( ["list" => $models, 'totals' => $count],$this->getCountTotal($vote));
    }

    public function exportVote($data)
    {
        $vote = Yii::$app->db->createCommand("select id,community_id,permission_type from ps_vote where id=:id",[":id"=>$data["vote_id"]])->queryOne();
        $result =['all_total'=>0,'vote_total'=>0,'totals'=>0,'list'=>[]];
        if( $vote["permission_type"]==2 ) {
            $result = $this->getMemberVote($data,$vote,1,1,'all');
        } elseif( $vote["permission_type"]==1 ) {
            $result = $this->getHouseVote($data,$vote,1,1,'all');
        } elseif ( $vote["permission_type"]==3 ) {
            $result = $this->getAppiontVote($data,$vote,1,1,'all');
        }
        return $result;
    }

    public function getCountTotal($vote)
    {
        if( $vote["permission_type"]==2 ) {
            $all_total =Yii::$app->db->createCommand("select count( distinct member_id)  as total from ps_room_user where community_id=:community_id and status=2",[":community_id"=>$vote["community_id"]])->queryScalar();
            $on  =Yii::$app->db->createCommand("select count(distinct member_id ) as total from ps_vote_member_det where vote_id=:vote_id and vote_channel=1",[":vote_id"=>$vote["id"]])->queryScalar();
            $off  =Yii::$app->db->createCommand("select count(distinct member_id ) as total from ps_vote_member_det where vote_id=:vote_id and vote_channel=2",[":vote_id"=>$vote["id"]])->queryScalar();
            $on = $on ? $on : 0;
            $off = $off ? $off : 0;
            return [
                "all_total"=> $all_total ? $all_total : 0 ,
                "on_vote_total"=>$on ,
                "off_vote_total"=>$off,
                "vote_total"=> ($on+$off),
            ];
        } elseif( $vote["permission_type"]==1 ) {
            $all = Yii::$app->db->createCommand("select count(id) as total,sum(charge_area) as area from ps_community_roominfo where community_id=:community_id",[":community_id"=>$vote["community_id"]])->queryOne();
            $on =   Yii::$app->db->createCommand("select count(A.room_id) as total,sum(r.charge_area)  as area from (select room_id 
            from ps_vote_member_det where  vote_id=:vote_id and vote_channel=1 group by room_id) A
          left join ps_community_roominfo r on r.id=A.room_id",[":vote_id"=>$vote["id"]])->queryOne();

            $off  =   Yii::$app->db->createCommand("select count(A.room_id)  as total,sum(r.charge_area)  as area from (select room_id 
            from ps_vote_member_det where  vote_id=:vote_id and vote_channel=2 group by room_id) A
          left join ps_community_roominfo r on r.id=A.room_id",[":vote_id"=>$vote["id"]])->queryOne();

            $on_total = $on['total'] ?  $on['total'] : 0;
            $off_total = $off['total'] ?  $off['total'] : 0;
            $on_area = $on['area'] ?  $on['area'] : 0.00;
            $off_area = $off['area'] ?  $off['area'] : 0.00;
            return  [
                'all_total'=> $all['total'] ? $all['total'] : 0,
                'on_vote_total'=> $on_total ,
                'off_vote_total'=> $off_total,
                "vote_total"=>($on_total+$off_total),
                'all_area'=>  $all['area'] ? $all['area'] : 0,
                'on_vote_area'=> $on_area,
                'off_vote_area'=> $off_area,
                'vote_area'=>($on_area+$off_area),
            ];
        } elseif ( $vote["permission_type"]==3 ) {
            $all = Yii::$app->db->createCommand("select count(A.id) as total,sum(R.charge_area) as area  from
                  (select * from ps_vote_member_appoint where vote_id=:vote_id group by member_id) A
                left join ps_community_roominfo R on R.id=A.room_id;",["vote_id"=>$vote["id"]] )->queryOne();
            $on  = Yii::$app->db->createCommand("select count(A.id) as total,sum(R.charge_area) as area from 
            (select * from ps_vote_member_det where vote_id=:vote_id and vote_channel=1 group by member_id) A 
            left join ps_community_roominfo R on R.id=A.room_id ",[":vote_id"=>$vote["id"]])->queryOne();
            $off = Yii::$app->db->createCommand("select count(A.id) as total,sum(R.charge_area) as area from
        (select * from ps_vote_member_det where vote_id=:vote_id and vote_channel=2 group by member_id) A 
        left join ps_community_roominfo R on R.id=A.room_id ",[":vote_id"=>$vote["id"]])->queryOne();

            $on_total = $on['total'] ?  $on['total'] : 0;
            $off_total = $off['total'] ?  $off['total'] : 0;
            $on_area = $on['area'] ?  $on['area'] : 0.00;
            $off_area = $off['area'] ?  $off['area'] : 0.00;

            return  [
                'all_total'=> $all['total'] ? $all['total'] : 0,
                'on_vote_total'=> $on_total ,
                'off_vote_total'=> $off_total,
                "vote_total"=>($on_total+$off_total),
                'all_area'=>  $all['area'] ? $all['area'] : 0,
                'on_vote_area'=> $on_area,
                'off_vote_area'=> $off_area,
                'vote_area'=>($on_area+$off_area),
            ];
        }
    }

    //投票列表详情
    public function voteListDetail($voteId, $memberId, $roomId){
        $params['id'] = $voteId;
        $model = new PsVote(['scenario'=>'detail']);
        if($model->load($params,"") && $model->validate()){
            $detail = $model->getDetail($params);
            $result = self::doVoteListDetail($detail,$memberId,$roomId);
            return $this->success($result);
        }else{
            return $this->failed($this->getError($model));
        }
    }

    //做投票列表详情数据
    public function doVoteListDetail($detail,$memberId,$roomId){

        $data = [];
        switch($detail['permission_type']){
            case 1: //每户一票
                //获得投票记录
                $votedResult = PsVoteMemberDet::find()
                    ->select(['vote_id','problem_id','option_id','member_id','room_id',"group_concat(vote_id,problem_id,option_id) as onlyId"])
                    ->where(['=','vote_id',$detail['id']])->andWhere(['=','room_id',$roomId])
                    ->asArray()->all();
                break;
            case 2: //每人一票
                //获得投票记录
                $votedResult = PsVoteMemberDet::find()
                    ->select(['vote_id','problem_id','option_id','member_id','room_id',"group_concat(vote_id,problem_id,option_id) as onlyId"])
                    ->where(['=','vote_id',$detail['id']])->andWhere(['=','member_id',$memberId])
                    ->andWhere(['=','room_id',$roomId])->asArray()->all();
                break;
            case 3: //指定业主投票
                //获得投票记录
                $votedResult = PsVoteMemberDet::find()
                    ->select(['vote_id','problem_id','option_id','member_id','room_id',"group_concat(vote_id,problem_id,option_id) as onlyId"])
                    ->where(['=','vote_id',$detail['id']])->andWhere(['=','member_id',$memberId])
                    ->andWhere(['=','room_id',$roomId])->asArray()->all();
                break;
        }

        $voteArr = [];
        $data['member_id'] = $memberId;
        $data['room_id'] = $roomId;
        $data['id'] = !empty($detail['id'])?$detail['id']:'';
        $data['vote_name'] = !empty($detail['vote_name'])?$detail['vote_name']:'';
        $data['problem'] = [];
        $data['is_check'] = 0;
        if(!empty($votedResult[0]['onlyId'])){
            //投过票
            $voteArr = explode(",",$votedResult[0]['onlyId']);
            $data['is_check'] = 1;
        }
        if(!empty($detail['problem'])){
            foreach($detail['problem'] as $key=>$value){
                $problemEle = [];
                $problemEle['id'] = !empty($value['id'])?$value['id']:'';
                $problemEle['title'] = !empty($value['title'])?$value['title']:'';
                $problemEle['option_type'] = !empty($value['option_type'])?$value['option_type']:'';
                $problemEle['option_type_msg'] = !empty($value['option_type'])?self::$Option_Type[$value['option_type']]:'';
                $problemEle['option'] = [];
                if(!empty($value['option'])){
                    foreach($value['option'] as $k=>$v){
                        $optionEle = [];
                        $optionEle['id'] = !empty($v['id'])?$v['id']:'';
                        $optionEle['title'] = !empty($v['title'])?$v['title']:'';
                        $optionEle['image_url'] = !empty($v['image_url'])?$v['image_url']:'';
                        $optionEle['option_desc'] = !empty($v['option_desc'])?$v['option_desc']:'';
                        $onlyId = $detail['id'].$value['id'].$v['id'];
                        $optionEle['is_check'] = 0;
                        if(in_array($onlyId,$voteArr)){
                            $optionEle['is_check'] = 1;
                        }
                        $problemEle['option'][] = $optionEle;
                    }
                }
                $data['problem'][] = $problemEle;
            }
        }
        return $data;
    }


    public function showMemberDet($vote_id, $member_id, $room_id)
    {
        $db = Yii::$app->db;
        $vote = $db->createCommand("select vote_name,vote_desc from ps_vote where id=:vote_id",[":vote_id"=>$vote_id])->queryOne();
        $vote["problems"]  = $this->getVoteProblem($vote_id, $member_id);
        /**********zhd 修改option选中错误的bug 在不动原有的代码逻辑下在查询的问题结果集基础上再修改option选中的问题s (如果$this->getVoteProblem改好了 就把我注释的s-e的代码删掉)**************/
        if (!empty($vote_id) & !empty($member_id) & !empty($room_id) & !empty($vote["problems"])) {
            $vote["problems"] = $this->getVoteProblemOption($vote_id, $member_id, $room_id, $vote["problems"]);
        }
        /**********zhd e **************/
        if($member_id > 0 ) {
            $vote["member_info"] = $db->createCommand("
        select b.name,b.identity_type,b.mobile,r.`group`,r.building,r.unit,r.room,r.charge_area 
              from ps_room_user b  
              left join ps_community_roominfo r on  r.id=b.room_id where b.room_id=:room_id and b.member_id=:member_id ",
                [":room_id"=>$room_id,":member_id"=>$member_id])->queryOne();
            $vote["member_info"]["identity_type_desc"] = $vote["member_info"]["identity_type"] ? PsCommon::getIdentityType( $vote["member_info"]["identity_type"], 'key') : "";
            $vote["member_info"]["mobile"] = PsCommon::isVirtualPhone($vote["member_info"]["mobile"]) ? '' : $vote["member_info"]["mobile"];
        }
        return $vote;
    }

    // 查询已选中的option项 zhd
    private function getVoteProblemOption( $vote_id, $member_id, $room_id, $vote)
    {
        $db = Yii::$app->db;
        //查询该问题用户选中的option
        $options = $db->createCommand("select option_id FROM ps_vote_member_det where vote_id =:vote_id and member_id=:member_id and room_id=:room_id",[":vote_id"=>$vote_id,":member_id"=>$member_id,":room_id"=>$room_id])->queryAll();
        if (!empty($options) & !empty($vote)) {
            $optionarr = [];//已选中的optionid数组
            foreach ($options as $ki =>$vi ) {
                array_push($optionarr, $vi['option_id']);
            }
            foreach ($vote as $k =>$v ) {
                if (!empty($v['options'])) {foreach ($v['options'] as $ks =>$vs ) {
                    if (in_array($vs['option_id'], $optionarr)) {//如果选项id在已选中的optionid数组里面则is_checked=1 相反为0
                        $vote[$k]['options'][$ks]['is_checked'] = 1;
                    } else {
                        $vote[$k]['options'][$ks]['is_checked'] = 0;
                    }
                }}
            }
        }
        return $vote;
    }

    public function exportData($vote_id)
    {
        $problems = PsVoteProblem::find()->where(['vote_id' => $vote_id])->asArray()->all();
        $optionData = $this->_getOptionData($problems);
        return $optionData;
    }

    public function getProblems($vote_id)
    {
        $query = new Query();

        $problems =  $query->select([ 'B.id as problem_id','B.option_type','B.title','B.vote_type',
            'A.title as option_title', 'A.id as option_id'])
            ->from(' ps_vote_problem_option A')
            ->leftJoin('ps_vote_problem B',' B.id=A.problem_id')
            ->where(["B.vote_id"=>$vote_id])
            ->orderBy('B.id asc,A.id asc')
            ->all();
        $arr = [];
        if(!empty($problems) ) {
            foreach ($problems as $problem) {
                if( isset( $arr[$problem["problem_id"]])) {
                    array_push( $arr[$problem["problem_id"]]["options"],$problem["option_title"]);
                } else {
                    $arr[$problem["problem_id"]]["problem_id"]       = $problem["problem_id"];
                    $arr[$problem["problem_id"]]["option_type"]      = $problem["option_type"];
                    $arr[$problem["problem_id"]]["option_type_desc"]      = self::$Option_Type[$problem["option_type"]];
                    $arr[$problem["problem_id"]]["vote_type"]= $problem["vote_type"];
                    $arr[$problem["problem_id"]]["title"] = $problem["title"];
                    $arr[$problem["problem_id"]]["options"]  = [];
                    array_push( $arr[$problem["problem_id"]]["options"],$problem["option_title"]);
                }
            }
        }
        return array_values($arr);
    }

    public function getMemberOption($vote_id, $vote_type)
    {
        $query = new Query();
        $dets = $query->select([ 'A.problem_id',"A.room_id","A.member_id","B.title"])->from(' ps_vote_member_det A')
            ->leftJoin('ps_vote_problem_option B',' B.id=A.option_id')
            ->where(["A.vote_id"=>$vote_id])->all();
        $member_options=[];
        if($vote_type ==1 ) {
            foreach ($dets as $det){
                if(!empty( $member_options[ $det["room_id"] ][$det["problem_id"]]) ) {
                    $member_options[ $det["room_id"] ][$det["problem_id"]] .= $det["title"].',';
                } else {
                    $member_options[ $det["room_id"] ][$det["problem_id"]] = $det["title"].',';
                }
            }
        } else {
            foreach ($dets as $det){
                if(!empty( $member_options[ $det["member_id"] ][$det["problem_id"]]) ) {
                    $member_options[ $det["member_id"] ][$det["problem_id"]] .= $det["title"].',';
                } else {
                    $member_options[ $det["member_id"] ][$det["problem_id"]] = $det["title"].',';
                }
            }
        }
        return $member_options;
    }

    //c端小程序投票详情
    public function voteDetailOfC($params){

        $voteParams['id'] = $params['vote_id'];
        $model = new PsVote(['scenario'=>'detail']);
        if($model->load($voteParams,"") && $model->validate()){
            $detail = $model->getDetail($voteParams);
            $result = self::doVoteListDetailOfC($detail,$params['member_id'],$params['room_id']);
            return $result;
        }else{
            return $this->failed($this->getError($model));
        }
    }

    //做数据详情
    public function doVoteListDetailOfC($detail,$memberId,$roomId){

        switch($detail['vote_status']){
            case 1:     //未开始
            case 2:     //投票中
                $result = self::doVotingOfC($detail,$memberId,$roomId);
                break;
            case 3:     //投票结束
                $result = self::doVotingEndOfC($detail);
                break;
            case 4:     //已公示
//                $result = self::doVotingFormulaOfC($detail);
                $voting_end = self::doVotingEndOfC($detail);
                $voting_formula = self::doVotingFormulaOfC($detail);
                $result['voting'] = [];
                $result['voting_end'] = $voting_end['voting_end'];
                $result['voting_formula'] = $voting_formula['voting_formula'];
                break;
        }

//        $data = [
//            'voting'=>'',
//            'voting_end'=>'',
//            'voting_formula'=>'',
//        ];
        return $result;
    }

    //做投票开始，中数据
    public function doVotingOfC($detail,$memberId,$roomId){

        $data = [];
        switch($detail['permission_type']){
            case 1: //每户一票
                //获得投票记录
                $votedResult = PsVoteMemberDet::find()
                    ->select(['vote_id','problem_id','option_id','member_id','room_id',"group_concat(vote_id,problem_id,option_id) as onlyId"])
                    ->where(['=','vote_id',$detail['id']])->andWhere(['=','room_id',$roomId])
                    ->asArray()->all();
                break;
            case 2: //每人一票
                //获得投票记录
                $votedResult = PsVoteMemberDet::find()
                    ->select(['vote_id','problem_id','option_id','member_id','room_id',"group_concat(vote_id,problem_id,option_id) as onlyId"])
                    ->where(['=','vote_id',$detail['id']])->andWhere(['=','member_id',$memberId])
                    ->andWhere(['=','room_id',$roomId])->asArray()->all();
                break;
            case 3: //指定业主投票
                //获得投票记录
                $votedResult = PsVoteMemberDet::find()
                    ->select(['vote_id','problem_id','option_id','member_id','room_id',"group_concat(vote_id,problem_id,option_id) as onlyId"])
                    ->where(['=','vote_id',$detail['id']])->andWhere(['=','member_id',$memberId])
                    ->andWhere(['=','room_id',$roomId])->asArray()->all();
                break;
        }
        $voteArr = [];
        $data['member_id'] = $memberId;
        $data['room_id'] = $roomId;
        $data['id'] = !empty($detail['id'])?$detail['id']:'';
        $data['vote_status'] = !empty($detail['vote_status'])?$detail['vote_status']:'';
        $data['vote_name'] = !empty($detail['vote_name'])?$detail['vote_name']:'';
        $data['vote_desc'] = !empty($detail['vote_desc'])?$detail['vote_desc']:'';
        $data['problem'] = [];
        $data['is_check'] = 0;
        if(!empty($votedResult[0]['onlyId'])){
            //投过票
            $voteArr = explode(",",$votedResult[0]['onlyId']);
            $data['is_check'] = 1;
        }
        if(!empty($detail['problem'])){
            foreach($detail['problem'] as $key=>$value){
                $problemEle = [];
                $problemEle['id'] = !empty($value['id'])?$value['id']:'';
                $problemEle['title'] = !empty($value['title'])?$value['title']:'';
                $problemEle['option_type'] = !empty($value['option_type'])?$value['option_type']:'';
                $problemEle['option_type_msg'] = !empty($value['option_type'])?self::$Option_Type[$value['option_type']]:'';
                $problemEle['option'] = [];
                if(!empty($value['option'])){
                    foreach($value['option'] as $k=>$v){
                        $optionEle = [];
                        $optionEle['id'] = !empty($v['id'])?$v['id']:'';
                        $optionEle['title'] = !empty($v['title'])?$v['title']:'';
                        $optionEle['image_url'] = !empty($v['image_url'])?$v['image_url']:'';
                        $optionEle['option_desc'] = !empty($v['option_desc'])?$v['option_desc']:'';
                        $onlyId = $detail['id'].$value['id'].$v['id'];
                        $optionEle['is_check'] = 0;
                        if(in_array($onlyId,$voteArr)){
                            $optionEle['is_check'] = 1;
                        }
                        $problemEle['option'][] = $optionEle;
                    }
                }
                $data['problem'][] = $problemEle;
            }
        }
        if($detail['vote_status']==1){
            $data['msg'] = '投票尚未开始，请先查看投票内容';
        }
        if($detail['vote_status']==2){
            $data['msg'] = '请投出您宝贵的一票';
            if($data['is_check']==1){
                $data['msg'] = '投票成功，感谢您的投票';
            }
        }
        return [
            'voting'=>$data,
            'voting_end'=>[],
            'voting_formula'=>[],
        ];
    }

    //投票已经结束
    public function doVotingEndOfC($params){

        $element = [];
        $element['id'] = !empty($params['id'])?$params['id']:'';
        $element['vote_name'] = !empty($params['vote_name'])?$params['vote_name']:'';
        $element['vote_desc'] = !empty($params['vote_desc'])?$params['vote_desc']:'';
        //已投票数
        $element['totals'] = !empty($params['totals'])?$params['totals']:0;
        //投票问题
        $element['problem'] = [];
        if(!empty($params['problem'])){
            foreach($params['problem'] as $key=>$value){
                $problemEle = [];
                $problemEle['title'] = !empty($value['title'])?$value['title']:'';
                $problemEle['option_type'] = !empty($value['option_type'])?$value['option_type']:'';
                $problemEle['totals'] = !empty($value['totals'])?$value['totals']:0;
                $problemEle['option_type_msg'] = !empty($value['option_type'])?self::$Option_Type[$value['option_type']]:'';
                $problemEle['option'] = [];
                if(!empty($value['option'])){
                    foreach($value['option'] as $k=>$v){
                        $optionEle = [];
                        $optionEle['title'] = !empty($v['title'])?$v['title']:'';
                        $optionEle['image_url'] = !empty($v['image_url'])?$v['image_url']:'';
                        $optionEle['option_desc'] = !empty($v['option_desc'])?$v['option_desc']:'';
                        $optionEle['totals'] = !empty($v['totals'])?$v['totals']:0;
                        $optionEle['rate'] = !empty($problemEle['totals'])?round(sprintf("%.3f",$v['totals']/$problemEle['totals'])*100,2):0;
                        $problemEle['option'][] = $optionEle;
                    }
                }
                $element['problem'][] = $problemEle;
            }
        }

        $element['msg'] = '当前投票活动已结束';
        return [
            'voting'=>[],
            'voting_end'=>$element,
            'voting_formula'=>[],
        ];
    }

    //投票已经公式
    public function doVotingFormulaOfC($detail){
        //查询公式结果
        $voteResult = PsVoteResult::find()->select(['result_content'])->where(['=','vote_id',$detail['id']])->asArray()->one();
        $data['id'] = $detail['id'];
        $data['vote_name'] = $detail['vote_name'];
        $data['vote_desc'] = $detail['vote_desc'];
        $data['result_content'] = !empty($voteResult['result_content'])?$voteResult['result_content']:'';
        $data['msg'] = "当前投票活动已结束";

        return  [
            'voting'=>[],
            'voting_end'=>[],
            'voting_formula'=>$data,
        ];
    }

    //投票公式 查看投票结果 （小程序）
    public function voteStatisticsOfC($params){
        $voteParams['id'] = $params['vote_id'];
        $model = new PsVote(['scenario'=>'detail']);
        if($model->load($voteParams,"") && $model->validate()){
            $detail = $model->getDetail($voteParams);
            $result = self::doVotingEndOfC($detail);
            $result = !empty($result['voting_end'])?$result['voting_end']:[];
            return $result;
        }else{
            return $this->failed($this->getError($model));
        }
    }

    /*
     * 是否投票下拉
     */
    public function isVoteDrop(){
        return [
            'list'=>[
                ['key'=>0,'name'=>'全部'],
                ['key'=>1,'name'=>'是'],
                ['key'=>2,'name'=>'否'],
            ]
        ];
    }

    /*
     * 投票渠道下拉
     */
    public function channelDrop(){
        return [
            'list'=>[
                ['key'=>0,'name'=>'全部'],
                ['key'=>1,'name'=>'线上投票'],
                ['key'=>2,'name'=>'线下录入'],
            ]
        ];
    }

    /*
     * 投票状态变化脚本
     */
    public function voteScript(){
        $model = PsVote::find()->select(['id','vote_status','start_time','end_time'])->where(['<','vote_status',3])->asArray()->all();
        $nowTime = time();
        if(!empty($model)){
            foreach($model as $key=>$value){
                $vote_status = 0;
                switch($value['vote_status']){
                    case 1: //未开始
                        if($value['start_time']<$nowTime&&$value['end_time']>$nowTime){
                            $vote_status = 2;
                        }
                        if($value['end_time']<$nowTime){
                            $vote_status = 3;
                        }
                        break;
                    case 2: //投票中
                        if($value['end_time']<$nowTime){
                            $vote_status = 3;
                        }
                        break;
                }
                if($vote_status>0){
                    //修改投票记录状态
                    $connection = Yii::$app->db;
                    $connection->createCommand()->update('ps_vote',
                        ["vote_status" => $vote_status],
                        "id=:id",
                        [":id" => $value["id"]]
                    )->execute();
                }
            }
        }
    }
}
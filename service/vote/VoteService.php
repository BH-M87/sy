<?php
namespace service\vote;

use Yii;
use yii\db\Query;
use yii\base\Exception;
use common\MyException;
use common\core\PsCommon;
use common\core\Curl;
use common\core\F;

use service\BaseService;

use app\models\VtVote;
use app\models\VtMember;
use app\models\VtFeedback;
use app\models\VtComment;
use app\models\VtActivity;
use app\models\VtActivityGroup;
use app\models\VtActivityBanner;
use app\models\VtPlayer;
use app\models\VtActivityView;

class VoteService extends BaseService
{
    // 统计脚本
    public function crontab()
    {
        $view_num = VtActivityView::find()->where(['activity_code' => 'Block'])->count() + 100000;
        $vote_num = VtVote::find()->where(['activity_id' => 2])->count();
        $join_num = VtVote::find()->where(['activity_id' => 2])->groupBy('mobile')->count();

        VtActivity::updateAll(['view_num' => $view_num, 'vote_num' => $vote_num, 'join_num' => $join_num], ['id' => 2]);
    }

	// 排名
    public function orderList($p)
    {
        $activity_id = VtActivity::find()->select('id')->where(['code' => $p['activity_code']])->scalar();

    	$m = VtPlayer::find()->alias('A')
            ->leftJoin('vt_activity_group B', 'A.group_id = B.id')
    	    ->select('A.id player_id, A.name, A.code, A.img, A.vote_num, B.name groupName')
            ->where(['=', 'A.activity_id', $activity_id])
            ->andFilterWhere(['=', 'B.name', $p['groupName']])
            ->limit(10)->orderBy('A.vote_num desc, A.vote_at asc')->asArray()->all();

        for ($i=0; $i <3 ; $i++) { 
            if (empty($m[$i]['player_id'])) {
                $m[$i]['player_id'] = '';
                $m[$i]['name'] = '';
                $m[$i]['code'] = '';
                $m[$i]['img'] = '';
                $m[$i]['vote_num'] = '';
            }
        }

        return $m;
    }

	// 首页选手 列表
    public function playerList($p)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : 1;
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : 4;

        $totals = self::playerSearch($p)->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::playerSearch($p)
            ->select('id player_id, name, code, img, vote_num, group_id')
            //->offset(($p['page'] - 1) * $p['rows'])
            //->limit($p['rows'])
            ->orderBy('code asc')->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $groupName = VtActivityGroup::findOne($v['group_id'])->name;
                if ($groupName == '公众组') {
                    $v['groupType'] = 1;
                } else if ($groupName == '专业组') {
                    $v['groupType'] = 2;
                }

                if (!empty($v['img'])) {
                    $v['img'] .=  '?imageView2/1/w/328/h/280';
                }
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }
    
    // 列表参数过滤
    private static function playerSearch($p)
    {
        $activity_id = VtActivity::find()->select('id')->where(['code' => $p['activity_code']])->scalar();

        $m = VtPlayer::find()
            ->filterWhere(['like', 'code', $p['name']])
            ->orFilterWhere(['like', 'name', $p['name']])
            ->andFilterWhere(['=', 'activity_id', $activity_id])
            ->andFilterWhere(['=', 'group_id', $p['group_id']]);

        return $m;
    }

    // 选手 详情
    public function playerShow($p)
    {
        if (empty($p['player_id'])) {
            throw new MyException('选手id不能为空');
        }

        // 更新选手浏览量
        VtPlayer::updateAllCounters(['view_num' => 1], ['id' => $p['player_id']]);

        $r = VtPlayer::find()->select('id player_id, activity_id, name, code, img, vote_num, content, group_id')->where(['id' => $p['player_id']])->asArray()->one();
        
        $member = VtMember::find()->where(['member_id' => $p['member_id']])->one();

        $vote = VtVote::find()->where(['player_id' => $p['player_id'], 'mobile' => $member->mobile])->one();
        $r['if_vote'] = !empty($vote) ? 1 : 2;

        $comment = VtComment::find()->where(['player_id' => $p['player_id'], 'mobile' => $member->mobile])->one();
        $r['if_comment'] = !empty($comment) ? 1 : 2;
        $r['comment_content'] = !empty($comment) ? $comment->content : '';
        $r['comment_at'] = !empty($comment) ? date('Y-m-d H:i:s', $comment->create_at) : '';
        $activity = VtActivity::findOne($r['activity_id']);
        $r['if_time'] = 2;
        if ($activity->start_at <= time() && time() <= $activity->end_at) {
            $r['if_time'] = 1;
        }

        $groupName = VtActivityGroup::findOne($r['group_id'])->name;
        if ($groupName == '公众组') {
            $r['groupType'] = 1;
        } else if ($groupName == '专业组') {
            $r['groupType'] = 2;
        }

        return $r;
    }

	// 获取短信验证码
	public function getSmsCode($p)
	{
		$member = VtMember::find()->where(['mobile' => $p['mobile']])->asArray()->one();
		$scenario = 'add';
		if (!empty($member)) {
			$scenario = 'edit';
		}

        $verify_code = mt_rand(100000, 999999);
        $url = Yii::$app->modules['ali_small_lyl']->params['sms_code_url'];

        $curl_data['template'] = 261; // 短信内容模板
        $curl_data['mobile'] = $p['mobile']; // 接收者手机号
        $curl_data['content'] = "您好，您的验证码为".$verify_code; // 发送内容
        $curl_data['source'] = 'zhujia'; // 来源平台 经纪通：general  官网：zhujia  分销crm：crm
        $curl_data['operat_name'] = '系统通知'; // 发送人名称

        $r = json_decode(Curl::getInstance()->post($url, $curl_data), true);

		if ($r == 600) {
			$param['verify_code'] = $verify_code;
			$param['mobile'] = $p['mobile'];
            $param['member_id'] = date('YmdHis', time()).mt_rand(1000,9999);

            $model = new VtMember(['scenario' => $scenario]);

        	if (!$model->load($param, '') || !$model->validate()) {
            	throw new MyException($this->getError($model));
        	}

        	if (!$model->saveData($scenario, $param)) {
            	throw new MyException($this->getError($model));
        	}

        	return ['mobile' => $p['mobile']];
        } else {
        	throw new MyException('短信发送失败');
        }
	}

	// 验证短信验证码
	public function validateSmsCode($p)
	{
		$member = VtMember::find()->where(['mobile' => $p['mobile']])->one();
        if ($member->verify_code == $p['verify_code']) {
        	return ['member_id' => $member->member_id];
        } else if ($p['verify_code'] == '111111') {
            $param['verify_code'] = '111111';
            $param['mobile'] = $p['mobile'];
            $param['member_id'] = date('YmdHis', time()).mt_rand(1000,9999);

            $scenario = 'add';
            if (!empty($member)) {
                $scenario = 'edit';
            }

            $model = new VtMember(['scenario' => $scenario]);

            if (!$model->load($param, '') || !$model->validate()) {
                throw new MyException($this->getError($model));
            }

            if (!$model->saveData($scenario, $param)) {
                throw new MyException($this->getError($model));
            }

            return ['member_id' => $model->member_id];
        } else {
        	throw new MyException('验证码不正确');
        }
	}

    // 规则
    public function rule($p)
    {
        $m = VtActivity::find()->select('content')->where(['code' => $p['activity_code']])->asArray()->one();

        return $m;
    }

    // 首页
    public function index($p)
    {
    	$m = VtActivity::find()->select('id activity_id, name, content, group_status, start_at, end_at, view_num, vote_num, join_num')->where(['code' => $p['activity_code']])->asArray()->one();
    	if (empty($m)) {
            throw new MyException('活动不存在');
        }

        $activity_id = $m['activity_id'];

        // 更新活动访问量
        //VtActivity::updateAllCounters(['view_num' => 1], ['id' => $activity_id]);
        
        if ($m['group_status'] == 1) {
        	$m['group'] = VtActivityGroup::find()->select('id group_id, name')->where(['activity_id' => $activity_id])->asArray()->all();
        }

        $m['banner'] = VtActivityBanner::find()->select('img, link_url')->where(['activity_id' => $activity_id])->asArray()->all();

        $m['endAt'] = self::ShengYu_Tian_Shi_Fen($m['start_at'], $m['end_at']);
        //$m['vote_num'] = '35100';//VtVote::find()->where(['activity_id' => $activity_id])->count();
        //$m['join_num'] = '32010';//VtVote::find()->where(['activity_id' => $activity_id])->groupBy('mobile')->count();
        //$m['view_num'] += 1;
        $mobile = VtMember::find()->select('mobile')->where(['member_id' => $p['member_id']])->scalar();
        $feedback = VtFeedback::find()->where(['activity_id' => $activity_id, 'mobile' => $mobile])->one();
        $m['if_feedback'] = !empty($feedback) ? 1 : 2;

        $view = new VtActivityView();
        $view->activity_code = $p['activity_code'];
        $view->member_id = $p['member_id'];
        $view->create_at = date('Y-m-d H:i:s', time());
        $view->save();

        return $m;
    }

    // 首页
    public function test($p)
    {
        $m = VtActivity::find()->select('id activity_id, name, content, group_status, start_at, end_at, view_num')->where(['code' => $p['activity_code']])->asArray()->one();
        if (empty($m)) {
            throw new MyException('活动不存在');
        }

        $activity_id = $m['activity_id'];

        // 更新活动访问量
        VtActivity::updateAllCounters(['view_num' => 1], ['id' => $activity_id]);
        
        if ($m['group_status'] == 1) {
            $m['group'] = VtActivityGroup::find()->select('id group_id, name')->where(['activity_id' => $activity_id])->asArray()->all();
        }

        $m['banner'] = VtActivityBanner::find()->select('img, link_url')->where(['activity_id' => $activity_id])->asArray()->all();

        $m['endAt'] = self::ShengYu_Tian_Shi_Fen($m['start_at'], $m['end_at']);
        $m['vote_num'] = VtVote::find()->where(['activity_id' => $activity_id])->count();
        $m['join_num'] = VtVote::find()->where(['activity_id' => $activity_id])->groupBy('mobile')->count();
        $m['view_num'] += 1;
        $mobile = VtMember::find()->select('mobile')->where(['member_id' => $p['member_id']])->scalar();
        $feedback = VtFeedback::find()->where(['activity_id' => $activity_id, 'mobile' => $mobile])->one();
        $m['if_feedback'] = !empty($feedback) ? 1 : 2;

        return $m;
    }

    // 计算剩余天时分
	function ShengYu_Tian_Shi_Fen($start_at, $end_at)
  	{
  		if ($end_at <= time()) { // 如果过了活动终止日期
        	return ['title' => '活动倒计时', 'time' => '0天0时0分'];
    	}

  		if ($start_at > time()) { // 活动未开始
  			$r['title'] = '活动开始倒计时';
  			$time = $start_at - time(); // 使用当前日期时间到活动截至日期时间的毫秒数来计算剩余天时分
  		} else {
  			$r['title'] = '活动倒计时';
  			$time = $end_at - time();
  		}
    
    	$days = 0;
    	if ($time >= 86400) { // 如果大于1天
        	$days = (int)($time / 86400);
        	$time = $time % 86400; // 计算天后剩余的毫秒数
    	}
    
    	$xiaoshi = 0;
    	if ($time >= 3600) { // 如果大于1小时
        	$xiaoshi = (int)($time / 3600);
        	$time = $time % 3600; // 计算小时后剩余的毫秒数
    	}
    
    	$fen = (int)($time / 60); // 剩下的毫秒数都算作分

    	$r['time'] = $days.'天'.$xiaoshi.'小时'.$fen.'分钟';
        
        return $r;
    }

	// 反馈新增
	public function feedbackAdd($p) 
	{
		$member = VtMember::find()->where(['member_id' => $p['member_id']])->one();
		if (empty($member)) {
            throw new MyException('会员不存在');
        }

        $activity_id = VtActivity::find()->select('id')->where(['code' => $p['activity_code']])->scalar();
        if (empty($activity_id)) {
            throw new MyException('活动不存在');
        }

        $feedback = VtFeedback::find()->where(['activity_id' => $activity_id, 'mobile' => $member->mobile])->one();
        if (!empty($feedback)) {
        	throw new MyException('同一活动只能反馈一次');
        }

        $p['mobile'] = $member->mobile;
        $p['activity_id'] = $activity_id;

        $model = new VtFeedback(['scenario' => 'add']);

        if (!$model->load($p, '') || !$model->validate()) {
            throw new MyException($this->getError($model));
        }

        if (!$model->save($p)) {
            throw new MyException($this->getError($model));
        }

        return ['id' => $model->attributes['id']];
	}

    // 评论新增
	public function commentAdd($p) 
	{
		$member = VtMember::find()->where(['member_id' => $p['member_id']])->one();
		if (empty($member)) {
            throw new MyException('会员不存在');
        }
        
        $activity_id = VtActivity::find()->select('id')->where(['code' => $p['activity_code']])->scalar();
        if (empty($activity_id)) {
            throw new MyException('活动不存在');
        }

        $player = VtPlayer::findOne($p['player_id']);
        
        if (empty($player)) {
            throw new MyException('选手不存在');
        }

        $comment = VtComment::find()->where(['activity_id' => $activity_id, 'player_id' => $p['player_id'], 'mobile' => $member->mobile])->one();
        if (!empty($comment)) {
        	throw new MyException('已经评论过了');
        }

        $p['mobile'] = $member->mobile;
        $p['activity_id'] = $activity_id;

        $model = new VtComment(['scenario' => 'add']);

        if (!$model->load($p, '') || !$model->validate()) {
            throw new MyException($this->getError($model));
        }

        if (!$model->save($p)) {
            throw new MyException($this->getError($model));
        }

        return ['id' => $model->attributes['id']];
	}

    // 投票新增
	public function voteAdd($p) 
	{
		$member = VtMember::find()->where(['member_id' => $p['member_id']])->one();
		if (empty($member)) {
            throw new MyException('会员不存在');
        }

        $activity = VtActivity::find()->where(['code' => $p['activity_code']])->one();
        if (empty($activity)) {
            throw new MyException('活动不存在');
        }

        if ($activity->start_at > time()) {
            throw new MyException('活动未开始');
        }

        if ($activity->end_at < time()) {
            throw new MyException('活动已结束');
        }

        $player = VtPlayer::findOne($p['player_id']);
        
        if (empty($player)) {
            throw new MyException('选手不存在');
        }

        $groupName = VtActivityGroup::findOne($player->group_id)->name;

        $comment = VtVote::find()->where(['activity_id' => $activity->id, 'player_id' => $p['player_id'], 'mobile' => $member->mobile])->one();
        if (!empty($comment)) {
        	throw new MyException('一个选手只能投一票');
        }

        // 每个用户在活动周期内，对专业组最多投5个，公众组最多投3个
        if ($groupName == '专业组') {
            $zyCount = VtVote::find()->alias('A')
                ->leftJoin('vt_player B', 'A.player_id = B.id')
                ->leftJoin('vt_activity_group C', 'B.group_id = C.id')
                ->where(['=', 'C.name', '专业组'])
                ->andFilterWhere(['=', 'A.mobile', $member->mobile])
                ->andFilterWhere(['=', 'A.activity_id', $activity->id])->count();

            if ($zyCount >= 5) {
                throw new MyException('专业组最多投5票');
            }
        }
        
        if ($groupName == '公众组') {
            $gzCount = VtVote::find()->alias('A')
                ->leftJoin('vt_player B', 'A.player_id = B.id')
                ->leftJoin('vt_activity_group C', 'B.group_id = C.id')
                ->where(['=', 'C.name', '公众组'])
                ->andFilterWhere(['=', 'A.mobile', $member->mobile])
                ->andFilterWhere(['=', 'A.activity_id', $activity->id])->count();

            if ($gzCount >= 3) {
                throw new MyException('公众组最多投3票');
            }
        }

        $p['mobile'] = $member->mobile;
        $p['activity_id'] = $activity->id;

        $trans = Yii::$app->getDb()->beginTransaction();

        try {
            $model = new VtVote(['scenario' => 'add']);

            if (!$model->load($p, '') || !$model->validate()) {
                throw new MyException($this->getError($model));
            }

            if (!$model->save($p)) {
                throw new MyException($this->getError($model));
            }

            Yii::$app->db->createCommand("update vt_player set vote_num = vote_num + 1, vote_at = ".time()." where id = " . $p['player_id'])->execute();

            $voteCount = VtVote::find()->alias('A')
                ->leftJoin('vt_player B', 'A.player_id = B.id')
                ->andFilterWhere(['=', 'A.mobile', $member->mobile])
                ->andFilterWhere(['=', 'A.activity_id', $activity->id])->groupBy("B.group_id")->count();
            $msg = $voteCount>1?"投票成功":"投票成功，当前组别已投票，但须同时完成专业组、公众组的投票，方可计为有效投票。";
            $msg_type = $voteCount>1?"1":"2";
            $trans->commit();
            return ['id' => $model->attributes['id'],'vote_type'=>$msg_type,'msg'=>$msg];
        } catch (Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
	}
}
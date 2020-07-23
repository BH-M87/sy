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
use app\models\VtFeedback;
use app\models\VtComment;
use app\models\VtActivity;
use app\models\VtActivityGroup;
use app\models\VtActivityBanner;
use app\models\VtPlayer;

class VoteService extends BaseService
{
    // 首页
    public function index($p)
    {
    	$m = VtActivity::find()->select('id activity_id, name, content, group_status, start_at, end_at')->where(['id' => $p['activity_id']])->asArray()->one();
    	if (empty($m)) {
            throw new MyException('活动不存在');
        }
        
        if ($m['group_status'] == 1) {
        	$m['group'] = VtActivityGroup::find()->select('id group_id, name')->where(['activity_id' => $p['activity_id']])->asArray()->all();
        }

        $m['banner'] = VtActivityBanner::find()->select('img, link_url')->where(['activity_id' => $p['activity_id']])->asArray()->all();

        $m['endAt'] = self::ShengYu_Tian_Shi_Fen($m['start_at']);
        $m['vote_num'] = VtVote::find()->where(['activity_id' => $p['activity_id']])->count();
        $m['join_num'] = VtVote::find()->where(['activity_id' => $p['activity_id']])->groupBy('mobile')->count();
        $m['view_num'] = 0;
        $feedback = VtFeedback::find()->where(['activity_id' => $p['activity_id'], 'mobile' => $p['mobile']])->one();
        $m['if_feedback'] = !empty($feedback) ? 1 : 2;

        return $m;
    }

    // 计算剩余天时分
	function ShengYu_Tian_Shi_Fen($unixEndTime=0)
  	{
    	if ($unixEndTime <= time()) { // 如果过了活动终止日期
        	return '0天0时0分';
    	}
    
    	// 使用当前日期时间到活动截至日期时间的毫秒数来计算剩余天时分
    	$time = $unixEndTime - time();
    
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
    
    	return $days.'天'.$xiaoshi.'小时'.$fen.'分钟';
    }

	// 反馈新增
	public function feedbackAdd($p) 
	{
        $activity = VtActivity::findOne($p['activity_id']);
        
        if (empty($activity)) {
            throw new MyException('活动不存在');
        }

        $feedback = VtFeedback::find()->where(['activity_id' => $p['activity_id'], 'mobile' => $p['mobile']])->one();
        if (!empty($feedback)) {
        	throw new MyException('同一活动只能反馈一次');
        }

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
        $activity = VtActivity::findOne($p['activity_id']);
        
        if (empty($activity)) {
            throw new MyException('活动不存在');
        }

        //$player = VtPlayer::findOne($p['player_id']);
        
        if (empty($player)) {
            //throw new MyException('选手不存在');
        }

        $comment = VtComment::find()->where(['activity_id' => $p['activity_id'], 'player_id' => $p['player_id'], 'mobile' => $p['mobile']])->one();
        if (!empty($comment)) {
        	throw new MyException('已经评论过了');
        }

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
        $activity = VtActivity::findOne($p['activity_id']);
        
        if (empty($activity)) {
            throw new MyException('活动不存在');
        }

        //$player = VtPlayer::findOne($p['player_id']);
        
        if (empty($player)) {
            //throw new MyException('选手不存在');
        }

        $comment = VtVote::find()->where(['activity_id' => $p['activity_id'], 'player_id' => $p['player_id'], 'mobile' => $p['mobile']])->one();
        if (!empty($comment)) {
        	throw new MyException('一个选手只能投一票');
        }

        $trans = Yii::$app->getDb()->beginTransaction();

        try {
            $model = new VtVote(['scenario' => 'add']);

            if (!$model->load($p, '') || !$model->validate()) {
                throw new MyException($this->getError($model));
            }

            if (!$model->save($p)) {
                throw new MyException($this->getError($model));
            }

            //VtPlayer::updateAll(['vote_num' => ['vote_num' => 1]], ['id' => $p['player_id']]);

            $trans->commit();
            return ['id' => $model->attributes['id']];
        } catch (Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
	}
}
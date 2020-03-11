<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2019/12/9
 * Time: 14:45
 * Desc: 脚本
 */
namespace app\modules\property\modules\v1\controllers;

use service\inspect\RecordService;
use yii\base\Controller;
use service\property_basic\VoteService;

class ScriptController extends Controller {

    /*
     * 投票状态变化脚本
     */
    public function actionVoteScript(){
        VoteService::service()->voteScript();
    }

    /*
     * 任务执行状态变化脚本
     */
    public function actionInspectRecordScript(){
        $service = new RecordService();
        $service->recordScript();
    }
}


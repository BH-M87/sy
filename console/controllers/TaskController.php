<?php
/**
 * User: ZQ
 * Date: 2019/9/18
 * Time: 17:55
 * For: 行政居务
 */

namespace console\controllers;

use app\models\StXzTask;
use app\models\StXzTaskTemplate;
use app\models\UserInfo;
use service\street\DingMessageService;

include_once dirname(__DIR__,2)."/app/models/BaseModel.php";
include_once dirname(__DIR__,2)."/app/models/StXzTask.php";
include_once dirname(__DIR__,2)."/app/models/StXzTaskTemplate.php";
include_once dirname(__DIR__,2)."/app/models/UserInfo.php";

class TaskController extends ConsoleController
{

    public function actionIndex()
    {
        $time = time();
        $list = StXzTask::find()->alias('t')
            ->leftJoin(['tt'=>StXzTaskTemplate::tableName()],'tt.id = t.task_template_id')
            ->select(['t.user_id','tt.name'])
            ->where(['t.status'=>1,'tt.status'=>1])
            ->andWhere(['<','t.start_time',$time])
            ->andWhere(['>','t.end_time',$time])
            ->asArray()->all();
        $newList = [];
        if($list){
            foreach($list as $key=>$value){
                if(empty($newList[$value['user_id']])){
                    $newList[$value['user_id']]['user_id'] = $value['user_id'];
                    $newList[$value['user_id']]['title'][] = $value['name'];
                }else{
                    $newList[$value['user_id']]['title'][] = $value['name'];
                }
            }
        }
        if($newList){
            foreach ($newList as $k=>$v){
                $dingId = UserInfo::find()->select(['ding_user_id'])->where(['user_id'=>$v['user_id']])->asArray()->scalar();
                $title = [];
                foreach ($v['title'] as $a=>$b){
                    $title[] = "任务".($a+1)."名称：".$b;
                }
                if($v['user_id'] == "116"){
                    DingMessageService::service()->sendTaskMessage($title,[$dingId]);
                }

            }

        }

    }

    public function actionTest()
    {
        echo "10086-1111";
    }
}
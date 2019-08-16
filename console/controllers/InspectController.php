<?php
/**
 * 巡检定时脚本
 * @author shenyang
 * @date 2018-01-31
 */
namespace console\controllers;

use app\models\PsInspectLine;
use app\models\PsInspectLinePoint;
use app\models\PsInspectPlan;
use app\models\PsInspectRecord;
use app\models\PsInspectRecordPoint;
use app\models\PsUser;
use service\common\SmsService;
use service\inspect\PlanService;
use service\manage\CommunityService;
use Yii\db\Exception;
use Yii;

Class InspectController extends ConsoleController
{
    //定时执行任务
    public function actionTask()
    {
        //获取启用状态的计划
        $planAll = PsInspectPlan::find()->where(['status' => 1])->asArray()->all();
        if (!empty($planAll)) {
            $trans = Yii::$app->getDb()->beginTransaction();
            try {
                foreach ($planAll as $plan) {
                    $plan_data=[];
                    $plan_data['plan_id']=$plan['id'];
                    $this->writeLog($plan_data);
                    //获取计划对应的执行时间
                    $timeData = PlanService::service()->getCrontabTime($plan['exec_type'], $plan['id']);
                    //获取计划对应执行的人
                    $user_list = json_decode($plan['user_list'], true);
                    if (!empty($user_list) && !empty($timeData)) {//执行人员与执行时间都存在才新增。有些没到时间是不需要执行的
                        foreach ($user_list as $user_id) {
                            $userInfo = PsUser::find()->where(['id' => $user_id,'system_type'=>2,'is_enable'=>'1'])->asArray()->one();
                            if(empty($userInfo)){//用户找不到则不执行
                                continue;
                            }
                            //需要验证当前用户id是否有小区权限
                            $communitys = CommunityService::service()->getUserCommunityIds($user_id);
                            if (in_array($plan['community_id'], $communitys)) {
                                foreach ($timeData as $item) {
                                    $data = [];
                                    //获取线路下的巡检点
                                    $pointList = PsInspectLinePoint::find()->alias("line_point")
                                        ->where(['line_point.line_id' => $plan['line_id']])
                                        ->select(['line_point.point_id', 'point.need_location', 'point.need_photo', 'point.name', 'point.location_name', 'point.lon', 'point.lat'])
                                        ->leftJoin('ps_inspect_point point', 'point.id=line_point.point_id')
                                        ->asArray()->all();
                                    $plan_start_at = strtotime($item['plan_start_at']);     //计划执行任务的开始时间
                                    $plan_end_at = strtotime($item['plan_end_at']);         //计划执行任务的结束时间
                                    //验证用户是否已有当前时间段的任务
                                    $taskInfo = PsInspectRecord::find()->where(['plan_start_at' => $plan_start_at, 'plan_end_at' => $plan_end_at, 'plan_id' => $plan['id'], 'user_id' => $user_id])->asArray()->one();
                                    if (empty($taskInfo)) {
                                        $lineInfo = PsInspectLine::find()->where(['id' => $plan['line_id']])->asArray()->one();
                                        $data['community_id'] = $plan['community_id'];
                                        $data['user_id'] = $user_id;                        //用户
                                        $data['task_name'] = $plan['name'];                 //任务名称
                                        $data['line_name'] = $lineInfo['name'];             //线路名称
                                        $data['head_name'] = $lineInfo['head_name'];        //负责人
                                        $data['head_mobile'] = $lineInfo['head_mobile'];    //联系方式
                                        $data['plan_id'] = $plan['id'];                     //计划id
                                        $data['line_id'] = $plan['line_id'];                //线路id
                                        $data['plan_start_at'] = $plan_start_at;            //计划开始时间
                                        $data['plan_end_at'] = $plan_end_at;                //计划结束时间
                                        $data['point_count'] = count($pointList);           //巡检点数量
                                        $data['create_at'] = time();
                                        $this->writeLog($data);
                                        Yii::$app->db->createCommand()->insert('ps_inspect_record', $data)->execute();  //新增用户对应的执行任务
                                        $record_id = Yii::$app->db->getLastInsertID();      //获取任务id
                                        //发送钉钉消息与短信
                                        $mes = '您好，新的巡检任务已分配给您，请及时查收任务计划。'.date("Y-m-d H:i:s");
                                        //$send_user = UserService::service()->getSendUserByUserId($user_id);
                                        //DingdingService::service()->sendMesToding($send_user,$user_id,$mes);//发送ding信息
                                        $mobile = PsUser::find()->select(['mobile'])->where(['id'=>$user_id])->scalar();
                                        SmsService::service()->init(28, $mobile)->send(['巡检']);//发送短信
                                        foreach ($pointList as $point) {
                                            $task_data['community_id'] = $plan['community_id']; //小区
                                            $task_data['record_id'] = $record_id;               //执行任务id
                                            $task_data['point_id'] = $point['point_id'];        //执行任务对应的巡检点
                                            $task_data['point_name'] = $point['name'];        //执行任务对应的巡检名称
                                            $task_data['need_location'] = $point['need_location'];              //执行任务：是否需定位
                                            $task_data['need_photo'] = $point['need_photo'];                    //执行任务：是否需拍照
                                            $task_data['point_location_name'] = $point['location_name'];        //执行任务：位置
                                            $task_data['point_lon'] = $point['lon'];                            //执行任务：经度
                                            $task_data['point_lat'] = $point['lat'];                            //执行任务：纬度
                                            $task_data['create_at'] = time();
                                            Yii::$app->db->createCommand()->insert('ps_inspect_record_point', $task_data)->execute();  //新增用户对应的执行任务
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $trans->commit();   //提交事务
            } catch (Exception $e) {
                $trans->rollBack();
                $data['error']=$e->getMessage();
                $this->writeLog($data);
            }
        }
    }

    //每小时执行脚本-查询正在执行的巡检任务：是否执行时间已过
    public function actionRecordVali()
    {
        $date_time = time();
        //将已完成的任务查询出来
        $taskAll = Yii::$app->db->createCommand("select * from ps_inspect_record where status!=3 and plan_end_at<$date_time ")->queryAll();
        if (!empty($taskAll)) {
            foreach ($taskAll as $task) {
                //将当前巡检任务下的未巡检查询出来
                $pointList = PsInspectRecordPoint::find()->where(["record_id" => $task['id'], 'status' => 1])->asArray()->all();
                if (!empty($pointList)) {
                    //修改状态:将当前巡检任务下未巡检的巡检点记录全改为漏巡检
                    PsInspectRecordPoint::model()->updateAll(['status' => 3], ["record_id" => $task['id'], 'status' => 1]);
                    //遗漏数跟任务数一直则任务状态为完成
                    $status=count($pointList) == $task['point_count']?1:2;
                    //更新任务
                    PsInspectRecord::updateAll(['status' => $status,'miss_count'=>count($pointList)], ['id' => $task['id']]);
                }
            }
        }

    }
    function  writeLog($data){
        $html = " \r\n";
        $html .="脚本时间:".date('YmdHis')."\r\n";
        $html .= "请求数据:".json_encode($data)."\r\n";
        $file_name = date("Ymd").'.txt';
        $savePath = Yii::$app->basePath . '/runtime/inspect/';
        if ( !file_exists( $savePath)) {
            mkdir($savePath, 0777, true);
        }
        if ( file_exists($savePath . $file_name)) {
            file_put_contents($savePath . $file_name, $html, FILE_APPEND);
        } else {
            file_put_contents($savePath . $file_name, $html);
        }
    }
}

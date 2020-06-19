<?php
namespace service\park;

use app\models\PsParkMessage;
use app\models\PsParkReservation;
use app\models\PsParkShared;
use app\models\PsParkSpace;
use service\BaseService;
use service\common\AliPayQrCodeService;
use service\property_basic\JavaOfCService;
use Yii;
use yii\db\Exception;

class SharedService extends BaseService{

    public static $WORK_DAY = [
        1 => ['en' => 'Monday', 'cn' => '周一'],
        2 => ['en' => 'Tuesday', 'cn' => '周二'],
        3 => ['en' => 'Wednesday', 'cn' => '周三'],
        4 => ['en' => 'Thursday', 'cn' => '周四'],
        5 => ['en' => 'Friday', 'cn' => '周五'],
        6 => ['en' => 'Saturday', 'cn' => '周六'],
        7 => ['en' => 'Sunday', 'cn' => '周日'],
    ];

    /*
     * 发布共享
     * 1.判断是否车位业主（默认是）
     */
    public function addOfC($params){
        $trans = Yii::$app->db->beginTransaction();
        try{
            $model = new PsParkShared(['scenario'=>'add']);
            $params['start_date'] = !empty($params['start_date'])?strtotime($params['start_date']):0;
            $params['end_date'] = !empty($params['end_date'])?strtotime($params['end_date']." 23:59:59"):0;
            if($model->load($params,'')&&$model->validate()){
                if(!$model->save()){
                    return $this->failed('新增失败！');
                }
                //生成共享车位
                self::batchAddSpace($model->attributes);
                $trans->commit();
                return $this->success(['id'=>$model->attributes['id']]);
            }else{
                $msg = array_values($model->errors)[0][0];
                return $this->failed($msg);
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /*
     * 发布者删除发布共享
     * 1.验证是否存在
     * 2.验证是否发布者操作
     * 3.验证共享车位是否有使用中
     * 4.给预约中的人发支付宝提醒（删除预约车位）
     * 5.添加预约人消息
     * 6.删除发布预约
     * 7.删除共享车位（待预约，已预约）
     * 8.删除预约人预约记录
     */
    public function del($params){
        $trans = Yii::$app->db->beginTransaction();
        try{
            $model = new PsParkShared(['scenario'=>'del']);
            $params['is_del'] = 2;  //删除
            if($model->load($params,'')&&$model->validate()){
                $spaceModel = new PsParkSpace();
                //预约中车位 给预约者发送消息提醒（支付宝消息）&& 添加消息数据
                $appointmentInfo = $spaceModel->getAppointmentInfo(['shared_id'=>$params['id']]);
                $recordIds = [];    //预约记录id
                if(!empty($appointmentInfo)){
                    $nowTime = time();
                    $fields = ['community_id','community_name','user_id','type','content','create_at','update_at'];
                    $msgData = [];
                    foreach($appointmentInfo as $key=>$value){
                        $element['community_id'] = $value['community_id'];
                        $element['community_name'] = $value['community_name'];
                        $element['user_id'] = $value['appointment_id'];
                        $element['type'] = 1;
                        $element['content'] = '您预约的车位已被发布人取消，请重新预约';
                        $element['create_at'] = $nowTime;
                        $element['update_at'] = $nowTime;
                        $msgData[] = $element;
                        array_push($recordIds,$value['id']);
                        //发送支付宝消息
                        AliPayQrCodeService::service()->sendMessage($value['ali_user_id'],$value['ali_form_id'],'pages/index/index',$element['content']);
                    }
                    Yii::$app->db->createCommand()->batchInsert(PsParkSpace::tableName(),$fields,$msgData)->execute();
                }
                //删除发布预约
                if(!$model->edit($params)){
                    return $this->failed('删除发布预约失败！');
                }
                //删除共享车位
                PsParkSpace::updateAll(['is_del'=>2],"shared_id=:shared_id and status in (1,2)",[':shared_id'=>$params['id']]);
                //删除预约人预约记录
                if(!empty($recordIds)){
                    PsParkReservation::updateAll(['is_del'=>2],['in','id',$recordIds]);
                }
                $trans->commit();
                return $this->success();
            }else{
                $msg = array_values($model->errors)[0][0];
                return $this->failed($msg);
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /*
     * 车位预约 （默认是业主）
     * 0.调用java接口 获得cropid
     * 1.判断预约人是否在黑名单中
     * 2.判断预约人超时时间是否被锁定
     * 3.判断预约人是否发布人
     * 4.判断预约人今天取消次数
     * 5.判断预约车位是否存在，待预约状态, 车辆是否有相同天数预约的车位（不能恶意占用资源：同一个车牌）预约时间大于共享结束时间 共享结束时间前15分钟不能预约
     * 6.车牌下放 (调用java接口)
     * 7.支付宝消息通知发布者
     * 8.修改共享车位信息
     * 9.添加消息记录
     */
    public function spaceReservation($params){
        $trans = Yii::$app->db->beginTransaction();
        try{
            if(empty($params['community_id'])){
                return $this->failed('小区id不能为空！');
            }

            $javaService = new JavaOfCService();
            $javaParam['token'] = $params['token'];
            $javaParam['id'] = $params['community_id'];
            $javaRes = $javaService->selectCommunityById($javaParam);
            $params['crop_id'] = !empty($javaRes['corpId'])?$javaRes['corpId']:'';

            $model = new PsParkReservation(['scenario'=>'add']);
            if($model->load($params,'')&&$model->validate()){
                if(!$model->saveData()){
                    return $this->failed('新增失败！');
                }
                //车牌下放 待定

                //修改共享车位信息
                $spaceModel = new PsParkSpace(['scenario'=>'info']);
                $info['id'] = $params['space_id'];
                $info['community_id'] = $params['community_id'];
                if($spaceModel->load($info,'')&&$spaceModel->validate()){
                    $spaceParams['status'] = 2;
                    $spaceParams['update_at'] = time();
                    $spaceParams['id'] = $params['space_id'];
                    if(!$spaceModel->edit($spaceParams)){
                        return $this->failed('共享车位信息修改失败！');
                    }
                    $spaceDetail = $spaceModel->getDetail(['id'=>$params['space_id']]);
                    //发送支付宝消息 通知发布者
                    $msg = "您于".date('m月d日',$spaceDetail['shared_at'])."共享的车位已被小区业主预约";
                    AliPayQrCodeService::service()->sendMessage($spaceDetail['ali_user_id'],$spaceDetail['ali_form_id'],'pages/index/index',$msg);
                    //添加消息记录
                    $msgParams['community_id'] = $params['community_id'];
                    $msgParams['community_name'] = $params['community_name'];
                    $msgParams['user_id'] = $spaceDetail['publish_id'];
                    $msgParams['type'] = 1;
                    $msgParams['content'] = $msg;
                    $msgModel = new PsParkMessage(['scenario'=>'add']);
                    if($msgModel->load($msgParams,'')&&$msgModel->validate()){
                        if(!$msgModel->saveData()){
                            return $this->failed('消息新增失败！');
                        }
                    }else{
                        $msg = array_values($msgModel->errors)[0][0];
                        return $this->failed($msg);
                    }
                }else{
                    $msg = array_values($spaceModel->errors)[0][0];
                    return $this->failed($msg);
                }
                $trans->commit();
                return $this->success(['id'=>$model->attributes['id']]);
            }else{
                $msg = array_values($model->errors)[0][0];
                return $this->failed($msg);
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }


    private function batchAddSpace($params){
        $dateParams['start_at'] = date('Y-m-d',$params['start_date']);
        $dateParams['end_at'] = date('Y-m-d',$params['end_date']);
        $dateParams['exec_type_msg'] = $params['exec_type_msg'];
        $dateAll = self::getExecDate($dateParams);
        $fields = [
                    'community_id','community_name','room_id','room_name','publish_id','publish_name','publish_mobile','shared_id',
                    'park_space','ali_form_id','ali_user_id','create_at','update_at','shared_at','start_at','end_at'
        ];
        $data = [];
        if(!empty($dateAll)){
            $element['community_id'] = $params['community_id'];
            $element['community_name'] = $params['community_name'];
            $element['room_id'] = $params['room_id'];
            $element['room_name'] = $params['room_name'];
            $element['publish_id'] = $params['publish_id'];
            $element['publish_name'] = $params['publish_name'];
            $element['publish_mobile'] = $params['publish_mobile'];
            $element['shared_id'] = $params['id'];
            $element['park_space'] = $params['park_space'];

            $element['ali_form_id'] = $params['ali_form_id'];
            $element['ali_user_id'] = $params['ali_user_id'];
            $element['create_at'] = $params['create_at'];
            $element['update_at'] = $params['update_at'];
            foreach($dateAll as $value){
                $element['shared_at'] = strtotime($value);
                $element['start_at'] = strtotime($value." ".$params['start_at']);
                $element['end_at'] = strtotime($value." ".$params['end_at']);
                $data[] = $element;
            }
        }
        if(!empty($data)){
            Yii::$app->db->createCommand()->batchInsert(PsParkSpace::tableName(),$fields,$data)->execute();
        }
    }


    /*
     * 获得执行日期
     */
    public function getExecDate($params){
        $dateAll = [];

        $exec_type_msg = explode(",",$params['exec_type_msg']);
        foreach($exec_type_msg as $value){
            $dateList = self::getWeeklyBuyDate($params['start_at'],$params['end_at'],$value,1);
            if(empty($dateAll)){
                $dateAll = $dateList;
            }else{
                $dateAll = array_merge($dateAll,$dateList);
            }
        }
        asort($dateAll);
        return $dateAll;
    }

    /**
     * desc 获取每x周X执行的所有日期
     * @param string $start 开始日期, 2016-10-17
     * @param string $end 结束日期, 2016-10-17
     * @param int $weekDay 1~5
     * @param int $interval
     * @return array
     */
    public function getWeeklyBuyDate($start, $end, $weekDay,$interval)
    {
        //获取每周要执行的日期 例如: 2016-01-02
        $start = empty($start) ? date('Y-m-d') : $start;
        $startTime = strtotime($start);
        $startDay = date('N', $startTime);
        if ($startDay <= $weekDay) {
            $startTime = strtotime(self::$WORK_DAY[$weekDay]['en'], strtotime($start)); //本周x开始, 例如, 今天(周二)用户设置每周四执行, 那本周四就会开始执行
        } else {
            $startTime = strtotime('next '.self::$WORK_DAY[$weekDay]['en'], strtotime($start));//下一个周x开始, 今天(周二)用户设置每周一执行, 那应该是下周一开始执行
        }

        $endTime = strtotime($end);
        $list = [];
        for ($i=0;;) {

            $dayOfWeek = strtotime("+{$i} week", $startTime); //每周x
            if ($dayOfWeek > $endTime) {
                break;
            }
            $list[] = date('Y-m-d', $dayOfWeek);
            $i = $i+$interval;
        }
        return $list;
    }

}
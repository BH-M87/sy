<?php
namespace service\park;


use app\models\PsParkBlack;
use app\models\PsParkBreakPromise;
use app\models\PsParkMessage;
use app\models\PsParkReservation;
use app\models\PsParkSet;
use app\models\PsParkSpace;
use service\BaseService;
use service\common\AliPayQrCodeService;
use Yii;
use yii\db\Exception;

class CallBackService extends BaseService  {

    /*
     * 车辆出入场
     */
    public function carEntryExit($params){
        if(empty($params['carNum'])){
            return $this->failed('车牌不能为空');
        }
        if(empty($params['arriveTime'])){
            return $this->failed('车辆入场时间不能为空');
        }
        if(empty($params['community_id'])){
            return $this->failed('小区不能为空');
        }
        $params['enter_at'] = !empty($params['arriveTime'])?strtotime($params['arriveTime']):'';
        $params['out_at'] = !empty($params['leaveTime'])?strtotime($params['leaveTime']):'';
        if(!empty($params['enter_at'])&&empty($params['out_at'])){
            //车辆进场
            $entryParams['enter_at'] = $params['enter_at'];
            $entryParams['car_number'] = $params['carNum'];
            $entryParams['community_id'] = $params['community_id'];
            return self::carEntry($entryParams);
        }
        if(!empty($params['enter_at'])&&!empty($params['out_at'])){
            //车辆出场
            $exitParams['enter_at'] = $params['enter_at'];
            $exitParams['out_at'] = $params['out_at'];
            $exitParams['car_number'] = $params['carNum'];
            $exitParams['community_id'] = $params['community_id'];
            return self::carExit($exitParams);
        }
    }

    /*
     * 车辆进场
     * 1.根据车牌 入场时间获得对应预约记录
     * 2.修改预约记录信息
     * 3.修改预约车位信息
     * 4.给共享车位人发送消息
     */
    private function carEntry($params){
        $trans = Yii::$app->db->beginTransaction();
        try{
            //获得预约记录
            $info = PsParkReservation::find()->select(['id','space_id','car_number','community_id','community_name'])
                        ->where(['=','car_number',$params['car_number']])
                        ->andWhere(['=','status',1])
                        ->andWhere(['=','is_del',1])
                        ->andWhere(['=','community_id',$params['community_id']])
                        ->andWhere(['=',"FROM_UNIXTIME(start_at,'%Y-%m-%d')",date('Y-m-d',$params['enter_at'])])
                        ->asArray()->one();
            if(empty($info)){
                return $this->failed('预约信息不存在');
            }
            $nowTime = time();
            $updateParams['status'] = 2;    //使用中
            $updateParams['update_at'] = $nowTime;
            $updateParams['enter_at'] = $params['enter_at'];    //入场时间
            PsParkReservation::updateAll($updateParams,['id'=>$info['id']]);
            $spaceParams['status'] = 3;
            $spaceParams['update_at'] = $nowTime;
            PsParkSpace::updateAll($spaceParams,['id'=>$info['space_id']]);

            $spaceModel = new PsParkSpace();
            $spaceDetail = $spaceModel->getDetail(['id'=>$info['space_id']]);
            //通知发布者
            $msg = $info['car_number']."于".date('H:i',$params['enter_at'])."入场您共享的车位";
            //添加消息记录
            $msgParams['community_id'] = $info['community_id'];
            $msgParams['community_name'] = $info['community_name'];
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

            $trans->commit();
            return $this->success();
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /*
     * 车辆出场
     * 1.记录出场时间
     * 2.判断是否超时
     * 3.如果超时 设置违约记录 （根据设置记录违约锁定时间）预约超过设定次数 加入黑名单
     * 4.积分设置 支付宝通知车位共享者
     * 5.系统删除车辆信息 （调用java接口）
     * 6.修改预约记录信息
     * 7.修改预约车位信息
     */
    private function carExit($params){
        $trans = Yii::$app->db->beginTransaction();
        try{
            $nowTime = time();
            //获得预约记录
            $fields = [
                        'id','space_id','start_at','end_at','community_id','community_name','room_id','room_name',
                        'appointment_id','appointment_name','appointment_mobile','corp_id','car_number'
            ];
            $info = PsParkReservation::find()->select($fields)
                ->where(['=','car_number',$params['car_number']])
                ->andWhere(['=','status',2])
                ->andWhere(['=','is_del',1])
                ->andWhere(['=','community_id',$params['community_id']])
                ->andWhere(['=',"FROM_UNIXTIME(start_at,'%Y-%m-%d')",date('Y-m-d',$params['enter_at'])])
                ->asArray()->one();
            if(empty($info)){
                return $this->failed('预约信息不存在');
            }
            //判断是否超时
            $timeOut = false; //没有超时
            if($params['out_at']>$info['end_at']){
                $info['out_at'] = $params['out_at'];
                $info['enter_at'] = $params['enter_at'];
                $timeOut = self::timeOut($info);
            }

            //添加共享积分
            //添加消息记录
            $setInfo = PsParkSet::find()->select(['black_num','appointment','appointment_unit','lock','lock_unit','min_time','integral'])->where(['=','corp_id',$info['corp_id']])->asArray()->one();
            if(empty($setInfo)){
                return $this->failed('系统设置不存在');
            }
            $spaceModel = new PsParkSpace();
            $spaceDetail = $spaceModel->getDetail(['id'=>$info['space_id']]);
            if(!empty($spaceDetail)){
                //添加共享积分
                $integral = floor(($params['out_at']-$params['enter_at'])/(3600*$setInfo['min_time']))*$setInfo['integral'];
                if($integral>0){
                    PsParkSpace::updateAll(['score'=>$integral],['id'=>$info['space_id']]);
                }
                //通知发布者
                $msg = $info['car_number'].'于'.date('H:i',$params['out_at']).'离场您共享的车位';
                //添加消息记录
                $msgParams['community_id'] = $info['community_id'];
                $msgParams['community_name'] = $info['community_name'];
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

                if($integral>0){
                    //添加积分消息记录
                    $integralMsg = '共享成功，获得'.$integral."积分";
                    //添加消息记录
                    $integralParams['community_id'] = $info['community_id'];
                    $integralParams['community_name'] = $info['community_name'];
                    $integralParams['user_id'] = $spaceDetail['publish_id'];
                    $integralParams['type'] = 2;
                    $integralParams['content'] = $integralMsg;
                    $integralModel = new PsParkMessage(['scenario'=>'add']);
                    if($integralModel->load($msgParams,'')&&$integralModel->validate()){
                        if(!$msgModel->saveData()){
                            return $this->failed('消息新增失败！');
                        }
                    }else{
                        $msg = array_values($integralModel->errors)[0][0];
                        return $this->failed($msg);
                    }
                }

            }
            //删除车辆信息


            //修改预约记录信息
            $reservationUpdate['status'] = $timeOut?3:6;    //已超时or 已完成
            $reservationUpdate['out_at'] = $params['out_at'];
            $reservationUpdate['update_at'] = $nowTime;
            PsParkReservation::updateAll($reservationUpdate,['id'=>$info['id']]);
            //修改预约车位信息

            $spaceParams['status'] = 5; //已完成
            $spaceParams['update_at'] = $nowTime;
            PsParkSpace::updateAll($spaceParams,['id'=>$info['space_id']]);
            $trans->commit();
            return $this->success();
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /*
     * 超时判断
     * 1.获得系统设置 根据小区id调用java接口 会的cropId
     * 2.设置违约记录 （新增or修改）
     * 3.加入黑名单
     */
    private function timeOut($params){

        $setInfo = PsParkSet::find()->select(['black_num','appointment','appointment_unit','lock','lock_unit','min_time','integral'])->where(['=','corp_id',$params['corp_id']])->asArray()->one();
        if(empty($setInfo)){
            return $this->failed('系统设置不存在');
        }
        $timeOut = false;
        if($setInfo['appointment_unit']==1){
            //分钟
            if(($params['out_at']-$params['end_at'])/60-$setInfo['appointment']>0){
                //超时
                $timeOut = true;
            }
        }else{
            //小时
            if(($params['out_at']-$params['end_at'])/3600-$setInfo['appointment']>0){
                //超时
                $timeOut = true;
            }
        }
        if($timeOut){
            $nowTime = time();
            $over_time = ceil(($params['out_at']-$params['end_at'])/60); //超时分钟
            $lock_at = 0;
            switch($setInfo['lock_unit']){
                case 1: //1分钟
                    $lock_at = $nowTime+$setInfo['lock']*60;
                    break;
                case 2: //2小时
                    $lock_at = $nowTime+$setInfo['lock']*3600;
                    break;
                case 3: //3天
                    $lock_at = $nowTime+$setInfo['lock']*86400;
                    break;
                case 4: //4周
                    $lock_at = $nowTime+$setInfo['lock']*86400*7;
                    break;
                case 5: //5月
                    $lock_at = $nowTime+$setInfo['lock']*86400*30;
                    break;
            }

            //超时处理
            $promiseInfo = PsParkBreakPromise::find()->select(['id','break_time','num','lock_at'])
                                ->where(['=','community_id',$params['community_id']])
                                ->andWhere(['=','user_id',$params['appointment_id']])
                                ->asArray()->one();
            $promiseCount = 0;  //累计违约次数
            if(!empty($promiseInfo)){
                //修改
                $updateParams['break_time'] = $promiseInfo['break_time']+$over_time;
                $updateParams['num'] = $promiseInfo['num']+1;
                $updateParams['lock_at'] = $lock_at;
                PsParkBreakPromise::updateAll($updateParams,['id'=>$promiseInfo['id']]);
                $promiseCount = $updateParams['num'];
            }else{
                //新增数据
                $addParams['user_id'] = $params['appointment_id'];
                $addParams['community_id'] = $params['community_id'];
                $addParams['community_name'] = $params['community_name'];
                $addParams['room_id'] = $params['room_id'];
                $addParams['room_name'] = $params['room_name'];
                $addParams['name'] = $params['appointment_name'];
                $addParams['mobile'] = $params['appointment_mobile'];
                $addParams['break_time'] = $over_time;
                $addParams['num'] = 1;
                $addParams['lock_at'] = $lock_at;
                $addParams['create_at'] = $nowTime;
                Yii::$app->db->createCommand()->insert(PsParkBreakPromise::tableName(),$addParams)->execute();
                $promiseCount = 1;
            }

            //判断是否加入黑名单
            if($promiseCount>=$setInfo['black_num']){
                $black = PsParkBlack::find()->select(['id'])
                            ->where(['=','community_id',$params['community_id']])
                            ->andWhere(['=','user_id',$params['appointment_id']])
                            ->asArray()->one();
                if(!empty($black)){
                    $blackUpdate['num'] = $promiseCount;
                    PsParkBlack::updateAll($blackUpdate,['id'=>$black['id']]);
                }else{
                    $blackAdd['user_id'] = $params['appointment_id'];
                    $blackAdd['community_id'] = $params['community_id'];
                    $blackAdd['community_name'] = $params['community_name'];
                    $blackAdd['room_id'] = $params['room_id'];
                    $blackAdd['room_name'] = $params['room_name'];
                    $blackAdd['name'] = $params['appointment_name'];
                    $blackAdd['mobile'] = $params['appointment_mobile'];
                    $blackAdd['num'] = $promiseCount;
                    $blackAdd['create_at'] = $nowTime;
                    Yii::$app->db->createCommand()->insert(PsParkBlack::tableName(),$blackAdd)->execute();
                }
            }
        }

        return $timeOut;
    }


}
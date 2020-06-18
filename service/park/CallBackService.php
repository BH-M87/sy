<?php
namespace service\park;


use app\models\PsParkReservation;
use app\models\PsParkSpace;
use service\BaseService;
use Yii;
use yii\db\Exception;

class CallBackService extends BaseService  {

    /*
     * 车辆出入场
     */
    public function carEntryExit($params){
        $params['enter_at'] = !empty($params['enter_at'])?strtotime($params['enter_at']):'';
        $params['out_at'] = !empty($params['out_at'])?strtotime($params['out_at']):'';
        if(!empty($params['enter_at'])&&empty($params['out_at'])){
            //车辆进场
            $entryParams['enter_at'] = $params['enter_at'];
            $entryParams['car_number'] = $params['car_number'];
            self::carEntry($entryParams);
        }
        if(!empty($params['enter_at'])&&!empty($params['out_at'])){
            //车辆出场
            $exitParams['enter_at'] = $params['enter_at'];
            $exitParams['out_at'] = $params['out_at'];
            $exitParams['car_number'] = $params['car_number'];
            self::carExit($exitParams);
        }
    }

    /*
     * 车辆进场
     * 1.根据车牌 入场时间获得对应预约记录
     * 2.修改预约记录信息
     * 3.修改预约车位信息
     */
    private function carEntry($params){
        $trans = Yii::$app->db->beginTransaction();
        try{

            //获得预约记录
            $info = PsParkReservation::find()->select(['id','space_id'])
                        ->where(['=','car_number',$params['car_number']])
                        ->andWhere(['=','status',1])
                        ->andWhere(['=','is_del',1])
                        ->andWhere(['=',"FROM_UNIXTIME(start_at,'%Y-%m-%d')",date('Y-m-d',$params['start_at'])])
                        ->asArray()->one();
            if(empty($info)){
                return $this->failed('预约信息不存在');
            }
            $nowTime = time();
            $updateParams['status'] = 2;    //使用中
            $updateParams['update_at'] = $nowTime;
            $updateParams['enter_at'] = $params['start_at'];    //入场时间
            PsParkReservation::updateAll($updateParams,['id'=>$info['id']]);

            $spaceParams['status'] = 3;
            $spaceParams['update_at'] = $nowTime;
            PsParkSpace::updateAll($spaceParams,['id'=>$info['space_id']]);
            $trans->commit();
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /*
     * 车辆出场
     * 1.记录出场时间
     * 2.判断是否超时
     * 3.如果超时 设置违约记录 （根据设置记录违约锁定时间）
     * 4.积分设置
     * 5.支付宝通知车位共享者
     * 6.系统删除车辆信息
     * 7.修改预约记录信息
     * 8.修改预约车位信息
     */
    private function carExit($params){
        //获得预约记录
        $info = PsParkReservation::find()->select(['id','space_id','start_at','end_at'])
            ->where(['=','car_number',$params['car_number']])
            ->andWhere(['=','status',2])
            ->andWhere(['=','is_del',1])
            ->andWhere(['=',"FROM_UNIXTIME(start_at,'%Y-%m-%d')",date('Y-m-d',$params['start_at'])])
            ->asArray()->one();
        if(empty($info)){
            return $this->failed('预约信息不存在');
        }

    }


}
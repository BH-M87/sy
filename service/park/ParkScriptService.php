<?php
namespace service\park;

use app\models\PsParkMessage;
use app\models\PsParkReservation;
use app\models\PsParkSpace;
use service\BaseService;
use service\common\AliPayQrCodeService;
use Yii;
use yii\db\Exception;

class ParkScriptService extends BaseService {

    /*
     * 业主车辆在场 预约时间开始前15分钟内 脚本
     * 1.查询符合条件的数据
     * 2.查询业主车辆是否在车场
     * 3.在车库： 生成系统消息（通知业主）
     * 4.在车库：修改共享车位 notice_15字段
     */
    public function notice15(){
        $trans = Yii::$app->db->beginTransaction();
        try{

            $nowTime = time();
            $diffTime = $nowTime - 15*60;
            $result = PsParkSpace::find()->select(['id','publish_id','community_id','community_name','shared_at'])
                            ->where(['=','is_del',1])
                            ->andWhere(['in','status',[1,2]])
                            ->andWhere(['=','notice_15',1])
                            ->andWhere(['>=','start_at',$diffTime])
                            ->andWhere(['<=','start_at',$nowTime])
                            ->asArray()->all();
            if(!empty($result)){
                $fields = ['community_id','community_name','user_id','type','content','create_at','update_at'];
                $data = [];
                $spaceIds = [];
                foreach($result as $key=>$value){
                    //判断业主车辆是否在车库
                    $judge = false;
                    if($judge){
                        $element['community_id'] = $value['community_id'];
                        $element['community_name'] = $value['community_name'];
                        $element['user_id'] = $value['publish_id'];
                        $element['type'] = 1;
                        $element['content'] = "您".date('Y-m-d',$value['shared_at'])."共享的车位。您的车尚在车库，是否继续预约。";
                        $element['create_at'] = $nowTime;
                        $element['update_at'] = $nowTime;
                        $data[] = $element;
                        array_push($spaceIds,$value['id']);
                    }

                }
                if(!empty($data)){
                    Yii::$app->db->createCommand()->batchInsert(PsParkMessage::tableName(),$fields,$data)->execute();
                }
                if(!empty($spaceIds)){
                    PsParkSpace::updateAll(['notice_15'=>'2'],['in','id',$spaceIds]);
                }
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /*
     *业主车辆在场 预约时间开始前5分钟内 脚本
     * 1.查询符合条件的数据
     * 2.查询业主车辆是否在车场
     * 3.在车库： 删除共享车位 通知业主 通知预约者
     */
    public function notice5(){
        $trans = Yii::$app->db->beginTransaction();
        try{

            $nowTime = time();
            $diffTime = $nowTime - 5*60;
            $result = PsParkSpace::find()->alias('space')
                ->leftJoin(['record'=>PsParkReservation::tableName()],'record.space_id=space.id')
                ->select(['space.id','space.publish_id','space.community_id','space.community_name','space.shared_at','record.ali_form_id','record.ali_user_id','record.appointment_id'])
                ->where(['=','space.is_del',1])
                ->andWhere(['in','space.status',[1,2]])
                ->andWhere(['=','space.notice_15',1])
                ->andWhere(['>=','space.start_at',$diffTime])
                ->andWhere(['<=','space.start_at',$nowTime])
                ->asArray()->all();
            if(!empty($result)){
                $fields = ['community_id','community_name','user_id','type','content','create_at','update_at'];
                $data = [];
                $spaceIds = [];
                foreach($result as $key=>$value){
                    //判断业主车辆是否在车库
                    $judge = false;
                    if($judge){
                        $element['community_id'] = $value['community_id'];
                        $element['community_name'] = $value['community_name'];
                        $element['user_id'] = $value['publish_id'];
                        $element['type'] = 1;
                        $element['content'] = "您".date('Y-m-d',$value['shared_at'])."共享的车位。您的车尚在车库，系统将删除您的共享。";
                        $element['create_at'] = $nowTime;
                        $element['update_at'] = $nowTime;
                        $data[] = $element;
                        if($value['appointment_id']){
                            //给预约人发消息通知
                            $msg = "您".date('Y-m-d',$value['shared_at'])."预约的车位。发布者车辆尚在车库，系统将作删除预约信息。";
                            AliPayQrCodeService::service()->sendMessage($value['ali_user_id'],$value['ali_form_id'],'pages/index/index',$msg);
                            //给预约人发消息通知
                            $ele['community_id'] = $value['community_id'];
                            $ele['community_name'] = $value['community_name'];
                            $ele['user_id'] = $value['appointment_id'];
                            $ele['type'] = 1;
                            $ele['content'] = $msg;
                            $ele['create_at'] = $nowTime;
                            $ele['update_at'] = $nowTime;
                            $data[] = $ele;
                        }
                        array_push($spaceIds,$value['id']);
                    }

                }
                if(!empty($data)){
                    Yii::$app->db->createCommand()->batchInsert(PsParkMessage::tableName(),$fields,$data)->execute();
                }
                if(!empty($spaceIds)){
                    PsParkSpace::updateAll(['notice_5'=>'2','is_del'=>2],['in','id',$spaceIds]);
                }
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }
}
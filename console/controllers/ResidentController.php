<?php
// 住户定时脚本
namespace console\controllers;

use yii\db\Query;
use app\models\PsRoomUser;
use service\resident\ResidentService;

Class ResidentController extends ConsoleController
{
    // 住户过期迁出 每分钟执行 */1 * * * * docker exec -it 37b175573c2c php api/yii resident/move-out
    public function actionMoveOut()
    {
        // 查询id出来，再执行更新，避免锁全表
        //$m = PsRoomUser::find()->where(['identity_type' => 3, 'status' => [1, 2]])
            //->andWhere(['>', 'time_end', 0])->andWhere(['<', 'time_end', time()])->all();
        $query = new Query();
        $m = $query->from("ps_room_user")
            ->where(['identity_type' => 3, 'status' => [1, 2]])
            ->andWhere(['>', 'time_end', 0])->andWhere(['<', 'time_end', time()])->all();
        
        if (!empty($m)) {
            foreach ($m as $v) {
                // 迁出租客的时候会需要把这个人同时也在JAVA那边删除，因此直接调用迁出的service
                $userInfo = ['id' => '1', 'username' => '系统操作'];
                ResidentService::service()->moveOut($v->id, $userInfo, $v->community_id);
            }
        }
    }
}
<?php
// 住户定时脚本
namespace console\controllers;

use app\models\PsRoomUser;
use service\resident\ResidentService;

Class ResidentController extends ConsoleController
{
    // 住户过期迁出 每分钟执行
    public function actionMoveOut()
    {
        // 查询id出来，再执行更新，避免锁全表
        $m = PsRoomUser::find()->where(['identity_type' => 3, 'status' => [1, 2]])
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
<?php
/**
 * 值班排班相关服务
 * User: wenchao.feng
 * Date: 2019/9/6
 * Time: 11:43
 */

namespace service\street;


use app\models\StScheduling;

class SchedulingService extends BaseService
{
    public function view($params)
    {
        $scheduleData = StScheduling::find()
            ->select('id, user_id, user_name, user_type, user_mobile, day_type')
            ->where(['organization_type' => $params['organization_type'], 'organization_id' => $params['organization_id']])
            ->asArray()
            ->all();
        $this->_processData($scheduleData);

    }

    public function publish($params, $userInfo = [])
    {

    }

    private function _processData($data)
    {
        print_r($data);


    }
}
<?php
/**
 * 值班排班相关服务
 * User: wenchao.feng
 * Date: 2019/9/6
 * Time: 11:43
 */

namespace service\street;


use app\models\StScheduling;
use app\models\UserInfo;

class SchedulingService extends BaseService
{
    public function view($params)
    {
        $scheduleData = StScheduling::find()
            ->select('id, user_id, user_name, user_type, user_mobile, day_type')
            ->where(['organization_type' => $params['organization_type'], 'organization_id' => $params['organization_id']])
            ->asArray()
            ->all();
        return $this->_processData($scheduleData);

    }

    public function publish($params, $userInfo = [])
    {
        print_r($params);exit;
//        foreach ($params as $k => $v) {
//            $v['organization_type'] = $params
//            $insertData[] = $v;
//        }
    }

    private function _processData($data)
    {
        $reData = [
            'day1' => [],
            'day2' => [],
            'day3' => [],
            'day4' => [],
            'day5' => [],
            'day6' => [],
            'day7' => []
        ];
        foreach ($data as $k => $v) {
            //查询头像
            $photo = UserInfo::find()
                ->select('profile_image')
                ->where(['user_id' => $v['user_id']])
                ->asArray()
                ->scalar();
            $v['user_photo'] = $photo ? $photo : '';
            $tmpKey = 'day'.$v['day_type'];
            unset($v['day_type']);
            array_push($reData[$tmpKey], $v);
        }

        //按照职级排序，值班领导
        foreach ($reData as $k => $v) {
            $tmpArr = $v;
            if (count($tmpArr) > 1) {
                foreach ($tmpArr as $kk => $vv) {
                    $userTypeList[] = $vv['user_type'];
                }
                array_multisort($userTypeList, SORT_ASC, $tmpArr);
                $reData[$k] = $tmpArr;
            }
        }
        return $reData;
    }

    private function _validateData()
    {

    }
}
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
use common\MyException;

class SchedulingService extends BaseService
{
    public function view($params)
    {
        $scheduleData = StScheduling::find()
            ->alias('st')
            ->leftJoin('user_info u','st.user_id = u.user_id')
            ->select('st.id, st.user_id, u.username as user_name, st.user_type, u.mobile_number as user_mobile, st.day_type, u.profile_image as user_photo')
            ->where(['st.organization_type' => $params['organization_type'], 'st.organization_id' => $params['organization_id']])
            ->asArray()
            ->all();
        return $this->_processData($scheduleData);

    }

    public function publish($params, $userInfo = [])
    {
        $insertData = [];
        $day1Data = $params['day1'];
        $day2Data = $params['day2'];
        $day3Data = $params['day3'];
        $day4Data = $params['day4'];
        $day5Data = $params['day5'];
        $day6Data = $params['day6'];
        $day7Data = $params['day7'];
        $tmpData = $this->_validateData($day1Data, 1);
        $insertData = array_merge($insertData, $tmpData);
        $tmpData = $this->_validateData($day2Data, 2);
        $insertData = array_merge($insertData, $tmpData);
        $tmpData = $this->_validateData($day3Data, 3);
        $insertData = array_merge($insertData, $tmpData);
        $tmpData = $this->_validateData($day4Data, 4);
        $insertData = array_merge($insertData, $tmpData);
        $tmpData = $this->_validateData($day5Data, 5);
        $insertData = array_merge($insertData, $tmpData);
        $tmpData = $this->_validateData($day6Data, 6);
        $insertData = array_merge($insertData, $tmpData);
        $tmpData = $this->_validateData($day7Data, 7);
        $insertData = array_merge($insertData, $tmpData);
        StScheduling::deleteAll(['organization_type' => $params['organization_type'], 'organization_id' => $params['organization_id']]);

        $insert_data = [];
        foreach ($insertData as $k => $v) {
            $insert_data['organization_type'][] = $params['organization_type'];
            $insert_data['organization_id'][] = $params['organization_id'];
            $insert_data['user_id'][] = $v['user_id'];
            $insert_data['user_type'][] = $v['user_type'];
            $insert_data['day_type'][] = $v['day_type'];
            $insert_data['operator_id'][] = $userInfo['id'];
            $insert_data['operator_name'][] = $userInfo['username'];
            $insert_data['create_at'][] = time();
        }
        $res = StScheduling::model()->batchInsert($insert_data);
        if ($res) {
            return true;
        } else {
            throw new MyException("编辑失败");
        }
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
            $v['user_photo'] = $v['user_photo'] ? $v['user_photo'] : '';
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

    private function _validateData($data, $dayType)
    {
        $userIds = [];
        $hasAdmin = 0;

        foreach ($data as $k => $v) {
            $userInfo = UserInfo::find()
                ->select('username as user_name,profile_image as user_photo,mobile_number')
                ->where(['user_id' => $v['user_id']])
                ->asArray()
                ->one();
            if (!$userInfo) {
                throw new MyException($v['user_name']."，此用户不存在");
            }
            if (in_array($v['user_id'], $userIds)) {
                throw new MyException($v['user_name']."，此用户已存在，不能重复添加");
            }
            array_push($userIds, $v['user_id']);
            if ($hasAdmin && $v['user_type'] == 1) {
                throw new MyException("数据有误，只能设置1个管理员");
            }
            if ($v['user_type'] == 1) {
                $hasAdmin = 1;
            }
            $tmp['user_id'] = $v['user_id'];
            $tmp['user_type'] = $v['user_type'];
            $tmp['day_type'] = $dayType;
            $sucData[] = $tmp;
        }
        return !empty($sucData) ? $sucData : [];
    }
}
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
        $insertData = $this->_validateData($params['user_ids'], $params['day_type']);
        StScheduling::deleteAll(['organization_type' => $params['organization_type'], 'organization_id' => $params['organization_id'], 'day_type' => $params['day_type']]);

        if (is_array($insertData) && count($insertData) > 0) {
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
        return true;
    }

    private function _processData($data)
    {
        $reData = [
            '1' => [],
            '2' => [],
            '3' => [],
            '4' => [],
            '5' => [],
            '6' => [],
            '7' => []
        ];
        foreach ($data as $k => $v) {
            //查询头像
            $v['user_photo'] = $v['user_photo'] ? $v['user_photo'] : '';
            $tmpKey = $v['day_type'];
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
        if (empty($data)) {
            return true;
        }
        $userIds = [];
        if (count($data) > 5) {
            throw new MyException("每天最多5人");
        }
        foreach ($data as $k => $v) {
            $userInfo = UserInfo::find()
                ->select('username as user_name,profile_image as user_photo,mobile_number')
                ->where(['user_id' => $v])
                ->asArray()
                ->one();
            if (!$userInfo) {
                throw new MyException("用户不存在");
            }
            if (in_array($v, $userIds)) {
                throw new MyException($userInfo['user_name']."，此用户已存在，不能重复添加");
            }
            array_push($userIds, $v);

            $tmp['user_id'] = $v;
            $tmp['user_type'] = $k === 0 ? 1 : 2;
            $tmp['day_type'] = $dayType;
            $sucData[] = $tmp;
        }
        return !empty($sucData) ? $sucData : [];
    }
}
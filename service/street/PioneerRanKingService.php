<?php
/**
 * User: yjh
 * Date: 2019/9/4
 * Time: 15:23
 * For: ****
 */

namespace service\street;


use app\models\StCommunist;
use app\models\StCommunistAppUser;
use app\models\StPartyTask;
use app\models\StPartyTaskOperateRecord;
use app\models\StPartyTaskStation;
use common\MyException;
use service\BaseService;


class PioneerRanKingService extends BaseService
{

    /**
     * 年份列表
     * @author yjh
     * @return array
     */
    public function getYearsList()
    {
        $max = StPartyTask::find()->select('create_at')->orderBy('create_at desc')->limit(1)->asArray()->one();
        $min = StPartyTask::find()->select('create_at')->orderBy('create_at')->limit(1)->asArray()->one();
        $max = date('Y',$max['create_at']);
        $min = date('Y',$min['create_at']);
        $data = [];
        for ($i = $min; $i<=$max;$i++) {
            $data['years']['list'][] = ['name' => $i,'id' => $i];
        }
        return $data;
    }

    /**
     * 后台先锋列表
     * @author yjh
     * @param $params
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function getList($params)
    {
        $params['years'] = empty($params['years']) ? date('Y',time()) : $params['years'];
        $params['start'] = strtotime($params['years'].'-01-01 00:00');
        $params['end'] = strtotime($params['years'].'-12-31 24:00');
        $data = StPartyTaskStation::getOrderList($params,true,false);
        return $data;
    }

    /**
     * 先锋详情
     * @author yjh
     * @param $params
     * @return array|\yii\db\ActiveRecord|null
     * @throws MyException
     */
    public function getInfo($params)
    {
        if (empty($params['communist_id'])) throw new MyException('党员ID不能为空');
        $info = StCommunist::find()->select('id,type,sex,image,name,branch,organization_type,job')->where(['id' => $params['communist_id']])->asArray()->one();
        if (!$info) {
            throw new MyException('ID错误');
        }
        $year = date('Y',time());
        $start = strtotime($year.'-01-01 00:00');
        $end = strtotime($year.'-12-31 24:00');
        $info['type_name'] = StCommunist::$type_desc[$info['type']];
        $info['organization_type_desc'] = $info['organization_type'] == 1 ? '1街道本级' : '社区';
        $info['sex'] = $info['sex'] == 1 ? '男' : '女';
        $info['all_score'] = StPartyTaskStation::find()->where(['communist_id' => $params['communist_id'],'status' => 3])->sum('pioneer_value');
        $info['year_score'] = StPartyTaskStation::find()->where(['communist_id' => $params['communist_id'],'status' => 3])
            ->andFilterWhere(['>','create_at',$start])
            ->andFilterWhere(['<','create_at',$end])
            ->sum('pioneer_value');
        return $info;
    }

    /**
     * 明细列表
     * @author yjh
     * @param $params
     * @return mixed
     * @throws MyException
     */
    public function getInfoList($params)
    {
        if (empty($params['communist_id'])) throw new MyException('党员ID不能为空');
        $info = StCommunist::find()->where(['id' => $params['communist_id']])->asArray()->one();
        if (!$info) {
            throw new MyException('ID错误');
        }
        $params['start'] = empty($params['start_time']) ? null : strtotime($params['start_time'].' 00:00');
        $params['end'] = empty($params['end_time']) ? null : strtotime($params['end_time'].' 24:00');
        $params['status'] = 3;
        $data = StPartyTaskStation::getList($params);
        return $data;
    }

    //##########################先锋排名###########################

    /**
     * 先锋列表
     * @author yjh
     * @param $params
     * @return mixed
     * @throws MyException
     * @throws \yii\db\Exception
     */
    public function getCommunistList($params)
    {
        $communist = PartyTaskService::service()->checkUser($params['user_id']);
        $params['years'] = date('Y',time());
        $params['start'] = strtotime($params['years'].'-01-01 00:00');
        $params['end'] = strtotime($params['years'].'-12-31 24:00');
        $data = StPartyTaskStation::getOrderList($params);
        $user_top = StPartyTaskStation::getUserTop($communist['id']);
        $data['user'] = $user_top[0] ?? null;
        return $data;
    }

    /**
     * 先锋明细列表
     * @author yjh
     * @param $params
     * @return mixed
     * @throws MyException
     * @throws \yii\db\Exception
     */
    public function getCommunistInfoList($params)
    {
        $communist = PartyTaskService::service()->checkUser($params['user_id']);
        $user_info = StPartyTaskStation::getUserTop($communist['id'],false);
        $info_list = $this->getInfoList(['communist_id' => $communist['id']]);
        $info_list['grade_order'] = $user_info[0]['grade_order'];
        $info_list['name'] = $user_info[0]['name'];
        $info_list['task_count'] = $user_info[0]['task_count'];
        $info_list['image'] = $user_info[0]['image'];
        return $info_list;
    }


    public function getStationList($params)
    {
        $communist = PartyTaskService::service()->checkUser($params['user_id']);
        $params['id'] = $communist['id'];
        $commInfo = CommunistService::service()->getData($params);
        $stationParam['organization_type'] = $commInfo->organization_type;
        $stationParam['organization_id'] = $commInfo->organization_id;
        return StationService::service()->getSimpleList($stationParam);
    }
    /**
     * 个人信息
     * @author yjh
     * @param $params
     * @return array|\yii\db\ActiveRecord|null
     * @throws MyException
     */
    public function getUserInfo($params)
    {
        //个人信息
        $communist = PartyTaskService::service()->checkUser($params['user_id']);
        $communist['join_party_time'] = date('Y-m-d H:i:s',$communist['join_party_time']);
        $communist['formal_time'] = date('Y-m-d H:i:s',$communist['formal_time']);
        $communist['birth_time'] = date('Y-m-d H:i:s',$communist['birth_time']);
        $communist['type_info'] = StCommunist::$type_desc[$communist['type']];

        $params['years'] = date('Y',time());
        $params['start'] = strtotime($params['years'].'-01-01 00:00');
        $params['end'] = strtotime($params['years'].'-12-31 24:00');
        //统计
        $model = StPartyTaskStation::find()->alias('sts')->select('sts.*')
            ->leftJoin('st_party_task as st', 'st.id = sts.task_id')
            ->where(['sts.communist_id' => $communist['id']])
            ->andFilterWhere(['>','sts.create_at',$params['start']])
            ->andFilterWhere(['<','sts.create_at',$params['end']]);
        $communist['task_statistics_info']['totals_num'] = $model->count();
        $communist['task_statistics_info']['wait_aduit_num'] = $model->andWhere(['sts.status' => 2])->count();
        $communist['task_statistics_info']['aduit_done_num'] =$model->andWhere(['sts.status' => 3])->count();
        $communist['task_statistics_info']['wait_do_num'] =$model->andWhere(['sts.status' => 1])->count();
        $communist['task_statistics_info']['cancel_done_num'] =$model->andWhere(['sts.status' => 4])->count();
        $checkUser = CommunistService::service()->getUser($params['user_id']);
        $communist['is_communist'] = $checkUser ? 1 : 0;
        return $communist;
    }
}
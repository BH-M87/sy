<?php
/**
 * User: yjh
 * Date: 2019/9/4
 * Time: 15:23
 * For: ****
 */

namespace service\street;


use app\models\StCommunist;
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
     * 先锋列表
     * @author yjh
     * @param $params
     * @return mixed
     */
    public function getList($params)
    {
        $params['years'] = empty($params['years']) ? date('Y',time()) : $params['years'];
        $params['start'] = strtotime($params['years'].'-01-01 00:00');
        $params['end'] = strtotime($params['years'].'-12-31 24:00');
        $data = StPartyTaskStation::getOrderList($params);
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

}
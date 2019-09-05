<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_party_task_station".
 *
 * @property string $id id
 * @property string $task_id 任务id
 * @property string $communist_id 党员id
 * @property int $status 审核状态 1=待完成 2=审核中 3=已审核 4=已取消
 * @property int $pioneer_value 获得的先锋值
 * @property string $create_at 创建时间
 */
class StPartyTaskStation extends \yii\db\ActiveRecord
{
    public static $audit_status_msg = [
        1 => '待完成',
        2 => '审核中',
        3 => '已审核',
        4 => '已取消',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_party_task_station';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['task_id', 'communist_id', 'status', 'pioneer_value', 'create_at'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'Task ID',
            'communist_id' => 'Communist ID',
            'status' => 'Status',
            'pioneer_value' => 'Pioneer Value',
            'create_at' => 'Create At',
        ];
    }

    /**
     * 获取列表
     * @author yjh
     * @param $param
     * @param bool $page
     * @return mixed
     */
    public static function getList($param,$page=true)
    {
        $model = self::find()->filterWhere(['status' => $param['status'] ?? null])
            ->andFilterWhere(['communist_id' => $param['communist_id']])
            ->andFilterWhere(['<=','create_at',$param['end'] ?? null])
            ->andFilterWhere(['<=','create_at',$param['end'] ?? null]);
        $model->orderBy([ 'create_at' => SORT_DESC]);
        if ($page) {
            $page = !empty($param['page']) ? $param['page'] : 1;
            $row = !empty($param['rows']) ? $param['rows'] : 10;
            $page = ($page-1)*$row;
            $count = $model->count();
            $data['totals'] = $count;
            $model->offset($page)->limit($row);
        }
        $data['list'] = $model->asArray()->all();
        if (!empty($data['list'])) {
            self::afterList($data['list']);
        }
        return $data;
    }

    /**
     * 获取审核列表
     * @author yjh
     * @param $param
     * @param bool $page
     * @return mixed
     */
    public static function getExamineList($param,$page=true)
    {
        $model = self::find()->alias('sts')
            ->select('st.task_name,sts.id,sts.status,sts.pioneer_value,sc.name,sc.mobile,st.station_id')
            ->leftJoin('st_party_task as st', 'st.id = sts.task_id')
            ->leftJoin('st_communist as sc', 'sc.id = sts.communist_id')
            ->filterWhere(['status' => $param['audit_status'] ?? [2,3]])
            ->andFilterWhere(['station_id' => $param['station_id'] ?? null])
            ->andFilterWhere(['like', 'name', $param['communist_name'] ?? null])
            ->andFilterWhere(['like', 'mobile', $param['communist_mobile'] ?? null])
            ->andFilterWhere(['like', 'task_name', $param['task_name'] ?? null]);
        $model->orderBy([ 'id' => SORT_DESC]);
        if ($page) {
            $page = !empty($param['page']) ? $param['page'] : 1;
            $row = !empty($param['rows']) ? $param['rows'] : 10;
            $page = ($page-1)*$row;
            $count = $model->count();
            $data['totals'] = $count;
            $model->offset($page)->limit($row);
        }
        $data['list'] = $model->asArray()->all();
        if (!empty($data['list'])) {
            self::afterExamineList($data['list']);
        }
        return $data;
    }

    /**
     * 获取排名列表
     * @author yjh
     * @param $param
     * @param bool $page
     * @return mixed
     */
    public static function getOrderList($param,$page=true)
    {
        $model = self::find()
            ->alias('sts')
            ->select('SUM(sts.pioneer_value) as total_score,sts.id,sc.name,sc.mobile,sc.branch,sc.type,sts.communist_id')
            ->leftJoin('st_communist as sc', 'sc.id = sts.communist_id')
            ->filterWhere(['or', ['like', 'name', $param['contact_name'] ?? null], ['like', 'mobile', $param['contact_name'] ?? null]])
            ->andFilterWhere(['>','sts.create_at',$param['start']])
            ->andFilterWhere(['<','sts.create_at',$param['end']])
            ->andFilterWhere(['sts.status' => 3])
            ->groupBy('communist_id')
            ->orderBy([ 'total_score' => SORT_DESC]);
        if ($page) {
            $page = !empty($param['page']) ? $param['page'] : 1;
            $row = !empty($param['rows']) ? $param['rows'] : 10;
            $pages = ($page-1)*$row;
            $count = $model->count();
            $data['totals'] = $count;
            $model->offset($pages)->limit($row);
        }
        $data['list'] = $model->asArray()->all();
        if (!empty($data['list'])) {
            self::afterOrderList($data['list'],$page,$row);
        }
        return $data;
    }


    public static function afterOrderList(&$data,$page,$row)
    {
        $num = $page == 1 ? 1 : $row*$page+1;
        foreach ($data as &$v) {
            $v['grade_order'] = $num++; //算排名
            $v['type_name'] = StCommunist::$type_desc[$v['type']];
        }
    }

    public static function afterExamineList(&$data)
    {
        foreach ($data as &$v) {
            $v['station_name'] = StStation::find()->where(['id' => $v['station_id']])->asArray()->one()['station'];
            $v['status_msg'] = self::$audit_status_msg[$v['status']];
        }
    }

    public static function afterList(&$data)
    {
        foreach ($data as &$v) {
            $task = StPartyTask::find()->where(['id' => $v['task_id']])->asArray()->one();
            $v['task_name'] = $task['task_name'];
            $v['status_msg'] = self::$audit_status_msg[$v['status']];
            $v['station_name'] = StStation::find()->where(['id' => $task['station_id']])->asArray()->one()['station'];
            $v['communist_name'] = StCommunist::find()->where(['id' => $v['communist_id']])->asArray()->one()['name'];
            $v['communist_mobile'] = StCommunist::find()->where(['id' => $v['communist_id']])->asArray()->one()['mobile'];
            $v['create_at'] = date('Y-m-d H:i:s',$v['create_at']);
        }
    }
}

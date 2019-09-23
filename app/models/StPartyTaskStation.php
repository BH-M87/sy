<?php

namespace app\models;

use common\core\F;
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
 * @property string $update_at 创建时间
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
            'update_at' => 'update_at',
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
            ->andFilterWhere(['communist_id' => $param['communist_id'] ?? null])
            ->andFilterWhere(['task_id' => $param['id'] ?? null])
            ->andFilterWhere(['<=','create_at',$param['end'] ?? null])
            ->andFilterWhere(['>=','create_at',$param['start'] ?? null]);
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
            ->select('st.task_name,sts.id,sts.status,sts.pioneer_value,sc.name,sc.mobile,st.station_id,sts.task_id')
            ->leftJoin('st_party_task as st', 'st.id = sts.task_id')
            ->leftJoin('st_communist as sc', 'sc.id = sts.communist_id')
            ->filterWhere(['status' => $param['audit_status'] ?? [2,3]])
            ->andFilterWhere(['st.station_id' => $param['station_id'] ?? null])
            ->andFilterWhere(['st.organization_type' => $param['organization_type'] ?? null])
            ->andFilterWhere(['st.organization_id' => $param['organization_id'] ?? null])
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
     * @param $type true 小程序 false后台
     * @param bool $page
     * @return mixed
     * @throws \yii\db\Exception
     */
    public static function getOrderList($param,$page=true,$type = true)
    {
        $model = self::find()
            ->alias('sts')
            ->select('sc.image,COUNT(`task_id`) as task_count,SUM(sts.pioneer_value) as grade_order,sts.id,sc.name,sc.mobile,sc.branch,sc.type,sts.communist_id')
            ->innerJoin('st_communist as sc', 'sc.id = sts.communist_id')
            ->filterWhere(['or', ['like', 'name', $param['contact_name'] ?? null], ['like', 'mobile', $param['contact_name'] ?? null]])
            ->andFilterWhere(['>','sts.create_at',$param['start']])
            ->andFilterWhere(['sc.organization_id' => $param['organization_id']])
            ->andFilterWhere(['sc.organization_type' => $param['organization_type']])
            ->andFilterWhere(['sts.status' => 3])
            ->groupBy('communist_id')
            ->orderBy([ 'grade_order' => SORT_DESC,'task_count' => SORT_DESC]);
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
            if ($type) {
                self::afterOrderList($data['list'],$param);
            } else {
                self::afterBackendOrderList($data['list'],$param);
            }
        }
        array_multisort(array_column($data['list'],'top'),SORT_ASC,$data['list']);
        return $data;
    }

    /**
     * 获取个人排名
     * @author yjh
     * @param $communist_id
     * @param $params
     * @param $type true 获取排名 false不获取
     * @return array|\yii\db\ActiveRecord|null
     * @throws \yii\db\Exception
     */
    public static function getUserTop($communist_id,$type = true,$params = [])
    {
        $model = self::find()
            ->alias('sts')
            ->select('sc.image,COUNT(`task_id`) as task_count,SUM(sts.pioneer_value) as grade_order,sts.id,sc.name,sc.mobile,sc.branch,sc.type,sts.communist_id')
            ->leftJoin('st_communist as sc', 'sc.id = sts.communist_id')
            ->andFilterWhere(['sts.status' => 3])
            ->andFilterWhere(['sts.communist_id' => $communist_id])
            ->andFilterWhere(['sc.organization_id' => $params['organization_id'] ?? null])
            ->andFilterWhere(['sc.organization_type' => $params['organization_type'] ?? null])
            ->groupBy('communist_id')
            ->orderBy([ 'grade_order' => SORT_DESC,'task_count' => SORT_DESC]);
        $data = $model->asArray()->all();
        if (!empty($data)) {
            if ($type) {
                self::afterOrderList($data,$params);
            }
        }
        return $data;
    }


    /**
     * 后台排名处理
     * @author yjh
     * @param $data
     * @throws \yii\db\Exception
     */
    public static function afterBackendOrderList(&$data,$param)
    {
        foreach ($data as &$v) {
            $v['top'] = self::getTop($v['id'],$param);
            $v['type_name'] = StCommunist::$type_desc[$v['type']];
        }
    }

    /**
     * 小程序排名处理
     * @author yjh
     * @param $data
     * @throws \yii\db\Exception
     */
    public static function afterOrderList(&$data,$param = null)
    {
        $top = self::getTop($data[0]['id'],$param);
        foreach ($data as &$v) {
            $v['top'] = $top++; //算排名
            $v['image'] = F::getOssImagePath($v['image']);
            $v['type_name'] = StCommunist::$type_desc[$v['type']];
        }
    }


    /**
     * 获取当前人员的排名
     * @author yjh
     * @param $id
     * @return mixed
     * @throws \yii\db\Exception
     */
    public static function getTop($id,$param)
    {
        $connection  = Yii::$app->db;
        $sql = 'SET @xh=0;';
        $command = $connection->createCommand($sql);
        $command->execute();
        $a_model = self::find()
            ->alias('sts')
            ->select('sc.image,COUNT(`task_id`) as task_count,SUM(sts.pioneer_value) as total_score,sts.id,sc.name,sc.mobile,sc.branch,sc.type,sts.communist_id')
            ->leftJoin('st_communist as sc', 'sc.id = sts.communist_id')
            ->andFilterWhere(['sts.status' => 3])
            ->andFilterWhere(['sc.organization_id' => $param['organization_id'] ?? null])
            ->andFilterWhere(['sc.organization_type' => $param['organization_type'] ?? null])
            ->groupBy('communist_id')
            ->orderBy([ 'total_score' => SORT_DESC,'task_count' => SORT_DESC]);
        $result = self::find()->select('(@xh := @xh + 1) as top,a.*')->from(['a' => $a_model])->asArray()->all();
        $found_key = array_search($id, array_column($result, 'id'));
        $top = $result[$found_key]['top'];
        return $top;
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

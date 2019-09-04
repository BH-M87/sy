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
        $model = self::find()->filterWhere(['status' => $param['status'] ?? null]);
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

    public static function afterList(&$data)
    {
        foreach ($data as &$v) {
            $v['task_name'] = StPartyTask::find()->where(['id' => $v['task_id']])->asArray()->one()['task_name'];
            $v['status_msg'] = self::$audit_status_msg[$v['status']];
            $v['communist_name'] = StCommunist::find()->where(['id' => $v['communist_id']])->asArray()->one()['name'];
            $v['communist_mobile'] = StCommunist::find()->where(['id' => $v['communist_id']])->asArray()->one()['mobile'];
            $v['create_at'] = date('Y-m-d H:i:s',$v['create_at']);
        }
    }
}

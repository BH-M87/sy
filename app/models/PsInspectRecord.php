<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_inspect_record".
 *
 * @property int $id
 * @property int $community_id 小区Id
 * @property int $user_id 执行人ID
 * @property int $plan_id 计划ID
 * @property int $line_id 线路Id
 * @property string $task_name 任务名称
 * @property string $line_name 线路名称
 * @property string $head_name 线路负责人
 * @property string $head_mobile 线路负责人联系方式
 * @property int $plan_start_at 计划开始时间
 * @property int $plan_end_at 计划结束时间
 * @property int $check_start_at 巡检开始时间
 * @property int $check_end_at 巡检结束时间
 * @property int $status 走访记录状态，1未完成 2部分完成 3已完成
 * @property int $point_count 巡检点数量
 * @property int $finish_count 完成数量
 * @property int $miss_count 漏检数量
 * @property int $issue_count 异常数量
 * @property int $finish_rate 完成率
 * @property int $create_at 创建时间
 */
class PsInspectRecord extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_inspect_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'plan_id', 'line_id', 'task_at', 'check_start_at', 'check_end_at', 'error_minute', 'status', 'run_status', 'result_status', 'point_count','finish_count','miss_count', 'issue_count', 'finish_rate', 'create_at','update_at'], 'integer'],
            [['task_name', 'line_name', 'head_name'], 'string', 'max' => 50],
            [['community_id', 'user_id', 'dd_user_id'], 'string', 'max' => 30],
            [['head_mobile'], 'string', 'max' => 20],
            [['id','community_id'], 'required','on'=>['detail']],
            [['id','community_id'],'infoData','on'=>["detail"]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
              'id'              => '任务id',
              'community_id'    => '小区Id',
              'user_id'         => '执行人ID',
              'dd_user_id'      => '执行人钉钉ID',
              'plan_id'         => '计划ID',
              'line_id'         => '线路Id',
              'task_name'       => '任务名称',
              'line_name'       => '线路名称',
              'head_name'       => '线路负责人',
              'head_mobile'     => '线路负责人联系方式',
              'task_at'         => '任务日期',
              'check_start_at'  => '巡检开始时间',
              'check_end_at'    => '巡检结束时间',
              'error_minute'    => '允许误差分钟',
              'status'          => '任务状态，1待巡检 2巡检中 3已完成 4已关闭',
              'run_status'      => '任务执行状态，1逾期 2旷巡 3正常',
              'result_status'   => '巡检结果状态，1未完成 2异常 3正常',
              'point_count'     => '巡检点数量',
              'finish_count'    => '完成数量',
              'miss_count'      => '漏检数量',
              'issue_count'     => '异常数量',
              'finish_rate'     => '完成率',
              'create_at'       => '创建时间',
              'update_at'       => '修改时间',
        ];
    }

    /***
     * 验证是否存在
     * @param $attribute
     */
    public function infoData($attribute)
    {
        if (!empty($this->id)&&!empty($this->community_id)) {
            $res = static::find()->select(['id'])->where('id=:id and community_id=:community_id', [':id' => $this->id,":community_id" => $this->community_id])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "该任务不存在!");
            }
        }
    }

    /***
     * 修改
     * @return bool
     */
    public function edit($param)
    {
        $param['update_at'] = time();
        return self::updateAll($param, ['id' => $param['id']]);
    }

    //列表
    public function getList($params){
        $fields = ['id','community_id','task_name','check_start_at','check_end_at','user_id','head_name','status','run_status','result_status'];
        $model = self::find()->select($fields)
            ->andFilterWhere(['in', 'community_id', $params['communityIds']])
            ->andFilterWhere(['=', 'community_id', $params['community_id']])
            ->andFilterWhere(['like', 'task_name', $params['task_name']])
            ->andFilterWhere(['=', 'status', $params['status']])
            ->andFilterWhere(['=', 'user_id', $params['user_id']])
            ->andFilterWhere(['=', 'task_at', $params['task_at']])
            ->andFilterWhere(['=', 'run_status', $params['run_status']]);
        $count = $model->count();
        $page = intval($params['page']);
        $pageSize = intval($params['pageSize']);
        $offset = ($page-1)*$pageSize;
        $model->offset($offset)->limit($pageSize);
        $model->orderBy(["id"=>SORT_DESC]);
        $result = $model->asArray()->all();
        return ['count'=>$count,'data'=>$result];
    }

    /*
     * 任务单表
     */
    public function getDataOne($params){
        $fields = ['status'];
        $model = self::find()->select($fields)->where(['=','id',$params['id']]);
        return $model->asArray()->one();

    }
}

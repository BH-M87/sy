<?php

namespace app\models;

use yii\db\ActiveRecord;

;


/**
 * This is the model class for table "ps_inspect_plan".
 *
 * @property integer $id
 * @property string $name
 * @property integer $community_id
 * @property integer $line_id
 * @property integer $exec_type
 * @property string $user_list
 * @property integer $status
 * @property integer $operator_id
 * @property integer $create_at
 */
class PsInspectPlan extends BaseModel
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_inspect_plan';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id','name','start_at','end_at','task_name','line_id', 'user_list','exec_interval','exec_type', 'operator_id'], 'required','on'=>'add'],
            [['community_id','name','start_at','end_at','task_name','line_id', 'user_list','operator_id'], 'required','on'=>'tempAdd'],
            [['id','community_id'], 'required','on'=>['detail','editStatus']],
            [['id','community_id'],'infoData','on'=>["detail",'editStatus']],
            [['community_id','name'],'nameUnique','on'=>['add','tempAdd']],   //计划名称唯一
            [['id', 'start_at','end_at','line_id', 'exec_type', 'exec_interval', 'error_minute', 'status','create_at','update_at','type'], 'integer'],
            [['exec_type'], 'in', 'range' => [1, 2, 3, 4], 'message' => '{attribute}取值范围错误'],
            [['exec_type','exec_type_msg'],'execVerification','on'=>'add'], //执行间隔验证
            [['type'], 'in', 'range' => [1, 2], 'message' => '{attribute}取值范围错误'],
            [['start_at','end_at'],'planTimeVerification','on'=>['add']],
            [['start_at','end_at'],'planTimeEqualVerification','on'=>['tempAdd']],
            [['name'], 'string', 'max' => 30],
            [['task_name'], 'string', 'max' => 20],
            [['user_list'], 'string', 'max' => 500],
            [['exec_type_msg'], 'string', 'max' => 200],
            [['community_id','operator_id'],'string','max'=>30],
            ['status', 'default', 'value' => 1],
            [['create_at','update_at'], 'default', 'value' => time(),'on'=>['add','tempAdd']],
            [['status'], 'default', 'value' => 1,'on'=>['add','tempAdd']],
            [['line_id','community_id'],'lineExist',"on"=>['add','tempAdd']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'            => '计划id',
            'name'          => '计划名称',
            'type'          => '计划类型',
            'community_id'  => '小区Id',
            'line_id'       => '线路Id',
            'start_at'      => '计划开始时间',
            'end_at'        => '计划结束时间',
            'task_name'     => '任务名称',
            'user_list'     => '执行人员',
            'exec_type'     => '执行类型',
            'exec_interval' => '执行间隔',
            'exec_type_msg' => '执行类型自定义日期',
            'error_minute'  => '允许误差分钟',
            'status'        => '计划状态',
            'operator_id'   => '创建人id',
            'create_at'     => '创建时间',
            'update_at'     => '修改时间',
        ];
    }

    /*
     * 计划名称唯一
     */
    public function nameUnique($attribute){
        if(!empty($this->community_id)&&!empty($this->name)){
            $res = self::find()->where('community_id=:community_id and name=:name and status!=:status',[":community_id"=>$this->community_id,":name"=>$this->name,":status"=>3])->asArray()->one();
            if(!empty($res)){
                return $this->addError($attribute, "计划名称唯一");
            }
        }
    }

    /*
     *
     */
    public function lineExist($attribute){
        if(!empty($this->line_id)&&!empty($this->community_id)){
            $model = PsInspectLine::find()->select(['id'])->where(['=','id',$this->line_id])->andWhere(['=','communityId',$this->community_id])->asArray()->one();
            if(empty($model)){
                return $this->addError($attribute, "巡检线路不存在");
            }
        }
    }

    /*
     * 计划时间验证
     */
    public function planTimeVerification($attribute){
        $nowTime = time();
        if(!empty($this->start_at)&&!empty($this->end_at)){
            if($this->start_at<$nowTime){
                return $this->addError($attribute, "有效时间开始时间需大于当前时间");
            }
            if($this->start_at>$this->end_at){
                return $this->addError($attribute, "有效时间结束时间需大于开始时间");
            }
        }
    }

    /*
     * 计划时间相等验证
     */
    public function planTimeEqualVerification($attribute){
        if(!empty($this->start_at)&&!empty($this->end_at)){
            if($this->start_at!=$this->end_at){
                return $this->addError($attribute, "有效时间开始时间需等于当前时间");
            }
        }
    }

    /*
     * 计划执行间隔类型验证
     */
    public function execVerification($attribute){
        if(!empty($this->exec_type)){
            switch($this->exec_type){
                case 1:     //天
                    if(!empty($this->exec_type_msg)){
                        return $this->addError($attribute, "执行类型自定义为空");
                    }
                    break;
                case 2:     //周
                    $temp = explode(",",$this->exec_type_msg);
                    if(empty($temp)){
                        return $this->addError($attribute, "执行类型自定义格式错误");
                    }
                    foreach($temp as $value){
                        $value = intval($value);
                        if(!is_int($value)){
                            return $this->addError($attribute, "执行类型自定义格式错误");
                        }
                        if($value<1||$value>7){
                            return $this->addError($attribute, "执行类型自定义格式错误");
                        }
                    }
                    break;
                case 3:     //月  32 默认最后一天
                    $temp = explode(",",$this->exec_type_msg);
                    if(empty($temp)){
                        return $this->addError($attribute, "执行类型自定义格式错误");
                    }
                    foreach($temp as $value){
                        $value = intval($value);
                        if(!is_int($value)){
                            return $this->addError($attribute, "执行类型自定义格式错误");
                        }
                        if($value<1||$value>31){
                            return $this->addError($attribute, "执行类型自定义格式错误");
                        }
                        if($value==32&&mb_strlen($this->exec_type_msg)!=2){
                            //月最后一天 值唯一
                            return $this->addError($attribute, "执行类型自定义格式错误");
                        }
                    }
                    break;
                case 4:     //年
                    if(!empty($this->exec_type_msg)){
                        return $this->addError($attribute, "执行类型自定义为空");
                    }
                    break;
            }
        }
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
                $this->addError($attribute, "该计划不存在!");
            }
        }
    }

    /***
     * 新增
     * @return bool
     */
    public function saveData()
    {
        return $this->save();
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

    /*
     * 关联任务 任务开始时间升序排序
     */
    public function getTaskStartAsc(){
        return self::hasMany(PsInspectRecord::className(),['plan_id'=>'id'])->orderBy(['check_start_at'=>SORT_ASC]);
    }

    /*
     * 关联任务
     */
    public function getTask(){
        return self::hasMany(PsInspectRecord::className(),['plan_id'=>'id']);
    }

    /*
     * 关联时间段
     */
    public function getPlanTime(){
        return self::hasMany(PsInspectPlanTime::className(),['plan_id'=>'id']);
    }

    /*
     * 计划单表
     */
    public function getPlanOne($params){
        $model = self::find()->select(['status','start_at','end_at'])->where(['=','id',$params['id']]);
        return $model->asArray()->one();
    }

    /*
     * 巡检列表查询
     */
    public function getList($params){
        $fields = ['p.id','p.type','p.status','p.name','p.start_at','p.end_at','p.community_id','p.exec_type','p.exec_interval','p.exec_type_msg','l.name as line_name'];
        $model = self::find()->alias("p")->select($fields)
                    ->leftJoin(['l'=>PsInspectLine::tableName()], "p.line_id = l.id")
                    ->with('taskStartAsc')
                    ->andFilterWhere(['in', 'p.community_id', $params['communityIds']])
                    ->andFilterWhere(['=', 'p.community_id', $params['community_id']])
                    ->andFilterWhere(['like', 'p.name', $params['name']])
                    ->andFilterWhere(['=', 'p.line_id', $params['line_id']]);
        $count = $model->count();
        $page = intval($params['page']);
        $pageSize = intval($params['pageSize']);
        $offset = ($page-1)*$pageSize;
        $model->offset($offset)->limit($pageSize);
        $model->orderBy(["p.id"=>SORT_DESC]);
        $result = $model->asArray()->all();
        return ['count'=>$count,'data'=>$result];
    }

    /*
     * 计划详情
     */
    public function getDetail($params){
        $fields = [
                    'p.id','p.type','p.status','p.name','p.start_at','p.end_at','p.community_id','p.exec_type',
                    'p.exec_interval','p.exec_type_msg','l.name as line_name','p.user_list','p.error_minute',
                    'p.task_name'
        ];
        $model = self::find()->alias("p")->select($fields)
            ->leftJoin(['l'=>PsInspectLine::tableName()], "p.line_id = l.id")
            ->with('task','planTime')
            ->andFilterWhere(['=', 'p.id', $params['id']]);
        return $model->asArray()->one();
    }
}

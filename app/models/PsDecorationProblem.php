<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/5/22
 * Time: 9:36
 * Desc: 兑换记录
 */
namespace app\models;

class PsDecorationProblem extends BaseModel {

    public $statusMsg = ['1'=>'待处理', '2'=>'已处理'];
    public $typeMsg = ['1'=>'违章','2'=>'安全','3'=>'环境','4'=>'持证'];


    public static function tableName()
    {
        return 'ps_decoration_problem';
    }

    public function rules()
    {
        return [
            // 所有场景
//            [['community_id','patrol_id','type_msg','content','assign_name','assign_id','assigned_name','assigned_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['community_id','patrol_id','assign_name','assign_id','assigned_name','assigned_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['id','community_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ["detail"]],
            [["id",'patrol_id','decoration_id','status','deal_at','create_at','update_at'], 'integer'],
            [['community_id','community_name','room_id','group_id','building_id','unit_id','assign_id','assigned_id'], 'string',"max"=>30],
            [['address','content','deal_content'], 'string',"max"=>200],
            [['problem_img','deal_img'], 'string',"max"=>255],
            [['type_msg'], 'string',"max"=>50],
            [['assign_name','assigned_name'], 'string',"max"=>20],
            [['community_id','community_name','room_id','group_id','building_id','unit_id','assign_id','assigned_id','address','content','deal_content','problem_img','deal_img','type_msg','assign_name','assigned_name'], 'trim'],
            [['id','community_id'], 'infoData', 'on' => ["detail"]],
            [['patrol_id','community_id'], 'recordExist', 'on' => ["add"]],    //装修登记是否存在
            [['patrol_id','community_id'], 'problemExist', 'on' => ["add"]],    //验证问题是否存在
            [["create_at",'update_at'],"default",'value' => time(),'on'=>['add']],
            [["status"],"default",'value' => 1,'on'=>['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id' => 'id',
              'patrol_id' => '巡查记录id',
              'decoration_id' => '装修登记id',
              'status' => '状态',
              'community_id' => '小区id',
              'community_name' => '小区名称',
              'room_id' => '房屋号id',
              'group_id' => '房屋苑/期/区',
              'building_id' => '幢',
              'unit_id' => '单元',
              'address' => '房屋地址',
              'type_msg' => '问题类型',
              'content' => '存在问题',
              'problem_img' => '问题图片',
              'assign_name' => '指派人',
              'assign_id' => '指派人id',
              'assigned_name' => '被指派人',
              'assigned_id' => '被指派人id',
              'deal_at' => '处理时间',
              'deal_content' => '处理问题',
              'deal_img' => '处理图片',
              'create_at' => '新增时间',
              'update_at' => '修改时间',
        ];
    }

    /***
     * 新增
     * @return true|false
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

    /***
     * 自定义验证企业客户是否存在
     * @param $attribute
     */
    public function infoData($attribute){
        if(!empty($this->id)&&!empty($this->community_id)){
            $res = static::find()->select(['id'])->where('id=:id and community_id=:community_id',[':id'=>$this->id,':community_id'=>$this->community_id])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "该数据不存在！");
            }
        }
    }

    /*
     * 巡检记录是否存在
     */
    public function recordExist($attribute){
        if(!empty($this->patrol_id)&&!empty($this->community_id)){
            $fields = ['decoration_id','community_id','community_name','room_id','group_id','building_id','unit_id','address'];
            $res = PsDecorationPatrol::find()->select($fields)->where('id=:id and community_id=:community_id',[':id'=>$this->patrol_id,':community_id'=>$this->community_id])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "巡检记录不存在！");
            }
            $this->community_id = $res['community_id'];
            $this->community_name = $res['community_name'];
            $this->room_id = $res['room_id'];
            $this->group_id = $res['group_id'];
            $this->building_id = $res['building_id'];
            $this->unit_id = $res['unit_id'];
            $this->address = $res['address'];
            $this->decoration_id = $res['decoration_id'];
        }
    }

    /*
     * 验证该巡检记录是否已存在问题
     */
    public function problemExist($attribute){
        if(!empty($this->patrol_id)&&!empty($this->community_id)){
            $res = self::find()->select(['id'])->where('patrol_id=:patrol_id and community_id=:community_id',[':patrol_id'=>$this->patrol_id,':community_id'=>$this->community_id])->asArray()->one();
            if(!empty($res)){
                $this->addError($attribute, "该巡检记录问题已记录！");
            }
        }
    }

    /*
     * 详情
     */
    public function detail($param){
        $field = [
            'id','address','owner_name','owner_phone','project_unit','project_name','project_phone','status','create_at','img','community_name'
        ];
        $model = static::find()->select($field)->with('patrol');
        if(!empty($param['id'])){
            $model->andWhere(['=','id',$param['id']]);
        }
        return $model->asArray()->one();
    }

    /*
     * 列表
     */
    public function getList($param){

        $field = [
            'problem.id','problem.address','problem.community_name','patrol.create_at','patrol.patrol_name','problem.deal_at',
            'problem.assigned_name','problem.type_msg','problem.status',
        ];
        $model = self::find()->alias('problem')
            ->leftJoin(['patrol'=>PsDecorationPatrol::tableName()],'patrol.id=problem.patrol_id')->select($field)->where(1);
        if(!empty($param['communityList'])){
            $model->andWhere(['in','problem.community_id',$param['communityList']]);
        }
        if(!empty($param['community_id'])){
            $model->andWhere(['=','problem.community_id',$param['community_id']]);
        }

        if(!empty($param['group_id'])){
            $model->andWhere(['=','problem.group_id',$param['group_id']]);
        }
        if(!empty($param['building_id'])){
            $model->andWhere(['=','problem.building_id',$param['building_id']]);
        }
        if(!empty($param['unit_id'])){
            $model->andWhere(['=','problem.unit_id',$param['unit_id']]);
        }

        if(!empty($param['status'])){
            $model->andWhere(['=','problem.status',$param['status']]);
        }

        if(!empty($param['assigned_name'])){    //处理人
            $model->andWhere(['like','problem.assigned_name',$param['assigned_name']]);
        }

        if(!empty($param['patrol_name'])){    //巡查人
            $model->andWhere(['like','patrol.patrol_name',$param['patrol_name']]);
        }

        $count = $model->count();
        if(!empty($param['page'])||!empty($param['pageSize'])){
            $page = !empty($param['page'])?intval($param['page']):1;
            $pageSize = !empty($param['pageSize'])?intval($param['pageSize']):10;
            $offset = ($page-1)*$pageSize;
            $model->offset($offset)->limit($pageSize);
        }
        $model->orderBy(["id"=>SORT_DESC]);
        $result = $model->asArray()->all();
        return [
            'list'=>$result,
            'totals'=>$count
        ];
    }
}
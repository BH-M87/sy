<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/5/22
 * Time: 9:36
 * Desc: 兑换记录
 */
namespace app\models;

class PsDecorationPatrol extends BaseModel {

    public $statusMsg = ['1'=>'进行中', '2'=>'已完成'];
    public $moneyStatusMsg = ['1'=>'否','2'=>'是'];


    public static function tableName()
    {
        return 'ps_decoration_patrol';
    }

    public function rules()
    {
        return [
            // 所有场景
            [['decoration_id','community_id','is_licensed','is_safe', 'is_violation','is_env','patrol_name','patrol_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['decoration_id','community_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['list']],
            [['id','community_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['detail']],
            [["id",'decoration_id', 'is_licensed','is_safe', 'is_violation','is_env', 'problem_num','create_at','update_at'], 'integer'],
            [['community_id','community_name','room_id','group_id','building_id','unit_id','content'], 'string',"max"=>30],
            [['address','remarks'], 'string',"max"=>200],
            [['patrol_name'], 'string',"max"=>20],
            [['content_show'], 'string'],
            ['problem_num','integer', 'min'=>0, 'max'=>20],
            [['community_id','community_name','room_id','group_id','building_id','unit_id','content','address','remarks','patrol_name'], 'trim'],
            [['decoration_id','community_id'], 'recordExist', 'on' => ["add","list"]],    //该户装修登记是否存在
            [['id','community_id'], 'infoData', 'on' => ["detail"]],    //该巡查记录是否存在
            [["create_at",'update_at'],"default",'value' => time(),'on'=>['add']],
            [["problem_num"],"default",'value' => 0,'on'=>['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id' => 'id',
              'decoration_id' => '装修登记id',
              'community_id' => '小区id',
              'community_name' => '小区名称',
              'room_id' => '房屋号id',
              'group_id' => '房屋苑/期/区',
              'building_id' => '幢',
              'unit_id' => '单元',
              'address' => '房屋地址',
              'is_licensed' => '持证情况',
              'is_safe' => '安全情况',
              'is_violation' => '违章情况',
              'is_env' => '环境情况',
              'problem_num' => '存在问题数',
              'content' => '装修内容',
              'content_show'=> '装修内容前端展示',
              'remarks' => '备注',
              'patrol_name' => '巡查人',
              'patrol_id' => '巡查人id',
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
     * 自定义验证该记录是否存在
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
     * 验证装修登记是否存在
     */
    public function recordExist($attribute){
        if(!empty($this->decoration_id)&&!empty($this->community_id)){
            $fields = ['community_id','community_name','room_id','group_id','building_id','unit_id','address'];
            $res = PsDecorationRegistration::find()->select($fields)->where('id=:id and community_id=:community_id',[':id'=>$this->decoration_id,':community_id'=>$this->community_id])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "装修登记不存在！");
            }
            $this->community_id = $res['community_id'];
            $this->community_name = $res['community_name'];
            $this->room_id = $res['room_id'];
            $this->group_id = $res['group_id'];
            $this->building_id = $res['building_id'];
            $this->unit_id = $res['unit_id'];
            $this->address = $res['address'];
        }
    }

    /*
     * 详情
     */
    public function detail($param){
        $field = [
            'id','is_licensed','is_safe', 'is_violation','is_env', 'problem_num','content','remarks','address','content_show'
        ];
        $model = static::find()->with('problem')->select($field);
        if(!empty($param['id'])){
            $model->andWhere(['=','id',$param['id']]);
        }
        return $model->asArray()->one();
    }

    /*
     * 新增违规问题详情
     */
    public function problemAddDetail($param){
        $field = [
            'p.id',
            'reg.address','reg.owner_name','reg.owner_phone','reg.project_unit','reg.project_name','reg.project_phone'
        ];
        $model = static::find()->alias('p')->leftJoin(['reg'=>PsDecorationRegistration::tableName()],"reg.id=p.decoration_id")->select($field);
        if(!empty($param['id'])){
            $model->andWhere(['=','p.id',$param['id']]);
        }
        return $model->asArray()->one();
    }

    //关联问题
    public function getProblem(){
        return $this->hasOne(PsDecorationProblem::className(),['patrol_id'=>'id']);
    }

    /*
     * 列表
     */
    public function getList($param){

        $field = [
                    'id','is_licensed','is_safe', 'is_violation','is_env', 'problem_num','patrol_name','content','create_at','remarks','address','content_show'
        ];
        $model = self::find()->select($field)->with('problem')->where(1);

        if(!empty($param['community_id'])){
            $model->andWhere(['=','community_id',$param['community_id']]);
        }

        if(!empty($param['decoration_id'])){
            $model->andWhere(['=','decoration_id',$param['decoration_id']]);
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
<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/5/22
 * Time: 9:36
 * Desc: 兑换记录
 */
namespace app\models;

class PsDecorationRegistration extends BaseModel {

    public $statusMsg = ['1'=>'进行中', '2'=>'已完成'];
    public $moneyStatusMsg = ['1'=>'否','2'=>'是'];


    public static function tableName()
    {
        return 'ps_decoration_registration';
    }

    public function rules()
    {
        return [
            // 所有场景
            [['community_id','community_name','room_id', 'group_id','building_id','unit_id','address','owner_name','owner_id','owner_phone','project_unit','project_name','project_phone'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['id','community_id','owner_phone','project_unit','project_name','project_phone'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['edit']],
            [['id','community_id','money'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['receive']],
            [['id','community_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ["detail","complete","refund"]],
            [["id",'status', 'is_refund','refund_at','is_receive','receive_at','create_at','update_at'], 'integer'],
            [['community_id','community_name','room_id','group_id','building_id','unit_id','owner_id'], 'string',"max"=>30],
            [['address'], 'string',"max"=>200],
            [['img'], 'string',"max"=>255],
            [['project_unit'], 'string',"max"=>50],
            [['owner_name','owner_phone','project_name','project_phone'], 'string',"max"=>20],
            [['community_id','community_name','room_id','group_id','building_id','unit_id','address','img','project_unit','owner_name','owner_phone','project_name','project_phone'], 'trim'],
            [['owner_phone','project_phone'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'联系电话必须手机号码格式'],
            [['id','community_id'], 'infoData', 'on' => ["detail","complete","edit"]],
            [['room_id','community_id'], 'recordExist', 'on' => ["add"]],    //该户装修登记已存在
            [['id','community_id'], 'receiveVerification', 'on' => ["receive"]],    //是否收过保证金
            [['id','community_id'], 'refundVerification', 'on' => ["refund"]],    //是否退过保证金
            [["create_at",'update_at'],"default",'value' => time(),'on'=>['add']],
            [["status","is_refund","is_receive"],"default",'value' => 1,'on'=>['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id' => 'id',
              'community_id' => '小区id',
              'community_name' => '小区名称',
              'status' => '状态 ',
              'room_id' => '房屋号id',
              'group_id' => '房屋苑/期/区',
              'building_id' => '幢',
              'unit_id' => '单元',
              'address' => '房屋地址',
              'owner_name' => '业主',
              'owner_phone' => '业主电话',
              'owner_id'    => '业主id',
              'project_unit' => '承包单位',
              'project_name' => '项目经理',
              'project_phone' => '项目经理电话',
              'img' => '装修备案图',
              'money' => '保证金',
              'is_refund' => '是否退款',
              'refund_at' => '退款时间',
              'is_receive' => '是否收款',
              'receive_at' => '收款时间',
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
     * 验证装修登记是否存在
     */
    public function recordExist($attribute){
        if(!empty($this->room_id)&&!empty($this->community_id)){
            $res = static::find()->select(['id'])->where('room_id=:room_id and community_id=:community_id',[':room_id'=>$this->room_id,':community_id'=>$this->community_id])->asArray()->one();
            if (!empty($res)) {
                $this->addError($attribute, "该户已存在装修登记，不能添加！");
            }
        }
    }

    /*
     * 保证金-收款验证
     */
    public function receiveVerification($attribute){
        if(!empty($this->id)&&!empty($this->community_id)){
            $res = static::find()->select(['id','is_receive'])->where('id=:id and community_id=:community_id and status=:status',[':id'=>$this->id,':community_id'=>$this->community_id,":status"=>1])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "该数据不存在！");
            }
            if($res['is_receive']==2){
                $this->addError($attribute, "已交保证金！");
            }
        }
    }

    /*
     * 保证金-退款验证
     */
    public function refundVerification($attribute){
        if(!empty($this->id)&&!empty($this->community_id)){
            $res = static::find()->select(['id','is_refund',"is_receive"])->where('id=:id and community_id=:community_id',[':id'=>$this->id,':community_id'=>$this->community_id])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "该数据不存在！");
            }
            if($res['is_receive']==1){
                $this->addError($attribute, "没有交过保证金！");
            }
            if($res['is_refund']==2){
                $this->addError($attribute, "已退保证金！");
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
     * 房屋详情
     */
    public function roomDetail($param){
        $field = [
            'address','community_name'
        ];
        $model = static::find()->select($field);
        if(!empty($param['id'])){
            $model->andWhere(['=','id',$param['id']]);
        }
        return $model->asArray()->one();
    }

    /*
     * 关联巡检记录
     */
    public function getPatrol(){
        return $this->hasMany(PsDecorationPatrol::className(),['decoration_id'=>'id'])->orderBy(['id'=>SORT_DESC]);
    }

    /*
     * 列表
     */
    public function getList($param){

        $field = ['id','address','owner_name','owner_phone','project_unit','project_name','project_phone','status','create_at','community_name','community_id'];
        $model = self::find()->select($field)->with('patrol')->where(1);
        if(!empty($param['communityList'])){
            $model->andWhere(['in','community_id',$param['communityList']]);
        }
        if(!empty($param['community_id'])){
            $model->andWhere(['=','community_id',$param['community_id']]);
        }

        if(!empty($param['group_id'])){
            $model->andWhere(['=','group_id',$param['group_id']]);
        }
        if(!empty($param['building_id'])){
            $model->andWhere(['=','building_id',$param['building_id']]);
        }
        if(!empty($param['unit_id'])){
            $model->andWhere(['=','unit_id',$param['unit_id']]);
        }

        if(!empty($param['status'])){
            $model->andWhere(['=','status',$param['status']]);
        }

        if(!empty($param['owner_name'])){
            $model->andWhere(['like','owner_name',$param['owner_name']]);
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

    /*
     * 押金列表
     */
    public function depositList($param){

        $field = [
                    'id','address','money','status','is_receive','receive_at','is_refund','refund_at',
                    'community_name','community_id','owner_name','owner_phone'
        ];
        $model = self::find()->select($field)->where(1);
        if(!empty($param['communityList'])){
            $model->andWhere(['in','community_id',$param['communityList']]);
        }
        if(!empty($param['community_id'])){
            $model->andWhere(['=','community_id',$param['community_id']]);
        }

        if(!empty($param['group_id'])){
            $model->andWhere(['=','group_id',$param['group_id']]);
        }
        if(!empty($param['building_id'])){
            $model->andWhere(['=','building_id',$param['building_id']]);
        }
        if(!empty($param['unit_id'])){
            $model->andWhere(['=','unit_id',$param['unit_id']]);
        }

        if(!empty($param['status'])){
            $model->andWhere(['=','status',$param['status']]);
        }

        if(!empty($param['is_receive'])){
            $model->andWhere(['=','is_receive',$param['is_receive']]);
        }

        if(!empty($param['is_refund'])){
            $model->andWhere(['=','is_refund',$param['is_refund']]);
        }

        if(!empty($param['owner_info'])){
            $model->andWhere([
                'or',
                ['like','owner_name',$param['owner_info']],
                ['like','owner_phone',$param['owner_info']]
            ]);
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

    //押金详情
    public function depositDetail($params){
        $field = [
            'id','address','money','status','is_receive','receive_at','is_refund','refund_at','community_name',
            'owner_name','owner_phone'
        ];
        $model = static::find()->select($field);
        if(!empty($params['id'])){
            $model->andWhere(['=','id',$params['id']]);
        }
        return $model->asArray()->one();
    }

    //编辑详情
    public function editDetail($params){
        $field = [
            'id','community_id','room_id', 'group_id','building_id','unit_id','owner_name','owner_phone','owner_id',
            'project_unit','project_name','project_phone','img'
        ];
        $model = static::find()->select($field);
        if(!empty($params['id'])){
            $model->andWhere(['=','id',$params['id']]);
        }
        return $model->asArray()->one();
    }
}
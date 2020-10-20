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
            [['community_id','community_name','room_id', 'group_id','building_id','unit_id','address','owner_name','owner_phone','project_unit','project_name','project_phone'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['id','community_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ["detail"]],
            [["id",'status', 'is_refund','refund_at','is_receive','receive_at','create_at','update_at'], 'integer'],
            [['community_id','community_name','room_id','group_id','building_id','unit_id'], 'string',"max"=>30],
            [['address'], 'string',"max"=>200],
            [['img'], 'string',"max"=>255],
            [['project_unit'], 'string',"max"=>50],
            [['owner_name','owner_phone','project_name','project_phone'], 'string',"max"=>20],
            [['community_id','community_name','room_id','group_id','building_id','unit_id','address','img','project_unit','owner_name','owner_phone','project_name','project_phone'], 'trim'],
            [['owner_phone','project_phone'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'联系电话必须手机号码格式'],
            [['id','community_id'], 'infoData', 'on' => ["detail"]],
            [['room_id','community_id'], 'recordExist', 'on' => ["add"]],    //该户装修登记已存在
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
     * 详情
     */
    public function detail($param){
        $field = [
            'id','product_name','cust_name','cust_mobile','address'
        ];
        $model = static::find()->select($field);
        if(!empty($param['id'])){
            $model->andWhere(['=','id',$param['id']]);
        }
        return $model->asArray()->one();
    }

    /*
     * 小程序列表
     */
    public function getListOfC($param){
        $field = ['id','product_name','create_at','product_num','product_img','verification_qr_code','confirm_type','product_id'];
        $model = self::find()->select($field)->where(['=','community_id',$param['community_id']])->andWhere(['=','user_id',$param['user_id']]);
//            ->andWhere(['=','room_id',$param['room_id']]);
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
     * 列表
     */
    public function getList($param){

        $field = ['id','product_name','create_at','cust_name','cust_mobile','product_num','address','delivery_type','status','courier_company','order_num'];
        $model = self::find()->select($field)->where(1);
        if(!empty($param['communityList'])){
            $model->andWhere(['in','community_id',$param['communityList']]);
        }
        if(!empty($param['cust_name'])){
            $model->andWhere(['like','cust_name',$param['cust_name']]);
        }
        if(!empty($param['cust_mobile'])){
            $model->andWhere(['like','cust_mobile',$param['cust_mobile']]);
        }
        if(!empty($param['delivery_type'])){
            $model->andWhere(['=','delivery_type',$param['delivery_type']]);
        }
        if(!empty($param['status'])){
            $model->andWhere(['=','status',$param['status']]);
        }
        if(!empty($param['courier_company'])){
            $model->andWhere(['like','courier_company',$param['courier_company']]);
        }
        if(!empty($param['order_num'])){
            $model->andWhere(['like','order_num',$param['order_num']]);
        }
        if(!empty($param['start_time'])){
            $model->andWhere(['>=','create_at',strtotime($param['start_time'])]);
        }
        if(!empty($param['end_time'])){
            $model->andWhere(['<=','create_at',strtotime($param['end_time']." 23:59:59")]);
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
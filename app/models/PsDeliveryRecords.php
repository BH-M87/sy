<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/5/22
 * Time: 9:36
 * Desc: 兑换记录
 */
namespace app\models;

class PsDeliveryRecords extends BaseModel {

    const DELIVERY_TYPE = ['1'=>'快递','2'=>'自提'];
    const STATUS = ['1'=>'未处理','2'=>'已发','3'=>'已提'];

    public $receiveType;


    public static function tableName()
    {
        return 'ps_delivery_records';
    }

    public function rules()
    {
        return [
            // 所有场景
            [['product_id','community_id','cust_name', 'cust_mobile','user_id','volunteer_id','product_num','address'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['volunteer_add']],
            [['id','delivery_type','courier_company','order_num','operator_name','operator_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['send_edit']],
            [['id','delivery_type','records_code','operator_name','operator_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['self_edit']],
            ['id', 'required', 'message' => '{attribute}不能为空！', 'on' => ['send_edit',"self_edit","detail"]],
            [['community_id','user_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ["app_list"]],
            [["id",'product_id', 'volunteer_id','product_num','integral','delivery_type','status','create_at','update_at'], 'integer'],
            [['community_id','room_id','product_name','cust_name','cust_mobile','user_id','operator_id'], 'string',"max"=>30],
            [['address'], 'string',"max"=>200],
            [['product_img','verification_qr_code'], 'string',"max"=>255],
            [['records_code','operator_name'], 'string',"max"=>10],
            [['courier_company','order_num'], 'string',"max"=>50],
            [['community_id','room_id','cust_name','cust_mobile','address','courier_company','order_num','records_code','operator_name','operator_id'], 'trim'],
            [['cust_mobile'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'联系电话必须是区号-电话格式或者手机号码格式'],
            [['id'], 'infoData', 'on' => ['send_edit',"self_edit","detail"]],
            [['product_id'], 'productExist', 'on' => ['volunteer_add']],
            [["create_at",'update_at'],"default",'value' => time(),'on'=>['volunteer_add']],
            [["product_num","status"],"default",'value' => 1,'on'=>['volunteer_add']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'                => '兑换id',
            'product_id'        => '商品id',
            'community_id'      => '小区id',
            'room_id'           => '房屋id',
            'product_name'      => '兑换商品名称',
            'product_img'       => '兑换商品图片',
            'cust_name'         => '兑换人',
            'cust_mobile'       => '兑换人手机',
            'user_id'           => '兑换人id',
            'volunteer_id'      => '志愿者id',
            'product_num'       => '兑换数量',
            'integral'          => '消耗积分',
            'address'           => '兑换地址',
            'delivery_type'     => '配送方式',
            'status'            => '状态',
            'courier_company'   => '快递公司',
            'order_num'         => '快递单号',
            'records_code'      => '自提码',
            'operator_name'     => '操作人',
            'operator_id'       => '操作人id',
            'create_at'         => '创建时间',
            'update_at'         => '修改时间',
            'verification_qr_code'=> '核销二维码',
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
        if(!empty($param['delivery_type'])){
            $param['status'] = $param['delivery_type']==1?2:3;
        }
        return self::updateAll($param, ['id' => $param['id']]);
    }

    /***
     * 自定义验证企业客户是否存在
     * @param $attribute
     */
    public function infoData($attribute){
        if(!empty($this->id)){
            $res = static::find()->select(['id'])->where('id=:id',[':id'=>$this->id])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "该数据不存在！");
            }
        }
    }

    /*
     * 验证商品是否存在 并设置信息 判断兑换次数
     */
    function productExist($attribute){
        if(!empty($this->product_id)){
            $res = Goods::find()->select(['id','name','img','score','isExchange','personLimit','receiveType'])->where('id=:id and isDelete=:isDelete',[':id'=>$this->product_id,":isDelete"=>2])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "该商品不存在或已删除！");
            }
            if($res['personLimit']>0){
                //判断是否兑换过商品
                $exchangeCount = self::find()->select(['id'])
                                        ->where(['=','product_id',$this->product_id])
                                        ->andWhere(['=','user_id',$this->user_id])
                                        ->count();
                if($exchangeCount>=$res['personLimit']){
                    $this->addError($attribute, "兑换已达上限，不能兑换！");
                }
            }
            $this->product_name = $res['name'];
            $this->product_img = $res['img'];
            $this->integral = $res['score']*$this->product_num;
            $this->receiveType = $res['receiveType'];
            if($res['isExchange']==2){
                //修改表状态
                Goods::updateAll(['isExchange'=>1,'updateAt'=>time()],['id'=>$this->product_id]);
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
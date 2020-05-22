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

    public static function tableName()
    {
        return 'ps_delivery_records';
    }

    public function rules()
    {
        return [
            // 所有场景
            [['product_id','community_id','cust_name', 'cust_mobile','product_num','address'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['delivery_type','courier_company','order_num','operator_name'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['send_edit']],
            [['delivery_type','records_code','operator_name'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['self_edit']],
            ['id', 'required', 'message' => '{attribute}不能为空！', 'on' => ['send_edit',"self_edit","detail"]],
            [["id",'product_id', 'product_num','create_at','update_at'], 'integer'],
            [['community_id','product_name','cust_name','cust_mobile'], 'string',"max"=>30],
            [['address'], 'string',"max"=>200],
            [['records_code','operator_name'], 'string',"max"=>10],
            [['courier_company','order_num'], 'string',"max"=>50],
            [['community_id','product_name','cust_name','cust_mobile','address','courier_company','order_num','records_code','operator_name'], 'trim'],
            [['cust_mobile'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'联系电话必须是区号-电话格式或者手机号码格式'],
            [['id'], 'infoData', 'on' => ['send_edit',"self_edit","detail"]],
            [['product_id'], 'productExist', 'on' => ['add']],
            [["create_at",'update_at'],"default",'value' => time(),'on'=>['add']],
            [["product_num"],"default",'value' => 1,'on'=>['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'                => 'ID',
            'product_id'        => '商品id',
            'community_id'      => '小区id',
            'product_name'      => '兑换名称',
            'cust_name'         => '兑换人',
            'cust_mobile'       => '兑换人手机',
            'product_num'       => '兑换数量',
            'address'           => '兑换地址',
            'delivery_type'     => '配送方式',
            'courier_company'   => '快递公司',
            'order_num'         => '快递单号',
            'records_code'      => '自提码',
            'operator_name'     => '操作人',
            'create_at'         => '创建时间',
            'update_at'         => '修改时间',
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
        if(!empty($this->id)){
            $res = static::find()->select(['id'])->where('id=:id',[':id'=>$this->id])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "该数据不存在！");
            }
        }
    }

    /*
     * 验证商品是否存在 并设置信息
     */
    function productExist($attribute){
        if(!empty($this->product_id)){
            $res = Goods::find()->select(['id','name'])->where('id=:id and isDelete=:isDelete',[':id'=>$this->product_id,":isDelete"=>2])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "该商品不存在或已删除！");
            }
            $this->product_name = $res['name'];
        }
    }

    /*
     * 资源详情
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
     * 资源列表
     */
    public function getList($param){

        $field = ['id','enterprise_name','name','content','opening_hours','contact_name','contact_mobile','state'];
        $model = self::find()->select($field)->where(['=','street_id',$param['street_id']]);
        if(!empty($param['enterprise_name'])){
            $model->andWhere(['like','enterprise_name',$param['enterprise_name']]);
        }
        if(!empty($param['name'])){
            $model->andWhere(['like','name',$param['name']]);
        }
        if(!empty($param['enterprise_id'])){
            $model->andWhere(['=','enterprise_id',$param['enterprise_id']]);
        }
        if(!empty($param['contact_info'])){
            $model->andWhere([
                'or',
                ['like','contact_name',$param['contact_info']],
                ['like','contact_mobile',$param['contact_info']]
            ]);
        }
        $count = $model->count();
        if(!empty($param['page'])||!empty($param['pageSize'])){
            $page = !empty($param['page'])?intval($param['page']):1;
            $pageSize = !empty($param['pageSize'])?intval($param['pageSize']):Yii::$app->params['defaultPageSize'];
            $offset = ($page-1)*$pageSize;
            $model->offset($offset)->limit($pageSize);
        }
        $model->orderBy(["id"=>SORT_DESC]);
        $result = $model->asArray()->all();
        return [
            'data'=>$result,
            'count'=>$count
        ];
    }
}
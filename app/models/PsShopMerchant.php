<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/7
 * Time: 16:23
 * Desc:
 */
namespace app\models;

class PsShopMerchant extends BaseModel {


    public $communityInfo = '';

    public static function tableName()
    {
        return 'ps_shop_merchant';
    }

    public function rules()
    {
        return [

            [['name','type','category_code', 'merchant_img','lon','lat','location','start','end','link_name','link_mobile','communityInfo','member_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['micro_add']],
            [['name','type','category_code', 'business_img','merchant_img','lon','lat','location','start','end','link_name','link_mobile','scale','area','communityInfo','member_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['individual_add']],
            [["id",'type', 'check_status','status','create_at','update_at'], 'integer'],
            [["lon",'lat'], 'number'],
            [['name','merchant_code','check_code','member_id','check_id','start','end','link_name','link_mobile','check_name','scale','area','category_code','merchant_img','business_img','location','check_content'], 'trim'],
            [['name','merchant_code','check_code','member_id','check_id'], 'string',"max"=>30],
            [['start','end','link_name','check_name'], 'string',"max"=>10],
            [['link_mobile'], 'string',"max"=>20],
            [['scale','area'], 'string',"max"=>100],
            [['category_code'], 'string',"max"=>64],
            [['merchant_img','business_img'], 'string',"max"=>500],
            [['location','check_content'], 'string',"max"=>255],
            [['start','end'],'date', 'format'=>'HH:mm','message' => '{attribute}格式错误'],
            [['start','end'],'planTimeVerification','on'=>['micro_add','individual_add']],
            [['name'],'customizeValue','on'=>['micro_add','individual_add']],   //设置商店的默认值
            [['link_mobile'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'手机格式有误'],
            [["create_at",'update_at'],"default",'value' => time(),'on'=>['micro_add','individual_add']],
            [["check_status","status"],"default",'value' => 1,'on'=>['micro_add','individual_add']],

            // 所有场景
//            [['product_id','community_id','cust_name', 'cust_mobile','user_id','volunteer_id','product_num','address'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['volunteer_add']],
//            [['id','delivery_type','courier_company','order_num','operator_name','operator_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['send_edit']],
//            [['id','delivery_type','records_code','operator_name','operator_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['self_edit']],
//            ['id', 'required', 'message' => '{attribute}不能为空！', 'on' => ['send_edit',"self_edit","detail"]],
//            [['community_id','user_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ["app_list"]],
//            [["id",'product_id', 'volunteer_id','product_num','integral','delivery_type','status','create_at','update_at'], 'integer'],
//            [['community_id','room_id','product_name','cust_name','cust_mobile','user_id','operator_id'], 'string',"max"=>30],
//            [['address'], 'string',"max"=>200],
//            [['product_img','verification_qr_code'], 'string',"max"=>255],
//            [['records_code','operator_name'], 'string',"max"=>10],
//            [['courier_company','order_num'], 'string',"max"=>50],
//            [['community_id','room_id','cust_name','cust_mobile','address','courier_company','order_num','records_code','operator_name','operator_id'], 'trim'],
//            [['cust_mobile'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'联系电话必须是区号-电话格式或者手机号码格式'],
//            [['id'], 'infoData', 'on' => ['send_edit',"self_edit","detail"]],
//            [['product_id'], 'productExist', 'on' => ['volunteer_add']],
//            [["create_at",'update_at'],"default",'value' => time(),'on'=>['volunteer_add']],
//            [["product_num","status"],"default",'value' => 1,'on'=>['volunteer_add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id'              => '商户',
              'name'            => '商家名称',
              'merchant_code'   => '商家code',
              'check_code'      => '审核code',
              'type'            => '商户类型',
              'category_code'   => '经营类目',
              'merchant_img'    => '商家照片',
              'business_img'    => '营业执照',
              'lon'             => '经度',
              'lat'             => '纬度',
              'location'        => '详细地址',
              'start'           => '营业开始时间',
              'end'             => '营业结束时间',
              'link_name'       => '联系人/法人',
              'link_mobile'     => '手机号',
              'scale'           => '规模',
              'area'            => '面积',
              'check_status'    => '审核状态',
              'status'          => '商家状态',
              'check_content'   => '审核备注',
              'member_id'       => '会员id (java平台)',
              'check_id'        => '审核人id',
              'check_name'      => '审核人名称',
              'create_at'       => '创建时间',
              'update_at'       => '修改时间',
              'communityInfo'   => '小区信息',
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

    //营业时间验证
    public function planTimeVerification($attribute){
        if(!empty($this->start)&&!empty($this->end)){
            if(strtotime($this->start) >= strtotime($this->end)){
                return $this->addError($attribute, "营业结束时间需大于营业开始时间");
            }
        }
    }

    //自定义 商家code 审核code
    public function customizeValue(){
        $nowTime = time();
        $this->merchant_code = 'SJ'.date('YmdHis',$nowTime);
        $this->check_code = 'SH'.date('YmdHis',$nowTime);
    }

}
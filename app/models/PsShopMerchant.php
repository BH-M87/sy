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

    public $checkMsg = ['1'=>'待审核','2'=>'已通过','3'=>'已驳回'];

    public $typeMsg = ['1'=>'小微商家','2'=>'个体工商户'];

    public static function tableName()
    {
        return 'ps_shop_merchant';
    }

    public function rules()
    {
        return [

            [['name','type','category_first', 'merchant_img','lon','lat','location','start','end','link_name','link_mobile','communityInfo','member_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['micro_add']],
            [['name','type','category_first', 'business_img','merchant_img','lon','lat','location','start','end','link_name','link_mobile','scale','area','communityInfo','member_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['individual_add']],
            [['check_code'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['checkDetail']],
            [["id",'type', 'check_status','status','create_at','update_at'], 'integer'],
            [["lon",'lat'], 'number'],
            [['name','merchant_code','check_code','member_id','check_id','start','end','link_name','link_mobile','check_name','scale','area','category_first','category_second','merchant_img','business_img','location','check_content'], 'trim'],
            [['name','merchant_code','check_code','member_id','check_id'], 'string',"max"=>30],
            [['start','end','link_name','check_name'], 'string',"max"=>10],
            [['link_mobile'], 'string',"max"=>20],
            [['scale','area'], 'string',"max"=>100],
            [['category_first','category_second'], 'string',"max"=>64],
            [['merchant_img','business_img'], 'string',"max"=>500],
            [['location','check_content'], 'string',"max"=>255],
            [['start','end'],'date', 'format'=>'HH:mm','message' => '{attribute}格式错误'],
            [['start','end'],'planTimeVerification','on'=>['micro_add','individual_add']],
            [['name'],'customizeValue','on'=>['micro_add','individual_add']],   //设置商店的默认值
            [['check_code'],'checkInfo','on'=>"checkDetail"], //验证审核数据是否存在
            [['link_mobile'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'手机格式有误'],
            [["create_at",'update_at'],"default",'value' => time(),'on'=>['micro_add','individual_add']],
            [["check_status","status"],"default",'value' => 1,'on'=>['micro_add','individual_add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id'                      => '商户',
              'name'                    => '商家名称',
              'merchant_code'           => '商家code',
              'check_code'              => '审核code',
              'type'                    => '商户类型',
              'category_first'     => '一级经营类目',
              'category_second'    => '二级经营类目',
              'merchant_img'            => '商家照片',
              'business_img'            => '营业执照',
              'lon'                     => '经度',
              'lat'                     => '纬度',
              'location'                => '详细地址',
              'start'                   => '营业开始时间',
              'end'                     => '营业结束时间',
              'link_name'               => '联系人/法人',
              'link_mobile'             => '手机号',
              'scale'                   => '规模',
              'area'                    => '面积',
              'check_status'            => '审核状态',
              'status'                  => '商家状态',
              'check_content'           => '审核备注',
              'member_id'               => '会员id (java平台)',
              'check_id'                => '审核人id',
              'check_name'              => '审核人名称',
              'create_at'               => '创建时间',
              'update_at'               => '修改时间',
              'communityInfo'           => '小区信息',
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

    //审核信息存在
    public function checkInfo($attribute){
        if(!empty($this->check_code)){
            $res = self::find()->select(['id'])->where(['=','check_code',$this->check_code])->asArray()->one();
            if(empty($res)){
                return $this->addError($attribute, "该商户数据不存在");
            }
            $this->id = $res['id'];
        }
    }

    //列表
    public function getCheckList($params){
        $fields = ['check_code','name','type','check_status','check_name','create_at'];
        $model = self::find()->select($fields)->where(['in','check_status',[1,3]]);
        if(!empty($params['check_status'])){
            $model->andWhere(['=','check_status',$params['check_status']]);
        }
        if(!empty($params['type'])){
            $model->andWhere(['=','type',$params['type']]);
        }
        if(!empty($params['name'])){
            $model->andWhere(['like','name',$params['name']]);
        }
        if(!empty($params['start_time'])){
            $model->andWhere(['>=','create_at',strtotime($params['start_time'])]);
        }
        if(!empty($params['end_time'])){
            $model->andWhere(['<=','create_at',strtotime($params['end_time']." 23:59:59")]);
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

    //关联小区信息
    public function getCommunity(){
        return $this->hasMany(PsShopMerchantCommunity::className(),['merchant_code'=>'merchant_code']);
    }

    //商户详情
    public function getDetail($params){
        $fields = [
                    'name','type','category_first','category_second','merchant_img','business_img','lon','lat','location','start',
                    'end','link_name','link_mobile','scale','area','merchant_code','check_code'
        ];
        $result = self::find()->select($fields)->with('community')->where(['=','id',$params['id']])->asArray()->one();
        return $result;
    }

}
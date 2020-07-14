<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/13
 * Time: 14:36
 */
namespace app\models;

class PsShopMerchantPromote extends BaseModel {

    public $statusMsg = ['1'=>'上架','2'=>'下架'];

    public static function tableName()
    {
        return 'ps_shop_merchant_promote';
    }

    public function rules()
    {
        return [
            [['name','img','merchant_code','merchant_name','shop_code', 'shop_name'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['id','name','img','merchant_code','merchant_name','shop_code', 'shop_name','sort','status'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['edit']],
            [['id','sort','status','create_at','update_at'], 'integer'],
            [['name','img','merchant_code','merchant_name','shop_code', 'shop_name'], 'trim'],
            [['name','img','merchant_code','merchant_name','shop_code', 'shop_name'], 'string',"max"=>30],
            [['img'], 'string',"max"=>255],
            ['sort', 'compare', 'compareValue' => 255, 'operator' => '<=','message' => '{attribute}小于等于255！'],
            ['sort', 'compare', 'compareValue' => 1, 'operator' => '>=','message' => '{attribute}大于等于1！'],
            [['merchant_code','merchant_name'],'merchantVerification','on'=>['add','edit']],      //商户验证
            [['merchant_code','shop_code','shop_name'],'shopVerification','on'=>['add','edit']],      //商铺验证
            [['id'],'infoData','on'=>['edit','detail']],
            ['status', 'in', 'range' => [1, 2]],
            [["create_at",'update_at'],"default",'value' => time(),'on'=>['add']],
            [["sort",'status'],"default",'value' => 1,'on'=>['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id'                  => '推广id',
              'merchant_code'       => '商家code',
              'merchant_name'       => '商家名称',
              'shop_code'           => '店铺code',
              'shop_name'           => '店铺名称',
              'name'                => '素材名称',
              'img'                 => '图片',
              'sort'                => '排序',
              'status'              => '状态',
              'create_at'           => '创建时间',
              'update_at'           => '修改时间',
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

    /*
     * 验证数据是否存在
     */
    public function infoData($attribute){
        if(!empty($this->id)){
            $res = self::find()->select(['id'])->where(['=','id',$this->id])->asArray()->one();
            if(empty($res)){
                return $this->addError($attribute, "该社区店铺推广数据不存在");
            }
        }
    }


    /*
     * 商户验证
     */
    public function merchantVerification($attribute){
        if(!empty($this->merchant_code)&&!empty($this->merchant_name)){
            $res = PsShopMerchant::find()->select(['id','check_status','status'])
                        ->where(['=','name',$this->merchant_name])
                        ->andWhere(['=','merchant_code',$this->merchant_code])
                        ->asArray()->one();
            if(empty($res)){
                return $this->addError($attribute, "该商户数据不存在");
            }
            if($res['check_status']!=2){
                return $this->addError($attribute, "该商户未审核通过");
            }
            if($res['status']==2){
                return $this->addError($attribute, "该商户锁定状态");
            }
        }
    }

    /*
     * 商铺验证
     */
    public function shopVerification($attribute){
        if(!empty($this->shop_code)&&!empty($this->shop_name)&&!empty($this->merchant_code)){
            $res = PsShop::find()->select(['id'])
                        ->where(['=','shop_name',$this->shop_name])
                        ->andWhere(['=','shop_code',$this->shop_code])
                        ->andWhere(['=','merchant_code',$this->merchant_code])
                        ->asArray()->one();
            if(empty($res)){
                return $this->addError($attribute, "该商铺数据不存在");
            }
        }
    }

    /*
     * 详情
     */
    public function getDetail($params){
        $fields = ['id','merchant_code','merchant_name','shop_code','shop_name','name','img','sort','status'];
        $model = self::find()->select($fields)->where(['=','id',$params['id']]);
        return $model->asArray()->one();
    }

    /*
     * 列表
     */
    public function getList($params){
        $fields = ['id','name','merchant_code','merchant_name','shop_code','shop_name','name','img','sort','status','create_at'];
        $model = self::find()->select($fields)->where(1);
        if(!empty($params['merchant_code'])){
            $model->andWhere(['=','merchant_code',$params['merchant_code']]);
        }
        if(!empty($params['shop_code'])){
            $model->andWhere(['=','shop_code',$params['shop_code']]);
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
        if(!empty($params['page'])||!empty($params['pageSize'])){
            $page = !empty($params['page'])?intval($params['page']):1;
            $pageSize = !empty($params['pageSize'])?intval($params['pageSize']):10;
            $offset = ($page-1)*$pageSize;
            $model->offset($offset)->limit($pageSize);
        }
        $model->orderBy(["sort"=>SORT_DESC,"id"=>SORT_DESC]);
        $result = $model->asArray()->all();
        return [
            'list'=>$result,
            'totals'=>$count
        ];
    }

}
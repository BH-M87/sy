<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/5/22
 * Time: 9:36
 * Desc: 兑换记录
 */
namespace app\models;

class PsEvent extends BaseModel {

    public $sourceMsg = ['1'=>'街道','2'=>'区数据局'];
    public $closeMsg = ['1'=>'未结案','2'=>'已结案'];
    public $statusMsg = ['1'=>'待处理','2'=>'处理中','3'=>'已办结','4'=>'已驳回'];

    public static function tableName()
    {
        return 'ps_event';
    }

    public function rules()
    {
        return [
            // 所有场景
            [['event_time','sq_id','sq_name', 'xq_id','xq_name','wy_id','wy_name','contacts_name','contacts_mobile','event_content','create_id','create_name'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            ['id', 'required', 'message' => '{attribute}不能为空！', 'on' => ["detail"]],
            [["id",'event_time', 'source','status','is_close','create_at','update_at'], 'integer'],
            [['jd_id','sq_id','xq_id','wy_id','contacts_name','contacts_mobile','create_id','create_name','property_user'], 'string',"max"=>20],
            [['jd_name','sq_name','xq_name','wy_name'], 'string',"max"=>100],
            [['address'], 'string',"max"=>200],
            [['event_content'], 'string',"max"=>1000],
            [['jd_id','sq_id','xq_id','wy_id','contacts_name','contacts_mobile','create_id','create_name','property_user','jd_name','sq_name','xq_name','wy_name','address','event_content'], 'trim'],
            [['contacts_mobile'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'联系电话必须是手机号码格式'],
            [['id'], 'infoData', 'on' => ["detail"]],
//            [['product_id'], 'productExist', 'on' => ['volunteer_add']],
            [["create_at",'update_at'],"default",'value' => time(),'on'=>['add']],
            [["source","status","is_close"],"default",'value' => 1,'on'=>['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id' => 'id',
              'jd_id' => '街道id',
              'jd_name' => '街道名称',
              'sq_id' => '社区id',
              'sq_name' => '社区名称',
              'xq_id' => '小区id',
              'xq_name' => '小区名称',
              'wy_id' => '物业id',
              'wy_name' => '物业名称',
              'contacts_name' => '联系人',
              'contacts_mobile' => '联系人电话',
              'address' => '地址',
              'event_time' => '上报时间',
              'event_content' => '事件内容',
              'source' => '来源：1街道，2区数据局',
              'event_img' => '事件照片',
              'status' => '状态：1待处理，2处理中，3已办结，4已驳回',
              'is_close' => '是否结案：1未结案，2已结案',
              'create_id' => '新增用户id',
              'create_name' => '新增用户名称',
              'property_user' => '物业钉钉管理员(平台用户id)',
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
        if(!empty($this->id)){
            $res = static::find()->select(['id'])->where('id=:id',[':id'=>$this->id])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "该数据不存在！");
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
     * 列表
     */
    public function getList($param){

        $field = [
                    'id','event_time','source','contacts_name','contacts_mobile','status','event_content',
                    'xq_name','wy_name'
        ];
        $model = self::find()->select($field)->where(1);
        if(!empty($param['jd_id'])){
            $model->andWhere(['=','jd_id',$param['jd_id']]);
        }

        if(!empty($param['xq_id'])){
            $model->andWhere(['=','xq_id',$param['xq_id']]);
        }

        if(!empty($param['wy_id'])){
            $model->andWhere(['=','wy_id',$param['wy_id']]);
        }
        if(!empty($param['status'])){
            $model->andWhere(['=','status',$param['status']]);
        }

        if(!empty($param['is_close'])){
            $model->andWhere(['=','is_close',$param['is_close']]);
        }

        if(!empty($param['start_time'])){
            $model->andWhere(['>=','event_time',strtotime($param['start_time'])]);
        }
        if(!empty($param['end_time'])){
            $model->andWhere(['<=','event_time',strtotime($param['end_time']." 23:59:59")]);
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
<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/8/19
 * Time: 16:48
 * Desc: 出门单
 */
namespace app\models;

class PsOutOrder extends BaseModel
{

    public $statusMsg = ['1'=>'待确认','2'=>'已确认','3'=>'已放行','4'=>'已作废'];

    public static function tableName()
    {
        return 'ps_out_order';
    }

    public function rules()
    {
        return [

            [['community_id', 'community_name','group_id', 'building_id', 'unit_id', 'room_id','application_name','application_mobile','member_type','room_address','application_id','application_at','content','ali_form_id','ali_user_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['edit']],
            [['application_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['listOfC']],
            [['id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['detail']],
            [["id", 'status', 'member_type', 'application_at', 'release_at', 'check_at','create_at', 'update_at'], 'integer'],
            [['community_id','community_name','group_id','building_id','unit_id','room_id','application_name','application_mobile','room_address','application_id','code','qr_url','content','content_img','car_number','release_id','release_name','check_id','check_name','ali_form_id','ali_user_id'], 'trim'],
            [['car_number','content_img'], 'string'],
            [['application_mobile'], 'string', "max" => 12],
            [['application_name','code','car_number','release_name','check_name'], 'string', "max" => 10],
            [['community_id','group_id','building_id','unit_id','room_id','application_id','release_id','check_id'], 'string', "max" => 30],
            [['community_name'], 'string', "max" => 50],
            [['ali_form_id','ali_user_id'], 'string', "max" => 100],
            [['room_address', 'application_id'], 'string', "max" => 255],
            [['application_mobile'],'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'手机号码格式有误'],
            ['status', 'in', 'range' => [1, 2, 3, 4], 'on' => ['add', 'edit']],
            [['id'], 'dataInfo', 'on' => ["edit", "detail"]], //活动是否存在
            [['application_at'], 'timeVerification', 'on' => ["add"]], //申请时间验证
            [["create_at", 'update_at'], "default", 'value' => time(), 'on' => ['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id'                  => '出门单id',
              'status'              => '状态 ',
              'community_id'        => '小区',
              'community_name'      => '小区名称',
              'group_id'             => '苑期区',
              'building_id'          => '幢',
              'unit_id'              => '单元',
              'room_id'              => '房屋',
              'application_name'    => '申请人',
              'application_mobile'  => '申请人电话',
              'member_type'         => '申请人身份',
              'room_address'        => '房屋地址',
              'application_id'      => '申请人',
              'application_at'      => '申请日期',
              'code'                => '出门唯一标识',
              'qr_url'              => '出门二维码',
              'content'             => '出门单内容',
              'content_img'         => '出门单内容图片',
              'car_number'          => '车牌号',
              'release_at'          => '出门时间',
              'release_id'          => '放行人',
              'release_name'        => '放行人名称',
              'check_id'            => '审核人id',
              'check_name'          => '审核人名称',
              'check_at'            => '审核时间',
              'ali_form_id'         => '小程序表单id，用户消息推送',
              'ali_user_id'         => '小程序用户id，用户消息推送',
              'create_at'           => '提交订单时间',
              'update_at'           => '修改订单时间',
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
    public function dataInfo($attribute){
        if(!empty($this->id)){
            $res = self::find()->select(['id'])->where(['=','id',$this->id])->asArray()->one();
            if(empty($res)){
                return $this->addError($attribute, "该出门单不存在");
            }
        }
    }

    /*
     * 申请时间验证
     */
    public function timeVerification($attribute){
        if(!empty($this->application_at)){
            $nowTime = time();
            if(date('Y-m-d',$this->application_at)<date('Y-m-d',$nowTime)){
                return $this->addError($attribute, "申请日期应大于当前日期");
            }
        }
    }

    //出门单二维码
    public function getOrderQrCode($params){
        $fields = ['id','qr_url','room_address','application_at','code','application_name','status'];
        $model = self::find()->select($fields)->where(['=','id',$params['id']]);
        return $model->asArray()->one();
    }

    //出门单详情
    public function getDetail($params){
        $fields = ['id','room_address','application_at','application_name','status','car_number','content','content_img'];
        $model = self::find()->select($fields)->where(['=','id',$params['id']]);
        return $model->asArray()->one();
    }

    //出门单列表c端
    public function listOfC($params){
        $fields = ['id','status','code','qr_url','application_at','create_at'];
        $model = self::find()->select($fields)->where(['=','application_id',$params['application_id']]);
        if(!empty($params['community_id'])){
            $model->andWhere(['=','community_id',$params['community_id']]);
        }
        if(!empty($params['room_id'])){
            $model->andWhere(['=','room_id',$params['room_id']]);
        }
        $count = $model->count();
        if(!empty($params['page'])||!empty($params['pageSize'])){
            $page = !empty($params['page'])?intval($params['page']):1;
            $pageSize = !empty($params['pageSize'])?intval($params['pageSize']):10;
            $offset = ($page-1)*$pageSize;
            $model->offset($offset)->limit($pageSize);
        }
        $model->orderBy(["id"=>SORT_DESC]);
        $result = $model->asArray()->all();
        return ['count'=>$count,'data'=>$result];
    }
}
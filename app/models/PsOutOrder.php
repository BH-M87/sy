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


    public static function tableName()
    {
        return 'ps_out_order';
    }

    public function rules()
    {
        return [

            [['community_id', 'groupId', 'buildingId', 'unitId', 'roomId','application_name','application_mobile','member_type','room_address','application_id','application_at','content','ali_form_id','ali_user_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['edit']],
            [['id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['detail']],
            [["id", 'status', 'member_type', 'application_at', 'release_at', 'check_at','create_at', 'update_at'], 'integer'],
            [['community_id','groupId','buildingId','unitId','roomId','application_name','application_mobile','room_address','application_id','code','qr_url','content','content_img','car_number','release_id','release_name','check_id','check_name','ali_form_id','ali_user_id'], 'trim'],
            [['application_mobile'], 'string', "max" => 12],
            [['application_name','code','car_number','release_name','check_name'], 'string', "max" => 10],
            [['community_id','groupId','buildingId','unitId','roomId','application_id','release_id','check_id'], 'string', "max" => 30],
            [['ali_form_id','ali_user_id'], 'string', "max" => 100],
            [['room_address', 'application_id'], 'string', "max" => 255],
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
              'groupId'             => '苑期区',
              'buildingId'          => '幢',
              'unitId'              => '单元',
              'roomId'              => '房屋',
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
                return $this->addError($attribute, "申请日期应大于当前时间");
            }
        }
    }
}
<?php
namespace app\models;

use yii\behaviors\TimestampBehavior;

use common\core\Regular;

class PsRoomVisitor extends BaseModel
{
    public static $statusMsg = [1 => '待访', 2 => '到访'];

    public static function tableName()
    {
        return 'ps_room_visitor';
    }

    public function rules()
    {
        return [
            [['room_id', 'communityId', 'groupId', 'buildingId', 'unitId', 'fullName', 'user_id', 'member_id', 'name','mobile'],'required','message'=>'{attribute}不能为空!','on' => ['add']],
            [['is_car', 'sex', 'visit_at', 'pass_at'], 'integer','message'=> '{attribute}格式错误!'],
            [['communityName', 'room_name', 'room_mobile', 'car_number', 'password'], 'string', 'max' => 30],
            [['qrcode'], 'string', 'max' => 300, 'on' => ['add']],
            [['room_mobile', 'mobile'], 'match', 'pattern'=>Regular::telOrPhone(), 'message'=>'{attribute}格式错误','on' => ['add']],
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
            ['status', 'default', 'value' => 1, 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'room_id' => '房屋id',
            'room_name' => '业主姓名',
            'room_mobile' => '业主手机号',
            'communityId' => '小区id',
            'communityName' => '小区名称',
            'groupId' => '苑期区',
            'buildingId' => '幢',
            'unitId' => '单元',
            'fullName' => '详细地址',
            'user_id' => '添加此访客的支付宝用户id',
            'member_id' => '住户id',
            'name' => '访客姓名',
            'mobile' => '访客手机号',
            'car_number' => '车牌号',
            'password' => '访客密码',
            'qrcode' => '二维码url',
            'sex' => '性别',
            'is_car' => '是否驾车',
            'status' => '访问状态',
            'visit_at' => '到访时间',
            'pass_at' => '通行时间',
            'create_at' => '添加时间',
        ];
    }

    // 新增 编辑
    public function saveData($scenario, $p)
    {
        if ($scenario == 'edit') {
            self::updateAll($p, ['id' => $p['id']]);
            return true;
        }
        return $this->save();
    }
}

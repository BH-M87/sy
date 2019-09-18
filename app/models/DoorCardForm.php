<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/8/21
 * Time: 17:46
 */

namespace app\models;


use yii\base\Model;

class DoorCardForm extends Model
{
    public $community_id;
    public $id;
    public $type;
    public $card_num;
    public $card_type;
    public $expires_in;
    public $name;
    public $mobile;
    public $group;
    public $building;
    public $unit;
    public $room;
    public $room_id;
    public $devices_id;
    public $status;
    public $identity_type;


    public function rules()
    {
        return [
            [['type','card_num', 'card_type', 'expires_in','community_id'],'required', 'message'=>'{attribute}不能为空!','on'=>['add','edit']],
            [['id'],'required', 'message'=>'{attribute}不能为空!','on'=>['edit','detail','delete','status']],
            [['status'],'required', 'message'=>'{attribute}不能为空!','on'=>'status'],
            [['group', 'building', 'unit', 'room'],'required', 'message'=>'{attribute}不能为空!','on'=>'user-list'],
            [['type', 'card_type', 'room_id'],'integer'],
            [['type'], 'validateType'],
            [['card_num'], 'string', 'max' => 60],
            [['name'], 'string', 'max' => 20],
            [['mobile'], 'string', 'max' => 15],
            [['devices_id'], 'string', 'max' => 100],
            [['group', 'building', 'unit', 'room','identity_type'], 'safe'],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区ID',
            'type' => '卡类型',
            'card_num' => '门卡号',
            'card_type' => '门卡类型',
            'expires_in' => '有效期',
            'name' => '姓名',
            'mobile' => '手机号',
            'room_id' => '房间号',
            'group' => '苑/期/区',
            'building' => '幢',
            'unit' => '单元',
            'room' => '室',
            'devices_id' => '授权门禁',
            'status' => '门卡状态',
            'identity_type' =>'住户类型'
        ];
    }

    //呼叫方式不同，验证的参数不同
    public function validateType($attribute)
    {
        $type = $this->$attribute;
        if ($type == '1') {
            //普通卡
            if (empty($this->group)) {
                $this->addError($attribute, $this->getAttributeLabel('group') . "不能为空");
            }
            if (empty($this->building)) {
                $this->addError($attribute, $this->getAttributeLabel('building') . "不能为空");
            }
            if (empty($this->unit)) {
                $this->addError($attribute, $this->getAttributeLabel('unit') . "不能为空");
            }
            if (empty($this->room)) {
                $this->addError($attribute, $this->getAttributeLabel('room') . "不能为空");
            }
            if (empty($this->name)) {
                $this->addError($attribute, $this->getAttributeLabel('name') . "不能为空");
            }
            $mobile = $this->mobile;
            if (empty($mobile)){
                $this->addError($attribute, $this->getAttributeLabel('mobile') . "不能为空");
            }
            if(!preg_match("/^1\d{10}$/",$mobile)){
                $this->addError($attribute, $this->getAttributeLabel('mobile') . "格式不正确");
            }
            if(empty($this->identity_type)){
                $this->addError($attribute, $this->getAttributeLabel('identity_type') . "不能为空");
            }
        } else if ($type == '2') {
            //管理卡
            if (empty($this->name)) {
                $this->addError($attribute, $this->getAttributeLabel('name') . "不能为空");
            }
            $mobile = $this->mobile;
            if (empty($mobile)){
                $this->addError($attribute, $this->getAttributeLabel('mobile') . "不能为空");
            }
            if(!preg_match("/^1\d{10}$/",$mobile)){
                $this->addError($attribute, $this->getAttributeLabel('mobile') . "格式不正确");
            }
            $devices_id = $this->devices_id;
            if (empty($devices_id)) {
                $this->addError($attribute, $this->getAttributeLabel('devices_id') . "不能为空");
            }
        } else {
            $this->addError($attribute, $attribute . "格式不对");
        }
    }
}
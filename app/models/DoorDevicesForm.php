<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/6/23
 * Time: 14:32
 */

namespace app\models;
use yii\base\Model;

class DoorDevicesForm extends Model
{
    public $community_id;
    public $id;
    public $name;
    public $type;
    public $device_id;
    public $permissions;
    public $status;
    public $supplier_id;
    public $note;
    public $list;
    public $user_id;
    public $room_id;
    public $devices_id;

    public function rules()
    {
        return [
            [['name','type','device_id','permissions','supplier_id','community_id'], 'required','message'=>'{attribute}不能为空!','on'=>['add','edit']],
            [['type','status'], 'in', 'range' => [1, 2, 3], 'message'=>'{attribute}只能是1或者2或者3!'],
            [['id'], 'required', 'message'=>'{attribute}不能为空!','on'=>['edit','detail','delete','status']],
            //[['status'],'required', 'message'=>'{attribute}不能为空!','on'=>['edit','add','status']],
            [['list'],'required', 'message'=>'{attribute}不能为空!','on'=>['key-list']],
            [['community_id','devices_id','user_id','room_id'],'required', 'message'=>'{attribute}不能为空!','on'=>['key-edit']],
            [['note'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id'=>'小区id',
            'supplier_id' => '供应商id',
            'name' => '门禁名称',
            'type' => '设备类型',
            'device_id' => '设备序列号',
            'permissions' => '门禁权限',
            'status' => '状态',
            'note' =>'备注',
            'list' =>'用户列表',
            'devices_id'=>'设备id',
            'user_id'=>'用户id',
            'room_id'=>'房屋id',
        ];
    }

}
<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "door_devices".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property int $supplier_id 供应商id
 * @property string $name 门禁名称
 * @property int $type 设备类型：1单元机2围墙机
 * @property int $device_type 开门类型，1入门设备，2出门设备
 * @property string $device_id 设备序列号
 * @property string $permissions 已弃用
 * @property string $note
 * @property int $status 状态：1启用 2禁用
 * @property int $online_status 1在线 2离线 3设备常开，未自动关闭
 * @property int $open_type 设备开门方式，1钥匙开门，2蓝牙开门
 * @property string $open_door_type 开门方式类型:1.人脸,2.蓝牙,3.二维码,4.电子钥匙,5密码 说明:多个方式逗号分隔
 * @property int $is_set_admin 是否已设置管理员ID，目前只有莱易用
 * @property int $update_time 更新时间
 * @property int $create_at 添加时间
 */
class DoorDevices extends BaseModel
{
    public $permissions;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'door_devices';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id'], 'required'],
            [['community_id', 'supplier_id', 'type', 'device_type', 'status', 'online_status', 'update_time', 'create_at'], 'integer'],
            [['note'], 'string'],
            [['name'], 'string', 'max' => 50],
            [['device_id'], 'string', 'max' => 100],
            [['community_id'], 'required', 'message' => '{attribute}不能为空', 'on' => ['common','list']],
            [['name','type','device_id','supplier_id','permissions','community_id'], 'required','message'=>'{attribute}不能为空!','on'=>['add','edit']],
            [['type'], 'in', 'range' => [1, 2, 3], 'message'=>'{attribute}只能是1或者2或者3!'],
            [['status'], 'in', 'range' => [1, 2], 'message'=>'{attribute}只能是1或者2!'],
            [['id'], 'required', 'message'=>'{attribute}不能为空!','on'=>['edit', 'detail', 'change-status', 'delete']],
            [['status'], 'required', 'message'=>'{attribute}不能为空!','on'=>['change-status']],
            [['note'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区id',
            'supplier_id' => '供应商id',
            'name' => '门禁名称',
            'type' => '设备类型',
            'device_type' => '出入类型',
            'device_id' => '设备序列号',
            'permissions' => '门禁权限',
            'note' => '备注',
            'status' => '设备状态',
            'online_status' => 'Online Status',
            'open_door_type' => '开门类型',
            'update_time' => 'Update Time',
            'create_at' => 'Create At',
        ];
    }
}

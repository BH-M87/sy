<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_device_repair".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $category_id
 * @property integer $device_id
 * @property string $device_name
 * @property string $device_no
 * @property integer $start_at
 * @property integer $end_at
 * @property string $repair_person
 * @property string $content
 * @property integer $status
 * @property string $check_note
 * @property string $check_person
 * @property integer $check_at
 * @property string $file_url
 * @property integer $create_at
 */
class PsDeviceRepair extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_device_repair';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'category_id', 'device_id', 'device_name', 'device_no', 'start_at', 'end_at', 'status', 'check_person', 'check_at'], 'required', 'on' => ['add', 'edit']],
            [['community_id', 'category_id', 'device_id', 'start_at', 'end_at', 'status', 'check_at', 'create_at'], 'integer', 'on' => ['add', 'edit']],
            [['device_name', 'device_no'], 'string', 'max' => 15, 'on' => ['add', 'edit']],
            [['repair_person', 'check_person'], 'string', 'max' => 15, 'on' => ['add', 'edit']],
            [['content', 'check_note'], 'string', 'max' => 200, 'on' => ['add', 'edit']],
            [['file_url'], 'string', 'max' => 500, 'on' => ['add', 'edit']],
            [['file_name'], 'string', 'max' => 100, 'on' => ['add', 'edit']],
            // 新增场景
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
            // 编辑场景
            [['id'], 'required', 'on' => ['edit']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            'community_id'  => '小区',
            'category_id'   => '设备分类',
            'device_id'     => '设备',
            'device_name'   => '设备名称',
            'device_no'     => '设备编号',
            'start_at'      => '保养开始时间',
            'end_at'        => '保养结束时间',
            'repair_person' => '设备保养人',
            'content'       => '保养要求与目的',
            'status'        => '保养状态',
            'check_note'    => '保养检查结果',
            'check_person'  => '检查人',
            'check_at'      => '检查日期',
            'file_url'      => '图片',
            'file_name'     => '文件名',
            'create_at'     => 'Create At',
        ];
    }

    // 新增 编辑
    public function saveData($scenario, $param)
    {
        if ($scenario == 'edit') {
            $param['create_at'] = time();
            return self::updateAll($param, ['id' => $param['id']]);
        }
        return $this->save();
    }
}

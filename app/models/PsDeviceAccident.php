<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_device_accident".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $category_id
 * @property integer $device_id
 * @property integer $happen_at
 * @property integer $scene_at
 * @property string $scene_person
 * @property string $confirm_person
 * @property string $describe
 * @property string $opinion
 * @property string $result
 * @property string $file_url
 * @property integer $create_at
 */
class PsDeviceAccident extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_device_accident';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'category_id', 'device_id', 'happen_at', 'scene_at'], 'required', 'on' => ['add', 'edit']],
            [['community_id', 'category_id', 'device_id', 'happen_at', 'scene_at'], 'integer', 'on' => ['add', 'edit']],
            [['scene_person', 'confirm_person'], 'string', 'max' => 15, 'on' => ['add', 'edit']],
            [['describe', 'opinion', 'result', 'file_url'], 'string', 'max' => 200, 'on' => ['add', 'edit']],
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
            'id'             => 'ID',
            'community_id'   => '小区',
            'category_id'    => '设备分类',
            'device_id'      => '设备',
            'happen_at'      => '事故发生时间',
            'scene_at'       => '出现场时间',
            'scene_person'   => '出现场人员',
            'confirm_person' => '确认人',
            'describe'       => '事故事件描述及损失范围',
            'opinion'        => '事故原因及处理意见',
            'result'         => '处理结果',
            'file_url'       => '附件',
            'file_name'      => '文件名',
            'create_at'      => 'Create At',
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

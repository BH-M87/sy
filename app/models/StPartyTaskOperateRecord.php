<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_party_task_operate_record".
 *
 * @property int $id
 * @property int $party_task_station_id 党员认领任务操作记录表
 * @property int $task_id 任务id
 * @property int $communist_id 党员id
 * @property int $operate_type 操作类型 1完成 2审核 3取消
 * @property string $content 输入内容  operate_type1时为完成内容 2 审核说明 3 时为取消理由
 * @property string $images 上传图片，多个以逗号相连
 * @property string $location 当前位置
 * @property string $lon 所在位置经度值
 * @property string $lat 所在位置纬度值
 * @property int $pioneer_value 评分  operate_type 为 2时必填
 * @property int $operator_id 操作人id
 * @property string $operator_name 操作人名
 * @property int $create_at 添加时间
 */
class StPartyTaskOperateRecord extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_party_task_operate_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['party_task_station_id', 'task_id', 'communist_id', 'operate_type', 'pioneer_value', 'operator_id', 'create_at'], 'integer'],
            [['lon', 'lat'], 'number'],
            [['content', 'images'], 'string', 'max' => 500],
            [['location'], 'string', 'max' => 255],
            [['operator_name'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'party_task_station_id' => 'Party Task Station ID',
            'task_id' => 'Task ID',
            'communist_id' => 'Communist ID',
            'operate_type' => 'Operate Type',
            'content' => 'Content',
            'images' => 'Images',
            'location' => 'Location',
            'lon' => 'Lon',
            'lat' => 'Lat',
            'pioneer_value' => 'Pioneer Value',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'create_at' => 'Create At',
        ];
    }
}

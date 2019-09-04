<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_station".
 *
 * @property int $id 主键
 * @property string $station 先锋岗名称
 * @property string $content 描述
 * @property int $status 状态 1显示 2隐藏
 * @property int $operator_id 创建人id
 * @property string $operator_name 操作人名
 * @property int $create_at 创建时间
 */
class StStation extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_station';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'operator_id', 'create_at'], 'integer'],
            [['operator_id', 'create_at'], 'required'],
            [['station'], 'string', 'max' => 30],
            [['content'], 'string', 'max' => 200],
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
            'station' => 'Station',
            'content' => 'Content',
            'status' => 'Status',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'create_at' => 'Create At',
        ];
    }
}

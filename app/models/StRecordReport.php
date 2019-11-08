<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_record_report".
 *
 * @property int $id
 * @property int $type 1车辆统计，2人行统计
 * @property string $day 时间，日期格式
 * @property string $time 时间，时间戳格式，默认当前日期的0点
 * @property int $num 抓拍次数
 * @property int $data_id 车的id(parking_cars)/人的id(ps_member)
 */
class StRecordReport extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_record_report';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'num', 'data_id'], 'integer'],
            [['day'], 'string', 'max' => 20],
            [['time'], 'string', 'max' => 11],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'day' => 'Day',
            'time' => 'Time',
            'num' => 'Num',
            'data_id' => 'Data ID',
        ];
    }
}

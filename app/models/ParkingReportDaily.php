<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_report_daily".
 *
 * @property int $id
 * @property int $community_id 小区ID
 * @property int $supplier_id 供应商id
 * @property string $day 统计日期
 * @property int $type 类型，1临时停车收费，2租赁收费
 * @property string $yesterday 昨日数据
 * @property string $week 近七日数据
 * @property string $month 近30天数据
 * @property string $year 近1年数据
 * @property int $create_at 创建时间
 */
class ParkingReportDaily extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_report_daily';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'day', 'type', 'create_at'], 'required'],
            [['community_id', 'supplier_id', 'type', 'create_at'], 'integer'],
            [['day'], 'safe'],
            [['yesterday', 'week', 'month', 'year'], 'number'],
            [['community_id', 'day', 'type'], 'unique', 'targetAttribute' => ['community_id', 'day', 'type']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'supplier_id' => 'Supplier ID',
            'day' => 'Day',
            'type' => 'Type',
            'yesterday' => 'Yesterday',
            'week' => 'Week',
            'month' => 'Month',
            'year' => 'Year',
            'create_at' => 'Create At',
        ];
    }
}

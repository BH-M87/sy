<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_shared_bill".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $shared_id
 * @property integer $period_id
 * @property integer $room_id
 * @property string $elevator_total
 * @property string $elevator_shared
 * @property string $corridor_total
 * @property string $corridor_shared
 * @property string $water_electricity_total
 * @property string $water_electricity_shared
 * @property string $shared_total
 * @property integer $bill_status
 * @property integer $create_at
 */
class PsSharedBill extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_shared_bill';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shared_id', 'period_id', 'room_id', 'bill_status', 'create_at'], 'integer'],
            [['community_id'],'string','max'=>30],
            [['elevator_total', 'elevator_shared', 'corridor_total', 'corridor_shared', 'water_electricity_total', 'water_electricity_shared', 'shared_total'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'shared_id' => 'Shared ID',
            'period_id' => 'Period ID',
            'room_id' => 'Room ID',
            'elevator_total' => 'Elevator Total',
            'elevator_shared' => 'Elevator Shared',
            'corridor_total' => 'Corridor Total',
            'corridor_shared' => 'Corridor Shared',
            'water_electricity_total' => 'Water Electricity Total',
            'water_electricity_shared' => 'Water Electricity Shared',
            'shared_total' => 'Shared Total',
            'bill_status' => 'Bill Status',
            'create_at' => 'Create At',
        ];
    }
}
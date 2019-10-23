<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_situation".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property string $community_no 小区编号，冗余字段，方便java查询
 * @property int $lot_id 车场id
 * @property string $park_code 车场编号，冗余字段，方便java查询
 * @property int $guestBerthNum 访客车位
 * @property int $guestRemainNum 访客剩余车位
 * @property int $monthlyBerthNum 月租车位
 * @property int $monthlyRemainNum 月租剩余车位
 * @property int $totBerthNum 总车位
 * @property int $totRemainNum 总剩余车位
 */
class ParkingSituation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_situation';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'lot_id', 'guestBerthNum', 'guestRemainNum', 'monthlyBerthNum', 'monthlyRemainNum', 'totBerthNum', 'totRemainNum'], 'integer'],
            [['community_no'], 'string', 'max' => 64],
            [['park_code'], 'string', 'max' => 30],
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
            'community_no' => 'Community No',
            'lot_id' => 'Lot ID',
            'park_code' => 'Park Code',
            'guestBerthNum' => 'Guest Berth Num',
            'guestRemainNum' => 'Guest Remain Num',
            'monthlyBerthNum' => 'Monthly Berth Num',
            'monthlyRemainNum' => 'Monthly Remain Num',
            'totBerthNum' => 'Tot Berth Num',
            'totRemainNum' => 'Tot Remain Num',
        ];
    }
}

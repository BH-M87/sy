<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_bill_income".
 *
 * @property integer $id
 * @property integer $room_id
 * @property integer $trade_no
 * @property integer $community_id
 * @property string $group
 * @property string $building
 * @property string $unit
 * @property string $room
 * @property string $pay_money
 * @property integer $trade_type
 * @property integer $pay_type
 * @property integer $pay_channel
 * @property integer $pay_status
 * @property integer $payee_id
 * @property string $payee_name
 * @property integer $income_time
 * @property integer $check_status
 * @property integer $check_id
 * @property string $check_name
 * @property integer $check_at
 * @property integer $review_id
 * @property string $review_name
 * @property integer $review_at
 * @property integer $entry_at
 * @property string $refund_note
 * @property string $qr_code
 * @property string $note
 * @property integer $create_at
 */
class PsBillIncome extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_bill_income';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['room_id', 'trade_no', 'community_id', 'group', 'building', 'unit', 'room', 'pay_type', 'pay_channel', 'pay_status', 'payee_id', 'payee_name', 'income_time', 'check_status', 'create_at'], 'required'],
            [['room_id', 'trade_no', 'community_id', 'trade_type', 'pay_type', 'pay_channel', 'pay_status', 'payee_id', 'income_time', 'check_status', 'check_id', 'check_at', 'review_id', 'review_at', 'entry_at', 'create_at'], 'integer'],
            [['pay_money'], 'number'],
            [['group', 'building', 'unit', 'room'], 'string', 'max' => 32],
            [['payee_name'], 'string', 'max' => 20],
            [['check_name', 'review_name'], 'string', 'max' => 50],
            [['refund_note'], 'string', 'max' => 100],
            [['qr_code'], 'string', 'max' => 255],
            [['note'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'room_id' => 'ps_community_roominfo表',
            'trade_no' => '交易流水号',
            'community_id' => '小区ID',
            'group' => '苑期区',
            'building' => '幢',
            'unit' => '单元',
            'room' => '房间号',
            'pay_money' => '收款金额',
            'trade_type' => '交易类型',
            'pay_type' => '收款类型',
            'pay_channel' => '收款方式',
            'pay_status' => '交易状态',
            'payee_id' => '收款操作人ID',
            'payee_name' => '收款操作人姓名',
            'income_time' => '收款日期',
            'check_status' => '状态',
            'check_id' => '复核人ID',
            'check_name' => '复核人姓名',
            'check_at' => '复核时间',
            'review_id' => '核销人ID',
            'review_name' => '核销人姓名',
            'review_at' => '核销时间',
            'entry_at' => '入账时间',
            'refund_note' => '退款原因',
            'qr_code' => '收款二维码',
            'note' => '收款备注',
            'create_at' => '操作时间',
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

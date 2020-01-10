<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_order".
 *
 * @property integer $id
 * @property integer $bill_id
 * @property integer $company_id
 * @property integer $community_id
 * @property string $order_no
 * @property integer $product_type
 * @property integer $product_id
 * @property string $product_subject
 * @property string $product_body
 * @property string $bill_amount
 * @property string $other_amount
 * @property string $pay_amount
 * @property integer $status
 * @property integer $is_del
 * @property integer $pay_channel
 * @property integer $pay_status
 * @property integer $pay_time
 * @property integer $pay_id
 * @property string $remark
 * @property string $trade_no
 * @property string $buyer_account
 * @property integer $create_at
 */
class PsOrder extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_order';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bill_id', 'company_id', 'community_id', 'product_type', 'product_id', 'status', 'is_del', 'pay_channel',
                'pay_status', 'pay_time', 'pay_id', 'create_at'], 'integer'],
            [['community_id', 'company_id', 'order_no', 'product_type', 'bill_amount', 'pay_amount', 'create_at'], 'required'],
            [['bill_amount', 'other_amount', 'pay_amount'], 'number'],
            [['order_no'], 'string', 'max' => 150],
            [['product_subject', 'remark'], 'string', 'max' => 100],
            [['product_body', 'trade_no'], 'string', 'max' => 200],
            [['buyer_account'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'bill_id' => '账单id',
            'community_id' => '小区ID',
            'company_id' => '物业公司ID',
            'order_no' => '订单号',
            'product_type' => '商品类型',
            'product_id' => '关联商品的ID',
            'product_subject' => '商品标题',
            'product_body' => '商品描述',
            'bill_amount' => '商品实际金额',
            'other_amount' => 'Other Amount',
            'pay_amount' => '应付金额',
            'pay_channel' => '支付渠道',
            'status' => '缴费状态',
            'is_del' => '是否删除',
            'pay_status' => '支付状态',
            'pay_time' => '支付时间',
            'pay_id' => '支付结果表id',
            'trade_no' => '交易流水号',
            'remark' => '付款说明',
            'buyer_account' => '买家支付宝账号',
            'create_at' => '订单创建时间',
        ];
    }

    public function getOne($param)
    {
        if (!isset($param['where'])) {
            $param['where'] = '1=1';
        }
        if (!isset($param['field'])) {
            $param['field'] = '*';
        }
        return self::find()->select($param['field'])->where($param['where'])->asArray()->one();
    }
}

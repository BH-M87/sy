<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_shop_discount".
 *
 * @property integer $id
 * @property integer $shop_id
 * @property integer $discount_type
 * @property integer $full_money
 * @property integer $off_money
 * @property integer $direct_off
 * @property integer $discount
 * @property integer $create_at
 */
class PsShopDiscount extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_shop_discount';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shop_id', 'discount_type', 'full_money', 'off_money', 'direct_off', 'create_at'], 'integer'],
            ['discount', 'number'],
            ['note', 'string', 'max'=>50],
            ['note', 'default', 'value'=>''],

            [['shop_id', 'discount_type'], 'required', 'on'=>['full_off', 'direct_off','discount']],
            //满减
            [['full_money', 'off_money'], 'required', 'on'=>['full_off']],
            [['full_money', 'off_money'], 'compare', 'compareValue'=>0, 'operator'=>'>', 'on'=>['full_off']],
            ['off_money', 'compare', 'compareAttribute'=>'full_money', 'operator'=>'<=', 'on'=>['full_off']],
            //
            [['full_money', 'off_money'], 'default', 'value'=>'0', 'on'=>['direct_off','discount']],
            //直减
            [['direct_off'], 'required', 'on'=>['direct_off']],
            [['direct_off'], 'compare', 'compareValue'=>0, 'operator'=>'>', 'on'=>['direct_off']],
            //
            [['direct_off'], 'default', 'value'=>0, 'on'=>['full_off', 'discount']],
            //打折
            [['discount'], 'required', 'on'=>'discount'],
            [['discount'], 'compare', 'compareValue'=>0, 'operator'=>'>=', 'on'=>['discount']],
            [['discount'], 'compare', 'compareValue'=>10, 'operator'=>'<=', 'on'=>['discount']],
            ['discount', 'default', 'value'=>0, 'on'=>['full_off', 'direct_off']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => '商家ID',
            'discount_type' => '打折类型',
            'full_money' => '满多少',
            'off_money' => '减多少',
            'direct_off' => '直减',
            'discount' => '打折',
            'create_at' => '创建时间',
        ];
    }

    public function beforeSave($insert)
    {
        if(!parent::beforeSave($insert)) return false;
        if($insert) {
            $this->create_at = time();
        }
        return true;
    }
}

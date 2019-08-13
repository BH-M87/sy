<?php

namespace app\models;

use common\core\Regular;
use Yii;

/**
 * This is the model class for table "ps_shop".
 *
 * @property integer $id
 * @property string $name
 * @property integer $referer
 * @property string $contactor
 * @property string $contact_tel
 * @property string $phone
 * @property string $alipay_account
 * @property string $position
 * @property string $logo_url
 * @property string $image
 * @property string $description
 * @property integer $status
 * @property integer $balance
 * @property integer $business
 * @property integer $income
 * @property integer $create_at
 */
class PsShop extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_shop';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['referer', 'default', 'value'=>2],
            [['balance', 'business', 'income'], 'number'],
            [['balance', 'business', 'income'], 'default', 'value'=>0],
            [['referer', 'status', 'shop_type'], 'integer'],
            ['alipay_account', 'string'],
            ['alipay_account', 'default', 'value'=>''],
            ['logo_url', 'string'],
            ['logo_url', 'default', 'value'=>''],
            [['name', 'referer', 'contactor', 'contact_tel', 'position', 'phone', 'image', 'status', 'shop_type'], 'required',
                'message'=>'{attribute}不能为空'],
            ['name', 'string', 'max'=>'20'],
            ['contactor', 'string', 'length'=>[2, 4]],
            ['contact_tel', 'match', 'pattern'=>Regular::telOrPhone(), 'message'=>'只能是手机号或座机'],
            ['position', 'string', 'max'=>50],
            ['phone', 'match', 'pattern'=> Regular::phone(), 'message'=>'关联手机格式不正确'],
            ['description', 'string', 'max'=>'500'],
            ['status', 'in', 'range'=>[1, 2]],
            ['shop_type', 'in', 'range'=>[1, 2]],
            ['phone', 'unique'],
            ['nonce', 'string', 'length'=>32]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '商家名称',
            'shop_type'=>'商家类型',
            'referer' => '商家所属',
            'contactor' => '联系人',
            'contact_tel' => '联系电话',
            'phone' => '关联手机号',
            'alipay_account' => '支付宝帐号',
            'position' => '商家位置',
            'logo_url' => '商家logo',
            'image' => '商家图片',
            'description' => '优惠说明',
            'status' => '状态',
            'balance' => 'Balance',
            'create_at' => 'Create At',
            'nonce' => 'Nonce'
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

    public function getCommunity()
    {
        return $this->hasMany(PsCommunityModel::className(), ['id'=>'community_id'])
            ->viaTable('ps_shop_community', ['shop_id'=>'id'])
            ->select('id, name');
    }

    public function getDiscount()
    {
        return $this->hasOne(PsShopDiscount::className(), ['shop_id'=>'id']);
    }
}

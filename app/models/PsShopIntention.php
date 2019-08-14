<?php

namespace app\models;

use common\core\Regular;
use Yii;

/**
 * This is the model class for table "ps_shop_intention".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $name
 * @property string $contactor
 * @property integer $phone
 * @property integer $status
 * @property integer $create_at
 * @property integer $update_at
 */
class PsShopIntention extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_shop_intention';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'name', 'contactor', 'phone', 'create_at', 'update_at'], 'required', 'message' => '{attribute}不能为空!', 'on' => 'add'],
            [['name',], 'string', 'max' => 20, 'message' => '{attribute}20字以内文字!', 'on' => 'add'],
            [['contactor',], 'string', 'max' => 4, 'message' => '{attribute}4字以内文字!', 'on' => 'add'],
            [['phone'], 'match', 'pattern' => Regular::phone(),
                'message' => '{attribute}手机号格式不正确', 'on' =>['publish']],
            [['community_id', 'status', 'create_at', 'update_at'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区id',
            'name'         => '店铺名称',
            'contactor'    => '联系人',
            'phone'        => '联系电话',
            'status'       => 'Status',
            'create_at'    => '添加时间',
            'update_at'    => '更新时间',
        ];
    }

    public function getCommunity()
    {
        return $this->hasOne(PsCommunityModel::className(), ['id'=>'community_id'])
            ->select('id, name');
    }
}

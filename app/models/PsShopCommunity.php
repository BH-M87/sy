<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_shop_community".
 *
 * @property integer $id
 * @property integer $shop_id
 * @property integer $community_id
 */
class PsShopCommunity extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_shop_community';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shop_id', 'community_id'], 'required'],
            [['shop_id', 'community_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => 'Shop ID',
            'community_id' => 'Community ID',
        ];
    }
}

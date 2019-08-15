<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_repair_materials".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property int $cate_id 耗材分类 枚举类型
 * @property string $name 耗材名称
 * @property string $price 单价
 * @property int $price_unit 价格单位  1 米  2卷  3个 4次
 * @property int $num 材料数量
 * @property int $status 状态为0的代表已删除
 * @property int $created_at 添加时间
 */
class PsRepairMaterials extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_repair_materials';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id'], 'required'],
            [['community_id', 'cate_id', 'price_unit', 'num', 'status', 'created_at'], 'integer'],
            [['price'], 'number'],
            [['name'], 'string', 'max' => 50],
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
            'cate_id' => 'Cate ID',
            'name' => 'Name',
            'price' => 'Price',
            'price_unit' => 'Price Unit',
            'num' => 'Num',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}

<?php
namespace app\models;

use Yii;

class PsShopGoodsTypeRela extends BaseModel
{
    public static function tableName()
    {
        return 'ps_shop_goods_type_rela';
    }

    public function rules()
    {
        return [
            [['goods_id', 'type_id'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_id' => '店铺ID',
            'type_id' => '商品分类ID',
        ];
    }

     // 新增 编辑
    public function saveData($scenario, $p)
    {
        if ($scenario == 'edit') {
            self::updateAll($p, ['id' => $p['id']]);
            return true;
        }
        return $this->save();
    }
}

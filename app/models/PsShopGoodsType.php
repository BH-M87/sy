<?php
namespace app\models;

use Yii;

class PsShopGoodsType extends BaseModel
{
    public static function tableName()
    {
        return 'ps_shop_goods_type';
    }

    public function rules()
    {
        return [
            [['shop_id', 'type_name'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
            [['shop_id'], 'integer', 'message'=> '{attribute}格式错误!'],
            [['type_name'], 'string', 'max' => 8],
            ['update_at', 'default', 'value' => 0, 'on' => 'add'],
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => '店铺ID',
            'type_name' => '商品分类名称',
            'update_at' => '更新时间',
            'create_at' => '新增时间',
        ];
    }

     // 新增 编辑
    public function saveData($scenario, $p)
    {
        if ($scenario == 'edit') {
            $p['update_at'] = time();
            self::updateAll($p, ['id' => $p['id']]);
            return true;
        }
        return $this->save();
    }
}

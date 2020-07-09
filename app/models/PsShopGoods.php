<?php
namespace app\models;

use Yii;

class PsShopGoods extends BaseModel
{
    public static function tableName()
    {
        return 'ps_shop_goods';
    }

    public function rules()
    {
        return [
            [['shop_id', 'status', 'img', 'goods_name', 'merchant_code'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
            [['goods_code'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add']],
            [['status'], 'integer', 'message'=> '{attribute}格式错误!'],
            [['goods_name'], 'string', 'max' => 20],
            [['img'], 'string', 'max' => 255],
            ['update_at', 'default', 'value' => 0, 'on' => 'add'],
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => '店铺ID',
            'merchant_code' => '商家编号',
            'goods_code' => '商品编号',
            'goods_name' => '商品名称',
            'status' => '商品状态',
            'img' => '商品图片',
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

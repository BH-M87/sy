<?php
namespace app\models;

use Yii;

class PsShopCommunity extends BaseModel
{
    public static function tableName()
    {
        return 'ps_shop_community';
    }

    public function rules()
    {
        return [
            [['shop_id', 'distance', 'community_id', 'community_name', 'society_id', 'society_name'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
            [['shop_id'], 'integer', 'message'=> '{attribute}格式错误!'],
            ['update_at', 'default', 'value' => 0, 'on' => 'add'],
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => '店铺ID',
            'distance' => '商品分类ID',
            'community_id' => '商家编号',
            'community_name' => '商品编号',
            'society_id' => '商品名称',
            'society_name' => '商品状态',
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

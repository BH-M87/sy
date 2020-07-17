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
            [['shop_id', 'distance', 'community_id', 'community_name'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
            [['shop_id'], 'integer', 'message'=> '{attribute}格式错误!'],
            [['society_id', 'society_name'], 'string', 'max' => 30],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => '店铺ID',
            'distance' => '距离',
            'community_id' => '小区ID',
            'community_name' => '小区编号',
            'society_id' => '社区ID',
            'society_name' => '社区编号',
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

<?php
namespace app\models;

use Yii;

class PsShopStatistic extends BaseModel
{
    public static function tableName()
    {
        return 'ps_shop_statistic';
    }

    public function rules()
    {
        return [
            [['shop_id', 'year', 'month', 'day'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
            ['click_num', 'default', 'value' => 1, 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => '店铺ID',
            'year' => '年',
            'month' => '月',
            'day' => '日',
            'click_num' => '点击量',
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

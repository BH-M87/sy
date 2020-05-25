<?php
namespace app\models;

use Yii;

class GoodsGroup extends BaseModel
{
    public static function tableName()
    {
        return 'ps_goods_group';
    }

    public function rules()
    {
        return [
            [['name', 'startAt', 'endAt', 'operatorId', 'operatorName'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
            [['startAt', 'endAt', 'operatorId'], 'integer', 'message'=> '{attribute}格式错误!'],
            [['name'], 'string', 'max' => 10],
            ['updateAt', 'default', 'value' => 0, 'on' => 'add'],
            ['createAt', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '期名称',
            'startAt' => '兑换开始时间',
            'endAt' => '兑换结束时间',
            'operatorId' => '操作人id',
            'operatorName' => '操作人名称',
            'updateAt' => '更新时间',
            'createAt' => '新增时间',
        ];
    }

     // 新增 编辑
    public function saveData($scenario, $p)
    {
        if ($scenario == 'edit') {
            $p['updateAt'] = time();
            self::updateAll($p, ['id' => $p['id']]);
            return true;
        }
        return $this->save();
    }
}

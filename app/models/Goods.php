<?php
namespace app\models;

use Yii;

class Goods extends BaseModel
{
    public static function tableName()
    {
        return 'ps_goods';
    }

    public function rules()
    {
        return [
            [['name', 'img', 'groupId', 'score', 'num', 'personLimit', 'operatorId', 'operatorName'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
            [['score', 'num', 'personLimit', 'operatorId', 'groupId', 'receiveType', 'type'], 'integer', 'message'=> '{attribute}格式错误!'],
            [['name'], 'string', 'max' => 20],
            [['img'], 'string', 'max' => 255],
            [['personLimit'], 'default', 'value' => 1, 'on' => 'add'],
            [['isExchange', 'isDelete'], 'default', 'value' => 2, 'on' => 'add'],
            ['updateAt', 'default', 'value' => 0, 'on' => 'add'],
            ['updateAt', 'default', 'value' => 0, 'on' => 'add'],
            ['createAt', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '兑换名称',
            'img' => '图片',
            'groupId' => '期数ID',
            'score' => '兑换分数',
            'num' => '可兑换数量',
            'receiveType' => '领取方式',
            'type' => '商品属性',
            'personLimit' => '每人兑换限制',
            'isExchange' => '是否兑换过',
            'isDelete' => '是否删除',
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

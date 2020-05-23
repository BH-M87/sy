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
            [['name', 'img', 'startAt', 'endAt', 'groupName', 'score', 'num', 'personLimit', 'operatorId', 'operatorName'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
            [['startAt', 'endAt', 'score', 'num', 'personLimit', 'operatorId'], 'integer', 'message'=> '{attribute}格式错误!'],
            [['name'], 'string', 'max' => 20],
            [['groupName'], 'string', 'max' => 10],
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
            'startAt' => '兑换开始时间',
            'endAt' => '兑换结束时间',
            'groupName' => '兑换期数',
            'score' => '兑换分数',
            'num' => '可兑换数量',
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
            self::updateAll($p, ['id' => $p['id']]);
            return true;
        }
        return $this->save();
    }
}

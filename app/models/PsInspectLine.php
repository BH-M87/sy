<?php

namespace app\models;

use common\core\Regular;
use Yii;

class PsInspectLine extends BaseModel
{
    public $point = [];//选择的巡检点

    public static function tableName()
    {
        return 'ps_inspect_line';
    }

    public function rules()
    {
        return [
            [['id', 'communityId', 'name', 'point', 'createAt'], 'required', 'message' => '{attribute}不能为空!'],
            [['communityId', 'createAt'], 'integer'],
            [['name'], 'string', 'max' => 15, 'tooLong' => '{attribute}长度不能超过15个字'],
            [['img', 'remark'], 'string', 'max' => 255, 'tooLong' => '{attribute}长度不能超过255个字'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '线路名称',
            'communityId' => '小区',
            'createAt' => '创建时间',
            'img' => '路线图',
            'remark' => '备注',
            'point' => '巡检点',
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        // 各个场景的活动属性
        $scenarios['add'] = ['communityId', 'name', 'point','createAt', 'img', 'remark'];//新增
        $scenarios['update'] = ['id', 'communityId', 'name', 'point', 'img', 'remark'];//编辑
        
        return $scenarios;
    }
}

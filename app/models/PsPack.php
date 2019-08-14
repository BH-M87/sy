<?php
namespace app\models;

class PsPack extends BaseModel
{
    public $name;
    public $type;
    public $described;

    public function rules()
    {
        return [
            [['name'], 'required','message' => '{attribute}不能为空!', 'on' => ['classify-add','pack-add']],
            [['name'], 'string', 'max'=>20, 'on'=>['classify-add','pack-add']],
            [['described'], 'string', 'max'=>100, 'on'=>['classify-add','pack-add']],
            [['type'], 'required','message' => '{attribute}不能为空!', 'on' => ['pack-add']],
            ['type', 'in', 'range' => [1, 2],
                'message' => '{attribute}不合法', 'on' =>['pack-add']],
        ];
    }

    public function attributeLabels()
    {
        return [
            "name"   => "名称",
            "described" => "备注",
            "type"   => "所属类型",
        ];
    }
}

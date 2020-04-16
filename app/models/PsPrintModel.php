<?php

namespace app\models;

use yii\base\Model;

class PsPrintModel extends Model
{
    public $community_id;
    public $model_type;
    public $model_title;
    public $first_area;
    public $second_area;
    public $first_area_to;
    public $second_area_to;

    public $group;
    public $building;
    public $unit;

    public $acct_period_start;
    public $acct_period_end;

    public $name;
    public $title;
    public $note;
    public $remark;
    public $room_id;
    public $bill_list;


    public function rules()
    {
        return [
            [['community_id'], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['list','unit-add','room-add',"show",'edit-water','app-show',"get-comm-info","charge-add","charge-bill","print-bill"]],
            [['room_id'], 'required','message' => '房屋id不能为空!', 'on' => ["print-bill"]],
            [['bill_list'], 'required','message' => '账单id不能为空!', 'on' => ["print-bill"]],
            [['model_type'], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['show','unit-add','room-add','edit-water',"app-show","charge-add"]],
            [['model_title','first_area'], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['unit-add','room-add']],
            [['second_area'], 'required','message' => '{attribute}不能为空!', 'on' => ['unit-add']],

            [['model_title'], 'required','message' => '{attribute}不能为空!', 'on' => ["charge-add"]],
            [['first_area_to'], 'required','message' => '{attribute}不能为空!', 'on' => ["charge-add"]],
            [['second_area_to'], 'required','message' => '{attribute}不能为空!', 'on' => ["charge-add"]],
            ['remark', 'string', 'length' => [1, 1500], 'on' =>['charge-add']],
            ['first_area_to', 'string', 'length' => [1, 30], 'on' =>['charge-add']],
            ['second_area_to', 'string', 'length' => [1, 30], 'on' =>['charge-add']],

            ['model_type', 'in', 'range' => [1, 2, 4, 5, 6],'message' => '{attribute}不正确', 'on' =>['add', 'show','charge-add']],
            ['model_type', 'in', 'range' => [3, 4],
                'message' => '{attribute}不正确', 'on' =>['edit-water',"app-show"]],

            ['model_title', 'string', 'length' => [1, 30], 'message' => '{attribute}长度不正确', 'on' =>['add','edit-water','charge-add']],
            ['first_area', 'string', 'length' => [1, 500], 'message' => '{attribute}长度不正确', 'on' =>['add','edit-water']],
            ['title', 'string', 'length' => [1, 20], 'message' => '栏目标题长度不正确', 'on' =>['add-advert']],
            ['name', 'string', 'length' => [1, 20], 'message' => '栏目名称长度不正确', 'on' =>['add-advert']],

            ['second_area', 'string', 'length' => [1, 500], 'message' => '{attribute}长度不正确', 'on' =>['add']],
            ['note', 'string', 'length' => [1, 500], 'message' => '文本区域长度不正确', 'on' =>['add-advert']],
//            [['community_id','group'], 'required','message' => '{attribute}不能为空!', 'on' => ['bill-list']],
            [['acct_period_start','acct_period_end'], 'date','format'=>'yyyy-MM-dd','message'=>'{attribute}不正确!','on'=>'bill-list'],
            ['acct_period_start', 'compare', 'compareAttribute' => 'acct_period_end', 'operator' => '<=' ,'on'=>'bill-list'],

        ];
    }

    public function attributeLabels()
    {
        return [
            'community_id'     => '小区id',
            'model_type'       => '模板类型',
            'model_title'      => '模板标题',
            'first_area'       => '自定义区域1',
            'second_area'      => '自定义区域2',
            'first_area_to'       => '收款人',
            'second_area_to'      => '收款单位',
            "acct_period_end"  => "账期结束时间",
            "acct_period_start"  => "账期开始时间",
            "group" => "期/苑/区",
            "building" => "幢",
            "unit" => "单元",
            "remark" => "备注",
        ];
    }

    public function getAdverts()
    {
        return $this->hasMany(PsWaterAdvert::className(), ['template_id'=>'id'])->with('images');
    }
}

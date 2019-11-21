<?php

namespace app\models;


use common\core\Regular;
use yii\base\Model;

class PsFormulaFrom extends Model
{

    public $community_id;
    public $formula_id;
    public $name;
    public $formula;
    public $acct_period_end;
    public $acct_period_start;
    public $task_id;
    public $cost_type;
    public $calc_rule;
    public $del_decimal_way;
    public $price;
    public $type;


    public function rules()
    {
        return [
            [['community_id'], 'required','message' => '{attribute}不能为空!', 'on' => ['list',"water-edit","water-list",'water-show']],

            //['price', 'compare', 'compareValue' => 0.01, 'message'=>'金额数值不能低于0.01','operator' => '>','on'=>'water-edit'],

            [['formula_id'], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['edit','show','delete']],

            [['task_id'], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['look-bill','release-bill','recall-bill']],

            [['name','formula','community_id','calc_rule','del_decimal_way'], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['edit','add']],

            [['del_decimal_way', 'calc_rule'], 'in', 'range' => [1, 2, 3], 'message' => '{attribute}取值范围错误', 'on' => ['edit', 'add']],

            ['name', 'match', 'pattern' => Regular::string(1, 20),
                'message' => '{attribute}最长不超过20个汉字，且不能含特殊字符', 'on' =>['add','edit']],

            [['acct_period_start','acct_period_end','community_id','cost_type','formula_id'],'required',
                'message'=>'{attribute}不能为空!','on'=>'bill'],
            [['acct_period_start','acct_period_end'], 'date','format'=>'yyyy-MM-dd','message'=>'{attribute}不正确!','on'=>'bill'],
            ['acct_period_start', 'compare', 'compareAttribute' => 'acct_period_end', 'operator' => '<=' ,'on'=>'bill'],

            //新增编辑电费
            [['community_id','type','calc_rule','del_decimal_way'],'required','message'=>'{attribute}不能为空!','on'=>['create-rule','phase_add']],
            [['community_id','type','calc_rule','del_decimal_way'],'number','on'=>['create-rule','phase_add']],
            ['price','number','message'=>'金额格式出错','on'=>['phase_add','shared-create']],
            ['price', 'compare', 'compareValue' => 0.01, 'message'=>'金额数值不能低于0.01','operator' => '>','on'=>['phase_add','shared-create']],
            //新增编辑公摊水电费
            [['community_id','price'],'required','message'=>'{attribute}不能为空!','on'=>['shared-create']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'community_id'     => '小区id',
            'formula_id'       => "公式标识id",
            'name'       => "公式名称",
            'formula'       => "输入的公式",
            'acct_period_start' => "账期开始时间",
            'acct_period_end' => "账期结束时间",
            'task_id'=>"任务id",
            "cost_type" => "服务项目",
            "calc_rule" => "计算规则",
            "del_decimal_way" => "小数去尾方式",
            "price" =>"单价",
        ];
    }

}

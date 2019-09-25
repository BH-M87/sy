<?php

namespace app\models;

use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class BillFrom extends Model
{
    public $out_room_id;
    public $acct_period_end;
    public $acct_period_start;
    public $cost_type;
    public $bill_entry_amount;
    public $community_id;
    public $bill_id;
    public $bill_ids;
    public $release_day;
    public $release_end_day;

    public $buyer_logon_id;
    public $community_name;
    public $property_company;
    public $property_account;
    public $paid_at_start;
    public $paid_at_end;
    public $trade_no;

    public $building;
    public $group;
    public $room;
    public $unit;
    public $pay_channel;
    public $pay_amount;
    public $pay_type;
    public $acct_period;
    public $body;
    public $notify_time;
    public $trade_status;
    public $det_list;
    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            //lists
            [['buyer_logon_id','community_name','property_company','property_account'],'string','max'=>'100','on'=>'lists'],
            [['trade_no'], 'string', 'max'=>64, 'on'=>'lists'],
            [['paid_at_start','paid_at_end'], 'date','format'=>'yyyy-MM-dd HH:mm:ss','on'=>'lists'],
            ['paid_at_start', 'compare', 'compareAttribute' => 'paid_at_end', 'operator' => '<=' ,'on'=>'lists'],

            [['release_day','release_end_day'], 'date','format'=>'yyyy-MM-dd','on'=>'room-lists'],
            ['release_day', 'compare', 'compareAttribute' => 'release_end_day', 'operator' => '<=' ,'on'=>'room-lists'],

            [['out_room_id','acct_period_start','acct_period_end','cost_type','bill_entry_amount'],'required','message'=>'{attribute}不能为空!','on'=>'create'],
//            ['bill_entry_amount','string','max'=>'15','on'=>'create'],
            [['acct_period_start','acct_period_end'], 'date','format'=>'yyyy-MM-dd','message'=>'{attribute}不正确!','on'=>'create'],
            ['acct_period_start', 'compare', 'compareAttribute' => 'acct_period_end', 'operator' => '<=' ,'on'=>'create'],
            ['bill_entry_amount', 'compare', 'compareValue' => 0.005, 'message'=>'金额数值不能低于0.01','operator' => '>','on'=>'create'],
//            ['bill_entry_amount', 'compare', 'compareValue' => 100000, 'operator' => '<','message'=>'金额最大不超过99999.99','on'=>'create'],
            ['cost_type','number','on'=>['create','life-list']],
//            ['bill_entry_amount','float','message'=>'金额格式出错','on'=>'create'],
//            [['acct_period_start','acct_period_end'],'match','pattern'=>'/^\d{4}-(?:(?:0[13-9]|1[12])-(?:0[1-9]|[12]\d|30)|(?:0[13578]|1[02])-31|02-(?:0[1-9]|1\d|2[0-8]))|(?:(?:\d{2}(?:[13579][26]|[2468][048])|(?:[13579][26]|[2468][048])00)-02-29)$/','message'=>'日期格式不正确','on'=>'create'],
//            ['bill_entry_amount','match','pattern'=>'/^[0-9]+(.[0-9]{1,2})?$/','message'=>'金额格式出错','on'=>'create'],
            [['out_room_id'],'required','message'=>'{attribute}不能为空!','on'=>'room-show'],
            [['bill_id'],'required','message'=>'{attribute}不能为空!','on'=>'show'],
            ['bill_id','number','on'=>'show'],
            [['community_id'],'required','message'=>'{attribute}不能为空!', 'on' => ['room-lists', 'get-excel','confirm-bill',"look-bill","park-list",'life-list',"del-bill-list","del-bill-check"]],
            //[['bill_ids'],'required','message'=>'{attribute}不能为空!', 'on' => ["del-bill-check"]],
            [['acct_period'], 'date','format'=>'yyyy-MM-dd','message'=>'{attribute}不正确!','on'=>['del-bill-list',"del-bill-check"]],

            [['community_id'],'required','message'=>'{attribute}不能为空!','on'=>'room-export'],
            [['notify_time','trade_no','trade_status','body','det_list'],'required','message'=>'{attribute}不能为空!','on'=>'notify'],
            [['paid_at_start','paid_at_end'], 'date','format'=>'yyyy-MM-dd','on'=>['park-list',"life-list",'room-lists']],
            ['paid_at_start', 'compare', 'compareAttribute' => 'paid_at_end', 'operator' => '<=' ,'on'=>['park-list',"life-list",'room-lists',]],

            [['community_id','group','building','unit','room'],'required','message'=>'{attribute}不能为空!','on'=>'cost-list'],
            [['out_room_id','pay_channel'],'required','message'=>'{attribute}不能为空!','on'=>'pay-cost'],
            //线下收款的参数验证
            [['bill_id','pay_amount','pay_type'],'required','message'=>'{attribute}不能为空!','on'=>'bill-collect'],
            ['pay_amount', 'compare', 'compareValue' => 0.005, 'message'=>'金额数值不能低于0.01','operator' => '>','on'=>'bill-collect'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'out_room_id' => '房屋识别号',
            'acct_period_start' => '账单开始日期',
            'acct_period_end' => '账单结束日期',
            'cost_type' => '缴费项目',
            'bill_entry_amount' =>'缴费金额',
            "community_id" => "小区id",
            "paid_at_start" =>"支付开始时间",
            "paid_at_end" =>"支付结束时间",
            "bill_id" => "账单id",
            "bill_ids" => "账单id列表",
            "pay_amount" => "收款金额",
            "pay_type" => "缴费方式",
            'buyer_logon_id' =>"支付账号",
            'group'          => '苑/期/区',
            'building'       => '幢',
            'room'           => '房号',
            'unit'           => '单元号',
        ];
    }

    public function validateAfterNow($attribute, $params)
    {
        if(strtotime($attribute) ) {
            $this->addError($attribute, '请选择正确的日期.');
        }
    }


}

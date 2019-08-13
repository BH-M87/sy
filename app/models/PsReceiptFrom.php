<?php
namespace app\models;
use common\core\Regular;
use Yii;
use yii\base\Model;

class PsReceiptFrom extends Model
{
    public $community_id;

    public $group;
    public $building;
    public $unit;
    public $room;
    public $pay_channel;
    public $new_pwd;
    public $confirm_pwd;
    public $old_pwd;
    public $code;
    public $password;
    public $task_id;
    public $cost_type;
    public $paid_entry_amount;
    public $acct_period_start;
    public $acct_period_end;
    //公摊项目
    public $name;
    public $shared_type;
    public $panel_type;
    public $panel_status;
    public $start_num;
    //账期抄表
    public $shared_id;
    public $period_id;
    public $latest_num;
    public $current_num;
    public $amount;


    public function rules()
    {
        return [
            [['community_id'], 'required','message' => '小区不能为空!', 'on' => ['get-group','get-building','get-room','get-unit','import-post']],
            [['group'], 'required','message' => '期苑区不能为空!', 'on' => ['get-building','get-room','get-unit','import-data']],
            [['building'], 'required','message' => '幢不能为空!', 'on' => ['get-room','get-unit','import-data']],
            [['unit'], 'required','message' => '单元不能为空!', 'on' => ['get-room','import-data']],
            [['room'], 'required','message' => '室号不能为空!', 'on' => ['import-data']],
            [['cost_type','acct_period_start','acct_period_end','paid_entry_amount'], 'required','message' => '{attribute}不能为空!', 'on' => ['import-data']],
            [['acct_period_start','acct_period_end'], 'date','format'=>'yyyy-MM-dd','message'=>'{attribute}不正确!','on'=>'import-data'],
            ['acct_period_start', 'compare', 'compareAttribute' => 'acct_period_end', 'operator' => '<=' ,'on'=>'import-data'],
            ['paid_entry_amount', 'compare', 'compareValue' => 0, 'operator' => '>','message'=>'金额最小不能低于0.01','on'=>'import-data'],
//            ['paid_entry_amount', 'compare', 'compareValue' => 100000, 'operator' => '<','message'=>'金额最大不超过99999.99','on'=>'import-data'],
            [['new_pwd','confirm_pwd'], 'required','message' => '{attribute}不能为空!', 'on' => ['add-pwd','edit-pwd','reset-pwd']],
            [['old_pwd'], 'required','message' => '{attribute}不能为空!', 'on' => ['edit-pwd']],
            [['code'], 'required','message' => '验证码不能为空!', 'on' => ['reset-pwd']],
            ['new_pwd', 'match', 'pattern' => Regular::letterOrNumber(6, 10),
                'message' => '{attribute}为6-10位英文或数字', 'on' =>['add-pwd','edit-pwd','reset-pwd']],
            ['confirm_pwd', 'compare', 'compareAttribute' => 'new_pwd', 'operator' => '===','message' => '两次密码不一样!','on'=>['add-pwd','edit-pwd','reset-pwd']],
            [['password'], 'required','message' => '收款密码不能为空!', 'on' => ['verify-pwd']],
            [['task_id'], 'required','message' => '上传任务不能为空!', 'on' => [ 'import-post']],
            [['pay_channel'], 'required','message' => '请填写一个支付渠道!', 'on' => [ 'import-post']],

            //公摊项目导入
            [['name'], 'required','message' => '公摊项目名称不能为空!', 'on' => ['shared-import']],
            [['shared_type'], 'required','message' => '公摊类型不能为空!', 'on' => ['shared-import','import-record']],
            [['panel_type'], 'required','message' => '表盘类型不能为空!', 'on' => ['shared-import']],
            [['panel_status'], 'required','message' => '表盘状态不能为空!', 'on' => ['shared-import']],
            [['start_num'], 'required','message' => '起始度数不能为空!', 'on' => ['shared-import']],

            //公摊项目抄表记录导入
            ['amount', 'compare', 'compareValue' => 0, 'operator' => '>','message'=>'金额最小不能低于0.01','on'=>'import-record'],
            [['period_id'], 'required','message' => '公摊账期不能为空!', 'on' => ['import-record']],
            [['shared_id'], 'required','message' => '公摊项目不能为空!', 'on' => ['import-record']],
            [['latest_num'], 'required','message' => '上次读数不能为空!', 'on' => ['import-record']],
            [['current_num'], 'required','message' => '本次读数不能为空!', 'on' => ['import-record']],

        ];
    }

    public function attributeLabels()
    {
        return [
            'shared_id'     => '公摊项目',
            'period_id'     => '账期',
            'latest_num'     => '上次读数',
            'current_num'     => '本次读数',
            'amount'     => '对应金额',

            'community_id'     => '小区id',
            'new_pwd'           => '新密码',
            "confirm_pwd"     => "确认密码",
            "old_pwd"         => "旧密码",
            "cost_type"      => "缴费项目",
            "paid_entry_amount" => "实收金额",
            "acct_period_start" => "账单开始时间",
            "acct_period_end" => "账单结束时间",
        ];
    }

}

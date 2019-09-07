<?php

namespace app\models;

use common\core\Regular;
use Yii;

/**
 * This is the model class for table "st_communist".
 *
 * @property int $id 主键
 * @property string $name 姓名
 * @property string $mobile 手机号
 * @property string $image 头像
 * @property int $sex 性别 1男 2女
 * @property int $birth_time 出生日期
 * @property int $join_party_time 入党日期
 * @property int $formal_time 转正日期
 * @property string $branch 所在支部
 * @property string $job 党内职务
 * @property int $type 党员类型：1离退休党员、2流动党员、3困难党员、4下岗失业党员、5在职党员
 * @property int $station_id 先锋岗位id
 * @property string $pioneer_value 获得的先锋值
 * @property int $operator_id 创建人id
 * @property string $operator_name 创建人
 * @property int $create_at 创建时间
 * @property int $is_authentication 是否支付宝认证 1是 2否
 * @property int $user_id 支付宝ps_app_user用户id
 */
class StCommunist extends BaseModel
{

    public $birth_time_date;
    public $join_party_time_date;
    public $formal_time_date;

    public static $type_desc = [
        1 => '在职党员',
        2 => '在册党员',
        3 => '发展党员',
        4 => '其他',
    ] ;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_communist';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sex', 'type', 'station_id', 'pioneer_value', 'operator_id',
                'create_at', 'is_authentication', 'user_id', 'is_del', 'organization_type', 'organization_id'], 'integer'],
            [['branch', 'job'], 'string', 'max' => 30, 'on' => ['add', 'edit'], 'message' => '{attribute}最多30个字！'],
            [['mobile'], 'string', 'max' => 13],
            [['image'], 'string', 'max' => 200],
            [['operator_name'], 'string', 'max' => 20],
            [['name'], 'string', 'max' => 10, 'message' => '{attribute}最多10个字！','on' => ['add', 'edit']],
            [['organization_type','organization_id'],'required', 'message' => '{attribute}不能为空', 'on' => ['add']],
            [['name', 'mobile', 'sex', 'birth_time_date', 'join_party_time_date', 'branch', 'type'],
                'required', 'message' => '{attribute}不能为空', 'on' => ['add', 'edit']],
            ['mobile', 'match', 'pattern' => Regular::phone(), 'message' => '{attribute}格式不正确', 'on' => ['add', 'edit']],
            [['birth_time_date','join_party_time_date', 'formal_time_date'], 'date','format'=>'yyyy-MM-dd', 'message'=>'{attribute}格式不正确!', 'on' => ['add', 'edit']],
            [['sex'],'in','range'=>[1,2], 'message' => '{attribute}只能是1或2','on' => ['add', 'edit']],
            [['join_party_time','formal_time'], 'validateTime', 'on' => ['add', 'edit']],
            [['name','birth_time', 'join_party_time', 'formal_time','branch', 'job'], 'safe'],
            [['id'], 'required', 'message' => '{attribute}不能为空', 'on' => ['edit','delete','view']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '党员id',
            'name' => '姓名',
            'mobile' => '手机号码',
            'image' => '头像',
            'sex' => '性别',
            'birth_time' => '出生日期',
            'join_party_time' => '入党日期',
            'formal_time' => '转正日期',
            'birth_time_date' => '出生日期',
            'join_party_time_date' => '入党日期',
            'formal_time_date' => '转正日期',
            'branch' => '所在支部',
            'job' => '党内职务',
            'type' => '党员类型',
            'station_id' => '党员先锋岗',
            'pioneer_value' => 'Pioneer Value',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'create_at' => 'Create At',
            'is_authentication' => 'Is Authentication',
            'user_id' => 'User ID',
        ];
    }

    public function validateTime($attribute, $params)
    {
        $birthdayTime = $this->birth_time;
        $joinPartyTime = $this->join_party_time;
        $compareBirthdayTime = $birthdayTime + 18*365*86400;
        $compareJoinPartyTime = $joinPartyTime + 365*86400;

        if ($birthdayTime && $joinPartyTime) {
            if ($this->join_party_time < $compareBirthdayTime) {
                $this->addError($attribute, "未满18周岁，不符合入党条件！请检查入党日期和出生日期");
            }
        }


        if ($this->formal_time && $this->formal_time < $compareJoinPartyTime) {
            $this->addError($attribute, "预备期未满1年，不符合党员条件！请检查入党日期和转正日期");
        }
    }
}

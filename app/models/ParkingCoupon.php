<?php

namespace app\models;

use common\MyException;
use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "parking_coupon".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property string $title 活动名称
 * @property int $type 优惠券类型:1.小时券,2.金额券
 * @property string $money 金额/时间(单位分钟)
 * @property int $amount 总数量
 * @property int $amount_left 剩余数量
 * @property int $amount_use 核销数量
 * @property int $expired_day 有效期
 * @property int $start_time 券使用有效期开始时间
 * @property int $end_time 券使用有效期结束时间
 * @property int $date_type 券使用有效期类型:1.相对时间（领取后N天有效）,2.绝对时间（领取后XXX-XXX时间段有效）
 * @property int $user_limit 每人领取券的上限:0无限制,其他为限制的张数
 * @property int $activity_start 活动开始时间
 * @property int $activity_end 活动结束时间
 * @property string $code_url 二维码链接地址
 * @property string $note 使用说明
 * @property int $deleted 是否删除:1.未删除,2已删除
 * @property int $created_at 创建时间
 * @property int $updated_at 创建时间
 * @property int $version 乐观锁版本号
 */
class ParkingCoupon extends BaseModel
{
    public $activity_start_date;
    public $activity_end_date;
    public $start_date;
    public $end_date;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_coupon';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['id', 'required', 'message' => '{attribute}不能为空', 'on' => ['update', 'download', 'view', 'delete']],
            [['community_id', 'type', 'amount', 'amount_left', 'amount_use', 'expired_day', 'start_time', 'end_time', 'date_type', 'user_limit', 'activity_start', 'activity_end', 'deleted', 'created_at', 'updated_at', 'version'], 'integer'],
            [
                [
                    'community_id',
                    'title',
                    'type',
                    'activity_start_date',
                    'activity_end_date',
                    'user_limit',
                    'money',
                    //'date_type',
                    'note',
                    'amount',
                ],
                'required',
                'message' => '{attribute}不能为空',
                'on' => ['create', 'update']],
            [['activity_start_date', 'activity_end_date'], 'date', 'format' => 'yyyy-mm-dd', 'message' => '{attribute}格式错误'],
            ['activity_start_date', 'compare', 'compareAttribute' => 'activity_end_date', 'operator' => '<=', 'message' => '开始时间必须小于结束时间'],
            //[['start_date', 'end_date'], 'date', 'format' => 'yyyy-mm-dd', 'message' => '{attribute}格式错误'],
            //['start_date', 'compare', 'compareAttribute' => 'end_date', 'operator' => '<=', 'message' => '开始时间必须小于结束时间'],
            [['type', 'date_type'], 'in', 'range' => [1, 2], 'message' => '{attribute}只能是1或者2!'],
            [['money'], 'number'],
            [['note'], 'string'],
            [['title'], 'string', 'max' => 200],
            [['code_url'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '优惠券编号',
            'community_id' => '小区编号',
            'title' => '活动名称',
            'status' => 'Status',
            'type' => '优惠券类型',
            'money' => '优惠券面值',
            'amount' => '总数量',
            'amount_left' => '剩余数量',
            'amount_use' => '使用数量',
            'expired_day' => '有效期',
            'start_date' => '券使用有效期开始时间',
            'end_date' => '券使用有效期结束时间',
            'date_type' => '券使用有效期类型',
            'user_limit' => '每人领取券的上限',
            'activity_start_date' => '活动开始时间',
            'activity_end_date' => '活动结束时间',
            'code_url' => '二维码链接地址',
            'note' => '使用说明',
            'deleted' => 'Deleted',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'version' => 'Version',
        ];
    }

    /**
     * @api 统一验证
     * @author wyf
     * @date 2019/7/2
     * @param $data
     * @param $scenario
     * @return mixed
     * @throws MyException
     */
    public function validParamArr($data, $scenario)
    {
        if (!empty($data)) {
            $this->setScenario($scenario);
            $datas["data"] = $data;
            $this->load($datas, "data");
            if ($this->validate()) {
                return $data;
            } else {
                $errorMsg = array_values($this->errors);
                throw new MyException($errorMsg[0][0]);
            }
        } else {
            throw new MyException('未接受到有效数据');
        }
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                //'value' => new Expression('NOW()'),
            ],
        ];
    }
}

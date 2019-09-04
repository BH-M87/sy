<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "st_party_task".
 *
 * @property string $id 任务id
 * @property string $station_id 先锋岗位id
 * @property string $task_name 任务标题
 * @property int $pioneer_value 先锋值
 * @property int $expire_time_type 领取截止时间类型 1 长期有效  2指定日期
 * @property int $expire_time 领取截止时间
 * @property string $party_address 任务地址
 * @property string $contact_name 联系人名称
 * @property string $contact_phone 联系人手机号码
 * @property int $is_location 是否需要定位 1是 2否
 * @property string $task_details 任务详情
 * @property string $create_at 创建时间
 * @property int $operator_id 创建人id
 * @property string $operator_name 操作人名
 */
class StPartyTask extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_party_task';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['station_id', 'pioneer_value', 'expire_time_type', 'expire_time', 'is_location', 'create_at', 'operator_id'], 'integer'],
            [['task_details', 'operator_id','station_id','task_name','pioneer_value','expire_time','party_address','contact_name','contact_phone','is_location'], 'required','message' => '{attribute}必填','on' => ['add','edit']],
            [['task_details'], 'string','max' => 1000],
            [['task_name'], 'string', 'max' => 30],
            [['is_location','expire_time_type'], 'in', 'range' => [1, 2],'message' => '{attribute}非法'],
            [['party_address'], 'string', 'max' => 50],
            [['contact_name'], 'string', 'max' => 10],
            [['contact_phone'], 'string', 'max' => 12],
            [['operator_name'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'station_id' => '岗位ID',
            'task_name' => '任务名称',
            'pioneer_value' => '先锋值',
            'expire_time_type' => '领取截止时间类型',
            'expire_time' => '截止时间',
            'party_address' => '任务地址',
            'contact_name' => '联系人',
            'contact_phone' => '联系电话',
            'is_location' => '是否需要定位',
            'task_details' => '任务详情',
            'create_at' => '创建时间',
            'operator_id' => '操作人ID',
            'operator_name' => '操作人名称',
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['create_at'],
                ],
                'value' => time()
            ]
        ];
    }
}

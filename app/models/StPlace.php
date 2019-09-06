<?php

namespace app\models;

use common\core\Regular;
use Yii;

/**
 * This is the model class for table "st_place".
 *
 * @property int $id
 * @property int $organization_type 所属组织类型(1街道本级 2社区)
 * @property int $organization_id 所属组织Id
 * @property int $company_id 单位id
 * @property string $name 场地名称
 * @property string $area 场地面积
 * @property int $open_start_weekday 开放开始时间周几
 * @property int $open_end_weekday 开放截止时间周几
 * @property string $open_start_time 开放开始时间点
 * @property string $open_end_time 开放截止时间点
 * @property string $contact_name 联系人
 * @property string $contact_mobile 联系人电话
 * @property string $address 详细地址
 * @property string $note 场地说明
 * @property int $people_num 可容纳人数
 * @property int $operator_id 操作人id
 * @property string $operator_name 操作人名
 * @property int $create_at 添加时间
 */
class StPlace extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_place';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['organization_type', 'organization_id', 'company_id', 'open_start_weekday', 'open_end_weekday', 'people_num', 'operator_id', 'create_at'], 'integer'],
            [['area'], 'number'],
            [['name'], 'string', 'max' => 30, 'message' => '{attribute}最多30个字！', 'on' => ['add', 'edit']],
            [['contact_name'], 'string', 'max' => 10, 'message' => '{attribute}最多10个字！', 'on' => ['add', 'edit']],
            [['open_start_time', 'open_end_time','operator_name'], 'string', 'max' => 20],
            [['contact_mobile'], 'string', 'max' => 12],
            [['address'], 'string', 'max' => 255],
            [['note'], 'string', 'max' => 1000, 'message' => '{attribute}最多1000个字！', 'on' => ['add', 'edit']],
            [['company_id', 'name', 'area', 'people_num', 'open_start_weekday', 'open_end_weekday',
                'open_start_time', 'open_end_time', 'contact_name', 'contact_mobile', 'address', 'note'],
                'required', 'message' => '{attribute}不能为空', 'on' => ['add', 'edit']],
            ['mobile', 'match', 'pattern' => Regular::phone(), 'message' => '{attribute}格式不正确', 'on' => ['add', 'edit']],
            [['id'], 'required', 'message' => '{attribute}不能为空', 'on' => ['edit','delete','view']],


        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'organization_type' => 'Organization Type',
            'organization_id' => 'Organization ID',
            'company_id' => '单位名称',
            'name' => '场地名称',
            'area' => '场地面积',
            'open_start_weekday' => '开放日期',
            'open_end_weekday' => '开放日期',
            'open_start_time' => '开放时间',
            'open_end_time' => '开放时间',
            'contact_name' => '联系人',
            'contact_mobile' => '手机号码',
            'address' => '场地地址',
            'note' => '场地说明',
            'people_num' => '可容纳人数',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'create_at' => 'Create At',
        ];
    }
}

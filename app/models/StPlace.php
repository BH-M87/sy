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
    public $area_min;
    public $area_max;

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
            [['organization_type','company_id', 'open_start_weekday', 'open_end_weekday', 'people_num', 'operator_id', 'create_at'], 'integer'],
            [['name'], 'string', 'max' => 30, 'tooLong' => '{attribute}不能超过30个字符!', 'message' => '{attribute}最多30个字！', 'on' => ['add', 'edit']],
            [['contact_name'], 'string', 'max' => 10, 'tooLong' => '{attribute}不能超过10个字符!',  'message' => '{attribute}最多10个字！', 'on' => ['add', 'edit']],
            [['open_start_time', 'open_end_time','operator_name'], 'string', 'max' => 20],
            [['contact_mobile'], 'string', 'max' => 12],
            [['address'], 'string', 'max' => 255],
            [['note'], 'string', 'max' => 1000, 'tooLong' => '{attribute}不能超过1000个字符!',  'message' => '{attribute}最多1000个字！', 'on' => ['add', 'edit']],
            [['company_id', 'name', 'area', 'people_num', 'open_start_weekday', 'open_end_weekday',
                'open_start_time', 'open_end_time', 'contact_name', 'contact_mobile', 'address', 'note'],
                'required', 'message' => '{attribute}不能为空', 'on' => ['add', 'edit']],
            ['contact_mobile', 'match', 'pattern' => Regular::phone(), 'message' => '{attribute}格式不正确', 'on' => ['add', 'edit']],
            [['id'], 'required', 'message' => '{attribute}不能为空', 'on' => ['edit','delete','view']],
            [['area'], 'match', 'pattern' => Regular::float(1),
                'message' => '{attribute}必须为正数，最多保留一位小数', 'on' =>['add', 'edit']],
            [['people_num'], 'match', 'pattern' => Regular::number(),
                'message' => '{attribute}必须为1-5000内的整数', 'on' =>['add', 'edit']],
            ['people_num', 'compare', 'compareValue' => 1, 'operator' => '>=', 'message' => '{attribute}必须为1-5000内的整数', 'on' =>['add', 'edit']],
            ['people_num', 'compare', 'compareValue' => 5000, 'operator' => '<=', 'message' => '{attribute}必须为1-5000内的整数', 'on' =>['add', 'edit']],
            [['open_start_weekday', 'open_end_weekday'], 'in', 'range' => [1,2,3,4,5,6,7],
                'message' => '{attribute}必须为1-7内的整数', 'on' =>['add', 'edit']],
            ['open_end_weekday', 'compare', 'compareAttribute' => 'open_start_weekday', 'operator' => '>=' ,'on'=>['add', 'edit'], 'message' => '{attribute}必须大于等于开放开始日期'],
            [['open_start_time','open_end_time'], 'date','format'=>'HH:mm','on' =>['add', 'edit'], 'message' => '{attribute}格式错误',],
            [['open_end_time'], 'validateTime', 'on' => ['add', 'edit']],
            [['area_min', 'area_max'], 'match', 'pattern' => Regular::float(1),
                'message' => '{attribute}必须为正数，最多保留一位小数', 'on' =>['list']],
            [['name','contact_name', 'note', 'area', 'organization_id'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '场地id',
            'organization_type' => 'Organization Type',
            'organization_id' => 'Organization ID',
            'company_id' => '单位名称',
            'name' => '场地名称',
            'area' => '场地面积',
            'open_start_weekday' => '开放开始日期',
            'open_end_weekday' => '开放截止日期',
            'open_start_time' => '开放开始时间',
            'open_end_time' => '开放截止时间',
            'contact_name' => '联系人',
            'contact_mobile' => '手机号码',
            'address' => '场地地址',
            'note' => '场地说明',
            'people_num' => '可容纳人数',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'create_at' => 'Create At',
            'area_min' => '场地最小面积',
            'area_max' => '场地最大面积'
        ];
    }

    public function validateTime($attribute, $params)
    {
        $startTime = $this->open_start_time;
        $endTime = $this->open_end_time;
        $day = date("Y-m-d",time());
        $strStartTime = $day." ".$startTime.":00";
        $strEndTime = $day." ".$endTime.":00";
        if (strtotime($strEndTime) <= strtotime($strStartTime)) {
            $this->addError($attribute, "结束时间必须大于开始时间");
        }
    }
}

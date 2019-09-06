<?php

namespace app\models;

use common\core\Regular;
use Yii;

/**
 * This is the model class for table "st_company".
 *
 * @property int $id
 * @property int $organization_type 所属组织类型(1街道本级 2社区)
 * @property int $organization_id 所属组织Id
 * @property string $name 单位名称
 * @property string $address 单位地址
 * @property int $type 单位性质  1国企 2私企 3事业 4其他
 * @property string $contact_name 单位负责人姓名
 * @property string $contact_position 联系人职务
 * @property string $contact_mobile 联系人手机号
 * @property string $lon 经度值
 * @property string $lat 纬度值
 * @property int $operator_id 操作人id
 * @property string $operator_name 操作人名
 * @property int $create_at 添加时间
 */
class StCompany extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_company';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['organization_type', 'organization_id', 'type', 'operator_id', 'create_at'], 'integer'],
            [['lon', 'lat'], 'number'],
            [['contact_position'], 'string', 'max' => 20, 'message' => '{attribute}最多20个字！', 'on' => ['add', 'edit']],
            [['name'], 'string', 'max' => 30, 'message' => '{attribute}最多30个字！', 'on' => ['add', 'edit']],
            [['contact_name'], 'string', 'max' => 10, 'message' => '{attribute}最多10个字！', 'on' => ['add', 'edit']],
            [['address'], 'string', 'max' => 255],
            [['operator_name'], 'string', 'max' => 20],
            [['contact_mobile'], 'string', 'max' => 12],
            [['name', 'type', 'contact_name', 'contact_mobile', 'address'],
                'required', 'message' => '{attribute}不能为空', 'on' => ['add', 'edit']],
            ['contact_mobile', 'match', 'pattern' => Regular::phone(), 'message' => '{attribute}格式不正确', 'on' => ['add', 'edit']],
            [['id'], 'required', 'message' => '{attribute}不能为空', 'on' => ['edit','delete','view']],
            [['type'],'in','range'=>[1,2,3,4], 'message' => '{attribute}有误','on' => ['add', 'edit', 'list']],
            [['name','contact_name', 'contact_position'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '单位ID',
            'organization_type' => 'Organization Type',
            'organization_id' => 'Organization ID',
            'name' => '单位名称',
            'address' => '详细地址',
            'type' => '单位性质',
            'contact_name' => '负责人',
            'contact_position' => '职务',
            'contact_mobile' => '手机号码',
            'lon' => 'Lon',
            'lat' => 'Lat',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'create_at' => 'Create At',
        ];
    }
}

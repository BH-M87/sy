<?php

namespace app\models;

use Yii;
use common\core\Regular;

/**
 * This is the model class for table "st_organization".
 *
 * @property int $id
 * @property int $organization_type 组织类型
 * @property int $organization_id 所属组织id
 * @property string $name
 * @property int $org_type 组织类型
 * @property int $org_build_time 组织成立时间
 * @property int $member_num 成员数量
 * @property string $address 所在地址
 * @property string $lat 所在地纬度值
 * @property string $lon 所在地经度值
 * @property string $link_man 联系人
 * @property string $link_mobile 联系人电话
 * @property string $job
 * @property int $create_at 添加时间
 */
class StOrganization extends BaseModel
{
    public $buildTime;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_organization';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['organization_type', 'organization_id', 'org_type', 'org_build_time', 'member_num', 'create_at','operator_id'], 'integer'],
            [['name', 'address', 'job'], 'string', 'max' => 255],
            [['contact_name'], 'string', 'max' => 50],
            [['contact_mobile'], 'string', 'max' => 15],
            [['operator_name'], 'string', 'max' => 20],
            [['name', 'org_type', 'org_build_time', 'member_num','contact_name','contact_mobile','address'],
                'required', 'message' => '{attribute}不能为空', 'on' => ['add', 'edit']],
            [['name'], 'string', 'max' => 30, 'tooLong' => '{attribute}不能超过30个字符!', 'on' => ['add', 'edit']],
            [['contact_name'], 'string', 'max' => 10, 'tooLong' => '{attribute}不能超过10个字符!', 'on' => ['add', 'edit']],
            [['job'], 'string', 'max' => 20, 'tooLong' => '{attribute}不能超过20个字符!', 'on' => ['add', 'edit']],
            ['contact_mobile', 'match', 'pattern' => Regular::phone(), 'message' => '{attribute}格式不正确', 'on' => ['add', 'edit']],
            [['org_type'],'in','range'=>[1,2], 'message' => '{attribute}有误','on' => ['add', 'edit', 'list']],
            [['buildTime'], 'date','format'=>'yyyy-MM-dd', 'message'=>'{attribute}格式不正确!', 'on' => ['add', 'edit']],
            [['id'], 'required', 'message' => '{attribute}不能为空', 'on' => ['edit','delete','view']],
            [['lat','lon'], 'safe'],
            ['member_num', 'number', 'min'=>1, 'max'=>9999, 'integerOnly'=>true,'tooSmall'=>'{attribute}只允许1-9999之间的正整数!', 'tooBig'=>'{attribute}只允许1-9999之间的正整数!', 'on' => ['add', 'edit']],
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
            'name' => '组织名称',
            'org_type' => '组织类型',
            'org_build_time' => '成立时间',
            'buildTime' => '成立时间',
            'member_num' => '成员数量',
            'address' => ' 所在地址',
            'lat' => 'Lat',
            'lon' => 'Lon',
            'contact_name' => '联系人',
            'contact_mobile' => '联系电话',
            'job' => '职务',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'create_at' => 'Create At',
        ];
    }
}

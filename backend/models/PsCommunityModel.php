<?php
namespace backend\models;
use common\core\Regular;
/**
 * This is the model class for table "ps_community".
 *
 * @property integer $id
 * @property string $community_no
 * @property string $province_code
 * @property string $city_id
 * @property string $district_code
 * @property integer $pro_company_id
 * @property string $name
 * @property string $group
 * @property string $location_lon
 * @property string $location_lat
 * @property string $address
 * @property string $phone
 * @property string $logo_url
 * @property integer $status
 * @property integer $create_at
 */
class PsCommunityModel extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_community';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'required','message' => '{attribute}不能为空!', 'on' => 'edit'],
            [['province_code', 'district_code', 'city_id', 'name', 'address', 'phone', 'pro_company_id'], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['create','edit']],
            ['name', 'string', 'max' => '20', 'message' => '{attribute}不能超过20个字符', 'on' => ['create','edit']],
            ['address', 'string', 'max' => '50', 'on' => ['create','edit']],
            ['logo_url', 'string', 'max' => '255', 'on' => ['create','edit']],
            ['phone', 'match', 'pattern' => Regular::telOrPhone(),
                'message' => '{attribute}格式出错，必须是区号-电话格式或者手机号码格式', 'on' => ['create','edit']],

            ['pro_company_id', 'integer', 'on' => ['create', 'edit']],
            ['status', 'number', 'on' => ['create', 'edit']],
            ['house_type', 'safe'],
            [['create_at'], 'default', 'value' =>time()],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '小区id',
            'community_no' => '小区编号',
            'province_code' => '所在省',
            'city_id' => '所在市',
            'district_code' => '所在区',
            'pro_company_id' => '物业公司ID',
            'name' => '小区名称',
            'group' => '苑/期/区',
            'locations' => '地图坐标',
            'address' => '小区地址',
            'phone' => '物业电话',
            'logo_url' => '小区logo',
            'status' => 'Status',
            'create_at' => 'Create At',
        ];
    }

    public function getProperty()
    {
        return $this->hasOne(PsPropertyCompany::className(), ['id'=>'pro_company_id'])
            ->select('id, property_name, alipay_account');
    }


    /**
     * 根据ID获取名称
     * @param array 一维数组 | number $lifeId
     * @return array
     */
    public static function getCommunityName($ids)
    {
        return self::find()->select('id, name')->where(['id' => $ids])->asArray()->all();
    }
}

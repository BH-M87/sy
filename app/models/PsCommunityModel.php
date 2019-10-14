<?php
namespace app\models;
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

    public static $house_type_desc = [
        '1' => '普通小区',
        '2' => '安置小区',
        '3' => '老旧小区',
    ];

    public static $status_desc = [
        '1' => '启用',
        '2' => '禁用',
    ];

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
            [['district_name','province_code', 'district_code', 'city_id', 'name','link_name', 'address', 'phone', 'pro_company_id','street_name',
                'longitude','latitude','map_gid','house_type','status'], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['create','edit']],
            [['name','link_name'], 'string', 'max' => '20', 'message' => '{attribute}不能超过20个字符', 'on' => ['create','edit']],
            ['address', 'string', 'max' => '50', 'on' => ['create','edit']],
            ['logo_url', 'string', 'max' => '255', 'on' => ['create','edit']],
            ['phone', 'match', 'pattern' => Regular::telOrPhone(),
                'message' => '{attribute}格式出错，必须是区号-电话格式或者手机号码格式', 'on' => ['create','edit']],
            ['pro_company_id', 'integer', 'on' => ['create', 'edit']],
            [['house_type'], 'in', 'range' => [1, 2, 3],'message' => '{attribute}非法'],
            [['status'], 'in', 'range' => [1, 2],'message' => '{attribute}非法'],
            [['create_at'], 'default', 'value' =>time()],
            [['build_time','delivery_time','acceptance_time','right_start','right_end','register_time'], 'integer',  'on' => ['create','edit']],
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
            'province' => '所在省',
            'city_id' => '所在市',
            'district_code' => '所在区',
            'district_name' => '社区名称',
            'pro_company_id' => '物业公司ID',
            'name' => '小区名称',
            'link_name' => '联系人名称',
            'street_name' => '街道名称',
            'locations' => '地图坐标',
            'longitude' => '经度',
            'latitude' => '维度',
            'house_type' => '小区类型',
            'address' => '小区地址',
            'map_gid' => '围栏Gid',
            'phone' => '联系电话',
            'logo_url' => '小区logo',
            'status' => '状态',
            'build_time' => '建成时间',
            'delivery_time' => '交付时间',
            'acceptance_time' => '验收时间',
            'right_start' => '产权开始时间',
            'right_end' => '产权结束时间',
            'register_time' => '登记时间',
            'create_at' => 'Create At',
        ];
    }

    public static function getList($param,$page = true)
    {
        $model = self::find()->andFilterWhere(['name' => $param['name'] ?? null])
            ->andFilterWhere(['street_name' => $param['street_name'] ?? null])
            ->andFilterWhere(['house_type' => $param['house_type'] ?? null])
            ->andFilterWhere(['pro_company_id' => $param['pro_company_id'] ?? null])
            ->andFilterWhere(['district_name'=> $param['district_name'] ?? null]);
        $model->orderBy([ 'id' => SORT_DESC]);
        if ($page) {
            $page = !empty($param['page']) ? $param['page'] : 1;
            $row = !empty($param['rows']) ? $param['rows'] : 10;
            $page = ($page-1)*$row;
            $count = $model->count();
            $data['totals'] = $count;
            $model->offset($page)->limit($row);
        }
        $data['list'] = $model->asArray()->all();
        if (!empty($data['list'])) {
            self::afterList($data['list']);
        }
        return $data;
    }

    public static function afterList(&$data)
    {
        foreach ($data as &$v) {
            $task = StPartyTask::find()->where(['id' => $v['task_id']])->asArray()->one();
            $city_name = PsAreaAli::find()->where(['areaCode' => $v['city_id']])->one()['areaName'];
            $district_name = PsAreaAli::find()->where(['areaCode' => $v['district_code']])->one()['areaName'];
            $v['street_info'] = $task['province'].$city_name.$district_name.$v['street_name'];
            $v['link_info'] = $v['link_name'].$v['phone'];
            $v['house_type'] = self::$house_type_desc[$v['house_type']];
            $v['company_name'] = PsPropertyCompany::find()->where(['id' => $v['pro_company_id']])->one()['property_name'];
            $v['create_at'] = date('Y-m-d H:i:s',$v['create_at']);
        }
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

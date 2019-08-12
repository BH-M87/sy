<?php
/**
 * Created by PhpStorm.
 * User: shendemin
 * Date: 2017/12/27
 * Time: 15:06
 */

namespace app\models;
use Yii;

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
 * @property string $locations
 * @property string $address
 * @property string $phone
 * @property string $logo_url
 * @property integer $is_init_service
 * @property integer $is_apply_online
 * @property string $pinyin
 * @property string $ali_next_action
 * @property string $ali_status
 * @property string $bill_pay_auth_url
 * @property integer $has_ali_code
 * @property string $qr_code_type
 * @property string $qr_code_image
 * @property integer $qr_code_expires
 * @property integer $comm_type
 * @property string $code_image
 * @property string $house_id
 * @property string $area_sign
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
            [['name','province_code','city_id','district_code','phone','comm_type','status','create_at'],'required','on' => ['add']],
            [['pro_company_id', 'is_init_service', 'is_apply_online', 'has_ali_code', 'qr_code_expires', 'comm_type', 'status', 'create_at'], 'integer'],
            [['name' , 'phone', 'create_at'], 'required'],
            [['community_no', 'province_code', 'city_id', 'district_code'], 'string', 'max' => 64],
            [['name', 'ali_next_action', 'ali_status'], 'string', 'max' => 100],
            [['group', 'house_id'], 'string', 'max' => 50],
            [['locations', 'address'], 'string', 'max' => 150],
            [['phone'], 'string', 'max' => 15],
            [['logo_url', 'code_image'], 'string', 'max' => 255],
            [['pinyin'], 'string', 'max' => 5],
            [['bill_pay_auth_url'], 'string', 'max' => 200],
            [['qr_code_type'], 'string', 'max' => 20],
            [['qr_code_image'], 'string', 'max' => 1000],
            [['area_sign'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_no' => '小区编号',
            'province_code' => '所子啊省编号',
            'city_id' => '所在市编号',
            'district_code' => '所在区编号',
            'pro_company_id' => '物业公司id',
            'name' => '小区名称',
            'group' => '苑/期/区',
            'locations' => '地图坐标',
            'address' => '小区地址',
            'phone' => '物业电话',
            'logo_url' => 'logo图片地址',
            'is_init_service' => '是否已初始化基础服务',
            'is_apply_online' => '是否已申请支付宝上线',
            'pinyin' => '拼音',
            'ali_next_action' => '支付宝next_action',
            'ali_status' => '支付宝小区状态',
            'bill_pay_auth_url' => '第三方授权url',
            'has_ali_code' => '支付二维码',
            'qr_code_type' => '支付二维码类型',
            'qr_code_image' => '支付二维码图片',
            'qr_code_expires' => '支付二维码时间',
            'comm_type' => '小区类型',
            'code_image' => '小区二维码图片地址',
            'house_id' => '楼盘id',
            'area_sign' => '地区标识',
            'status' => '状态',
            'create_at' => '新增时间',
        ];
    }

    /***
     * 新增小区
     * @return bool
     */
    public function addCommunity(){
        return $this->save();
    }

}



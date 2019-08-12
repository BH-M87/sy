<?php

namespace app\models;

use app\models\BaseModel;
use Yii;

/**
 * This is the model class for table "ps_device".
 *
 * @property int $id
 * @property int $community_id 小区Id
 * @property int $category_id 设备分类ID
 * @property string $name 设备名称
 * @property string $device_no 设备编号
 * @property string $technology 技术规格
 * @property int $num 数量
 * @property int $price 单价
 * @property string $supplier 供应商
 * @property string $supplier_tel 供应商联系电话
 * @property string $install_place 安装地点
 * @property string $leader 设备负责人
 * @property int $inspect_status 巡检状态：1正常；2异常
 * @property int $status 设备状态 1运行 2报废
 * @property string $plan_scrap_at 拟报废日期
 * @property string $start_at 出厂日期
 * @property string $expired_at 保修截止日期
 * @property string $age_limit 寿命年限
 * @property string $repair_company 保修单位
 * @property string $make_company 制造单位
 * @property string $make_company_tel 制造单位电话
 * @property string $install_company 安装单位
 * @property string $install_company_tel 安装单位电话
 * @property string $note 备注
 * @property string $file_url 文件地址
 * @property string $file_name 文件名称
 * @property string $scrap_person 报废人
 * @property string $scrap_note 报废说明
 * @property int $scrap_at 报废日期
 * @property int $create_at 操作时间
 */
class PsDevice extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_device';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'category_id', 'name', 'device_no', 'supplier', 'supplier_tel', 'install_place', 'leader', 'status', 'plan_scrap_at'], 'required'],
            [['community_id', 'category_id', 'num', 'price', 'inspect_status', 'status', 'scrap_at', 'create_at'], 'integer'],
            [['plan_scrap_at', 'start_at', 'expired_at'], 'safe'],
            [['name', 'device_no', 'technology', 'supplier', 'supplier_tel', 'install_place', 'leader', 'age_limit', 'repair_company', 'make_company', 'make_company_tel', 'install_company', 'install_company_tel', 'scrap_person'], 'string', 'max' => 15],
            [['note', 'scrap_note'], 'string', 'max' => 200],
            [['file_url'], 'string', 'max' => 500],
            [['file_name'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'category_id' => 'Category ID',
            'name' => 'Name',
            'device_no' => 'Device No',
            'technology' => 'Technology',
            'num' => 'Num',
            'price' => 'Price',
            'supplier' => 'Supplier',
            'supplier_tel' => 'Supplier Tel',
            'install_place' => 'Install Place',
            'leader' => 'Leader',
            'inspect_status' => 'Inspect Status',
            'status' => 'Status',
            'plan_scrap_at' => 'Plan Scrap At',
            'start_at' => 'Start At',
            'expired_at' => 'Expired At',
            'age_limit' => 'Age Limit',
            'repair_company' => 'Repair Company',
            'make_company' => 'Make Company',
            'make_company_tel' => 'Make Company Tel',
            'install_company' => 'Install Company',
            'install_company_tel' => 'Install Company Tel',
            'note' => 'Note',
            'file_url' => 'File Url',
            'file_name' => 'File Name',
            'scrap_person' => 'Scrap Person',
            'scrap_note' => 'Scrap Note',
            'scrap_at' => 'Scrap At',
            'create_at' => 'Create At',
        ];
    }
}

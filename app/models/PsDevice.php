<?php

namespace app\models;

use app\models\BaseModel;
use common\core\Regular;
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
            [['community_id', 'category_id', 'name', 'device_no', 'supplier', 'supplier_tel', 'install_place', 'leader', 'status', 'plan_scrap_at'], 'required', 'on' => ['add', 'edit']],
            [['community_id', 'category_id', 'num', 'price', 'status', 'scrap_at', 'create_at'], 'integer', 'on' => ['add', 'edit']],
            [['plan_scrap_at', 'start_at', 'expired_at'], 'safe'],
            [['name', 'device_no', 'technology', 'supplier', 'supplier_tel', 'install_place', 'leader', 'age_limit', 'repair_company', 'make_company', 'make_company_tel', 'install_company', 'install_company_tel', 'scrap_person'], 'string', 'max' => 15, 'on' => ['add', 'edit']],
            [['note', 'scrap_note'], 'string', 'max' => 200, 'on' => ['add', 'edit']],
            [['supplier_tel', 'make_company_tel', 'install_company_tel'], 'match', 'pattern' => Regular::phone(), 'message' => '{attribute}必须是手机号码格式', 'on' => ['add','edit']],
            [['id', 'community_id', 'name', 'device_no'], 'existData', 'on' => ['add', 'edit']],
            // 新增场景
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
            ['inspect_status', 'default', 'value' => 1, 'on' => 'add'],
            // 编辑场景
            [['id'], 'required', 'on' => ['edit']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'                  => 'ID',
            'community_id'        => '小区',
            'category_id'         => '设备分类',
            'name'                => '设备名称',
            'device_no'           => '设备编号',
            'technology'          => '技术规格',
            'num'                 => '数量',
            'price'               => '单价',
            'supplier'            => '供应商',
            'supplier_tel'        => '供应商联系电话',
            'install_place'       => '安装地点',
            'leader'              => '设备负责人',
            'status'              => '设备状态',
            'plan_scrap_at'       => '拟报废日期',
            'start_at'            => '出厂日期',
            'expired_at'          => '保修截止日期',
            'age_limit'           => '寿命年限',
            'repair_company'      => '保修单位',
            'make_company'        => '制造单位',
            'make_company_tel'    => '制造单位电话',
            'install_company'     => '安装单位',
            'install_company_tel' => '安装单位电话',
            'note'                => '备注',
            'scrap_person'        => '报废人',
            'scrap_note'          => '报废说明',
            'scrap_at'            => '报废日期',
            'create_at'           => 'Create At',
        ];
    }

    // 新增 编辑
    public function saveData($scenario, $param)
    {
        if ($scenario == 'edit') {
            $param['create_at'] = time();
            Yii::$app->db->createCommand()->update("ps_device_accident", ["category_id" => $param["category_id"]], ["device_id" => $param['id']])->execute();
            Yii::$app->db->createCommand()->update("ps_device_repair", ["device_name" => $param["name"], "device_no" => $param["device_no"], "category_id" => $param["category_id"]], ["device_id" => $param['id']])->execute();
            Yii::$app->db->createCommand()->update("ps_inspect_point", ["device_name" => $param["name"], "device_no" => $param["device_no"], "category_id" => $param["category_id"]], ["device_id" => $param['id']])->execute();
            return self::updateAll($param, ['id' => $param['id']]);
        }
        return $this->save();
    }

    // 判断是否已存在
    public function existData()
    {
        $model = self::find()->where(['!=', "id", !empty($this->id) ? $this->id : 0])
            ->andWhere(['=', 'name', $this->name])
            ->andWhere(['=', 'community_id', $this->community_id])
            ->one();

        if ($model) {
            $this->addError('', "数据已存在");
        }

        $device = self::find()->where(['!=', "id", !empty($this->id) ? $this->id : 0])
            ->andWhere(['=', 'device_no', $this->device_no])
            ->andWhere(['=', 'community_id', $this->community_id])
            ->one();

        if ($device) {
            $this->addError('', "设备编号已存在");
        }
    }
}

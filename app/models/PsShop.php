<?php
namespace app\models;

use Yii;

class PsShop extends BaseModel
{
    public static function tableName()
    {
        return 'ps_shop';
    }

    public function rules()
    {
        return [
            [['merchant_code', 'shop_code', 'shop_name', 'address', 'lon', 'lat', 'link_name', 'link_mobile', 'start', 'end'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
            [['status'], 'integer', 'message'=> '{attribute}格式错误!'],
            [['shop_name'], 'string', 'max' => 20],
            [['app_name', 'app_id'], 'string', 'max' => 20],
            ['status', 'default', 'value' => 1, 'on' => 'add'],
            ['update_at', 'default', 'value' => 0, 'on' => 'add'],
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'merchant_code' => '商家编号',
            'shop_code' => '店铺编号',
            'shop_name' => '店铺名称',
            'address' => '店铺地址',
            'lon' => '经度',
            'lat' => '纬度',
            'link_name' => '姓名',
            'link_mobile' => '手机号',
            'start' => '营业开始时间',
            'end' => '营业结束时间',
            'status' => '店铺状态',
            'app_id' => '小程序appID',
            'app_name' => '小程序app名称',
            'update_at' => '更新时间',
            'create_at' => '新增时间',
        ];
    }

     // 新增 编辑
    public function saveData($scenario, $p)
    {
        if ($scenario == 'edit') {
            $p['update_at'] = time();
            self::updateAll($p, ['id' => $p['id']]);
            return true;
        }
        return $this->save();
    }
}

<?php
namespace app\models;

use Yii;
use common\core\Regular;

class PsShop extends BaseModel
{
    public static function tableName()
    {
        return 'ps_shop';
    }

    public function rules()
    {
        return [
            [['merchant_code', 'shop_code', 'shop_name', 'address', 'lon', 'lat', 'link_name', 'link_mobile', 'start', 'end', 'shopImg'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
            [['app_id'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['getDetail']],
            [['status'], 'integer', 'message'=> '{attribute}格式错误!'],
            [['shop_name', 'link_name'], 'string', 'max' => 20],
            [['app_name', 'app_id'], 'string', 'max' => 20],
            [['shopImg'], 'string', 'max' => 255],
            ['link_mobile', 'match', 'pattern' => Regular::phone(), 'message' => '{attribute}格式出错', 'on' => ['add', 'edit']],
            [['app_id'], 'appDataInfo','on' => ['getDetail']], //信息是否存在
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
            'shopImg' => '门头照片',
            'app_id' => '小程序appID',
            'app_name' => '小程序app名称',
            'update_at' => '更新时间',
            'create_at' => '新增时间',
        ];
    }

    public function appDataInfo($attribute){
        if(!empty($this->app_id)){
            $res = self::find()->select(['id'])->where(['=','app_id',$this->app_id])->asArray()->one();
            if(empty($res)){
                return $this->addError($attribute, "店铺不存在");
            }
        }
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

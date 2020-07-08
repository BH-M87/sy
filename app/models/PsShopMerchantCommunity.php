<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/8
 * Time: 10:08
 */
namespace app\models;

class PsShopMerchantCommunity extends BaseModel
{


    public $communityInfo = '';

    public static function tableName()
    {
        return 'ps_shop_merchant_community';
    }

    public function rules()
    {
        return [

//            [['merchant_code', 'community_id', 'community_name', 'society_id', 'society_name'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['merchant_code', 'community_id', 'community_name'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [["id", 'create_at', 'update_at'], 'integer'],
            [['merchant_code', 'community_id', 'community_name', 'society_id', 'society_name'], 'trim'],
            [['merchant_code', 'community_id', 'community_name', 'society_id', 'society_name'], 'string', "max" => 30],
            [["create_at", 'update_at'], "default", 'value' => time(), 'on' => ['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id'              => '关联',
              'merchant_code'   => '商家code',
              'community_id'    => '小区id',
              'community_name'  => '小区名称',
              'society_id'      => '社区id',
              'society_name'    => '社区名称',
              'create_at'       => '创建时间',
              'update_at'       => '修改时间',
        ];
    }

    /***
     * 新增
     * @return true|false
     */
    public function saveData()
    {
        return $this->save();
    }

    /***
     * 修改
     * @return bool
     */
    public function edit($param)
    {
        $param['update_at'] = time();
        return self::updateAll($param, ['id' => $param['id']]);
    }
}
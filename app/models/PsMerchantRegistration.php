<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/6/4
 * Time: 14:46
 * Desc: 商户报名
 */
namespace app\models;

class PsMerchantRegistration extends BaseModel {

    public static function tableName()
    {
        return 'ps_merchant_registration';
    }

    public function rules()
    {
        return [
            [['name','link_name','link_mobile','type','address','content'], 'required', 'message' => '{attribute}不能为空', 'on' => ['add']],
            [['id', 'create_at', 'update_at'], 'integer'],
            [['address'], 'string', 'max' => 50],
            [['content'], 'string', 'max' => 50],
            [['name','link_name','link_mobile','type'], 'string', 'max' => 30],
            [['link_mobile'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'手机格式有误'],
            [["create_at",'update_at'],"default",'value' => time(),'on'=>['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id'          => 'ID',
              'name'        => '商户名称',
              'link_name'   => '联系人',
              'link_mobile' => '联系人电话',
              'type'        => '商户类别',
              'address'     => '商户地址',
              'content'     => '权益内容',
              'create_at'   => '新增时间',
              'update_at'   => '修改时间',
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
        return self::updateAll($param, ['company_id' => $param['company_id']]);
    }
}
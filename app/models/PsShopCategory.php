<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/8
 * Time: 10:44
 * Desc: 商品类目
 */
namespace app\models;

class PsShopCategory extends BaseModel {

    public static function tableName()
    {
        return 'ps_shop_category';
    }

    public function rules()
    {
        return [

            [["type"], 'integer'],
            [['code','name','parentCode'], 'string',"max"=>64],
        ];
    }

    public function attributeLabels()
    {
        return [
              'code'        => '编码',
              'name'        => '名称',
              'parentCode'  => '父级编码',
              'type'        => '类型',
        ];
    }

    /*
     * 根据code 获得名称
     */
    public function getNameByCode($code){
        $res = self::find()->select(['name'])->where(['=','code',$code])->asArray()->one();
        return $res['name'];
    }
}
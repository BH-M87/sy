<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/7
 * Time: 15:55
 * Desc: 商户service
 */
namespace service\shop;

use app\models\PsShopMerchant;
use service\BaseService;
use Yii;
use yii\db\Exception;

Class MerchantService extends BaseService {

    //商户入驻
    public function addOfC($params){
        $trans = Yii::$app->db->beginTransaction();
        try{
            $addParams['name'] = !empty($params['name'])?$params['name']:'';
            $addParams['type'] = !empty($params['type'])?$params['type']:'';
            $addParams['category_code'] = !empty($params['category_code'])?$params['category_code']:'';
            $addParams['business_img'] = !empty($params['business_img'])?$params['business_img']:'';
            $addParams['merchant_img'] = !empty($params['merchant_img'])?$params['merchant_img']:'';
            $addParams['lon'] = !empty($params['lon'])?$params['lon']:'';
            $addParams['lat'] = !empty($params['lat'])?$params['lat']:'';
            $addParams['location'] = !empty($params['location'])?$params['location']:'';
            $addParams['start'] = !empty($params['start'])?$params['start']:'';
            $addParams['end'] = !empty($params['end'])?$params['end']:'';
            $addParams['link_name'] = !empty($params['link_name'])?$params['link_name']:'';
            $addParams['link_mobile'] = !empty($params['link_mobile'])?$params['link_mobile']:'';
            $addParams['scale'] = !empty($params['scale'])?$params['scale']:'';
            $addParams['area'] = !empty($params['area'])?$params['area']:'';
            $addParams['member_id'] = !empty($params['member_id'])?$params['member_id']:'';
            $scenario = $addParams['type']==1?'micro_add':'individual_add';
            $model = new PsShopMerchant(['scenario'=>$scenario]);
            if($model->load($addParams,'')&&$model->validate()){
                die;
                if(!$model->save()){
                    return $this->failed('新增失败！');
                }
                $trans->commit();
                return $this->success(['check_code'=>$model->attributes['check_code']]);
            }else{
                $msg = array_values($model->errors)[0][0];
                return $this->failed($msg);
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }
}
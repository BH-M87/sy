<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/7
 * Time: 15:55
 * Desc: 商户service
 */
namespace service\shop;

use app\models\PsShopCategory;
use app\models\PsShopMerchant;
use app\models\PsShopMerchantCommunity;
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
            $addParams['communityInfo'] = !empty($params['communityInfo'])?$params['communityInfo']:[];
            $scenario = $addParams['type']==1?'micro_add':'individual_add';
            $model = new PsShopMerchant(['scenario'=>$scenario]);
            if($model->load($addParams,'')&&$model->validate()){
                if(!$model->save()){
                    return $this->failed('入驻失败！');
                }
                $relModel = new PsShopMerchantCommunity(['scenario'=>'add']);
                foreach($addParams['communityInfo'] as $key=>$value){
                    $value['merchant_code'] = $model->attributes['merchant_code'];
                    if($relModel->load($value,'')&&$relModel->validate()){
                        if(!$relModel->save()){
                            return $this->failed('关联小区失败！');
                        }
                    }else{
                        $msg = array_values($relModel->errors)[0][0];
                        return $this->failed($msg);
                    }
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

    /*
     * 商品类目
     */
    public function getCategory(){
        //获得一级类目
        $result = PsShopCategory::find()->select(['code','name'])->where("type=1")->asArray()->all();
        $categoryResult = [];
        if(!empty($result)){
            foreach($result as $key=>$value){
                $list = PsShopCategory::find()->select(['code','name'])->where("type=2 and parentCode=".$value['code'])->asArray()->all();
                $result[$key]['list'] = !empty($list)?$list:[];
            }

            $redis = Yii::$app->redis;
            $category = 'ps_shop_category';
            $categoryResult = json_decode($redis->get($category),true);
            if(empty($categoryResult)){
                //设置缓存
                $redis->set($category,json_encode($result));
                //设置180天效期
                $redis->expire($category,180*86400);

                $categoryResult = $result;
            }
        }

        return $this->success($categoryResult);
    }

    /*
     * 审核列表
     */
    public function checkList($params){
        $model = new PsShopMerchant();
        $result = $model->getCheckList($params);
        if(!empty($result['list'])){
            foreach($result['list'] as $key=>$value){
                $result['list'][$key]['create_at_msg'] = !empty($value['create_at'])?date('Y-m-d H:i:s',$value['create_at']):'';
                $result['list'][$key]['type_msg'] = !empty($value['type'])?$model->typeMsg[$value['type']]:'';
                $result['list'][$key]['check_status_msg'] = !empty($value['check_status'])?$model->checkMsg[$value['check_status']]:'';
            }
        }
        return $this->success($result);
    }
}
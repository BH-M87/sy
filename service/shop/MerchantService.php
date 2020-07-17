<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/7
 * Time: 15:55
 * Desc: 商户service
 */
namespace service\shop;

use app\models\PsShop;
use app\models\PsShopCategory;
use app\models\PsShopGoods;
use app\models\PsShopGoodsType;
use app\models\PsShopGoodsTypeRela;
use app\models\PsShopMerchant;
use app\models\PsShopMerchantCommunity;
use app\models\PsShopMerchantPromote;
use service\BaseService;
use service\property_basic\JavaOfCService;
use Yii;
use yii\db\Exception;

Class MerchantService extends BaseService {

    //商户入驻
    public function addOfC($params){
        $trans = Yii::$app->db->beginTransaction();
        try{
            $addParams['name'] = !empty($params['name'])?$params['name']:'';
            $addParams['type'] = !empty($params['type'])?$params['type']:'';
            $addParams['category_first'] = !empty($params['category_first'])?$params['category_first']:'';
            $addParams['category_second'] = !empty($params['category_second'])?$params['category_second']:'';
            $addParams['business_img'] = !empty($params['business_img'])?$params['business_img']:'';
            $addParams['merchant_img'] = !empty($params['merchant_img'])?$params['merchant_img']:'';
            $addParams['lon'] = !empty($params['lon'])?$params['lon']:'';
            $addParams['lat'] = !empty($params['lat'])?$params['lat']:'';
            $addParams['location'] = !empty($params['location'])?$params['location']:'';
            $addParams['address'] = !empty($params['address'])?$params['address']:'';
            $addParams['start'] = !empty($params['start'])?$params['start']:'';
            $addParams['end'] = !empty($params['end'])?$params['end']:'';
            $addParams['link_name'] = !empty($params['link_name'])?$params['link_name']:'';
            $addParams['link_mobile'] = !empty($params['link_mobile'])?$params['link_mobile']:'';
            $addParams['scale'] = !empty($params['scale'])?$params['scale']:'';
            $addParams['area'] = !empty($params['area'])?$params['area']:'';
            $addParams['member_id'] = !empty($params['member_id'])?$params['member_id']:'';
            $addParams['ali_form_id'] = !empty($params['ali_form_id'])?$params['ali_form_id']:'';
            $addParams['communityInfo'] = !empty($params['communityInfo'])?$params['communityInfo']:[];

            //根据token 调用java获得支付宝id
            $javaService = new JavaOfCService();
            $javaParams['token'] = $params['token'];
            $javaResult = $javaService->selectMemberInfo($javaParams);
            $addParams['ali_user_id'] = !empty($javaResult['onlyNumber'])?$javaResult['onlyNumber']:'';

            $scenario = $addParams['type']==1?'micro_add':'individual_add';
            $model = new PsShopMerchant(['scenario'=>$scenario]);
            if($model->load($addParams,'')&&$model->validate()){
                if(!$model->saveData()){
                    throw new Exception('入驻失败！');
                }
                foreach($addParams['communityInfo'] as $key=>$value){
                    $relModel = new PsShopMerchantCommunity(['scenario'=>'add']);
                    $value['merchant_code'] = $model->attributes['merchant_code'];
                    if($relModel->load($value,'')&&$relModel->validate()){
                        if(!$relModel->save()){
                            throw new Exception('关联小区失败！');
                        }
                    }else{
                        $msg = array_values($relModel->errors)[0][0];
                        throw new Exception($msg);
                    }
                }
                $trans->commit();
                return $this->success(['check_code'=>$model->attributes['check_code']]);
            }else{
                $msg = array_values($model->errors)[0][0];
                throw new Exception($msg);
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /*
     * 商户详情小程序端
     */
    public function merchantDetailOfc($params){
        $model = new PsShopMerchant(['scenario'=>'merchantDetailOfc']);
        if($model->load($params,'')&&$model->validate()){
            $result = self::getDetail($model,['id'=>$model->attributes['id']]);
            return $this->success($result);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
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
                $result[$key]['subList'] = !empty($list)?$list:[];
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

    /*
     * 商家列表
     */
    public function merchantList($params){
        $model = new PsShopMerchant();
        $result = $model->getMerchantList($params);
        if(!empty($result['list'])){
            foreach($result['list'] as $key=>$value){
                $count = count($value['shop']);
                unset($result['list'][$key]['shop']);
                $result['list'][$key]['create_at_msg'] = !empty($value['check_at'])?date('Y-m-d H:i:s',$value['check_at']):'';
                $result['list'][$key]['type_msg'] = !empty($value['type'])?$model->typeMsg[$value['type']]:'';
                $result['list'][$key]['status_msg'] = !empty($value['status'])?$model->statusMsg[$value['status']]:'';
                $result['list'][$key]['count'] = $count;
            }
        }
        return $this->success($result);
    }

    /*
     * 审核详情
     */
    public function checkDetail($params){
        $model = new PsShopMerchant(['scenario'=>'checkDetail']);
        if($model->load($params,'')&&$model->validate()){
            $result = self::getDetail($model,['id'=>$model->attributes['id']]);
            return $this->success($result);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 商家详情
     */
    public function merchantDetail($params){
        $model = new PsShopMerchant(['scenario'=>'merchantDetail']);
        if($model->load($params,'')&&$model->validate()){
            $result = self::getDetail($model,['id'=>$model->attributes['id']]);
            return $this->success($result);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    public function getDetail($model,$params){
        $cateModel = new PsShopCategory();
        $result = $model->getDetail(['id'=>$params['id']]);
        $community = $result['community'];
        $shop = $result['shop'];
        $communityArray = [];
        if(!empty($community)){
            foreach ($community as $key=>$value){
                $element['community_id'] = $value['community_id'];
                $element['community_name'] = $value['community_name'];
                $communityArray[] = $element;
            }
        }
        unset($result['community'],$result['shop']);
        $result['category_first_msg'] = !empty($result['category_first'])?$cateModel->getNameByCode($result['category_first']):'';
        $result['category_second_msg'] = !empty($result['category_second'])?$cateModel->getNameByCode($result['category_second']):'';
        $result['type_msg'] = !empty($result['type'])?$model->typeMsg[$result['type']]:'';
        $result['merchant_img_array'] = !empty($result['merchant_img'])?explode(',',$result['merchant_img']):'';
        $result['business_img_array'] = !empty($result['business_img'])?explode(',',$result['business_img']):'';
        $result['community_array'] = $communityArray;
        $result['create_at_msg'] = !empty($result['create_at'])?date('Y-m-d H:i:s',$result['create_at']):'';
        $result['check_at_msg'] = !empty($result['check_at'])?date('Y-m-d H:i:s',$result['check_at']):'';
        $result['check_status_msg'] = !empty($result['check_status'])?$model->checkMsg[$result['check_status']]:"";
        $result['scale_msg'] = !empty($result['scale'])?$model->scaleMsg[$result['scale']]:"";
        $result['area_msg'] = !empty($result['area'])?$model->areaMsg[$result['area']]:"";
        $result['count'] = !empty($shop)?count($shop):0;
        $result['status_msg'] = !empty($result['status'])?$model->statusMsg[$result['status']]:0;
        return $result;
    }

    /*
     * 商家审核
     */
    public function merchantChecked($params){
        $updateParams['check_code'] = !empty($params['check_code'])?$params['check_code']:'';
        $updateParams['check_status'] = !empty($params['check_status'])?$params['check_status']:'';
        $updateParams['check_id'] = !empty($params['create_id'])?$params['create_id']:'';
        $updateParams['check_name'] = !empty($params['create_name'])?$params['create_name']:'';
        $updateParams['check_at'] = time();
        $model = new PsShopMerchant(['scenario'=>'checked']);
        if($model->load($updateParams,'')&&$model->validate()){
            $updateParams['id'] = $model->attributes['id'];
            if(!$model->edit($updateParams)){
                return $this->failed('审核失败！');
            }
            return $this->success(['check_code'=>$model->attributes['check_code']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 商家编辑
     */
    public function merchantEdit($params){
        $updateParams['merchant_code'] = !empty($params['merchant_code'])?$params['merchant_code']:'';
        $updateParams['status'] = !empty($params['status'])?$params['status']:'';
        $model = new PsShopMerchant(['scenario'=>'merchantEdit']);
        if($model->load($updateParams,'')&&$model->validate()){
            $updateParams['id'] = $model->attributes['id'];
            if(!$model->edit($updateParams)){
                return $this->failed('编辑失败！');
            }
            return $this->success(['merchant_code'=>$model->attributes['merchant_code']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 规模面积下拉
     */
    public function dropOfCommon(){
        $area = [
            ['key'=>1,'value'=>'10㎡以内'],
            ['key'=>2,'value'=>'10~50㎡'],
            ['key'=>3,'value'=>'50~100㎡'],
            ['key'=>4,'value'=>'100㎡以上'],
        ];

        $scale = [
            ['key'=>1,'value'=>'0~5人'],
            ['key'=>2,'value'=>'5~10人'],
            ['key'=>3,'value'=>'10~20人'],
            ['key'=>4,'value'=>'20~50人'],
            ['key'=>5,'value'=>'50以上人'],
        ];

        return $this->success(['area'=>$area,'scale'=>$scale]);
    }

    //社区推广新增
    public function addPromote($params){

        $addParams['merchant_code'] = !empty($params['merchant_code'])?$params['merchant_code']:'';
        $addParams['merchant_name'] = !empty($params['merchant_name'])?$params['merchant_name']:'';
        $addParams['shop_code'] = !empty($params['shop_code'])?$params['shop_code']:'';
        $addParams['shop_name'] = !empty($params['shop_name'])?$params['shop_name']:'';
        $addParams['name'] = !empty($params['name'])?$params['name']:'';
        $addParams['img'] = !empty($params['img'])?$params['img']:'';
        $addParams['sort'] = !empty($params['sort'])?$params['sort']:'';

        $model = new PsShopMerchantPromote(['scenario'=>'add']);
        if($model->load($addParams,'')&&$model->validate()){
            if(!$model->save()){
                return $this->failed('社区商铺推广新增失败！');
            }
            return $this->success(['id'=>$model->attributes['id']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //社区推广修改
    public function editPromote($params){
        $updateParams['merchant_code'] = !empty($params['merchant_code'])?$params['merchant_code']:'';
        $updateParams['merchant_name'] = !empty($params['merchant_name'])?$params['merchant_name']:'';
        $updateParams['shop_code'] = !empty($params['shop_code'])?$params['shop_code']:'';
        $updateParams['shop_name'] = !empty($params['shop_name'])?$params['shop_name']:'';
        $updateParams['name'] = !empty($params['name'])?$params['name']:'';
        $updateParams['img'] = !empty($params['img'])?$params['img']:'';
        $updateParams['id'] = !empty($params['id'])?$params['id']:'';
        $updateParams['sort'] = !empty($params['sort'])?$params['sort']:'';
        $updateParams['status'] = !empty($params['status'])?$params['status']:'';

        $model = new PsShopMerchantPromote(['scenario'=>'edit']);
        if($model->load($updateParams,'')&&$model->validate()){
            if(!$model->edit($updateParams)){
                return $this->failed('社区商铺推广修改失败！');
            }
            return $this->success(['id'=>$model->attributes['id']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //社区推广详情
    public function promoteDetail($params){
        $model = new PsShopMerchantPromote(['scenario'=>'detail']);
        if($model->load($params,'')&&$model->validate()){
            $result = $model->getDetail($params);
            $result['status_msg'] = !empty($result['status'])?$model->statusMsg[$result['status']]:'';
            return $this->success($result);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 推广列表
     */
    public function promoteList($params){
        $model = new PsShopMerchantPromote();
        $result = $model->getList($params);
        if(!empty($result['list'])){
            foreach($result['list'] as $key=>$value){
                $result['list'][$key]['create_at_msg'] = !empty($value['create_at'])?date('Y-m-d H:i:s',$value['create_at']):'';
                $result['list'][$key]['status_msg'] = !empty($value['status'])?$model->statusMsg[$value['status']]:'';
            }
        }
        return $this->success($result);
    }

    /*
     * 商家下拉
     */
    public function dropMerchant(){
        $model = new PsShopMerchant();
        $result = $model->getDropList();
        return $this->success($result);
    }

    /*
     * 判断是否入驻
     */
    public function judgmentExist($params){
        $model = new PsShopMerchant(['scenario'=>'judgmentExist']);
        if($model->load($params,'')&&$model->validate()){
            $result = $model->getListByMember($params);
            $flag = 1;
            if(empty($result)){
                $flag = 2;
            }
            return $this->success(['flag'=>$flag]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 商铺+分类
     */
    public function getShop($params){
        $model = new PsShop(['scenario'=>'getDetail']);
        if($model->load($params,'')&&$model->validate()){
            $detail = $model::find()->select(['id','shop_code','shop_name','shopImg'])->where(['=','app_id',$params['app_id']])->asArray()->one();
            //分类
            $cate = PsShopGoodsType::find()->select(['id','type_name'])->where(['=','shop_id',$detail['id']])->orderBy(['id'=>SORT_ASC])->asArray()->all();
            return $this->success(['shop'=>$detail,'cate'=>$cate]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 店铺详情
     */
    public function getShopDetail($params){
        $model = new PsShop(['scenario'=>'getDetail']);
        if($model->load($params,'')&&$model->validate()){
            $cateModel = new PsShopCategory();
            $fields = [
                        's.shop_name','shopImg','s.merchant_code','m.check_at','s.address','s.location','s.lon','s.lat','s.start','s.end','s.link_name',
                        's.link_mobile','m.category_first','m.category_second','m.business_img','m.merchant_img'
            ];
            $detail = $model::find()->alias('s')
                            ->leftJoin(['m'=>PsShopMerchant::tableName()],'m.merchant_code=s.merchant_code')
                            ->select($fields)
                            ->where(['=','s.app_id',$params['app_id']])
                            ->asArray()->one();
            $detail['business_img_array'] = !empty($detail['business_img'])?explode(',',$detail['business_img']):[];
            $detail['merchant_img_array'] = !empty($detail['merchant_img'])?explode(',',$detail['merchant_img']):[];
            $detail['category_first_msg'] = !empty($detail['category_first'])?$cateModel->getNameByCode($detail['category_first']):'';
            $detail['category_second_msg'] = !empty($detail['category_second'])?$cateModel->getNameByCode($detail['category_second']):'';
            $detail['check_at_msg'] = !empty($detail['check_at'])?date('Y-m-d',$detail['check_at']):'';
            return $this->success($detail);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 商品商品列表
     */
    public function shopGoodsList($params){
        if(empty($params['shop_id'])){
            return $this->failed('店铺id必填！');
        }

        $fields = ['g.id','g.img','g.goods_name'];

        $model = PsShopGoods::find()
                        ->select($fields)
                        ->alias('g')
                        ->leftJoin(['r'=>PsShopGoodsTypeRela::tableName()],'g.id=r.goods_id')
                        ->where(['=','g.shop_id',$params['shop_id']])
                        ->andWhere(['=','g.status',1]);
        if(!empty($params['type_id'])){
            $model->andWhere(['=','r.type_id',$params['type_id']]);
        }
        $count = $model->count();

        if(!empty($params['page'])&&!empty($params['rows'])){
            $page = !empty($params['page'])?intval($params['page']):1;
            $pageSize = !empty($params['rows'])?intval($params['rows']):10;
            $offset = ($page-1)*$pageSize;
            $model->offset($offset)->limit($pageSize);
        }

        $model->orderBy(["g.id"=>SORT_DESC]);
        $result = $model->asArray()->all();

        $odd = $even = [];
        if(!empty($result)){
            foreach($result as $key=>$value){
                $value['img'] = !empty($value['img'])?explode(',',$value['img']):[];

                if ($key % 2 == 0) {
                    $odd[] = $value;
                } else {
                    $even[] = $value;
                }
            }
        }

        return $this->success(['odd' => $odd, 'even' => $even, 'total' => $count]);
    }
}
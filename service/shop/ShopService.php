<?php
namespace service\shop;

use Yii;
use yii\db\Query;
use yii\base\Exception;
use common\MyException;
use common\core\PsCommon;
use common\core\Curl;
use common\core\F;

use service\BaseService;

use app\models\PsShopGoods;
use app\models\PsShop;
use app\models\PsShopGoodsType;
use app\models\PsShopGoodsTypeRela;
use app\models\PsShopCommunity;
use app\models\PsShopMerchant;
use app\models\PsShopCategory;
use app\models\PsShopMerchantCommunity;

use service\property_basic\JavaOfCService;

class ShopService extends BaseService
{
    public function smallIndex($p)
    {
        $merchant = PsShopMerchant::find()->where(['member_id' => $p['member_id']])->one();
        
        if ($merchant->check_status == 2) {
            $shop = PsShop::find()->where(['merchant_code' => $merchant->merchant_code])->one();
        }

        $r['type'] = !empty($merchant) ? 1 : 2;
        $r['shop_type'] = !empty($shop) ? 1 : 2;
        $r['shop_id'] = !empty($shop) ? $shop->id : '';
        $r['check_status'] = !empty($merchant) ? $merchant->check_status : '';
        $r['merchant_id'] = !empty($merchant) ? $merchant->id : '';

        return $r;
    }

    // ----------------------------------     店铺管理     ----------------------------

    // 店铺 新增
    public function shopAdd($p)
    {
        return self::_saveShop($p, 'add');
    }

    // 店铺 编辑
    public function shopEdit($p)
    {
        return self::_saveShop($p, 'edit');
    }

    public function _saveShop($p, $scenario)
    {
        if ($scenario == 'edit') {
            $model = PsShop::findOne($p['id']);
            if (empty($model)) {
                throw new MyException('数据不存在!');
            }
        }

        $merchant = PsShopMerchant::find()->where(['member_id' => $p['member_id'], 'check_status' => 2])->one();
        if (empty($merchant)) {
            throw new MyException('商户不存在');
        }

        $shop = PsShop::find()->where(['shop_name' => $p['shop_name']])
            ->andFilterWhere(['=', 'merchant_code', $merchant->merchant_code])
            ->andFilterWhere(['!=', 'id', $p['id']])->one();

        if (!empty($shop)) {
            throw new MyException('店铺名称已存在!');
        }

        if ($scenario == 'add') {
            $param['shop_code'] = 'DP'.date('YmdHis',time());

            $community = PsShopMerchantCommunity::find()->where(['merchant_code' => $merchant->merchant_code])->asArray()->all();
        }

        $param['id'] = $p['id'];
        $param['merchant_code'] = $merchant->merchant_code;
        $param['shop_name'] = $p['shop_name'];
        $param['address'] = $p['address'];
        $param['lon'] = $p['lon'];
        $param['lat'] = $p['lat'];
        $param['link_name'] = $p['link_name'];
        $param['link_mobile'] = $p['link_mobile'];
        $param['start'] = $p['start'];
        $param['end'] = $p['end'];
        $param['status'] = $p['status'];
        $param['shopImg'] = $p['shopImg'];
        $param['app_id'] = $p['app_id'];
        $param['app_name'] = $p['app_name'];

        $trans = Yii::$app->getDb()->beginTransaction();

        try {
            $model = new PsShop(['scenario' => $scenario]);

            if (!$model->load($param, '') || !$model->validate()) {
                throw new MyException($this->getError($model));
            }

            if (!$model->saveData($scenario, $param)) {
                throw new MyException($this->getError($model));
            }

            $shopId = $scenario == 'add' ? $model->attributes['id'] : $p['id'];

            if (!empty($community)) {
                PsShopCommunity::deleteAll(['shop_id' => $shopId]);
                foreach ($community as $k => $v) {
                    $javaParam['token'] = $p['token'];
                    $javaParam['id'] = $v['community_id'];
                    $javaResult = JavaOfCService::service()->selectCommunityById($javaParam);

                    if (!empty($javaResult)) {
                        $distance = F::getDistance($p['lat'], $p['lon'], $javaResult['lat'], $javaResult['lon']);
                        $commParam[] = [
                            'shop_id' => $shopId, 
                            'distance' => $distance, 
                            'community_id' => $javaResult['id'],
                            'community_name' => $javaResult['communityName'],
                            'society_id' => $v['society_id'],
                            'society_name' => $v['society_name'],
                        ];
                    }
                }
                Yii::$app->db->createCommand()->batchInsert('ps_shop_community', ['shop_id', 'distance', 'community_id', 'community_name', 'society_id', 'society_name'], $commParam)->execute();       
            }

            $trans->commit();
            return ['id' => $shopId];
        } catch (Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
    }

    // 店铺 关联小区
    public function shopCommunity($p)
    {
        $shop_id = $p['shop_id'];

        $m = PsShop::findOne($shop_id);
        if (empty($m)) {
            throw new MyException('店铺不存在');
        }

        if (empty($p['community'])) {
            throw new MyException('请选择小区');
        }

        $trans = Yii::$app->getDb()->beginTransaction();

        try {

            PsShopCommunity::deleteAll(['shop_id' => $shop_id]);

            foreach ($p['community'] as $k => $v) {
                $javaParam['token'] = $p['token'];
                $javaParam['id'] = $v['community_id'];
                $javaResult = JavaOfCService::service()->selectCommunityById($javaParam);

                if (!empty($javaResult)) {
                    $distance = F::getDistance($m->lat, $m->lon, $javaResult['lat'], $javaResult['lon']);
                    $commParam[] = [
                        'shop_id' => $shop_id, 
                        'distance' => $distance, 
                        'community_id' => $v['community_id'],
                        'community_name' => $v['community_name'],
                        'society_id' => $v['society_id'],
                        'society_name' => $v['society_name'],
                    ];
                }       
            }

            Yii::$app->db->createCommand()->batchInsert('ps_shop_community', ['shop_id', 'distance', 'community_id', 'community_name', 'society_id', 'society_name'], $commParam)->execute();

            $trans->commit();
            return ['id' => $shop_id];
        } catch (Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
    }

    // 店铺 关联小区列表
    public function shopCommunityList($p)
    {
        if (empty($p['shop_id'])) {
            throw new MyException('店铺ID不能为空');
        }

        $m = PsShopCommunity::find()->where(['shop_id' => $p['shop_id']])->asArray()->all();

        return $m;
    }

    // 店铺 详情
    public function shopShow($p)
    {
        if (empty($p['id'])) {
            throw new MyException('id不能为空');
        }

        $r = PsShop::find()->select('shop_name, status, address, merchant_code')->where(['id' => $p['id']])->asArray()->one();
        if (!empty($r)) {
            $merchant = PsShopMerchant::find()->where(['merchant_code' => $r['merchant_code']])->one();
            $r['img'] = $merchant->business_img;
            $r['category_name'] = PsShopCategory::find()->where(['code' => $merchant->category_second])->one()->name;
            $r['statusMsg'] = $r['status'] == 1 ? '营业中' : '打烊';
            $r['shopImg'] = 'https://community-static.zje.com/community-1591859855119-j4brg0tf76o0.jpeg';

            return $r;
        }

        throw new MyException('数据不存在!');
    }

    // 店铺 列表
    public function shopList($p)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : '1';
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : '10';

        $totals = self::shopSearch($p)->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::shopSearch($p)
            ->offset(($p['page'] - 1) * $p['rows'])
            ->limit($p['rows'])
            ->orderBy('A.id desc')->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $v['goodsNum'] =  PsShopGoods::find()->where(['shop_id' => $v['id']])->count();
                $v['community'] = PsShopCommunity::find()->select('community_name')->where(['shop_id' => $v['id']])->asArray()->all();
                $v['create_at'] = date('Y-m-d H:i:s', $v['create_at']);
                $v['app_id'] = !empty($v['app_id']) ? $v['app_id'] : '';
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 列表参数过滤
    private static function shopSearch($p)
    {
        $start_at = !empty($p['start_at']) ? strtotime($p['start_at']) : '';
        $end_at = !empty($p['end_at']) ? strtotime($p['end_at'].'23:59:59') : '';

        $m = PsShop::find()->alias('A')
            ->leftJoin('ps_shop_community B', 'A.id = B.shop_id')
            ->filterWhere(['=', 'A.merchant_code', $p['merchant_code']])
            ->andFilterWhere(['=', 'A.shop_code', $p['shop_code']])
            ->andFilterWhere(['>=', 'A.create_at', $start_at])
            ->andFilterWhere(['<=', 'A.create_at', $end_at])
            ->andFilterWhere(['=', 'B.society_id', $p['society_id']])
            ->andFilterWhere(['=', 'B.community_id', $p['community_id']])
            ->andFilterWhere(['like', 'B.community_name', $p['community_name']]);
        return $m;
    }
    
    // 店铺状态变更
    public function shopStatus($p)
    {
        $m = PsShop::findOne($p['id']);

        if (empty($m)) {
            throw new MyException('数据不存在');
        }

        if ($m->status == 1) {
            $status = 2;
        } else {
            $status = 1;
        }

        return PsShop::updateAll(['status' => $status], ['id' => $p['id']]);
    }

    // 店铺关联小程序
    public function shopApp($p)
    {
        $m = PsShop::find()->where(['shop_code' => $p['shop_code']])->one();

        if (empty($m)) {
            throw new MyException('数据不存在');
        }

        if (empty($p['app_id'])) {
            throw new MyException('小程序ID必填');
        }

        return PsShop::updateAll(['app_id' => $p['app_id']], ['shop_code' => $p['shop_code']]);
    }

    // 商品下拉列表
    public function shopDropDown($p)
    {
       $m = PsShop::find()->select('id, shop_name, shop_code')
           ->filterWhere(['merchant_code' => $p['merchant_code']])
           ->orderBy('id desc')->asArray()->all();

        return $m ?? [];
    }

    // ----------------------------------     商品分类管理     ----------------------------

    // 商品分类 新增
    public function goodsTypeAdd($p)
    {
        return self::_saveGoodsType($p, 'add');
    }

    // 商品分类 编辑
    public function goodsTypeEdit($p)
    {
        return self::_saveGoodsType($p, 'edit');
    }

    public function _saveGoodsType($p, $scenario)
    {
        if ($scenario == 'edit') {
            $model = PsShopGoodsType::findOne($p['id']);
            if (empty($model)) {
                throw new MyException('数据不存在!');
            }
        }

        $type = PsShopGoodsType::find()->where(['type_name' => $p['type_name'], 'shop_id' => $p['shop_id']])
            ->andFilterWhere(['!=', 'id', $p['id']])->one();

        if (!empty($type)) {
            throw new MyException('分类已存在!');
        }

        $shop = PsShop::findOne($p['shop_id']);
        if (empty($shop)) {
            throw new MyException('店铺不存在!');
        }

        $param['id'] = $p['id'];
        $param['type_name'] = $p['type_name'];
        $param['shop_id'] = $p['shop_id'];

        $trans = Yii::$app->getDb()->beginTransaction();

        try {
            $model = new PsShopGoodsType(['scenario' => $scenario]);

            if (!$model->load($param, '') || !$model->validate()) {
                throw new MyException($this->getError($model));
            }

            if (!$model->saveData($scenario, $param)) {
                throw new MyException($this->getError($model));
            }

            $id = $scenario == 'add' ? $model->attributes['id'] : $p['id'];

            $trans->commit();
            return ['id' => $id];
        } catch (Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
    }

    // 商品分类 列表
    public function goodsTypeList($p)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : '1';
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : '10';

        $totals = self::goodsTypeSearch($p)->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::goodsTypeSearch($p)
            ->offset(($p['page'] - 1) * $p['rows'])
            ->limit($p['rows'])
            ->orderBy('id desc')->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $v['goodsNum'] =  PsShopGoodsTypeRela::find()->where(['type_id' => $v['id']])->count();
                $v['right'] = [
                    ["type" => "edit", "text" => "编辑"], 
                    ["type" => "delete", "text" => "删除", "fColor" => "yellow"]
                ];
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 列表参数过滤
    private static function goodsTypeSearch($p)
    {
        $m = PsShopGoodsType::find()
            ->filterWhere(['like', 'type_name', $p['type_name']])
            ->andFilterWhere(['=', 'shop_id', $p['shop_id']]);
        return $m;
    }

    // 商品分类 下拉列表
    public function goodsTypeDropDown($p)
    {
       $m = PsShopGoodsType::find()->select('id, type_name')->where(['shop_id' => $p['shop_id']])->orderBy('id desc')->asArray()->all();

       if (!empty($m)) {
           foreach ($m as $k => &$v) {
                $v['right'] = [
                    ["type" => "edit", "text" => "编辑"], 
                    ["type" => "delete", "text" => "删除", "fColor" => "yellow"]
                ];
            }
       }

        return $m ?? [];
    }
    
    // 商品分类 删除
    public function goodsTypeDelete($p)
    {
        $m = PsShopGoodsType::findOne($p['id']);
        if (empty($m)) {
            throw new MyException('数据不存在!');
        }
        
        $trans = Yii::$app->getDb()->beginTransaction();

        try {
            PsShopGoodsType::deleteAll(['id' => $p['id']]);
            PsShopGoodsTypeRela::deleteAll(['type_id' => $p['id']]);
            $trans->commit();
            return ['id' => $p['id']];
        } catch (Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }

    }

    // ----------------------------------     商品管理     ----------------------------

    // 商品 新增
    public function goodsAdd($p)
    {
        return self::_saveGoods($p, 'add');
    }

    // 商品 编辑
    public function goodsEdit($p)
    {
        return self::_saveGoods($p, 'edit');
    }

    public function _saveGoods($p, $scenario)
    {
        if ($scenario == 'edit') {
            $model = PsShopGoods::findOne($p['id']);
            if (empty($model)) {
                throw new MyException('数据不存在!');
            }
        }

        $goods = PsShopGoods::find()->where(['goods_name' => $p['goods_name'], 'shop_id' => $p['shop_id']])
            ->andFilterWhere(['!=', 'id', $p['id']])->one();

        if (!empty($goods)) {
            throw new MyException('商品已存在!');
        }

        $shop = PsShop::findOne($p['shop_id']);
        if (empty($shop)) {
            throw new MyException('店铺不存在!');
        }

        if (!empty($p['type_id']) && !is_array($p['type_id'])) {
            throw new MyException('商品分类格式错误!');
        }

        if (!empty($p['type_id']) && count($p['type_id']) > 5) {
            throw new MyException('同一商品最多关联5个分类!');
        }

        if (count($p['img']) > 4) {
            throw new MyException('商品图片最多4张!');
        }

        if ($scenario == 'add') {
            $param['goods_code'] = 'S'.date('YmdHis',time());
        }

        $param['id'] = $p['id'];
        $param['merchant_code'] = $shop->merchant_code;
        $param['shop_id'] = $p['shop_id'];
        $param['goods_name'] = $p['goods_name'];
        $param['status'] = $p['status'];
        $param['img'] = is_array($p['img']) ? implode(',', $p['img']) : '';

        $trans = Yii::$app->getDb()->beginTransaction();

        try {
            $model = new PsShopGoods(['scenario' => $scenario]);

            if (!$model->load($param, '') || !$model->validate()) {
                throw new MyException($this->getError($model));
            }

            if (!$model->saveData($scenario, $param)) {
                throw new MyException($this->getError($model));
            }

            $id = $scenario == 'add' ? $model->attributes['id'] : $p['id'];

            if (!empty($p['type_id']) && is_array($p['type_id']) && count($p['type_id']) > 0) {
                PsShopGoodsTypeRela::deleteAll(['goods_id' => $id]);
                foreach ($p['type_id'] as $type_id) {
                    if (!empty($type_id)) {
                        $goodsType = PsShopGoodsType::findOne($type_id);
                        if (empty($goodsType)) {
                            throw new MyException('商品分类不存在');
                        }

                        $rela = new PsShopGoodsTypeRela();
                        $rela->goods_id = $id;
                        $rela->type_id = $type_id;
                        $rela->save();
                    }
                }
            }

            $trans->commit();
            return ['id' => $id];
        } catch (Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
    }

    // 商品 列表
    public function goodsList($p)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : '1';
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : '10';

        $totals = self::goodsSearch($p)->groupBy('A.id')->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::goodsSearch($p)
            ->offset(($p['page'] - 1) * $p['rows'])
            ->limit($p['rows'])
            ->orderBy('A.id desc')->groupBy('A.id')->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $shop = PsShop::findOne($v['shop_id']);
                $v['img'] =  explode(',', $v['img']);
                $v['statusMsg'] = $v['status'] == 1 ? '上架' : '下架';
                $v['type_name'] = self::_goodsTypeName($v['id']);
                $v['shop_name'] = $shop->shop_name;
                $v['shop_code'] = $shop->shop_code;
                $v['update_at'] = !empty($v['update_at']) ? date('Y-m-d H:i:s', $v['update_at']) : date('Y-m-d H:i:s', $v['create_at']);
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 列表参数过滤
    private static function goodsSearch($p)
    {
        $m = PsShopGoods::find()->alias('A')
            ->leftJoin('ps_shop_goods_type_rela B', 'A.id = B.goods_id')
            ->leftJoin('ps_shop C', 'C.id = A.shop_id')
            ->filterWhere(['=', 'B.type_id', $p['type_id']])
            ->andFilterWhere(['=', 'A.merchant_code', $p['merchant_code']])
            ->andFilterWhere(['=', 'C.shop_code', $p['shop_code']])
            ->andFilterWhere(['like', 'A.goods_name', $p['goods_name']])
            ->andFilterWhere(['like', 'C.shop_name', $p['shop_name']])
            ->andFilterWhere(['=', 'A.shop_id', $p['shop_id']]);
        return $m;
    }

    // 商品 详情
    public function goodsShow($p)
    {
        if (empty($p['id'])) {
            throw new MyException('id不能为空');
        }

        $r = PsShopGoods::find()->where(['id' => $p['id']])->asArray()->one();
        if (!empty($r)) {
            $rela = PsShopGoodsTypeRela::find()->where(['goods_id' => $r['id']])->asArray()->all();
            $r['type_id'] = array_column($rela, 'type_id');
            $r['type_name'] = self::_goodsTypeName($r['id']);
            $r['statusMsg'] = $r['status'] == 1 ? '上架' : '下架';
            $r['img'] = explode(',', $r['img']);

            return $r;
        }

        throw new MyException('数据不存在!');
    }
    
    // 获取商品分类名称 逗号隔开
    public function _goodsTypeName($goods_id)
    {
        $m = PsShopGoodsType::find()->alias('A')->select('A.type_name')
            ->leftJoin('ps_shop_goods_type_rela B', 'A.id = B.type_id')
            ->where(['B.goods_id' => $goods_id])
            ->asArray()->all();

        $type = array_column($m, 'type_name');

        return implode(',', $type);
    }

    // 商品状态变更
    public function goodsStatus($p)
    {
        $m = PsShopGoods::findOne($p['id']);

        if (empty($m)) {
            throw new MyException('数据不存在');
        }

        if ($m->status == 1) {
            $status = 2;
        } else {
            $status = 1;
        }

        return PsShopGoods::updateAll(['status' => $status], ['id' => $p['id']]);
    }

    // 商品下拉列表
    public function goodsDropDown($p)
    {
       $m = PsShopGoods::find()->select('id, goods_name')
           ->filterWhere(['shop_id' => $p['shop_id']])
           ->andFilterWhere(['merchant_code' => $p['merchant_code']])
           ->orderBy('id desc')->asArray()->all();

        return $m ?? [];
    }
}
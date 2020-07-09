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
        $merchant = PsShopMerchant::find()->where(['member_id' => $p['member_id'], 'check_status' => 2])->one();
        $shop = PsShop::find()->where(['merchant_code' => $merchant->merchant_code])->one();

        $r['type'] = !empty($merchant) ? 1 : 2;
        $r['shop_type'] = !empty($shop) ? 1 : 2;

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
            $param['shop_code'] = 'DP'.time();

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

    // 店铺 详情
    public function shopShow($p)
    {
        if (empty($p['id'])) {
            throw new MyException('id不能为空');
        }

        $r = PsShop::find()->select('shop_name, status, address, merchant_code')->where(['id' => $p['id']])->asArray()->one();
        if (!empty($r)) {
            $merchant = PsShopMerchant::find()->where(['merchant_code' => $r['merchant_code']])->one();
            $r['img'] = $merchant->merchant_img;
            $r['category_name'] = PsShopCategory::find()->where(['code' => $merchant->category_second])->one()->name;
            $r['statusMsg'] = $r['status'] == 1 ? '营业中' : '打烊';

            return $r;
        }

        throw new MyException('数据不存在!');
    }

    // ----------------------------------     商品分类管理     ----------------------------

    // 商品分类 新增
    public function goodsTypeAdd($p, $userInfo)
    {
        return self::_saveGoodsType($p, 'add');
    }

    // 商品分类 编辑
    public function goodsTypeEdit($p, $userInfo)
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

        return $m;
    }

    // ----------------------------------     商品管理     ----------------------------

    // 商品 新增
    public function goodsAdd($p, $userInfo)
    {
        return self::_saveGoods($p, 'add');
    }

    // 商品 编辑
    public function goodsEdit($p, $userInfo)
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

        if ($scenario == 'add') {
            $param['goods_code'] = 'SP'.time();
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

            if (!empty($p['type_id']) && is_array($p['type_id'])) {
                foreach ($p['type_id'] as $type_id) {
                    $rela = new PsShopGoodsTypeRela();
                    $rela->goods_id = $id;
                    $rela->type_id = $type_id;
                    $rela->save();
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

        $totals = self::goodsSearch($p)->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::goodsSearch($p)
            ->offset(($p['page'] - 1) * $p['rows'])
            ->limit($p['rows'])
            ->orderBy('id desc')->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $v['img'] =  explode(',', $v['img']);
                $v['statusMsg'] = $v['status'] == 1 ? '上架' : '下架';
                $v['type_name'] = '';
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 列表参数过滤
    private static function goodsSearch($p)
    {
        $m = PsShopGoods::find()->alias('A')
            ->leftJoin('ps_shop_goods_type_rela B', 'A.id = B.goods_id')
            ->filterWhere(['=', 'B.type_id', $p['type_id']])
            ->andFilterWhere(['=', 'A.shop_id', $p['shop_id']]);
        return $m;
    }

    // 商品 详情
    public function goodsShow($p)
    {
        if (empty($p['id'])) {
            throw new MyException('id不能为空');
        }

        $r = PsShopGoods::find()->where(['A.id' => $p['id']])->asArray()->one();
        if (!empty($r)) {
            $r['type_id'] = PsShopGoodsTypeRela::find()->where(['goods_id' => $r['id']])->asArray()->all();
            $r['type_name'] = '';
            $r['statusMsg'] = $r['status'] == 1 ? '上架' : '下架';

            return $r;
        }

        throw new MyException('数据不存在!');
    }
}
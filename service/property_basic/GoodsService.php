<?php
namespace service\property_basic;

use Yii;
use yii\db\Query;
use yii\base\Exception;
use common\MyException;
use common\core\PsCommon;

use service\BaseService;

use app\models\Goods;
use app\models\GoodsCommunity;
use service\property_basic\JavaService;

class GoodsService extends BaseService
{
    // 新增
    public function add($p, $userInfo)
    {
        return self::_saveGoods($p, 'add', $userInfo);
    }

    // 编辑
    public function edit($p, $userInfo)
    {
        return self::_saveGoods($p, 'edit', $userInfo);
    }

    public function _saveGoods($p, $scenario, $userInfo)
    {
        if ($scenario == 'edit') {
            $model = Goods::findOne($p['id']);
            if (empty($model)) {
                throw new MyException('数据不存在!');
            }
        }

        $goods = Goods::find()->where(['name' => $p['name']])->andFilterWhere(['!=', 'id', $p['id']])->one();

        if (!empty($goods)) {
            throw new MyException('数据已存在!');
        }

        $param['id'] = $p['id'];
        $param['name'] = $p['name'];
        $param['img'] = $p['img'];
        $param['startAt'] = strtotime($p['startAt']);
        $param['endAt'] = strtotime($p['endAt'].'23:59:59');
        $param['group'] = $p['group'];
        $param['score'] = $p['score'];
        $param['num'] = $p['num'];
        $param['personLimit'] = $p['personLimit'];
        $param['operatorId'] = $userInfo['id'];
        $param['operatorName'] = $userInfo['truename'];

        $trans = Yii::$app->getDb()->beginTransaction();

        try {
            $model = new Goods(['scenario' => $scenario]);

            if (!$model->load($param, '') || !$model->validate()) {
                throw new MyException($this->getError($model));
            }

            if (!$model->saveData($scenario, $param)) {
                throw new MyException($this->getError($model));
            }

            $goodsId = $scenario == 'add' ? $model->attributes['id'] : $p['id'];

            if (!empty($p['community'])) {
                GoodsCommunity::deleteAll(['goodsId' => $goodsId]);
                foreach ($p['community'] as $k => $v) {
                    $comm = new GoodsCommunity();
                    $comm->goodsId = $goodsId;
                    $comm->communityId = $v;
                    $comm->save();
                }
            }

            $trans->commit();
            return ['id' => $goodsId];
        } catch (Exception $e) {
            $trans->rollBack();//array_values($model->errors)[0][0]
            throw new MyException($e->getMessage());
        }
    }

    // 详情
    public function show($p)
    {
        if (empty($p['id'])) {
            throw new MyException('id不能为空');
        }

        $r = Goods::find()->where(['id' => $p['id']])->asArray()->one();
        if (!empty($r)) {
            $comm = GoodsCommunity::find()->where(['goodsId' => $p['id']])->asArray()->all();

            $r['community'] = [];
            if (!empty($comm)) {
                foreach ($comm as $k => $v) {
                    $community = JavaService::service()->communityDetail(['token' => $p['token'], 'id' => $v['communityId']]);
             
                    $r['community'][$k]['id'] = $v['communityId'];
                    $r['community'][$k]['name'] = $community['communityName'];
                }
            }
            
            $r['startAt'] = date('Y-m-d H:i:s', $r['startAt']);
            $r['endAt'] = date('Y-m-d H:i:s', $r['endAt']);

            return $r;
        }

        throw new MyException('数据不存在!');
    }

    // 删除
    public function delete($p)
    {
        if (empty($p['id'])) {
            throw new MyException('id不能为空');
        }

        $m = Goods::findOne($p['id']);
        if (empty($m)) {
            throw new MyException('数据不存在');
        }

        Goods::updateAll(['isDelete' => 1], ['id' => $p['id']]);

        return true;
    }

    // 列表
    public function list($p)
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
                $v['startAt'] = date('Y-m-d H:i:s', $v['startAt']);
                $v['endAt'] = date('Y-m-d H:i:s', $v['endAt']);
                $v['updateAt'] = !empty($v['updateAt']) ? date('Y-m-d H:i:s', $v['updateAt']) : '';
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 列表参数过滤
    private static function goodsSearch($p)
    {
        $startAt = !empty($p['startAt']) ? strtotime($p['startAt']) : '';
        $endAt = !empty($p['endAt']) ? strtotime($p['endAt'] . '23:59:59') : '';

        $m = Goods::find()
            ->filterWhere(['like', 'name', $p['name']])
            ->andFilterWhere(['like', 'group', $p['group']])
            ->andFilterWhere(['>=', 'startAt', $startAt])
            ->andFilterWhere(['<=', 'endAt', $endAt]);
        return $m;
    }
}
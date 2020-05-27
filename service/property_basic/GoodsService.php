<?php
namespace service\property_basic;

use Yii;
use yii\db\Query;
use yii\base\Exception;
use common\MyException;
use common\core\PsCommon;
use common\core\Curl;

use service\BaseService;

use app\models\Goods;
use app\models\GoodsGroup;
use app\models\GoodsGroupCommunity;
use app\models\PsDeliveryRecords;
use service\property_basic\JavaService;

class GoodsService extends BaseService
{
    // 列表
    public function groupDropDown($p)
    {
       $m = GoodsGroup::find()->select('id, name')->orderBy('id desc')->asArray()->all();

        return $m;
    }

    // 新增
    public function groupAdd($p, $userInfo)
    {
        return self::_saveGroup($p, 'add', $userInfo);
    }

    // 编辑
    public function groupEdit($p, $userInfo)
    {
        return self::_saveGroup($p, 'edit', $userInfo);
    }

    public function _saveGroup($p, $scenario, $userInfo)
    {
        if ($scenario == 'edit') {
            $model = GoodsGroup::findOne($p['id']);
            if (empty($model)) {
                throw new MyException('数据不存在!');
            }
        }

        $group = GoodsGroup::find()->where(['name' => $p['name']])->andFilterWhere(['!=', 'id', $p['id']])->one();

        if (!empty($group)) {
            throw new MyException('数据已存在!');
        }

        $param['id'] = $p['id'];
        $param['name'] = $p['name'];
        $param['startAt'] = strtotime($p['startAt']);
        $param['endAt'] = strtotime($p['endAt']);
        $param['content'] = $p['content'];
        $param['operatorId'] = $userInfo['id'];
        $param['operatorName'] = $userInfo['truename'];

        $trans = Yii::$app->getDb()->beginTransaction();

        try {
            $model = new GoodsGroup(['scenario' => $scenario]);

            if (!$model->load($param, '') || !$model->validate()) {
                throw new MyException($this->getError($model));
            }

            if (!$model->saveData($scenario, $param)) {
                throw new MyException($this->getError($model));
            }

            $groupId = $scenario == 'add' ? $model->attributes['id'] : $p['id'];

            if (!empty($p['communityIdList'])) {
                GoodsGroupCommunity::deleteAll(['groupId' => $groupId]);
                foreach ($p['communityIdList'] as $k => $v) {
                    $comm = new GoodsGroupCommunity();
                    $comm->groupId = $groupId;
                    $comm->communityId = $v;
                    $comm->save();
                }
            }

            $trans->commit();
            return ['id' => $groupId];
        } catch (Exception $e) {
            $trans->rollBack();//array_values($model->errors)[0][0]
            throw new MyException($e->getMessage());
        }
    }

    // 活动关联小区
    public function groupCommunity($p)
    {
        $groupId = $p['groupId'];

        $m = GoodsGroup::findOne($groupId);
        if (empty($m)) {
            throw new MyException('兑换活动不存在');
        }

        if (empty($p['communityIdList'])) {
            throw new MyException('请选择小区');
        }

        GoodsGroupCommunity::deleteAll(['groupId' => $groupId]);
        foreach ($p['communityIdList'] as $k => $v) {
            $comm = new GoodsGroupCommunity();
            $comm->groupId = $groupId;
            $comm->communityId = $v;
            $comm->save();
        }

        return [];
    }

    // 详情
    public function groupShow($p)
    {
        if (empty($p['id'])) {
            throw new MyException('id不能为空');
        }

        $r = GoodsGroup::find()->where(['id' => $p['id']])->asArray()->one();
        if (!empty($r)) {
            $comm = GoodsGroupCommunity::find()->where(['groupId' => $p['id']])->asArray()->all();

            $r['community'] = [];
            if (!empty($comm)) {
                foreach ($comm as $k => $v) {
                    $community = JavaService::service()->communityDetail(['token' => $p['token'], 'id' => $v['communityId']]);
             
                    $r['community'][$k]['id'] = $v['communityId'];
                    $r['community'][$k]['name'] = $community['communityName'];
                    $communityName .= $community['communityName'] . ' ';
                }
            }
            
            $r['communityName'] = $communityName;

            $r['startAt'] = date('Y-m-d H:i:s', $r['startAt']);
            $r['endAt'] = date('Y-m-d H:i:s', $r['endAt']);
            $r['content'] = htmlspecialchars($r['content']);

            return $r;
        }

        throw new MyException('数据不存在!');
    }

    // 删除
    public function groupDelete($p)
    {
        if (empty($p['id'])) {
            throw new MyException('id不能为空');
        }

        $m = GoodsGroup::findOne($p['id']);
        if (empty($m)) {
            throw new MyException('数据不存在');
        }

        $goods = Goods::find()->where(['groupId' => $p['id'], 'isDelete' => 2])->one();
        if (!empty($goods)) {
            throw new MyException('请先删除兑换商品');
        }

        GoodsGroup::deleteAll(['id' => $p['id']]);

        return true;
    }

    // 列表
    public function groupList($p)
    {
        $p['page'] = !empty($p['pageNum']) ? $p['pageNum'] : '1';
        $p['rows'] = !empty($p['pageSize']) ? $p['pageSize'] : '10';

        $totals = self::groupSearch($p)->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::groupSearch($p)
            ->offset(($p['page'] - 1) * $p['rows'])
            ->limit($p['rows'])
            ->orderBy('id desc')->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $v['startAt'] = date('Y-m-d H:i:s', $v['startAt']);
                $v['endAt'] = date('Y-m-d H:i:s', $v['endAt']);
                $v['updateAt'] = !empty($v['updateAt']) ? date('Y-m-d H:i:s', $v['updateAt']) : '';
                $v['content'] = htmlspecialchars($v['content']);

                $comm = GoodsGroupCommunity::find()->where(['groupId' => $v['id']])->asArray()->all();

                $v['communityList'] = [];
                if (!empty($comm)) {
                    foreach ($comm as $key => $val) {
                        $community = JavaService::service()->communityDetail(['token' => $p['token'], 'id' => $val['communityId']]);
                 
                        $v['communityList'][$key]['id'] = $val['communityId'];
                        $v['communityList'][$key]['communityName'] = $community['communityName'];
                    }
                }
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 列表参数过滤
    private static function groupSearch($p)
    {
        $startAt = !empty($p['startAt']) ? strtotime($p['startAt']) : '';
        $endAt = !empty($p['endAt']) ? strtotime($p['endAt'] . '23:59:59') : '';

        $m = GoodsGroup::find()
            ->filterWhere(['like', 'name', $p['name']])
            ->andFilterWhere(['>=', 'startAt', $startAt])
            ->andFilterWhere(['<=', 'endAt', $endAt]);
        return $m;
    }

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

        $group = GoodsGroup::findOne($p['groupId']);
        if (empty($group)) {
            throw new MyException('期数不存在!');
        }

        $param['id'] = $p['id'];
        $param['name'] = $p['name'];
        $param['img'] = $p['img'];
        $param['groupId'] = $p['groupId'];
        $param['score'] = $p['score'];
        $param['num'] = $p['num'];
        $param['receiveType'] = $p['receiveType'];
        $param['type'] = $p['type'];
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

        $r = Goods::find()->alias('A')
            ->select('A.*, B.name as groupName, B.startAt, B.endAt')
            ->leftJoin('ps_goods_group B', 'B.id = A.groupId')
            ->where(['A.id' => $p['id']])->asArray()->one();
        if (!empty($r)) {
            
            $r['startAt'] = date('Y-m-d H:i:s', $r['startAt']);
            $r['endAt'] = date('Y-m-d H:i:s', $r['endAt']);
            $r['receiveTypeMsg'] = $r['receiveType'] == 1 ? '快递' : '自提';
            $r['typeMsg'] = $r['type'] == 1 ? '实物' : '虚拟';

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
        $p['page'] = !empty($p['pageNum']) ? $p['pageNum'] : '1';
        $p['rows'] = !empty($p['pageSize']) ? $p['pageSize'] : '10';

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
                $v['receiveTypeMsg'] = $v['receiveType'] == 1 ? '快递' : '自提';
                $v['typeMsg'] = $v['type'] == 1 ? '实物' : '虚拟';
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 列表参数过滤
    private static function goodsSearch($p)
    {
        $startAt = !empty($p['startAt']) ? strtotime($p['startAt']) : '';
        $endAt = !empty($p['endAt']) ? strtotime($p['endAt'] . '23:59:59') : '';

        $m = Goods::find()->alias('A')
            ->select('A.*, B.name groupName, B.startAt, B.endAt')
            ->leftJoin('ps_goods_group B', 'B.id = A.groupId')
            ->where(['A.isDelete' => 2])
            ->filterWhere(['like', 'A.name', $p['name']])
            ->andFilterWhere(['like', 'B.name', $p['groupName']])
            ->andFilterWhere(['>=', 'B.startAt', $startAt])
            ->andFilterWhere(['<=', 'B.endAt', $endAt]);
        return $m;
    }

    // 列表参数过滤
    private static function _search($p)
    {
        $startAt = !empty($p['startAt']) ? strtotime($p['startAt']) : '';
        $endAt = !empty($p['endAt']) ? strtotime($p['endAt'] . '23:59:59') : '';

        $m = Goods::find()->alias('A')
            ->leftJoin('ps_goods_group_community B', 'A.groupId = B.groupId')
            ->leftJoin('ps_goods_group C', 'A.groupId = C.id')
            ->filterWhere(['=', 'B.communityId', $p['community_id']])
            ->andFilterWhere(['=', 'A.isDelete', 2])
            ->andFilterWhere(['!=', 'A.groupId', $p['notGroupId']])
            ->andFilterWhere(['=', 'A.groupId', $p['groupId']]);
        return $m;
    }

    // 往期兑换列表
    public function groupListSmall($p)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : '1';
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : '10';

        $p['notGroupId'] = Goods::find()->alias('A')->select('A.groupId')
            ->leftJoin('ps_goods_group_community B', 'A.groupId = B.groupId')
            ->filterWhere(['=', 'B.communityId', $p['community_id']])
            ->orderBy('A.groupId desc')->scalar();

        $totals = self::_search($p)->groupBy('A.groupId')->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::_search($p)->select('C.name groupName, A.groupId')
            ->offset(($p['page'] - 1) * $p['rows'])
            ->limit($p['rows'])
            ->groupBy('A.groupId')->orderBy('A.groupId desc')->asArray()->all();

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 最新商品列表
    public function goodsList($p)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : '1';
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : '10';

        if (empty($p['groupId'])) {
            $p['groupId'] = GoodsGroup::find()->alias('A')->select('B.groupId')
                ->leftJoin('ps_goods_group_community B', 'A.id = B.groupId')
                ->filterWhere(['=', 'B.communityId', $p['community_id']])
                ->orderBy('B.groupId desc')->scalar();
        }

        $totals = self::_search($p)->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::_search($p)->select('A.id, A.name, A.img, A.score, A.num')
            ->offset(($p['page'] - 1) * $p['rows'])
            ->limit($p['rows'])
            ->orderBy('A.id desc')->asArray()->all();


        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $use = PsDeliveryRecords::find()->where(['product_id' => $v['id']])->count();
                $v['surplus'] = $v['num'] - $use;
            }
        }

        $content = GoodsGroup::findOne($p['groupId'])->content;

        return ['list' => $list, 'totals' => (int)$totals, 'content' => $content];
    }

    // 可兑换积分
    public function integralSurplus($p)
    {
        $host = Yii::$app->modules['ali_small_lyl']->params['volunteer_host'];
        $get_url = $host."/internal/volunteer/score-info";
        $curl_data = ["sysUserId" => $p['user_id']];
        $r = json_decode(Curl::getInstance()->post($get_url, $curl_data), true);

        if ($r['code'] == 1) {
            return $r['data'];
        } else {
            return ['integralSurplus' => '1000'];
        }
    }

    // 文明志愿码
    public function codeInfo($p)
    {
        $host = Yii::$app->modules['ali_small_lyl']->params['volunteer_host'];
        $get_url = $host."/internal/volunteer/code-info";
        $curl_data = ["sysUserId" => $p['user_id']];
        $r = json_decode(Curl::getInstance()->post($get_url, $curl_data), true)['data'];

        $civilizationSurplus = !empty($r['civilizationSurplus']) ? $r['civilizationSurplus'] : '0';
        $img = !empty($r['img']) ? $r['img'] : '';

        return ['civilizationSurplus' => $civilizationSurplus, 'img' => $img];
    }

    // 判断志愿者是否注册过
    public function isRegister($p)
    {
        $host = Yii::$app->modules['ali_small_lyl']->params['volunteer_host'];
        $get_url = $host."/internal/volunteer/is-register";
        $curl_data = ["mobile" => $p['mobile']];
        $r = json_decode(Curl::getInstance()->post($get_url, $curl_data), true);

        if ($r['code'] == 1) {
            return $r['data'];
        } else {
            return ['isRegister' => false];
        }
    }
}
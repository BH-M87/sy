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
use app\models\GoodsGroupSelect;
use app\models\GoodsGroupCommunity;
use app\models\PsDeliveryRecords;
use service\property_basic\JavaService;
use app\models\PsEvent;
use app\models\PsEventComment;
use app\models\PsEventProcess;

class GoodsService extends BaseService
{
    // 事件详情
    public function eventShow($p) 
    {
        $event = new PsEvent();

        $m = PsEvent::find()->where(['id' => $p['id']])->asArray()->one();

        if (empty($m)) {
            throw new MyException('数据不存在!');
        }

        $m['statusMsg'] = $event->statusMsg[$m['status']];
        $m['event_time'] = date('Y-m-d H:i:s', $m['event_time']);
        $m['sourceMsg'] = $event->sourceMsg[$m['source']];
        $m['event_img'] = !empty($m['event_img']) ? explode(',', $m['event_img']) : '';

        $m['comment'] = PsEventComment::find()->select("create_at commentAt, comment commentMsg")
            ->where(['event_id' => $p['id']])->orderBy('create_at desc')->asArray()->all();
        if (!empty($m['comment'])) {
            foreach ($m['comment'] as &$v) {
                $v['commentAt'] = date('Y-m-d H:i', $v['commentAt']);
            }
        }

        $m['process'] = PsEventProcess::find()->where(['event_id' => $p['id']])->orderBy('create_at desc')->asArray()->all();
        if (!empty($m['process'])) {
            foreach ($m['process'] as &$v) {
                $v['create_at'] = date('Y-m-d H:i', $v['create_at']);
                $v['process_img'] = !empty($v['process_img']) ? explode(',', $v['process_img']) : '';
            }
        }

        $m['reject'] = PsEventProcess::find()->select('create_at rejectAt, content rejectMsg')->where(['event_id' => $p['id'], 'status' => 3])->orderBy('create_at desc')->asArray()->all();
        if (!empty($m['reject'])) {
            foreach ($m['reject'] as &$v) {
                $v['rejectAt'] = date('Y-m-d H:i', $v['rejectAt']);
            }
        }

        $m['close'] = PsEventProcess::find()->select('create_at closeAt, content closeMsg')->where(['event_id' => $p['id'], 'status' => 4])->orderBy('create_at desc')->asArray()->all();
        if (!empty($m['close'])) {
            foreach ($m['close'] as &$v) {
                $v['closeAt'] = date('Y-m-d H:i', $v['closeAt']);
            }
        }

        return $m;
    }

    // 事件签收
    public function eventSign($p) 
    {
        $model = PsEvent::findOne($p['id']);
        if (empty($model)) {
            throw new MyException('数据不存在!');
        }

        if ($model->status != 1) {
            throw new MyException('待处理状态才能签收!');
        }

        $model->status = 2;

        $trans = Yii::$app->getDb()->beginTransaction();

        try {
            $model->save();

            $process = new PsEventProcess();
            $process->event_id = $model->id;
            $process->status = 1;
            $process->create_at = time();
            $process->create_id = $p['user_id'];
            $process->create_name = $p['user_name'];
            $process->save();

            $trans->commit();
            return ['id' => $model->id];
        } catch (Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
    }

    // 事件办结
    public function eventFinish($p) 
    {
        $model = PsEvent::findOne($p['id']);
        if (empty($model)) {
            throw new MyException('数据不存在!');
        }

        if ($model->status != 2) {
            throw new MyException('处理中状态才能办结!');
        }

        if (empty($p['content'])) {
            throw new Exception("办结描述必填");
        }

        if (!empty($p['process_img']) && !is_array($p['process_img'])) {
            throw new Exception("办结图片数组格式");
        }

        $model->status = 3;
        $model->is_close = 1;

        $trans = Yii::$app->getDb()->beginTransaction();

        try {
            $model->save();

            $process = new PsEventProcess();
            $process->event_id = $model->id;
            $process->status = 2;
            $process->create_at = time();
            $process->create_id = $p['user_id'];
            $process->create_name = $p['user_name'];
            $process->content = $p['content'];
            $process->process_img = implode(',', $p['process_img']);
            $process->save();

            $trans->commit();
            return ['id' => $model->id];
        } catch (Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
    }

    // 事件数据分析
    public function eventData($p) 
    {}

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
                GoodsGroupSelect::deleteAll(['groupId' => $groupId]);
                foreach ($p['communityIdList'] as $k => $v) {
                    $selectOne = GoodsGroupSelect::find()->where(['code' => $v['id'], 'groupId' => $groupId])->one();

                    if (!empty($selectOne)) {
                        continue;
                    }

                    $select = new GoodsGroupSelect();
                    $select->groupId = $groupId;
                    $select->code = $v['id'];
                    $select->name = $v['name'];
                    $select->isCommunity = $v['isCommunity'];
                    $select->save();

                    if ($v['isCommunity'] == 2) { // 指定小区
                        $commParam[] = ['groupId' => $groupId, 'communityId' => $v['id']];
                    } else {
                        $idArr = explode(',', $v['id']);
                        // 获得所有小区
                        $javaParam['token'] = $p['token'];
                        $javaParam['pageNum'] = 1;
                        $javaParam['pageSize'] = 10000;
                        $javaParam['provinceCode'] = $idArr[0];
                        $javaParam['cityCode'] = $idArr[1];
                        $javaParam['districtCode'] = $idArr[2];
                        $javaParam['streetCode'] = $idArr[3];
                        $javaParam['villageCode'] = $idArr[4];
                        $javaResult = JavaService::service()->communityOperationList($javaParam)['list'];

                        if (!empty($javaResult)) {
                            foreach ($javaResult as $key => $val) {
                                $commParam[] = ['groupId' => $groupId, 'communityId' => $val['communityId']];
                            }
                        }
                    }
                }
                Yii::$app->db->createCommand()->batchInsert('ps_goods_group_community', ['groupId', 'communityId'], $commParam)->execute();
            } else {
                throw new MyException('兑换小区范围');
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
            $r['community'] = GoodsGroupSelect::find()->select('code id, name, isCommunity')->where(['groupId' => $p['id']])->asArray()->all();

            if (!empty($r['community'])) {
                foreach ($r['community'] as $k => $v) {
                    $communityName .= $v['name'] . ' ';
                }
            }
            
            $r['communityName'] = $communityName;

            $r['startAt'] = date('Y-m-d H:i:s', $r['startAt']);
            $r['endAt'] = date('Y-m-d H:i:s', $r['endAt']);

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
                $v['content'] =  strip_tags(str_replace("&lt;br&gt;&nbsp;","",$v['content']));
                $v['content'] = str_replace("&nbsp;","",$v['content']);

                $v['communityList'] =  GoodsGroupSelect::find()->select('code id, name communityName')->where(['groupId' => $v['id']])->asArray()->all();
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
        $param['describe'] = $p['describe'];

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

        $list = self::_search($p)->select('A.id, A.name, A.img, A.score, A.num, A.describe')
            ->offset(($p['page'] - 1) * $p['rows'])
            ->limit($p['rows'])
            ->orderBy('A.id desc')->asArray()->all();


        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $v['describe'] = $v['describe'] == '<p><br></p>' ? null : $v['describe'];
                $use = PsDeliveryRecords::find()->where(['product_id' => $v['id']])->count();
                $v['surplus'] = $v['num'] - $use;
            }
        }

        $content = GoodsGroup::findOne($p['groupId'])->content;

        return ['list' => $list, 'totals' => (int)$totals, 'content' => $content];
    }

    // 商品详情
    public function goodsContent($p)
    {
        if (empty($p['goods_id'])) {
            throw new MyException('商品ID不能为空');
        }

        $r = Goods::find()->select('describe')->where(['id' => $p['goods_id']])->asArray()->one();
        if (empty($r)) {
            throw new MyException('数据不存在');
        }

        return ['describe' => $r['describe'] ?? ''];
    }

    // 核销接口
    public function recordConfirm($p)
    {
        if (empty($p['record_id'])) {
            throw new MyException('兑换记录ID不能为空');
        }

        $m = PsDeliveryRecords::findOne($p['record_id']);
        if (empty($m)) {
            throw new MyException('兑换记录不存在');
        }

        if ($m->delivery_type == 1) {
            throw new MyException('兑换记录不需要核销');
        }

        if ($m->confirm_type == 2) {
            throw new MyException('请不要重复核销');
        }

        $member = JavaOfCService::service()->memberBase(['token' => $p['token']]);

        PsDeliveryRecords::updateAll(['confirm_type' => 2, 'confirm_at' => time(), 'confirm_name' => $member['trueName']], ['id' => $p['record_id']]);

        return true;
    }

    // 核销详情接口
    public function recordConfirmShow($p)
    {
        if (empty($p['record_id'])) {
            throw new MyException('兑换记录ID不能为空');
        }

        $m = PsDeliveryRecords::find()->select('product_id, product_name, product_img, integral, create_at, confirm_type, confirm_at, confirm_name, verification_qr_code')->where(['id' => $p['record_id']])->asArray()->one();
        if (empty($m)) {
            throw new MyException('兑换记录不存在');
        }
        
        $m['create_at'] = !empty($m['create_at']) ? date('Y/m/d H:i', $m['create_at']) : '';
        $m['confirm_at'] = !empty($m['confirm_at']) ? date('Y/m/d H:i', $m['confirm_at']) : '';
        $m['group_name'] = Goods::find()->alias('A')->select('B.name')->leftJoin('ps_goods_group B', 'B.id = A.groupId')->where(['A.id' => $m['product_id']])->scalar();

        return $m;
    }

    // 可兑换积分
    public function integralSurplus($p)
    {
        $host = Yii::$app->modules['ali_small_lyl']->params['volunteer_host'];
        $get_url = $host."/internal/volunteer/score-info";
        $curl_data = ["sysUserId" => $p['user_id']];
        $r = json_decode(Curl::getInstance()->post($get_url, $curl_data), true);

        error_log('[' . date('Y-m-d H:i:s', time()) . ']' . PHP_EOL . "请求url：".$get_url . "请求参数：".json_encode($curl_data) . PHP_EOL . '返回结果：' . json_encode($r).PHP_EOL, 3, \Yii::$app->getRuntimePath().'/logs/street.log');
        
        if ($r['code'] == 1) {
            return $r['data'];
        } else {
            return ['integralSurplus' => '0'];
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

        error_log('[' . date('Y-m-d H:i:s', time()) . ']' . PHP_EOL . "请求url：".$get_url . "请求参数：".json_encode($curl_data) . PHP_EOL . '返回结果：' . json_encode($r).PHP_EOL, 3, \Yii::$app->getRuntimePath().'/logs/street.log');

        return ['civilizationSurplus' => $civilizationSurplus, 'img' => $img];
    }

    // 判断志愿者是否注册过
    public function isRegister($p)
    {
        $host = Yii::$app->modules['ali_small_lyl']->params['volunteer_host'];
        $get_url = $host."/internal/volunteer/is-register";
        $curl_data = ["mobile" => $p['mobile']];
        $r = json_decode(Curl::getInstance()->post($get_url, $curl_data), true);
        
        error_log('[' . date('Y-m-d H:i:s', time()) . ']' . PHP_EOL . "请求url：".$get_url . "请求参数：".json_encode($curl_data) . PHP_EOL . '返回结果：' . json_encode($r).PHP_EOL, 3, \Yii::$app->getRuntimePath().'/logs/street.log');

        if ($r['code'] == 1) {
            return $r['data'];
        } else {
            return ['isRegister' => false];
        }
    }

    // 判断是否加入过小区队伍
    public function isInTeam($p)
    {
        $host = Yii::$app->modules['ali_small_lyl']->params['volunteer_host'];
        $get_url = $host."/internal/volunteer/is-in-team";
        $curl_data = ["sysUserId" => $p['user_id'], 'teamId' => $p['teamId']];
        $r = json_decode(Curl::getInstance()->post($get_url, $curl_data), true);

        error_log('[' . date('Y-m-d H:i:s', time()) . ']' . PHP_EOL . "请求url：".$get_url . "请求参数：".json_encode($curl_data) . PHP_EOL . '返回结果：' . json_encode($r).PHP_EOL, 3, \Yii::$app->getRuntimePath().'/logs/street.log');

        if ($r['code'] == 1) {
            return $r['data'];
        } else {
            return ['isInTeam' => false];
        }
    }
}
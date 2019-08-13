<?php
/**
 * 商家服务
 * @author shenyang
 * @date 2017-05-12
 */

namespace service\alipay;

use common\core\PsCommon;
use app\models\PsShop;
use app\models\PsShopCommunity;
use app\models\PsShopDiscount;
use app\models\PsShopIncome;
use app\models\PsShopIntention;
use app\models\PsShopMessage;
use app\models\PsShopOrders;
use app\models\PsShopTransaction;
use app\models\PsShopWechat;
use service\BaseService;

Class ShopService extends BaseService
{

    public $statuss = [
        1 => ['id' => 1, 'name' => '上线'],
        2 => ['id' => 2, 'name' => '下线'],
    ];

    public $referers = [
        1 => ['id' => 1, 'name' => '物业'],
        2 => ['id' => 2, 'name' => '平台'],
    ];

    public $intentionStatus = [
        1 => ['id' => 1, 'name' => '已受理'],
        2 => ['id' => 2, 'name' => '未受理'],
    ];

    //交易类型
    public $transactionTypes = [
        1 => '收银',
        2 => '收益',
        3 => '结算',
    ];

    /**
     * 公共参数
     * @return mixed
     */
    public function getCommon()
    {
        $data['referers'] = array_values($this->referers);
        $data['status'] = array_values($this->statuss);
        return $data;
    }

    /**
     * 针对单行数据进行数据格式化
     * @param $data
     * @return array
     */
    private function _formatData($data, $isReplace = true)
    {
        if (!$data) {
            return [];
        }
        $data['create_time'] = !empty($data['create_at']) ? date('Y-m-d H:i:s', $data['create_at']) : '';
        $data['community'] = !empty($data['community']) ? $data['community'] : [];
        $data['status'] = !empty($data['status']) ? PsCommon::get($this->statuss, $data['status'], []) : [];
        $data['referer'] = !empty($data['referer']) ? PsCommon::get($this->referers, $data['referer'], []) : [];
        if (isset($data['phone']) && $isReplace) {
            $data['phone'] = PsCommon::hideMobile($data['phone']);
        }
        return $data;
    }

    /**
     * 商家列表
     */
    public function getShopLists($params, $page, $pageSize)
    {
        $data = PsShop::find()
            ->select('id, name, phone, contact_tel, contactor, alipay_account, referer, status')
            ->andFilterWhere([
                'id' => PsCommon::get($params, 'id'),
                'status' => PsCommon::get($params, 'status'),
                'contact_tel' => PsCommon::get($params, 'contact_tel'),
            ])
            ->andFilterWhere(['like', 'name', PsCommon::get($params, 'name')])
            ->andFilterWhere(['like', 'contactor', PsCommon::get($params, 'contactor')])
            ->orderBy('id desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $result[] = $this->_formatData($v);
        }
        return $result;
    }

    /**
     * 商家列表查询条数
     * @param $params
     */
    public function getShopCount($params)
    {
        return PsShop::find()
            ->andFilterWhere([
                'id' => PsCommon::get($params, 'id'),
                'status' => PsCommon::get($params, 'status'),
                'contact_tel' => PsCommon::get($params, 'contact_tel'),
            ])
            ->andFilterWhere(['like', 'name', PsCommon::get($params, 'name')])
            ->andFilterWhere(['like', 'contactor', PsCommon::get($params, 'contactor')])
            ->count();
    }

    /**
     * 商家详情
     * @param $id
     */
    public function showShop($id)
    {
        $data = PsShop::find()
            ->with('discount')
            ->with('community')
            ->where(['id' => $id])
            ->asArray()->one();
        //编辑不隐藏手机号码
        return $this->_formatData($data, false);
    }

    /**
     * 新增商家
     * @param $params
     */
    public function createShop($params)
    {
        $db = \Yii::$app->getDb();
        $transaction = $db->beginTransaction();
        try {
            list($shopId, $error) = $this->_saveShop($params);
            if ($error) {
                throw new \Exception($error);
            }
            $r = $this->_saveShopCommunity($shopId, $params);
            if ($r !== true) {
                throw new \Exception($r);
            }
            $flag = $this->_saveShopDiscount($shopId, $params);
            if ($flag !== true) {
                throw new \Exception($flag);
            }
            $transaction->commit();
            return $this->success();
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /**
     * 编辑商家
     * @param $params
     * @return array
     */
    public function editShop($params)
    {
        $db = \Yii::$app->getDb();
        $transaction = $db->beginTransaction();
        try {
            list($shopId, $error) = $this->_saveShop($params);
            if ($error) {
                throw new \Exception($error);
            }
            $r = $this->_saveShopCommunity($shopId, $params);
            if ($r !== true) {
                throw new \Exception($r);
            }
            $flag = $this->_saveShopDiscount($shopId, $params);
            if ($flag !== true) {
                throw new \Exception($flag);
            }
            $transaction->commit();
            return $this->success();
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /**
     * 保存到shop表
     * @param $params
     */
    private function _saveShop($params)
    {
        if (!empty($params['id'])) {
            $model = PsShop::findOne($params['id']);
        } else {
            $model = new PsShop();
        }
        if (!$model) {
            return [0, '数据不存在'];
        }
        $params['nonce'] = $this->getRandCode();
        $model->load($params, '');
        if ($model->validate()) {
            if ($model['shop_type'] == 2 && empty($params['alipay_account'])) {
                return [0, '企业支付宝帐号必填'];
            }
            if ($model->save()) {
                return [$model->id, ''];
            }
        }
        $errors = array_values($model->getFirstErrors());
        $error = !empty($errors[0]) ? $errors[0] : '系统错误';
        return [0, $error];
    }

    //随机码
    public function getRandCode()
    {
        $str = '';
        for ($i = 0; $i < 8; $i++) {
            $str .= chr(mt_rand(33, 126));
        }
        return md5(uniqid(md5(microtime(true)), true) . $str);
    }

    /**
     * 保存商家小区关联表
     * @param $params
     */
    private function _saveShopCommunity($shopId, $params)
    {
        if (!$shopId) return '商家ID不能为空';
        if (empty($params['community_ids'])) {
            return '关联小区不能为空';
        }
        PsShopCommunity::deleteAll(['shop_id' => $shopId]);
        $a = $b = [];
        foreach ($params['community_ids'] as $cid) {
            $a[] = $shopId;
            $b[] = $cid;
        }
        $data['shop_id'] = $a;
        $data['community_id'] = $b;
        $rows = PsShopCommunity::model()->batchInsert($data);
        return $rows ? true : '商家关联小区失败';
    }

    /**
     * 保存到shop_discount表
     * @param $params
     */
    private function _saveShopDiscount($shopId, $params)
    {
        if (!$shopId) return true;
        //先删除原始数据
        PsShopDiscount::deleteAll(['shop_id' => $shopId]);
//        if(empty($params['discount']) || empty($params['discount']['discount_type'])) {
        //2017-12-11陈科浪修改，不管什么类型备注内容都要有用
        if (empty($params['discount'])) {
            return true;
        }
        $type = $params['discount']['discount_type'];
        $params['discount']['shop_id'] = $shopId;

        $model = new PsShopDiscount();
        $model->load($params['discount'], '');
        if ($type == 1) {
            $model->setScenario('full_off');
        } elseif ($type == 2) {
            $model->setScenario('discount');
        } elseif ($type == 3) {
            $model->setScenario('direct_off');
        }
        if (!$model->save()) {
            $errors = array_values($model->getFirstErrors());
            return !empty($errors[0]) ? $errors[0] : '系统错误';
        }
        return true;
    }

    /**
     * 上下线
     * @param $id
     * @param $status
     * @return string
     */
    public function upShop($id, $status)
    {
        $shop = PsShop::findOne($id);
        if (!$shop) {
            return '数据不存在';
        }
        if ($shop->status == $status) {
            return '无法重复修改';
        }
        $shop->status = $status;
        return $shop->save() ? true : '系统错误';
    }

    /**
     * 获取上线的商家
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getOnlineShop($id)
    {
        return PsShop::find()->where(['id' => $id, 'status' => 1])->asArray()->one();
    }

    /**
     * 商家余额列表
     * @param $params
     */
    public function getBalanceLists($params, $page, $pageSize)
    {
        $shopIds = $this->getShopIdsByCid(PsCommon::get($params, 'community_id'));

        $data = PsShop::find()->select('id, name, balance')
            ->with(['community'])
            ->andFilterWhere(['id' => $shopIds])
            ->andFilterWhere(['like', 'name', PsCommon::get($params, 'name')])
            ->orderBy('id desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $v['community'] = !empty($v['community']) ? $v['community'] : [];
            $result[] = $v;
        }
        return $result;
    }

    public function getShopIdsByCid($community_id)
    {
        if (!empty($community_id)) {
            $shopIds = PsShopCommunity::find()->where(['community_id' => $community_id])->select('shop_id')->column();
            if (!empty($shopIds)) {
                return $shopIds;
            } 
            return ['-1'];
        } 
        return [];
    }

    /**
     * 商家余额总数
     * @param $params
     * @return int|string
     */
    public function getBalanceCount($params)
    {
        $shopIds = $this->getShopIdsByCid(PsCommon::get($params, 'community_id'));
        return PsShop::find()
            ->andFilterWhere(['id' => $shopIds])
            ->andFilterWhere(['like', 'name', PsCommon::get($params, 'name')])
            ->count();
    }

    /**
     * 意向商家公共变量
     */
    public function getIntentionCommon()
    {
        $data['status'] = array_values($this->intentionStatus);
        return $data;
    }

    /**
     * 意向商家列表(活动预约)
     * @param $params
     * @param $page
     * @param $pageSize
     */
    public function getIntentionLists($params, $page, $pageSize)
    {
        $data = PsShopIntention::find()
            ->select('id, community_id, name, phone, status, contactor')
            ->with('community')
            ->andFilterWhere([
                'community_id' => PsCommon::get($params, 'community_id'),
                'phone' => PsCommon::get($params, 'phone'),
                'status' => PsCommon::get($params, 'status')
            ])->andFilterWhere(['like', 'name', PsCommon::get($params, 'name')])
            ->andFilterWhere(['like', 'contactor', PsCommon::get($params, 'contactor')])
            ->orderBy('id desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $v['community'] = !empty($v['community']) ? $v['community'] : [];
            $v['status'] = PsCommon::get($this->intentionStatus, $v['status'], []);
            $result[] = $v;
        }
        return $result;
    }

    /**
     * 意向商家总数
     * @param $params
     * @return int|string
     */
    public function getIntentionCount($params)
    {
        return PsShopIntention::find()
            ->andFilterWhere([
                'community_id' => PsCommon::get($params, 'community_id'),
                'contactor' => PsCommon::get($params, 'contactor'),
                'phone' => PsCommon::get($params, 'phone'),
                'status' => PsCommon::get($params, 'status')
            ])->andFilterWhere(['like', 'name', PsCommon::get($params, 'name')])
            ->count();
    }

    /**
     * 设置为已处理
     * @param $id
     * @return int
     */
    public function intentionAccept($id)
    {
        return PsShopIntention::updateAll(['status' => 1], ['id' => $id]);
    }

    /**
     * 商家营收列表
     * @param $params
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function getBusinessLists($shopId, $params, $page, $pageSize)
    {
        if (!$shopId) return [];
        $start = PsCommon::get($params, 'start');
        $end = PsCommon::get($params, 'end');
        $start = $start ? strtotime($start . ' 00:00:00') : '';
        $end = $end ? strtotime($end . ' 23:59:59') : '';
        $data = PsShopOrders::find()
            ->select('buyer_login_id, id, pay_at, pay_price, note')
            ->andFilterWhere(['>=', 'pay_at', $start])
            ->andFilterWhere(['<=', 'pay_at', $end])
            ->andWhere(['shop_id' => $shopId, 'pay_status' => 1])
            ->orderBy('id desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $v['pay_time'] = date('Y-m-d H:i:s', $v['pay_at']);
            $result[] = $v;
        }
        return $result;
    }

    /**
     * 商家营收记录总数
     * @param $params
     */
    public function getBusinessCount($shopId, $params)
    {
        if (!$shopId) return [];
        $start = PsCommon::get($params, 'start');
        $end = PsCommon::get($params, 'end');
        $start = $start ? strtotime($start . ' 00:00:00') : '';
        $end = $end ? strtotime($end . ' 23:59:59') : '';
        return PsShopOrders::find()
            ->andFilterWhere(['>=', 'pay_at', $start])
            ->andFilterWhere(['<=', 'pay_at', $end])
            ->andWhere(['shop_id' => $shopId, 'pay_status' => 1])
            ->count();
    }

    /**
     * 获取商家总营收
     * @param $shopId
     */
    public function getShopBusiness($shopId)
    {
        $shop = PsShop::find()->select('id, name, shop_type, balance')->where(['id' => $shopId])->one();
        if (!$shopId) {
            return [];
        }
        if ($shop['shop_type'] == 2) {
            $sum = 0;
        } else {
            $sum = PsShopOrders::find()
                ->where(['shop_id' => $shopId, 'pay_status' => 1])
                ->sum('pay_price');
        }
        $result = [
            'id' => $shopId,
            'name' => $shop['name'],
            'balance' => $shop['balance'],
            'business' => $sum
        ];
        return $result;
    }

    /**
     * 获取商家总收益
     * @param $shopId
     * @return array
     */
    public function getShopIncome($shopId)
    {
        $shop = PsShop::find()->select('id, name, shop_type, balance')->where(['id' => $shopId])->one();
        if (!$shopId) {
            return [];
        }
        if ($shop['shop_type'] == 2) {
            $sum = 0;
        } else {
            $sum = PsShopIncome::find()
                ->where(['shop_id' => $shopId])
                ->sum('money');
        }
        $result = [
            'id' => $shopId,
            'name' => $shop['name'],
            'balance' => $shop['balance'],
            'income' => $sum
        ];
        return $result;
    }

    /**
     * 商家收益列表
     * @param $shopId
     * @param $params
     * @param $page
     * @param $pageSize
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getIncomeLists($shopId, $params, $page, $pageSize)
    {
        if (!$shopId) return [];
        $start = PsCommon::get($params, 'start');
        $end = PsCommon::get($params, 'end');
        $start = $start ? strtotime($start . ' 00:00:00') : '';
        $end = $end ? strtotime($end . ' 23:59:59') : '';
        $data = PsShopIncome::find()
            ->select('income_day, id, info, money')
            ->andFilterWhere(['>=', 'create_at', $start])
            ->andFilterWhere(['<=', 'create_at', $end])
            ->andWhere(['shop_id' => $shopId])
            ->orderBy('id desc')
            ->offset(($page - 1) * $pageSize)
            ->asArray()->all();
        return $data;
    }

    /**
     * 商家收益记录数
     * @param $shopId
     * @param $params
     * @return array|int|string
     */
    public function getIncomeCount($shopId, $params)
    {
        if (!$shopId) return [];
        $start = PsCommon::get($params, 'start');
        $end = PsCommon::get($params, 'end');
        $start = $start ? strtotime($start . ' 00:00:00') : '';
        $end = $end ? strtotime($end . ' 23:59:59') : '';
        return PsShopIncome::find()
            ->andFilterWhere(['>=', 'create_at', $start])
            ->andFilterWhere(['<=', 'create_at', $end])
            ->andWhere(['shop_id' => $shopId])
            ->count();
    }

    /**
     * 获取商家绑定的openId
     * @param $shopId
     */
    public function getOpenId($shopId)
    {
        return PsShopWechat::find()->select('openid')->where(['shop_id' => $shopId])->scalar();
    }

    /**
     * 保存待发送消息
     */
    public function saveMessage($shopId, $type, $id)
    {
        if (!$openId = $this->getOpenId($shopId)) {
            //未绑定
            return false;
        }
        $model = new PsShopMessage();
        $model->shop_id = $shopId;
        $model->openid = $openId;
        $model->type = $type;
        $model->obj_id = $id;
        $model->create_at = time();
        $model->status = 3;
        $model->send_at = 0;
        return $model->save();
    }

    /**
     * 商家是否绑定过微信openId
     * @param $openId
     */
    public function getShopByOpenId($openId)
    {
        $cacheKey = 'shop-openid:' . $openId;
//        return $this->cache($cacheKey,3600, function ()use($openId){
        return PsShopWechat::find()->where(['openid' => $openId])->asArray()->one();
//        });
    }

    /**
     * 商家解绑
     * @param $openId
     * @return int
     */
    public function unBind($openId)
    {
        return PsShopWechat::deleteAll(['openid' => $openId]);
    }

    /**
     * 绑定
     * @param $openId
     * @param $shopId
     */
    public function saveShopWechat($openId, $phone)
    {
        //绑定过，直接返回shop_id
        $one = $this->getShopByOpenId($openId);
        if ($one) return $one['shop_id'];
        if (!$shopId = $this->getShopIdByPhone($phone)) return false;
        $model = new PsShopWechat();
        $model->shop_id = $shopId;
        $model->openid = $openId;
        $model->phone = $phone;
        $model->create_at = time();
        $log = $model->toArray();
        if ($model->save()) {
            $log['status'] = 1;
            return $shopId;
        }
        $log['status'] = 0;
        return 0;
    }

    /**
     * 根据绑定手机号查询商家ID
     * @param $phone
     * @return bool|false|null|string
     */
    public function getShopIdByPhone($phone)
    {
        if (!$phone) return false;
        return PsShop::find()->where(['phone' => $phone])->select('id')->scalar();
    }

    /**
     * 获取商家的交易记录
     * @param $shopId
     */
    public function getTransactions($shopId, $params = [])
    {
        $data = PsShopTransaction::find()
            ->where(['shop_id' => $shopId])
            ->andFilterWhere(['type' => PsCommon::get($params, 'type')])
            ->andFilterWhere(['>=', 'create_at', PsCommon::get($params, 'time_start')])
            ->orderBy('id desc')
            ->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $v['type_name'] = $this->getTransType($v['type']);
            $v['create_time'] = date('Y-m-d H:i:s', $v['create_at']);
            $result[] = $v;
        }
        return $result;
    }

    /**
     * 商家展示信息
     * @param $shopId
     * @return static
     */
    public function getShop($shopId)
    {
        if (!$shopId) return [];
        $data = PsShop::find()->select('id, logo_url, name, position, contactor, contact_tel, balance, business, income')
            ->where(['id' => $shopId])->asArray()->one();
        return $data;
    }

    /**
     * 交易类型
     * @param $type
     * @return mixed|string
     */
    public function getTransType($type)
    {
        return !empty($this->transactionTypes[$type]) ? $this->transactionTypes[$type] : '未知';
    }

    //上线商家，下拉列表
    public function getSimpleList()
    {
        return PsShop::find()->select('id, name')
            ->where(['status' => 1])
            ->orderBy('id desc')
            ->asArray()->all();
    }
}
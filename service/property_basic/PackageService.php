<?php
/**
 * 包裹服务
 * @author shenyang
 * @date 2017/11/26
 */
namespace service\property_basic;

use Yii;
use common\core\PsCommon;
use app\models\PsCommunityRoominfo;
use app\models\PsPackage;
use service\common\SmsService;
use service\BaseService;

Class PackageService extends BaseService
{
    const STATUS_UNPICK = 1;//未领取
    const STATUS_PICKED = 2;//已领取
    //包裹状态
    public $status = [
        1 => ['id' => 1, 'name' => '待领取'],
        2 => ['id' => 2, 'name' => '已领取'],
    ];

    //快递公司
    public $delivery = [
        1 => ['id' => 1, 'name' => '顺丰'],
        2 => ['id' => 2, 'name' => '圆通'],
        3 => ['id' => 3, 'name' => '申通'],
        4 => ['id' => 4, 'name' => '韵达'],
        5 => ['id' => 5, 'name' => '中通'],
        6 => ['id' => 6, 'name' => '天天'],
        7 => ['id' => 7, 'name' => 'EMS'],
        8 => ['id' => 8, 'name' => '优速'],
        9 => ['id' => 9, 'name' => '宅急送'],
        10 => ['id' => 10, 'name' => '速尔'],
        11 => ['id' => 11, 'name' => '百世'],
        12 => ['id' => 12, 'name' => '京东'],
        13 => ['id' => 13, 'name' => '德邦'],
        14 => ['id' => 14, 'name' => '其他物流'],
    ];

    //包裹备注
    public $note = [
        1 => ['id' => 1, 'name' => '小包裹（单手可拿）'],
        2 => ['id' => 2, 'name' => '大包裹（需要双手才可拿）'],
        3 => ['id' => 3, 'name' => '多包裹（数量大于1的包裹）'],
        4 => ['id' => 4, 'name' => '重包裹（单人无法搬动）'],
        5 => ['id' => 5, 'name' => '文件'],
    ];

    /**
     * 查询搜索
     * @param $params
     */
    private function _search($params)
    {
        $start = PsCommon::get($params, 'time_start');
        $start = $start ? strtotime($start) : null;
        $end = PsCommon::get($params, 'time_end');
        $end = $end ? strtotime($end.' 23:59:59') : null;
        return PsPackage::find()->alias('t')
            ->leftJoin(['r' => PsCommunityRoominfo::tableName()], 't.room_id=r.id')
            ->where(['t.community_id' => PsCommon::get($params, 'community_id')])
            ->andFilterWhere([
                't.mobile' => PsCommon::get($params, 'mobile'),
                't.delivery_id' => PsCommon::get($params, 'delivery_id'),
                't.tracking_no' => PsCommon::get($params, 'tracking_no'),
                't.status' => PsCommon::get($params, 'status'),
                'r.group' => PsCommon::get($params, 'group'),
                'r.building' => PsCommon::get($params, 'building'),
                'r.unit' => PsCommon::get($params, 'unit'),
                'r.room' => PsCommon::get($params, 'room'),
            ])
            ->andFilterWhere(['like', 't.receiver', PsCommon::get($params, 'receiver')])
            ->andFilterWhere(['>=', 't.create_at', $start])
            ->andFilterWhere(['<=', 't.create_at', $end]);
    }

    /**
     * 后台列表
     * @param $params
     * @param $page
     * @param $pageSize
     */
    public function getList($params, $page, $pageSize)
    {
        $data = $this->_search($params)
            ->select('t.*, r.group, r.building, r.unit, r.room')
            ->orderBy('id desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $v['room_info'] = $v['room'] ? $v['group'].$v['building'].$v['unit'].$v['room'] : '';
            $v['delivery'] = PsCommon::get($this->delivery, $v['delivery_id'], []);
            $v['note'] = PsCommon::get($this->note, $v['note'], []);
            $v['status'] = PsCommon::get($this->status, $v['status']);
            $v['create_at'] = date('Y-m-d H:i', $v['create_at']);
            $v['receive_at'] = $v['receive_at'] ? date('Y-m-d H:i', $v['receive_at']) : '';
            $result[] = $v;
        }
        return $result;
    }

    /**
     * 后台查询总数
     * @param $params
     */
    public function getListCount($params)
    {
        return intval($this->_search($params)->count());
    }

    /**
     * 新增包裹
     * @param $params
     */
    public function create($params)
    {
        return $this->_save($params);
    }

    //发送模版消息
    private function _sendLifeMessage($toUid, $life, $data)
    {
        $content = "快递提醒\n\n您有包裹已经投递到小区包裹服务站\n\n快递公司： {key0}\n快递单号： {key1}\n备注信息： 该包裹为{key2}，请及时去小区服务站领取";
        $keys = $values = [];
        foreach($data as $k=>$v) {
            $keys[] = '{key'.$k.'}';//{key0} {key1}
            $values[] = $v;
        }
        $str = str_replace($keys, $values, $content);
        $biz = [
            'to_user_id' => $toUid,
            'msg_type' => 'text',
            'text' => [
                'content' => $str,
            ],
        ];
        if ($life['add_type'] == 1) {
            $aliService = new AlipayLifeService($life['app_id'], $life['mechart_private_key'], $life['alipay_public_key']);
        } else {
            $alipayPublicKey = file_get_contents(Yii::$app->params['isv_alipay_public_key_file']);
            $rsaPrivateKey   = file_get_contents(Yii::$app->params['isv_rsa_private_key_file']);
            $aliService = new IsvLifeService(Yii::$app->params['isv_app_id'], $life['mechart_pid'], $life['app_auth_token'] ,$alipayPublicKey, $rsaPrivateKey);
        }
        $result = $aliService->sendCustomMsg($biz);
        if (is_array($result) && $result['code'] == 10000) {
            return $this->success();
        } else {
            $err = !empty($result['sub_code']) ? $result['sub_code'] : 'Error';
            return $this->failed('发送生活号消息失败: '.$err);
        }
    }

    /**
     * 编辑包裹
     * @param $params
     */
    public function edit($id, $params)
    {
        return $this->_save($params, $id);
    }

    private function _save($params, $id = false)
    {
        if (!$id) {
            $model = new PsPackage();
            $model->status = self::STATUS_UNPICK;
        } else {
            $model = PsPackage::findOne($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            if ($model->status == self::STATUS_PICKED) {
                return $this->failed('包裹已被领取，无法编辑');
            }
        }
        $model->community_id = PsCommon::get($params, 'community_id');
        if (!$model->community_id) {
            return $this->failed('小区ID不能为空');
        }
        $model->delivery_id = PsCommon::get($params, 'delivery_id');

        $model->tracking_no = PsCommon::get($params, 'tracking_no');
        $model->receiver = PsCommon::get($params, 'receiver');
        $model->mobile = PsCommon::get($params, 'mobile');
        $model->note = PsCommon::get($params, 'note');

        $group = PsCommon::get($params, 'group');
        $building = PsCommon::get($params, 'building');
        $unit = PsCommon::get($params, 'unit');
        $room = PsCommon::get($params, 'room');
        if ($group && $building && $unit && $room) {
            $roomId = RoomService::service()->findRoom($model->community_id, $group, $building, $unit, $room);
            if ($roomId) {
                $model->room_id = $roomId['id'];
            }
        }
        $model->create_at = time();
        if (!$model->validate()) {
            return $this->failed($this->getError($model));
        }
        if (!in_array($model->delivery_id, array_keys($this->delivery))) {
            return $this->failed('快递公司不在取值范围');
        }
        if (!in_array($model->note, array_keys($this->note))) {
            return $this->failed('快递备注不在取值范围');
        }
        if (!$model->save()) {
            return $this->failed($this->getError($model));
        }
        //发短信
        $sendData[] = $this->delivery[$model->delivery_id]['name'];//快递公司
        $sendData[] = $model->tracking_no;//运单号
        $sendData[] = $this->note[$model->note]['name'];
        //
        if (YII_ENV == 'master' || YII_ENV == 'test' || YII_ENV == 'release') {//本地环境不发
            SmsService::service()->init(21, $model->mobile)->send($sendData);
            $member = MemberService::service()->getInfo($model->mobile);
            if ($member) {
                $auth = ResidentService::service()->isAuth($member['id'], $model->community_id);
                if ($auth) {//已认证
                    $toUids = MemberService::service()->getAliIdByMobile($model->mobile);
                    $life = LifeNoService::service()->getLifeByCommunity($model->community_id);//所有的生活号
                    if ($toUids && $life) {
                        foreach ($toUids as $toUid) {
                            $this->_sendLifeMessage($toUid, $life, $sendData);
                        }
                    }
                }
            }
        }
        return $this->success();
    }

    /**
     * 包裹详情
     * @param $params
     */
    public function detail($id, $communityId)
    {
        $data = PsPackage::find()->alias('t')
            ->select('t.*, r.group, r.building, r.unit, r.room')
            ->leftJoin(['r' => PsCommunityRoominfo::tableName()], 't.room_id=r.id')
            ->where(['t.id' => $id, 't.community_id' => $communityId])
            ->asArray()->one();
        if ($data) {
            $data['delivery'] = PsCommon::get($this->delivery, $data['delivery_id'], []);
            $data['note'] = PsCommon::get($this->note, $data['note'], []);
            $data['status'] = PsCommon::get($this->status, $data['status']);
        }
        return $data;
    }

    /**
     * 确定领取
     * @param $id
     * @param $communityId
     * @return array
     */
    public function receive($id, $communityId)
    {
        $model = PsPackage::findOne(['id' => $id, 'community_id' => $communityId]);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        if ($model->status != self::STATUS_UNPICK) {
            return $this->failed('已领取过，无法重复领取');
        }
        $model->status = self::STATUS_PICKED;
        $model->receive_at = time();
        $model->save();
        return $this->success();
    }

    /**
     * 未领取快递数
     */
    public function unPickCount($mobile, $communityId)
    {
        return PsPackage::find()
            ->where(['mobile' => $mobile, 'community_id' => $communityId, 'status' => self::STATUS_UNPICK])->count();
    }
}

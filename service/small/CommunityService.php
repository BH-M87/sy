<?php
/**
 * 吴建阳
 * 2019-4-30 社区评分&邻里互动 
 * 2019-6-5 社区曝光台
 */ 
namespace service\small;

use Yii;

use yii\db\Exception;

use common\core\F;
use common\core\Curl;
use common\core\PsCommon;

use service\BaseService;
use service\rbac\OperateService;
use service\common\AreaService;
use service\message\MessageService;
use service\property_basic\JavaOfCService;

use app\models\PsCommunityComment;
use app\models\PsCommunityCommentDetail;
use app\models\PsCommunityCircle;
use app\models\PsCommunityCircleImage;
use app\models\PsCommunityCirclePraise;
use app\models\PsSensitiveWord;
use app\models\PsCommunityExposure;
use app\models\PsCommunityExposureImage;
use app\models\ParkingCarport;
use app\models\EventTemplate;

Class CommunityService extends BaseService
{
    // java路由
    public $urlJava= [
        'addEvent' => '/eventDing/addEvent', // 新增曝光台事件
        'dealDetail' => '/community/communityExposure/getExposureDealWithDetailById' // 处理结果
    ];

    // -----------------------------------     社区曝光台   ------------------------------
    
    // 曝光台 发布
    public function exposureAdd($p)
    {
        // 敏感词检测
        $word = self::_sensitiveWord($p['describe']);
        if (!empty($word)) {
            return $this->failed($word);
        }

        if (!is_array($p['image_url'])) {
            return $this->failed('图片不是数组格式！');
        }

        $imageLength = count($p['image_url']);
        
        if (!($imageLength >= 1 && $imageLength <= 5)) {
            return $this->failed('图片最少一张最多五张！');
        }

        // 查询业主
        $member = PsAppMember::find()->alias('A')->leftJoin('ps_member B', 'B.id = A.member_id')
            ->select('B.*')->where(['A.app_user_id' => $p['user_id']])->asArray()->one();
        if (!$member) {
            return $this->failed('业主不存在！');
        }

        $roomUser = PsRoomUser::find()->select('status')->where(['member_id' => $member['id'], 'room_id' => $p['room_id']])->orderBy("status")->asArray()->one();

        if ($roomUser['status'] != 2) {
            return $this->failed('房屋未认证！');
        }

        $roomInfo = PsCommunityRoominfo::find()->alias('A')
            ->leftJoin('ps_community B', 'B.id = A.community_id')->select('A.id, A.community_id')
            ->where(['A.id' => $p['room_id']])->asArray()->one();
        if (!$roomInfo) {
            return $this->failed('房屋不存在！');
        }

        $community = PsCommunityModel::findOne($roomInfo['community_id']);
        $appUser = PsAppUser::findOne($p['user_id']);

        $p['app_user_id'] = $p['user_id'];
        $p['avatar'] = !empty($appUser->avatar) ? $appUser->avatar : 'http://static.zje.com/2019041819483665978.png';
        $p['name'] = $member['name'];
        $p['mobile'] = $member['mobile'];
        $p['community_id'] = $roomInfo['community_id'];
        $p['event_community_no'] = $community->event_community_no;

        $trans = Yii::$app->getDb()->beginTransaction();

        try {
            $model = new PsCommunityExposure(['scenario' => 'add']);

            if (!$model->load($p, '') || !$model->validate()) {
                return $this->failed($this->getError($model));
            }

            if (!$model->save()) {
                return $this->failed($this->getError($model));
            }

            if (!empty($p['image_url'])) {
                foreach ($p['image_url'] as $k => $v) {
                    if (empty($v)) {
                        return $this->failed('请上传图片！');
                    }
                    $image = new PsCommunityExposureImage();
                    $image->community_exposure_id = $model->attributes['id'];
                    $image->image_url = $v;
                    $image->type = 1;
                    $image->save();
                }
            }

            // 处理结果 调Java接口
            $data = [
                'title' => $p['title'],
                'description' => $p['describe'], 
                'eventFrom' => 2,
                'eventTime' => date('Y-m-d H:i:s', time()),
                'eventType' => $p['event_child_type_id'],
                'imageUrl' => $p['image_url'],
                'reportAddress' => $p['address'],
                'address' => $p['address'],
                'userId' => $member['id'],
                'xqName' => $community->name,
                'xqOrgCode' => $community->event_community_no,
            ];
            $event = Curl::getInstance()->post(Yii::$app->params['java_domain'].$this->urlJava['addEvent'], json_encode($data,JSON_UNESCAPED_UNICODE), true);
            \Yii::info("add-event".'request:'.json_encode($data,JSON_UNESCAPED_UNICODE).'---result'.$event,'smallapp');
            $model->event_no = json_decode($event, true)['data'];
            $model->save();

            $trans->commit();
            return $this->success(['id' => $model->attributes['id']]);
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    // 曝光台 列表
    public function exposureList($param)
    {
        $page = !empty($param['page']) ? $param['page'] : 1;
        $rows = !empty($param['rows']) ? $param['rows'] : 5;
        $user_id = PsCommon::get($param,'user_id');

        if ($user_id) { // 我的曝光
            // 查询业主
            $member = PsAppMember::find()->alias('A')->leftJoin('ps_member B', 'B.id = A.member_id')
                ->select('B.*')->where(['A.app_user_id' => $param['user_id']])->asArray()->one();
            if (!$member) {
                return $this->failed('业主不存在！');
            }

            $roomUser = PsRoomUser::find()->select('status')->where(['member_id' => $member['id'], 'room_id' => $param['room_id']])->asArray()->one();

            if ($roomUser['status'] != 2) {
                return $this->success(['list' => [], 'total' => 0]);
            }

            $param['status'] = $param['status'] == 3 ? 3 : [1,2]; // 待处理 显示待处理和已处理数据

            unset($param['room_id']); // 判断房屋是否认证用 筛选不用这个参数
        }
        $m = $this->_searchExposure($param)
            ->orderBy('A.created_at desc')
            ->offset(($page - 1) * $rows)->limit($rows)->asArray()->all();
        $list = [];
        $avatar = [];
        if (!empty($m)) {
            foreach ($m as $k => &$v) {
                $v['status_msg'] = PsCommunityExposure::status($v['status']);
                $v['parent_type_desc'] = EventTemplate::findOne($v['event_parent_type_id'])->title;
                $v['child_type_desc'] = EventTemplate::findOne($v['event_child_type_id'])->title;
                $v['type_desc'] = $v['parent_type_desc'];
                $image_1 = PsCommunityExposureImage::find()->select('image_url')->where(['community_exposure_id' => $v['id'], 'type' => 1])->asArray()->all();
                $v['image_url'] = F::ossImagePath(array_column($image_1, 'image_url'));
                $v['name'] =  CommunityService::service()->_hideName($v['name']);

                // 处理结果 调Java接口
                $event = Curl::getInstance()->post(Yii::$app->params['java_domain'].$this->urlJava['dealDetail'], json_encode(['exposureId' => $v['id']]), true);
                $res = json_decode($event, true);
                $event = !empty($res['data']) ? $res['data'] : [];
                $v['content'] = $event['content'] ?? '';
                $v['deal_at'] = $event['dealAt'] ?? '';
                $v['deal_image'] = $event['dealWithPicList'] ?? '';

                if (!empty($param['homePage']) && $k < 3) {
                    $avatar[] = $v['avatar']; 
                }
            }
        }

        $total = $this->_searchExposure($param)->count();

        return $this->success(['list' => $m, 'total' => $total, 'avatar' => $avatar]);
    }

    // 曝光台 搜索
    private function _searchExposure($param)
    {
        $start_at = !empty($param['start_at']) ? $param['start_at'] : '';
        $end_at = !empty($param['end_at']) ? $param['end_at'].' 23:59:59' : '';

        $model = PsCommunityExposure::find()->alias("A")
            ->leftJoin('ps_community_roominfo B', 'A.room_id = B.id')
            ->filterWhere(['like', 'A.name', PsCommon::get($param, 'name')])
            ->orFilterWhere(['like', 'A.mobile', PsCommon::get($param, 'name')])
            ->filterWhere(['=', 'A.app_user_id', PsCommon::get($param, 'user_id')])
            ->andFilterWhere(['=', 'A.event_parent_type_id', PsCommon::get($param, 'parent_type')])
            ->andFilterWhere(['=', 'A.event_child_type_id', PsCommon::get($param, 'child_type')])
            ->andFilterWhere(['in', 'A.status', PsCommon::get($param, 'status')])
            ->andFilterWhere(['=', 'A.room_id', PsCommon::get($param, 'room_id')])
            ->andFilterWhere(['=', 'A.community_id', PsCommon::get($param, 'community_id')])
            ->andFilterWhere(['>=', 'A.created_at', $start_at])
            ->andFilterWhere(['<=', 'A.created_at', $end_at])
            ->andFilterWhere(['=', 'A.is_del', 1])
            ->andFilterWhere(['=', 'A.hide_status', 1])
            ->andFilterWhere(['=', 'B.group', PsCommon::get($param, 'group')])
            ->andFilterWhere(['=', 'B.building', PsCommon::get($param, 'building')])
            ->andFilterWhere(['=', 'B.unit', PsCommon::get($param, 'unit')])
            ->andFilterWhere(['=', 'B.room', PsCommon::get($param, 'room')]);

        return $model;
    }

    // 曝光台 详情
    public function exposureShow($p)
    {
        $m = PsCommunityExposure::find()->where(['id' => $p['id'], 'is_del' => 1])->asArray()->one();

        if (empty($m)) {
            return $this->failed('数据不存在！');
        }

        if (!empty($p['user_id']) && $m['app_user_id'] != $p['user_id']) {
            return $this->failed('没有权限！');
        }

        // 查询业主
        $member = PsAppMember::find()->alias('A')->leftJoin('ps_member B', 'B.id = A.member_id')
            ->select('B.*')->where(['A.app_user_id' => $p['user_id']])->asArray()->one();
        if (!$member) {
            return $this->failed('业主不存在！');
        }

        $m['status_msg'] = PsCommunityExposure::status($m['status']);
        $m['type_msg'] = EventTemplate::typeDesc($m);
        $image_1 = PsCommunityExposureImage::find()->select('image_url')->where(['community_exposure_id' => $m['id'], 'type' => 1])->asArray()->all();
        $m['image_url'] = F::ossImagePath(array_column($image_1, 'image_url'));
        
        // 处理结果 调Java接口
        $event = Curl::getInstance()->post(Yii::$app->params['java_domain'].$this->urlJava['dealDetail'], json_encode(['exposureId' => $p['id']]), true);
        $event = json_decode($event, true)['data'];
        $m['content'] = $event['content'] ?? '';
        $m['deal_at'] = $event['dealAt'] ?? '';
        $m['deal_image'] = $event['dealWithPicList'] ?? '';

        return $this->success($m);
    }

    // 曝光台 删除
    public function exposureDelete($p)
    {
        $m = PsCommunityExposure::find()->where(['id' => $p['id'], 'is_del' => 1])->asArray()->one();

        if (empty($m)) {
            return $this->failed('数据不存在！');
        }

        if (!empty($p['user_id']) && $m['app_user_id'] != $p['user_id']) {
            return $this->failed('没有权限删除！');
        }

        PsCommunityExposure::updateAll(['is_del' => 2], ['id' => $p['id']]);
        
        return $this->success();
    }

    // 曝光台 类型
    public function exposureType($p)
    {
        if ($p['type'] == 1) { // 
            $count = PsCommunityExposure::find()->select('count(id) as c, event_parent_type_id as type')->where(['is_del' => 1, 'hide_status' => 1, 'community_id' => $p['community_id']])->orderBy('event_parent_type_id asc')->groupBy('event_parent_type_id')->asArray()->all();

            if (!empty($count)) {
                foreach ($count as $k => $v) {
                    switch ($v['type']) {
                        case '1':
                            $arr['1'] = $v['c'];
                            break;
                        default:
                            $arr[$v['type']] = $v['c'];
                            break;
                    }
                }
            }
            
            $arr_sum = !empty($arr) ? array_sum($arr) : 0;
            $r[0]['id'] = 0;
            $r[0]['name'] = '全部';
            $r[0]['total'] = $arr_sum;
        }

        $type = EventTemplate::type(['type' => 1]);
        foreach ($type as $k => $v) {
            if ($p['type'] == 1) {
                ++$k;
                $total = !empty($arr[$v['id']]) ? $arr[$v['id']] : 0;

                $r[$k]['id'] = $v['id'];
                $r[$k]['name'] = $v['name'];
                $r[$k]['total'] = $total;
            } else {
                $r[$k]['id'] = $v['id'];
                $r[$k]['name'] = $v['name'];
                $r[$k]['subList'] = $typeChild = EventTemplate::typeChild(['parent_id' => $v['id']]);
            }
        }

        return $this->success($r ?? []);
    }

    // -----------------------------------     小区评分     ------------------------------

	// 小区评分 首页
    public function commentIndex($p)
    {
        $beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
        $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));

        $detail = PsCommunityCommentDetail::find()
            ->where(['>=', 'created_at', $beginThismonth])
            ->andWhere(['=', 'member_id', $p['user_id']])
            ->andWhere(['=', 'community_id', $p['community_id']])
            ->andWhere(['<=', 'created_at', $endThismonth])->asArray()->one();

        $r['month'] = date('m',time());
        $r['score'] = self::_score($p['community_id']);
        $r['type'] = !empty($detail) ? '1' : '2';

        return $this->success($r);
    }

    // 小区评分 平均值
    private function _score($community_id)
    {
        $comment = PsCommunityCommentDetail::find()->select('score')->where(['community_id' => $community_id])->asArray()->all();
        if (!empty($comment)) {
            return (string)round(array_sum(array_map(function($val){return $val['score'];}, $comment)) / count($comment), 1);
        } else {
            return '5.0';
        }
    }
    
    // 小区评分 比例
    public function _scoreRate($community_id)
    {
        $model = PsCommunityCommentDetail::find()
            ->select("count(id) as c, score")
            ->where(['=', 'community_id', $community_id])
            ->groupBy('score')->orderBy('score desc')->asArray()->all();

        if (!empty($model)) {
            $score1 = $score2 = $score3 = $score4 = $score5 = 0;
            foreach ($model as $k => $v) {
                switch ($v['score']) {
                    case '5.0':
                        $score5 = $v['c'];
                        break;
                    case '4.0':
                        $score4 = $v['c'];
                        break;
                    case '3.0':
                        $score3 = $v['c'];
                        break;
                    case '2.0':
                        $score2 = $v['c'];
                        break;
                    case '1.0':
                        $score1 = $v['c'];
                        break;
                }
            }
            $sum = $score1 + $score2 + $score3 + $score4 + $score5;
            $arr[] = round($score5 / $sum, 2) * 100;
            $arr[] = round($score4 / $sum, 2) * 100;
            $arr[] = round($score3 / $sum, 2) * 100;
            $arr[] = round($score2 / $sum, 2) * 100;
            $arr[] = round($score1 / $sum, 2) * 100;

            return $arr;
        } 
        return [100, 0, 0, 0, 0];
    }

    // 小区评分 评价页面
    public function commentShow($p)
    {
        $roomInfo = JavaOfCService::service()->roomInfo(['token' => $p['token'], 'id' => $p['room_id']]);
        if (empty($roomInfo['id'])) {
            return F::apiSuccess('房屋不存在');
        }

        $beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
        $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));

        $community_id = $roomInfo['communityId'];
        $model['community_id'] = $community_id;
        $model['community_name'] = $roomInfo['communityName'];
        $model['score'] = self::_score($community_id);
        $model['score_msg'] = PsCommunityComment::scoreMsg($model['score']);
        $model['month'] = (int)date('m', time());
        $model['score_rate'] = self::_scoreRate($community_id);
        $info = PsCommunityCommentDetail::find()->select("avatar, name, score, created_at, content")
            ->where(['>=', 'created_at', $beginThismonth])
            ->andWhere(['=', 'member_id', $p['user_id']])
            ->andWhere(['=', 'community_id', $community_id])
            ->andWhere(['<=', 'created_at', $endThismonth])->asArray()->one();
        $model['info'] = !empty($info) ? $info : '';
        if (!empty($model['info'])) {
            $model['info']['create_at'] = self::_time($model['info']['created_at']);
            $model['info']['name'] = self::_hideName($model['info']['name']);
        }
      
        return $this->success($model);
    }

    // 小区评分 评价 提交
    public function commentAdd($p)
    {
        $roomInfo = JavaOfCService::service()->roomInfo(['token' => $p['token'], 'id' => $p['room_id']]);
        if (empty($roomInfo['id'])) {
            return F::apiSuccess('房屋不存在');
        }

        // 查找用户的信息
        $member = JavaOfCService::service()->memberBase(['token' => $p['token']]);
        if (empty($member['id'])) {
            return F::apiSuccess('用户不存在');
        }

        $trans = Yii::$app->getDb()->beginTransaction();

        try {

            $comment_year = date('Y', time());
            $comment_month = date('m', time());

            $comment = PsCommunityComment::find()
                ->where(['community_id' => $roomInfo['communityId']])
                ->andWhere(['comment_year' => $comment_year, 'comment_month' => $comment_month])
                ->one();

            if (!empty($comment)) {
                $comment_id = $comment->id;
                $detail = PsCommunityCommentDetail::find()->select('sum(score) sum_score, count(id) count')
                    ->where(['community_id' => $roomInfo['communityId']])
                    ->andWhere(['comment_year' => $comment_year, 'comment_month' => $comment_month])
                    ->asArray()->one();

                $total = $detail['count'] + 1;
                $score = round(($detail['sum_score'] + $p['starIdx']) / $total, 1);
                PsCommunityComment::updateAll(['score' => $score, 'total' => $total], ['id' => $comment_id]);
            } else {
                $comment = new PsCommunityComment();
                $comment->community_id = $roomInfo['communityId'];
                $comment->community_name = $roomInfo['communityName'];
                $comment->comment_year = $comment_year;
                $comment->comment_month = $comment_month;
                $comment->score = $p['starIdx'];
                $comment->total = 1;
                $comment->save();
                $comment_id = $comment->id;
            }

            $params['comment_id'] = $comment_id;
            $params['community_id'] = $roomInfo['communityId'];
            $params['community_name'] = $roomInfo['communityName'];
            $params['group_id'] = $roomInfo ? $roomInfo['groupId'] : '';
            $params['building_id'] = $roomInfo ? $roomInfo['buildingId'] : '';
            $params['unit_id'] = $roomInfo ? $roomInfo['unitId'] : '';
            $params['fullName'] = $roomInfo ? $roomInfo['fullName'] : '';
            $params['room_id'] = $p['room_id'];
            $params['member_id'] = $member['id'];
            $params['avatar'] = !empty($member['avatar']) ? $member['avatar'] : 'http://static.zje.com/2019041819483665978.png';
            $params['name'] = $member['trueName'];
            $params['mobile'] = $member['sensitiveInf'];
            $params['score'] = $p['starIdx'];
            $params['content'] = $p['content'];
            $params['comment_year'] = $comment_year;
            $params['comment_month'] = $comment_month;

            $model = new PsCommunityCommentDetail(['scenario' => 'add']);

            if (!$model->load($params, '') || !$model->validate()) {
                return $this->failed($this->getError($model));
            }

            if (!$model->save()) {
                return $this->failed($this->getError($model));
            }

            $trans->commit();
            return $this->success(['id' => $model->attributes['id']]);
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    // 小区评分 搜索
    private function _searchComment($param)
    {
        if (!empty($param['month'])) {
            $timestamp = strtotime($param['month']);
            $start_month = strtotime(date('Y-m-1 00:00:00', $timestamp));
            $mdays = date('t', $timestamp);
            $end_month = strtotime(date('Y-m-' . $mdays . ' 23:59:59', $timestamp));
        }

        $start_at = !empty($param['start_at']) ? strtotime($param['start_at']) : '';
        $end_at = !empty($param['end_at']) ? strtotime($param['end_at'].' 23:59:59') : '';

        $model = PsCommunityCommentDetail::find()
            ->filterWhere(['like', 'name', PsCommon::get($param, 'name')])
            ->andFilterWhere(['like', 'mobile', PsCommon::get($param, 'mobile')])
            ->andFilterWhere(['=', 'member_id', $param['user_id']])
            ->andFilterWhere(['=', 'room_id', $param['room_id']])
            ->andFilterWhere(['=', 'comment_id', $param['id']])
            ->andFilterWhere(['>=', 'created_at', $start_at])
            ->andFilterWhere(['<=', 'created_at', $end_at])
            ->andFilterWhere(['>=', 'created_at', $start_month])
            ->andFilterWhere(['<=', 'created_at', $end_month])
            ->andFilterWhere(['=', 'community_id', $param['community_id']])
            ->andFilterWhere(['=', 'group_id', $param['group_id']])
            ->andFilterWhere(['=', 'building_id', $param['building_id']])
            ->andFilterWhere(['=', 'unit_id', $param['unit_id']]);   

        return $model;
    }

    // 社区评价 列表
    public function commentList($param)
    {
        $page = !empty($param['page']) ? $param['page'] : 1;
        $pageSize = !empty($param['rows']) ? $param['rows'] : 5;

        $model = $this->_searchComment($param)
            ->orderBy('created_at desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)->asArray()->all();

        return $model;
    }
    
    // 社区评价 总数
    public function commentTotal($param)
    {
        return $this->_searchComment($param)->count();
    }

    // 月份
    public function month($param)
    {
        $community_id = $param['community_id'];
        $model = Yii::$app->db->createCommand("SELECT DISTINCT FROM_UNIXTIME(created_at, '%Y-%m') create_at 
            from ps_community_comment where community_id = $community_id ORDER BY create_at desc")->queryAll();

        $arr = array_column($model, 'create_at');
        
        return $arr;
    }

    // -----------------------------------     小区话题 邻里互动     ------------------------------

    // 小区话题 发布
    public function circleAdd($param)
    {
        // 敏感词检测
        $word = self::_sensitiveWord($param['content']);
        if (!empty($word)) {
            return $this->failed($word);
        }

        if (empty($param['type'])) {
            return $this->failed('话题类型必填！');
        }
 
        // 查询业主
        $member = PsAppMember::find()->alias('A')->leftJoin('ps_member B', 'B.id = A.member_id')
            ->select('B.*')->where(['A.app_user_id' => $param['user_id']])->asArray()->one();
        if (!$member) {
            return $this->failed('业主不存在！');
        }

        $roomUser = PsRoomUser::find()->select('status')->where(['member_id' => $member['id'], 'room_id' => $param['room_id']])->orderBy("status")->asArray()->one();

        if ($roomUser['status'] != 2) {
            return $this->failed('房屋未认证！');
        }

        $roomInfo = PsCommunityRoominfo::find()->alias('A')
            ->leftJoin('ps_community B', 'B.id = A.community_id')->select('A.*')
            ->where(['A.id' => $param['room_id']])->asArray()->one();
        if (!$roomInfo) {
            return $this->failed('房屋不存在！');
        }

        $avatar = PsAppUser::findOne($param['user_id'])->avatar;

        $params['community_id'] = $roomInfo['community_id'];
        $params['room_id'] = $param['room_id'];
        $params['app_user_id'] = $param['user_id'];
        $params['avatar'] = !empty($avatar) ? $avatar : 'http://static.zje.com/2019041819483665978.png';
        $params['name'] = $member['name'];
        $params['mobile'] = $member['mobile'];
        $params['content'] = $param['content'];
        $params['type'] = !empty($param['type']) ? implode(",", $param['type']) : 1;

        $trans = Yii::$app->getDb()->beginTransaction();

        try {
            $model = new PsCommunityCircle(['scenario' => 'add']);

            if (!$model->load($params, '') || !$model->validate()) {
                return $this->failed($this->getError($model));
            }

            if (!$model->save()) {
                return $this->failed($this->getError($model));
            }

            if (!empty($param['image_url'])) {
                foreach ($param['image_url'] as $k => $v) {
                    $image = new PsCommunityCircleImage();
                    $image->community_circle_id = $model->attributes['id'];
                    $image->image_url = $v;
                    $image->save();
                }
            }

            // 发送消息
            $data = [
                'community_id' => $roomInfo['community_id'],
                'id' => 0,
                'member_id' => $member['id'],
                'user_name' => $member['name'],
                'create_user_type' => 2,

                'remind_tmpId' => 13,
                'remind_target_type' => 13,
                'remind_auth_type' => 13,
                'msg_type' => 3,

                'msg_tmpId' => 13,
                'msg_target_type' => 13,
                'msg_auth_type' => 13,
                'remind' =>[0 => $member['name']],
                'msg' => [
                    0 => $member['name'],
                    1 => $params['content'],
                    2 => $member_name,
                    3 => $roomInfo['group'].''.$roomInfo['building'].''.$roomInfo['unit'].$roomInfo['room'],
                    4 => date("Y-m-d H:i:s",time())
                ]
            ];
            MessageService::service()->addMessageTemplate($data);

            $trans->commit();
            return $this->success(['id' => $model->attributes['id']]);
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    // 小区话题 列表
    public function circleList($param)
    {
        $page = !empty($param['page']) ? $param['page'] : 1;
        $pageSize = !empty($param['rows']) ? $param['rows'] : 5;

        if ($param['types'] == 1) { // 我发布的
            $data['user_id'] = $param['user_id'];
        } else if ($param['types'] == 2) { // 我参与的
            $community_circle_id = PsCommunityCirclePraise::find()->select('community_circle_id')->where(['app_user_id' => $param['user_id']])->asArray()->all();  
            $ids = array_column($community_circle_id, 'community_circle_id');
            $data['ids'] = !empty($ids) ? $ids : 'null';
            $data['not_user_id'] = $param['user_id'];
        } else {
            $data['room_id'] = $param['room_id'];
            $data['start_at'] = $param['start_at'];
            $data['end_at'] = $param['end_at'];
            $data['name'] = $param['name'];
            $data['group'] = $param['group'];
            $data['building'] = $param['building'];
            $data['unit'] = $param['unit'];
            $data['room'] = $param['room'];
        }

        $data['type'] = $param['type'];
        $data['community_id'] = $param['community_id'];

        $model = $this->_searchCircle($data)
            ->orderBy('A.created_at desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)->asArray()->all();
        $arr = [];
        if (!empty($model)) {
            foreach ($model as $k => $v) {
                $arr[$k]['id'] = $v['id'];
                $arr[$k]['avatar'] = $v['avatar'];
                $arr[$k]['mobile'] = $v['mobile'];
                $arr[$k]['content'] = $v['content'];
                $images = PsCommunityCircleImage::find()->select('image_url')->where(['community_circle_id' => $v['id']])->asArray()->all();
                $arr[$k]['image_url'] = F::ossImagePath(array_column($images, 'image_url'));
                $arr[$k]['praise_total'] = PsCommunityCirclePraise::find()->where(['community_circle_id' => $v['id']])->count();

                if ($param['systemtype'] == 1) { // 物业系统
                    $room = PsCommunityRoominfo::find()->alias('A')->select('B.name, A.address')
                        ->leftJoin('ps_community B', 'B.id = A.community_id')
                        ->where(['A.id' => $v['room_id']])->asArray()->one();
                    $arr[$k]['room_info'] = $room['name'].$room['address'];
                    $arr[$k]['create_at'] = date('Y年m月d日 H:i:s', $v['created_at']);
                    $arr[$k]['name'] =  $v['name'];
                } else {
                    $arr[$k]['type'] = explode(',', $v['type']);
                    $arr[$k]['type_msg'] = PsCommunityCircle::type($arr[$k]['type']);
                    $arr[$k]['create_at'] = self::_time($v['created_at']);
                    $arr[$k]['name'] =  CommunityService::service()->_hideName($v['name']);
                    $arr[$k]['community_name'] = PsCommunityModel::find()->select('name')->where(['id' => $v['community_id']])->scalar();
                    $is_praise = PsCommunityCirclePraise::find()->where(['community_circle_id' => $v['id'], 'app_user_id' => $param['user_id']])->count();
                    $arr[$k]['is_praise'] = !empty($is_praise) ? 1 : 2;
                }   
            }
        }

        $total = $this->_searchCircle($data)->count();

        if ($param['types'] == 1 || $param['types'] == 2) {
            $love_total = self::circleUnreadTotal($param)['total'];
            return ['list' => $arr, 'total' => $total, 'love_total' => $love_total];
        } else {
            return ['list' => $arr, 'total' => $total];
        }
    }

    // 小区话题 搜索
    private function _searchCircle($param)
    {
        $start_at = !empty($param['start_at']) ? strtotime($param['start_at']) : '';
        $end_at = !empty($param['end_at']) ? strtotime($param['end_at'].' 23:59:59') : '';

        $model = PsCommunityCircle::find()->alias("A")
            ->leftJoin('ps_community_roominfo B', 'A.room_id = B.id')
            ->filterWhere(['like', 'A.name', PsCommon::get($param, 'name')])
            ->orFilterWhere(['like', 'A.mobile', PsCommon::get($param, 'name')])
            ->filterWhere(['=', 'A.app_user_id', $param['user_id']])
            ->andFilterWhere(['like', 'A.type', $param['type']])
            ->andFilterWhere(['=', 'A.room_id', $param['room_id']])
            ->andFilterWhere(['=', 'A.community_id', $param['community_id']])
            ->andFilterWhere(['in', 'A.id', $param['ids']])
            ->andFilterWhere(['>=', 'A.created_at', $start_at])
            ->andFilterWhere(['<=', 'A.created_at', $end_at])
            ->andFilterWhere(['=', 'A.is_del', 1])
            ->andFilterWhere(['=', 'B.group', $param['group']])
            ->andFilterWhere(['=', 'B.building', $param['building']])
            ->andFilterWhere(['=', 'B.unit', $param['unit']])
            ->andFilterWhere(['=', 'B.room', $param['room']])
            ->andFilterWhere(['!=', 'A.app_user_id', $param['not_user_id']]);   

        return $model;
    }

    // 小区话题 详情
    public function circleShow($param)
    {
        $model = PsCommunityCircle::find()->where(['id' => $param['id'], 'is_del' => 1])->asArray()->one();

        if (empty($model)) {
            return $this->failed('数据不存在！');
        }

        // 查询业主
        $member = PsAppMember::find()->alias('A')->leftJoin('ps_member B', 'B.id = A.member_id')
            ->select('B.*')->where(['A.app_user_id' => $param['user_id']])->asArray()->one();
        if (!$member) {
            return $this->failed('业主不存在！');
        }

        $arr['id'] = $model['id'];
        $arr['avatar'] = $model['avatar'];
        $arr['name'] =  self::_hideName($model['name']);
        $arr['content'] = $model['content'];
        $images = PsCommunityCircleImage::find()->select('image_url')->where(['community_circle_id' => $model['id']])->asArray()->all();
        $arr['image_url'] = F::ossImagePath(array_column($images, 'image_url'));
        $arr['type'] = explode(',', $model['type']);
        $arr['type_msg'] = PsCommunityCircle::type($arr['type']);
        $arr['create_at'] = self::_time($model['created_at']);
        $arr['app_user_id'] = $model['app_user_id'];
        $arr['total'] = PsCommunityCirclePraise::find()->where(['community_circle_id' => $model['id']])->count();
        $is_praise = PsCommunityCirclePraise::find()->where(['community_circle_id' => $model['id'], 'app_user_id' => $param['user_id']])->count();
        $arr['is_praise'] = !empty($is_praise) ? 1 : 2;

        return $this->success($arr);
    }

    // 小区话题 删除
    public function circleDelete($param, $userinfo = '')
    {
        $model = PsCommunityCircle::find()->where(['id' => $param['id'], 'is_del' => 1])->asArray()->one();

        if (empty($model)) {
            return $this->failed('数据不存在！');
        }

        if (!empty($param['user_id']) && $model['app_user_id'] != $param['user_id']) {
            return $this->failed('没有权限删除！');
        }

        PsCommunityCircle::updateAll(['is_del' => 2], ['id' => $param['id']]);

        if (!empty($userinfo)) { // 保存日志
            $content = "发布内容:" . $model->content . ',';
            $content .= "状态:删除";
            $operate = [
                "community_id" => $model['community_id'],
                "operate_menu" => "社区运营",
                "operate_type" => "删除邻里互动",
                "operate_content" => $content,
            ];
            OperateService::addComm($userinfo, $operate);
        }

        return $this->success();
    }

    // 小区话题 点赞
    public function circlePraise($param)
    {
        $model = PsCommunityCircle::find()->where(['id' => $param['id'], 'is_del' => 1])->asArray()->one();

        if (empty($model)) {
            return $this->failed('数据不存在！');
        }

        // 查询业主
        $member = PsAppMember::find()->alias('A')->leftJoin('ps_member B', 'B.id = A.member_id')
            ->select('B.*')->where(['A.app_user_id' => $param['user_id']])->asArray()->one();
        if (!$member) {
            return $this->failed('业主不存在！');
        }

        $roomUser = PsRoomUser::find()->select('status')->where(['member_id' => $member['id'], 'room_id' => $param['room_id']])->orderBy("status")->asArray()->one();

        if ($roomUser['status'] != 2) {
            return $this->failed('房屋未认证！');
        }

        $roomInfo = PsCommunityRoominfo::find()->alias('A')
            ->leftJoin('ps_community B', 'B.id = A.community_id')->select('A.id, A.community_id')
            ->where(['A.id' => $param['room_id']])->asArray()->one();
        if (!$roomInfo) {
            return $this->failed('房屋不存在！');
        }

        $avatar = PsAppUser::findOne($param['user_id'])->avatar;

        $params['community_id'] = $roomInfo['community_id'];
        $params['room_id'] = $param['room_id'];
        $params['community_circle_id'] = $param['id'];
        $params['app_user_id'] = $param['user_id'];
        $params['avatar'] = !empty($avatar) ? $avatar : 'http://static.zje.com/2019041819483665978.png';
        $params['name'] = $member['name'];
        $params['mobile'] = $member['mobile'];

        $model = new PsCommunityCirclePraise(['scenario' => 'add']);

        if (!$model->load($params, '') || !$model->validate()) {
            return $this->failed($this->getError($model));
        }

        if (!$model->save()) {
            return $this->failed($this->getError($model));
        }

        return $this->success(['id' => $model->attributes['id']]);
    }

    // 小区话题 取消点赞
    public function circlePraiseCancel($param)
    {
        $model = PsCommunityCircle::find()->where(['id' => $param['id'], 'is_del' => 1])->asArray()->one();

        if (empty($model)) {
            return $this->failed('数据不存在！');
        }

        // 查询业主
        $member = PsAppMember::find()->alias('A')->leftJoin('ps_member B', 'B.id = A.member_id')
            ->select('B.*')->where(['A.app_user_id' => $param['user_id']])->asArray()->one();
        if (!$member) {
            return $this->failed('业主不存在！');
        }

        $praise = PsCommunityCirclePraise::find()->where(['community_circle_id' => $param['id'], 'app_user_id' => $param['user_id']])->asArray()->one();

        if (empty($praise)) {
            return $this->failed('没有点过赞哦！');
        }

        PsCommunityCirclePraise::deleteAll(['community_circle_id' => $param['id'], 'app_user_id' => $param['user_id']]);
        
        return $this->success();
    }

    // 我的 话题数
    public function circleUnreadTotal($param)
    {
        $circle = PsCommunityCircle::find()->select('id')->where(['app_user_id' => $param['user_id'], 'community_id' => $param['community_id'], 'is_del' => 1])->asArray()->all();
        
        $data['community_circle_id'] = array_column($circle, 'id');
        $data['is_read'] = 1; // 未读
        $data['user_id'] = $param['user_id']; // 自己点的赞 不算话题数

        $total = $this->_searchCircleLove($data)->count();

        return ['total' => $total];
    }

    // 我收到的爱心列表 && 话题详情的点赞列表
    public function circleLove($param)
    {
        $page = !empty($param['page']) ? $param['page'] : 1;
        $pageSize = !empty($param['rows']) ? $param['rows'] : 5;

        if ($param['type'] == 1) { // 我收到的爱心列表
            $circle = PsCommunityCircle::find()->select('id')->where(['app_user_id' => $param['user_id'], 'community_id' => $param['community_id'], 'is_del' => 1])->asArray()->all();
        
            $data['community_circle_id'] = array_column($circle, 'id');
            $data['is_del'] = 1;
            $data['user_id'] = $param['user_id']; // 自己点的赞 爱心列表不展示
        } else { // 话题详情的点赞列表
            $data['community_circle_id'] = $param['id'];
        }

        $model = $this->_searchCircleLove($data)
            ->orderBy('created_at desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)->asArray()->all();

        $list = [];
        if (!empty($model)) {
            foreach ($model as $k => $v) {
                $list[$k]['praise_id'] = $v['id'];
                $list[$k]['id'] = $v['community_circle_id'];
                $list[$k]['avatar'] = $v['avatar'];
                $list[$k]['name'] = self::_hideName($v['name']);
                $list[$k]['created_at'] = self::_time($v['created_at']);
                if ($param['type'] == 1) { // 我收到的爱心列表
                    $content = PsCommunityCircle::find()->select('content')->where(['id' => $v['community_circle_id']])->scalar();
                    $list[$k]['content'] = !empty($content) ? $content : '';
                }
                $list[$k]['image_url'] = F::ossImagePath(PsCommunityCircleImage::find()->select('image_url')->where(['community_circle_id' => $v['community_circle_id']])->orderBy('id asc')->scalar());
            }
        }

        $total = $this->_searchCircleLove($data)->count();

        if ($param['type'] == 1) { // 我收到的爱心列表 才更新已读状态
            $circleModel = PsCommunityCircle::find()->select('id')->where(['app_user_id' => $param['user_id']])->asArray()->all();
        
            PsCommunityCirclePraise::updateAll(['is_read' => 2], ['in', 'community_circle_id', array_column($circleModel, 'id')]);
        }

        return ['list' => $list, 'total' => $total];
    }

    // 点赞 搜索
    private function _searchCircleLove($param)
    {
        $community_circle_id = !empty($param['community_circle_id']) ? $param['community_circle_id'] : 'null';

        $model = PsCommunityCirclePraise::find()
            ->filterWhere(['!=', 'app_user_id', $param['user_id']])
            ->andFilterWhere(['=', 'is_read', $param['is_read']])
            ->andFilterWhere(['=', 'is_del', $param['is_del']])
            ->andFilterWhere(['=', 'community_id', $param['community_id']])
            ->andFilterWhere(['in', 'community_circle_id', $community_circle_id]);   

        return $model;
    }

    // 我的爱心列表 删除消息
    public function circlePraiseDelete($param)
    {
        $model = PsCommunityCirclePraise::find()->where(['id' => $param['praise_id'], 'is_del' => 1])->asArray()->one();

        if (empty($model)) {
            return $this->failed('数据不存在！');
        }

        $circle = PsCommunityCircle::find()->select('app_user_id')->where(['id' => $model['community_circle_id']])->asArray()->one();

        if ($circle['app_user_id'] != $param['user_id']) {
            return $this->failed('没有权限删除！');
        }

        PsCommunityCirclePraise::updateAll(['is_del' => 2], ['id' => $param['praise_id']]);
        
        return $this->success();
    }

    // -----------------------------------     公共方法     ------------------------------
    
    // 当天显示时分 之前的显示年月日
    public function _time($time)
    {
        if (empty($time)) {
            return '';
        }

        $today = date('Y-m-d', time());
        $create_at = date('Y-m-d', $time);

        return $today == $create_at ? date('H:i', $time) : $create_at;
    }
    
    // 隐藏姓名
    public function _hideName($name)
    {
        $lenth = strlen($name);
        if ($lenth <= 6) {
            return substr($name, 0, 3) . '*';
        } else {
            return substr($name, 0, 3) . '*' . substr($name, -3);
        }
    }

    // 敏感词检测
    public function _sensitiveWord($content)
    {
        $model = PsSensitiveWord::find()->select('name')->asArray()->all();
        
        $arr = array_column($model, 'name');
        $str = '';
        for($i = 0; $i < count($arr); $i++) { 
            if (substr_count($content, trim($arr[$i])) > 0) { 
                $str = trim($arr[$i]);
            }
        }

        if ($str) {
            $str = '含有敏感词“'.$str.'”！';
        }

        return $str;
    }

    /**
     * 获取单个小区名称
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getCommunityName($id)
    {
        return PsCommunityModel::find()->select('id, name, logo_url')
            ->where(['id' => $id])
            ->asArray()->one();
    }
}
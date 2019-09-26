<?php
namespace service\small;

use common\core\F;
use common\core\PsCommon;

use app\models\PsCommunityUnits;
use app\models\PsComplaint;
use app\models\PsComplaintImages;
use app\models\PsGuide;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsRoomUser;
use app\models\PsSteWard;
use app\models\PsSteWardEvaluate;
use app\models\PsSteWardRelat;
use app\models\PsMember;
use app\models\PsAppUser;
use app\models\PsCommunityBuilding;

use common\MyException;
use service\message\MessageService;
use service\BaseService;

class ComplaintService extends BaseService
{
    // 投诉建议列表
    public function getList($params)
    {
        $community_id = !empty($params['community_id']) ? $params['community_id'] : '';
        $type = !empty($params['type']) ? $params['type'] : '';
        $room_id = !empty($params['room_id']) ? $params['room_id'] : '';
        $user_id = !empty($params['user_id']) ? $params['user_id'] : '';
        if (!$community_id || !$type || !$user_id || !$room_id) {
            return $this->failed('参数错误！');
        }

        // 获取业主id
        $member_id = $this->getMemberByUser($user_id);
        // 获取小区名称
        $community = PsCommunityModel::find()->select('id, name')->where(['id' => $community_id])->asArray()->one();
        // 房屋地址
        $address = PsCommunityRoominfo::find()->select('id,address')
            ->where(['id' => $room_id])->asArray()->one();
        $data = PsComplaint::find()->select(['id', 'status', 'content', 'create_at'])->where([
            'community_id' => $community_id,
            'type' => $type,
            'member_id' => $member_id
        ])->orderBy('id desc')->asArray()->all();

        if (empty($data)) {
            return $this->success();
        }
        // 组装前端字段
        self::format($data);
        $success['list'] = $data ?? [];
        $success['room_info'] = $address['address'];
        $success['community_name'] = $community['name'];

        return $this->success($success);
    }

    // 投诉建议详情
    public function show($params)
    {
        if (!$params['id']) {
            return $this->failed('id不能为空');
        }
        $data = PsComplaint::find()->where(['id' => $params['id']])->asArray()->one();
        if (empty($data)) {
            return $this->failed('投诉建议不存在');
        }
        // 查询投诉建议对于的图片
        $imgList = PsComplaintImages::find()->select('img')->where(['complaint_id' => $params['id']])->asArray()->all();
        $result['id'] = $data['id'];
        $result['content'] = $data['content'];
        $result['status'] = $data['status'];
        $result['status_label'] = PsComplaint::$status[$data['status']];
        $result['type'] = $data['type'];
        $result['handle_at'] = !empty($data['handle_at']) ? date('Y-m-d H:i', $data['handle_at']) : '';
        $result['handle_content'] = $data['handle_content'];
        $result['create_at'] = date('Y-m-d H:i', $data['create_at']);
        $result['images'] = F::ossImagePath(array_column($imgList, 'img'));

        return $this->success($result);
    }

    // 新增投诉建议
    public function add($params)
    {
        $community_id = !empty($params['community_id']) ? $params['community_id'] : '';
        $room_id = !empty($params['room_id']) ? $params['room_id'] : '';
        $type = !empty($params['type']) ? $params['type'] : '';
        $user_id = !empty($params['user_id']) ? $params['user_id'] : '';
        $content = !empty($params['content']) ? $params['content'] : '';
        if (!$community_id || !$type || !$user_id || !$content || !$room_id) {
            return $this->failed('参数错误！');
        }
        if (!in_array($type, [1, 2])) {
            return $this->failed('类型错误！');
        }

        // 获取业主id
        $member_id = $this->getMemberByUser($user_id);
        $member_name = $this->getMemberNameByUser($member_id);

        $model = new PsComplaint();
        $model->room_id = $room_id;
        $model->community_id = $community_id;
        $model->content = $content;
        $model->type = $type;
        $model->status = 1;
        $model->member_id = $member_id;
        $model->create_at = time();
        if (!$model->save()) {
            return $this->failed('添加失败');
        }

        if (!empty($params['images'])) {
            foreach ($params['images'] as $image) {
                $modelImage = new PsComplaintImages();
                $modelImage->img = $image;
                $modelImage->complaint_id = $model->id;;
                $modelImage->save();
            }
        }

        // 发送消息
        $data = [
            'community_id' => $community_id,
            'id' => $model->id,
            'member_id' => $member_id,
            'user_name' => $member_name,
            'create_user_type' => 2,

            'remind_tmpId' => 9,
            'remind_target_type' => 9,
            'remind_auth_type' => 9,
            'msg_type' => 2,

            'msg_tmpId' => 9,
            'msg_target_type' => 9,
            'msg_auth_type' => 9,
            'remind' =>[
                0 => $type == 1 ? "投诉" : "建议"
            ],
            'msg' => [
                0 => $type == 1 ? "投诉" : "建议",
                1 => $member_name,
                2 => $content,
                3 => date("Y-m-d H:i:s",time())
            ]
        ];
        MessageService::service()->addMessageTemplate($data);

        return $this->success();
    }

    // 取消投诉建议
    public function cancel($params)
    {
        $community_id = !empty($params['community_id']) ? $params['community_id'] : '';
        $id = !empty($params['id']) ? $params['id'] : '';
        $user_id = !empty($params['user_id']) ? $params['user_id'] : '';
        if (!$community_id || !$id || !$user_id) {
            return $this->failed('参数错误！');
        }
        // 获取业主id
        $member_id = $this->getMemberByUser($user_id);

        $model = PsComplaint::find()->where(['id' => $id, 'member_id' => $member_id])->one();
        if (empty($model)) {
            return $this->failed('投诉建议不存在！');
        }

        $model->status = 2;
        if (!$model->save()) {
            return $this->failed('取消失败');
        }

        return $this->success();
    }

    // 格式化数据类型
    public static function format(&$data)
    {
        foreach ($data as &$item) {
            if (isset($item['create_at'])) {
                $item['status_label'] = PsComplaint::$status[$item['status']];
                $item['create_at'] = date('Y-m-d H:i', $item['create_at']);
            }
        }
    }

    // 获取联系电话
    public function getGuideList($params)
    {
        $community_id = !empty($params['community_id']) ? $params['community_id'] : '';
        $keyword = !empty($params['keyword']) ? $params['keyword'] : '';
        if (!$community_id) {
            return $this->failed('小区id不能为空！');
        }
        $model = PsGuide::find()->select(['id', 'phone', 'title'])->where(['community_id' => $community_id, 'status' => 1]);
        // 关键字存在则查询对应数据
        if (!empty($keyword)) {
            $model->andWhere(['or', ['like', 'title', $keyword], ['like', 'phone', $keyword]]);
        }
        $result = $model->asArray()->all();
        $success['list'] = $result ?? [];
        return $this->success($success);
    }

    // 获取首页的管家详情-根据房屋id获取对应的楼幢
    public function stewardIndexInfo($params)
    {
        $community_id = !empty($params['community_id']) ? $params['community_id'] : '';
        $room_id = !empty($params['room_id']) ? $params['room_id'] : '';
        //获取幢id
        $room = PsCommunityRoominfo::findOne($room_id);
        $buiding_id = '';
        if(!empty($room)){
            $buiding_id = PsCommunityBuilding::find()->select('id')->where(['group_name' => $room->group, 'community_id' => $community_id, 'name' => $room->building])->scalar();
        }
        if (!empty($buiding_id)) {
            //获取管家信息
            $steward = PsSteWard::find()->alias('ward')->select(['ward.id','ward.name','ward.mobile','ward.evaluate','ward.praise'])
                ->leftJoin("ps_steward_relat rela", "ward.id=rela.steward_id")
                ->where(['rela.data_id' => $buiding_id, 'rela.data_type' => 1, 'ward.community_id' => $community_id,'ward.is_del'=>'1'])->asArray()->one();
            if (!empty($steward)) {
                $steward['praise_rate'] = !empty($steward['evaluate']) ? floor($steward['praise'] / $steward['evaluate'] * 100) : '0';
                return $steward;
            }
        }
        return '';
    }

    // 获取管家详情
    public function stewardInfo($params)
    {
        $community_id = !empty($params['community_id']) ? $params['community_id'] : '';
        $user_id = !empty($params['user_id']) ? $params['user_id'] : '';
        $id = !empty($params['id']) ? $params['id'] : '';
        $room_id = !empty($params['room_id']) ? $params['room_id'] : '';
        if (!$community_id || !$id || !$user_id) {
            return $this->failed('参数错误！');
        }
        // 获取管家信息
        $steward = PsSteWard::find()->select('id,name,mobile,evaluate,praise')->where(['community_id' => $community_id,'is_del'=>'1','id'=>$id])->asArray()->one();
        $steward['praise_rate'] = !empty($steward['evaluate'])?floor($steward['praise'] / $steward['evaluate'] * 100):'0';
        // 获取管家评价的标签排行榜,取前六条数据
        $result =  PsSteWardEvaluate::find()->alias('eval')
            ->select(['rela.data_id as label_id,count(rela.data_id) as total'])
            ->leftJoin("ps_steward_relat rela", "eval.id=rela.evaluate_id")
            ->where(['eval.steward_id' => $params['id'], 'rela.data_type' => 2])
            ->andFilterWhere(['=', 'eval.community_id', $community_id])
            ->groupBy("rela.data_id")
            ->orderBy("total desc")
            ->limit("6")->asArray()->all();
        $label_list = [];
        if(!empty($result)){
            foreach ($result as $label){
                $label['name'] = $this->getStewardLabel($label['label_id']);
                $label_list[] = $label;
            }
        }
        $member_id = $this->getMemberByUser($user_id);
        $roomUser = PsRoomUser::find()->select('status')->where(['room_id' => $room_id, 'member_id' => $member_id])->orderBy('status asc')->asArray()->one();
        $steward['is_auth'] = $roomUser['status']==2 ? 1 : 2; // 当前房屋是否认证 1已认证 2未认证
        $steward['label'] = $label_list;
        //获取好评差评参数
        $steward['label_params'] = $this->getStewardLabel();
        //获取用户当天有没有评价-好评
        $praise_status = PsSteWardEvaluate::find()->where(['user_id'=>$user_id,'steward_id'=>$id,'community_id'=>$community_id,'steward_type'=>1])->andWhere(['>','create_at',strtotime(date('Y-m-d',time()))])->one();
        $steward['praise_status'] = !empty($praise_status)?'1':'2';   //用户当天是否评价：1已评价，2没有
        //获取用户当天有没有评价-差评
        $review_status = PsSteWardEvaluate::find()->where(['user_id'=>$user_id,'steward_id'=>$id,'community_id'=>$community_id,'steward_type'=>2])->andWhere(['>','create_at',strtotime(date('Y-m-d',time()))])->one();
        $steward['review_status'] = !empty($review_status)?'1':'2';   //用户当天是否评价：1已评价，2没有
        $steward['params'] = ['user_id'=>$user_id,'steward_id'=>$id,'community_id'=>$community_id,'steward_type'=>1,'creat'=>strtotime(date('Y-m-d',time()))];
        return $this->success($steward);
    }

    // 获取管家评价列表
    public function stewardList($params)
    {
        $community_id = !empty($params['community_id']) ? $params['community_id'] : '';
        $id = !empty($params['id']) ? $params['id'] : '';
        $page = !empty($params['page']) ? $params['page'] : 1;
        $rows = !empty($params['rows']) ? $params['rows'] : 10;
        if (!$community_id || !$id) {
            return $this->failed('参数错误！');
        }
        $list = [];
        $sel = $this->_search($params);
        $total = $this->_search($params)->count();
        $resultAll = $sel->orderBy('create_at desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();
        foreach ($resultAll as $v){
            $v['create_at'] = date('Y-m-d H:i',$v['create_at']);
            $v['user_name'] = $this->substr_cut(PsMember::userinfo($v['user_id'])['name']);
            $v['avatar'] = PsAppUser::find()->select('avatar')->where(['id' => $v['user_id']])->scalar();
            $list[] = $v;
        }

        return $this->success(['list'=>$list,'total'=>$total]);
    }

    // 添加管家评价
    public function addSteward($params)
    {
        $community_id = !empty($params['community_id']) ? $params['community_id'] : '';
        $user_id = !empty($params['user_id']) ? $params['user_id'] : '';
        $room_id = !empty($params['room_id']) ? $params['room_id'] : '';
        $label_id = !empty($params['label_id']) ? $params['label_id'] : '';
        $id = !empty($params['id']) ? $params['id'] : '';
        $type = !empty($params['type']) ? $params['type'] : '1';     //1表扬 2批评
        $content = !empty($params['content']) ? $params['content'] : '';     //1表扬 2批评

        $page = !empty($params['page']) ? $params['page'] : 1;
        $rows = !empty($params['rows']) ? $params['rows'] : 10;
        if (!$community_id || !$id || !$user_id || !$type || !$room_id || !$label_id) {
            return $this->failed('参数错误！'.json_encode($params));
        }
        $userInfo = PsAppUser::find()->select(['nick_name','true_name','phone'])->where(['id' => $user_id])->asArray()->one();
        if(empty($userInfo)){
            return $this->failed('用户不存在！');
        }
        if (!is_array($label_id)) {
            return $this->failed("标签参数错误");
        }
        // 获取用户当天有没有评价
        $status = PsSteWardEvaluate::find()->where(['user_id'=>$user_id,'steward_id'=>$id,'community_id'=>$community_id,'steward_type'=>$type])->andWhere(['>','create_at',strtotime(date('Y-m-d',time()))])->one();
        if(!empty($status)){
            $msg = $type==1?'表扬':'批评';
            return $this->failed("您当天已".$msg);
        }
        // 根据选择的标签获取内容
        $info = '';
        foreach ($label_id as $label){
            $info.=$this->getStewardLabel($label).',';
        }
        $info = substr($info, 0, -1);
        $model = new PsSteWardEvaluate();
        $model->user_name = !empty($userInfo['true_name'])?$userInfo['true_name']:$userInfo['nick_name'];
        $model->user_mobile = $userInfo['phone'];
        $model->user_id = $user_id;
        $model->room_id = $room_id;
        $model->community_id = $community_id;
        $model->steward_id = $id;
        $model->steward_type = $type;
        $model->content = $content;
        $model->create_at = time();
        if($model->save()){
            $content = !empty($content)?$info.','.$content:$info;
            PsSteWardEvaluate::updateAll(['content'=>$content],['id'=>$model->id]);
            //更新管家的评价数量
            $ward = PsSteWard::model()->find()->where(['id'=>$id])->one();
            $ward->evaluate=$ward->evaluate+1;
            if($type==1){
                $ward->praise=$ward->praise+1;
            }
            $ward->save();
            //添加标签与评价的关系数据
            foreach ($label_id as $label){
                $rela = new PsSteWardRelat();
                $rela->steward_id=$id;
                $rela->evaluate_id=$model->id;
                $rela->data_id=$label;
                $rela->data_type=2;
                $rela->save();
            }
            // 发送消息
            $member_id = $this->getMemberByUser($user_id);
            $member_name = $this->getMemberNameByUser($member_id);
            $room_info = CommunityRoomService::getCommunityRoominfo($room_id);
            $data = [
                'community_id' => $community_id,
                'id' => $id,
                'member_id' => $member_id,
                'user_name' => $member_name,
                'create_user_type' => 2,

                'remind_tmpId' => 11,
                'remind_target_type' => 11,
                'remind_auth_type' => 11,
                'msg_type' => 3,

                'msg_tmpId' => 11,
                'msg_target_type' => 11,
                'msg_auth_type' => 11,
                'remind' =>[
                    0 => $ward->name,
                    1 => $type == 1 ? "表扬" : "批评"
                ],
                'msg' => [
                    0 => $ward->name,
                    1 => $type == 1 ? "表扬" : "批评",
                    2 => $ward->name,
                    3 => $content,
                    4 => $member_name,
                    5 => $room_info['group'].''.$room_info['building'].''.$room_info['unit'].$room_info['room'],
                    6 => date("Y-m-d H:i:s",time())
                ]
            ];
            MessageService::service()->addMessageTemplate($data);
            return $this->success();
        }else{
            $errors = array_values($model->getErrors());
            $error = !empty($errors[0][0]) ? $errors[0][0] : '系统错误';
            return $this->failed($error);
        }
    }

    // 管家公用的搜索
    public function _search($params)
    {
       return PsSteWardEvaluate::find()
            ->where(['steward_id' => $params['id']])
            ->andFilterWhere(['=', 'community_id', $params['community_id']])
            ->andFilterWhere(['=', 'steward_type', $params['steward_type']]);
    }

    // 获取
    public static function getStewardLabel($index = 0)
    {
        // 好评
        $praise =  [
            ["key" => "1", "name" => "态度好服务棒"],
            ["key" => "2", "name" => "神准时"],
            ["key" => "3", "name" => "服务规范"],
            ["key" => "4", "name" => "诚恳心善"],
            ["key" => "5", "name" => "专业细心"],
            ["key" => "6", "name" => "文明礼貌"],
            ["key" => "7", "name" => "全程跟进"],

        ];
        // 差评
        $negative =  [
            ["key" => "50", "name" => "态度恶劣"],
            ["key" => "51", "name" => "响应速度慢"],
            ["key" => "52", "name" => "敷衍马虎"],
            ["key" => "53", "name" => "没有责任心"],
            ["key" => "54", "name" => "有待提高"],
            ["key" => "55", "name" => "服务不规范"],
        ];

        if (!empty($index)) {
            // 合并两个数组-数据查不到就设置为空   '-'
            $label_list = array_merge($praise, $negative);
            foreach ($label_list as $list) {
                if ($list['key'] == $index) {
                    return $list['name'];break;
                }
            }
            return '-';
        } else {
            $label['praise'] = $praise;
            $label['negative'] = $negative;
            return $label;
        }
    }

    // 只保留字符串首尾字符，隐藏中间用*代替（两个字符时只显示第一个）
    public function substr_cut($user_name)
    {
        $strlen = mb_strlen($user_name, 'utf-8');
        $firstStr = mb_substr($user_name, 0, 1, 'utf-8');
        $lastStr = mb_substr($user_name, -1, 1, 'utf-8');

        return $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($user_name, 'utf-8') - 1) : $firstStr . str_repeat("*", $strlen - 2) . $lastStr;
    }
}
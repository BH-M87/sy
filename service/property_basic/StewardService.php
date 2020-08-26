<?php
namespace service\property_basic;

use app\models\PsSteWardTag;
use common\core\F;
use common\core\PsCommon;
use common\MyException;

use service\BaseService;
use service\rbac\OperateService;

use app\models\PsCommunityBuilding;
use app\models\PsSteWard;
use app\models\PsSteWardEvaluate;
use app\models\PsSteWardRelat;
use yii\db\Exception;
use Yii;

class StewardService extends BaseService
{
    // 参数验证
    public function _checkBackendList($params)
    {
//        if (empty($params['community_id'])) {
//            throw new MyException('小区ID不能为空');
//        }

        if (!empty($params['building_id'])) {
            if (is_array($params['building_id'])) {
                foreach ($params['building_id'] as $v) {
                    if (!is_numeric($v)) {
                        throw new MyException('楼幢ID错误');
                    }
                }
            } else {
                throw new MyException('楼幢ID必须是数组格式');
            }
        }
    }

    // 管家详情
    public function steWardInfo($params)
    {
        $data = PsCommon::validParamArr(new PsSteWard(), $params, 'detail');
        if (!$data['status']) {
            throw new MyException($data['errorMsg']);
        }

        $this->checkSteward($params);
        // 获取管家信息
        $steward = PsSteWard::find()->select('id,name,mobile,evaluate,praise,sex')->where(['community_id' => $params['community_id'],'is_del'=>'1','id'=>$params['id']])->asArray()->one();
        $steward['negative'] = $steward['evaluate']-$steward['praise']; // 差评数量
        $steward_r[0] = $steward; // 方便遍历
        
        $this->getGroupBuildingInfo($steward_r, []);
        
        return $steward_r[0];
    }

    // 评论列表
    public function commentList($p)
    {
        $this->checkSteward($p);
        if (empty($p['steward_type']) || $p['steward_type'] == 3) {
            $p['steward_type'] = null;
        }

        $page = !empty($p['page']) ? $p['page'] : 1;
        $rows = !empty($p['rows']) ? $p['rows'] : 10;

        $stewardEvaluate = PsSteWardEvaluate::find()->select()
            ->where(['steward_id' => $p['id']])
            ->andFilterWhere(['=', 'community_id', $p['community_id']])
            ->andFilterWhere(['=', 'steward_type', $p['steward_type']]);

        $totals = $stewardEvaluate->count();
        $list = $stewardEvaluate->orderBy('create_at desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();
        foreach ($list as &$v){
            $v['create_at'] = date('Y-m-d H:i', $v['create_at']);
        }

        return ['list' => $list, 'totals' => $totals];
    }

    // 获取后台专属管家列表
    public function getBackendStewardList($params, $pageSize, $page)
    {
        $this->_checkBackendList($params);
        $stewatd = PsSteWard::find()->alias('s')->select('s.name,s.mobile,s.id,s.evaluate,s.praise,s.sex,s.community_id,s.community_name')->distinct()
            ->filterWhere(['or', ['like', 's.name', $params['name'] ?? null], ['like', 's.mobile', $params['name'] ?? null]])
            ->leftJoin(['r' => PsSteWardRelat::tableName()], 's.id = r.steward_id')
            ->andFilterWhere(['r.building_id' => $params['building_id']])->andFilterWhere(['s.community_id' => $params['community_id']])->andWhere(['s.is_del' => 1]);
        if(!empty($params['communityList'])){
            $stewatd->andWhere(['in','s.community_id',$params['communityList']]);
        }
        $count = $stewatd->count();
        if ($count > 0) {
            $allPage = ceil($count/$pageSize);
            $page1 = $allPage>$page?$page:$allPage;
//            $offset = ($page-1)*$pageSize;
            $offset = ($page1-1)*$pageSize;
            $list = $stewatd->orderBy('id desc')->offset($offset)->limit($pageSize)->asArray()->all();
            $this->getGroupBuildingInfo($list, []);
        }

        return ['list' => $list ?? [], 'totals' => $count];
    }

    // 获取楼幢信息
    public function getGroupBuildingInfo(&$data, $building_id = null)
    {
        foreach ($data as $k => &$v) {
            $building = PsSteWardRelat::find()->select(['group_id','group_name','building_name','building_id',"concat(group_name,'',building_name) as merge_name"])
                ->where(['steward_id' => $v['id']])
                ->filterWhere(['building_id' => $building_id])->asArray()->all();
            $v['building_info'] = $building;
            $v['sex_desc'] = PsSteWard::$sex_info[$v['sex']];
            $v['praise_rate'] = $this->getPraiseRate($v['evaluate'], $v['praise']);
            $v['hide_mobile'] = F::processMobile($v['mobile']);
        }
    }

    // 管家删除
    public function deleteBackendSteward($params, $userinfo = '')
    {
        $data = PsCommon::validParamArr(new PsSteWard(), $params, 'delete');
        if (!$data['status']) {
            throw new MyException($data['errorMsg']);
        }
        $info = $this->checkSteward($params);
        $info->is_del = 2;
        $info->save();
        $operate = [
            "community_id" =>$info['community_id'],
            "operate_menu" => "管家管理",
            "operate_type" => '删除管家',
            "operate_content" => '名称'.$info["name"]
        ];
        OperateService::addComm($userinfo, $operate);
    }

    // 新增专属管家
    public function addBackendSteward($params, $userinfo)
    {
        $steward_relat = new PsSteWardRelat();
        $steward = $this->checkStewardBaseInfo($params);
        $trans = \Yii::$app->getDb()->beginTransaction();
        $info = null;

        try {
            $steward->save();

            $javaService = new JavaService();
            $javaParams['token'] = $params['token'];
            $javaParams['id'] = $params['community_id'];
            $javaResult = $javaService->unitTree_($javaParams);
            if(!empty($javaResult['list'])){
                foreach($params['buildings'] as $communityValue){
                    foreach($javaResult['list'] as $key=>$value){
                        foreach($value['children'] as $k=>$v){
                            if($v['id'] == $communityValue){
                                $info[] = [$steward->id, $value['id'],$value['name'],$v['id'],$v['name']];
                                continue;
                            }
                        }
                    }
                }
            }else{
                $this->failed("小区下不存在苑期区幢");
            }
//            foreach ($params['groups'] as $key=>$value) {
//                foreach($value['buildings'] as $k=>$v){
//                    $info[] = [$steward->id, $value['group_id'],$value['group_name'],$v['building_id'],$v['building_name']];
//                }
//            }
            $steward_relat->yiiBatchInsert(['steward_id', 'group_id', 'group_name','building_id','building_name'], $info);
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "管家管理",
                "operate_type" => '新增管家',
                "operate_content" => '名称'.$params["name"]
            ];
            OperateService::addComm($userinfo, $operate);
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
    }

    // 管家修改
    public function editBackendSteward($params, $userinfo = '')
    {
        $steward_relat = new PsSteWardRelat();
        $this->checkStewardBaseInfo($params,false);
        $steward = $this->checkSteward($params);
        $trans = \Yii::$app->getDb()->beginTransaction();
        $info = null;
        try {
            $steward->name = $params['name'];
            $steward->mobile = $params['mobile'];
            $steward->sex = $params['sex'];
            $steward->save();

            $javaService = new JavaService();
            $javaParams['token'] = $params['token'];
            $javaParams['id'] = $params['community_id'];
            $javaResult = $javaService->unitTree_($javaParams);
            if(!empty($javaResult['list'])){
                foreach($params['buildings'] as $communityValue){
                    foreach($javaResult['list'] as $key=>$value){
                        foreach($value['children'] as $k=>$v){
                            if($v['id'] == $communityValue){
                                $info[] = [$steward->id, $value['id'],$value['name'],$v['id'],$v['name']];
                                continue;
                            }
                        }
                    }
                }
            }else{
                $this->failed("小区下不存在苑期区幢");
            }

//            foreach ($params['groups'] as $key=>$value) {
//                foreach($value['buildings'] as $k=>$v){
//                    $info[] = [$steward->id, $value['group_id'],$value['group_name'],$v['building_id'],$v['building_name']];
//                }
//            }
            PsSteWardRelat::deleteAll(['steward_id' => $steward->id]);
            $steward_relat->yiiBatchInsert(['steward_id', 'group_id', 'group_name','building_id','building_name'], $info);
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "管家管理",
                "operate_type" => '修改管家',
                "operate_content" => '名称'.$params["name"]
            ];
            OperateService::addComm($userinfo, $operate);
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
    }

    public function getOptionBuildingInfo($params)
    {
        if (empty($params['community_id'])) {
            throw new MyException('小区ID不能为空');
        }
        $data = PsCommunityBuilding::find()->where(['community_id' => $params['community_id']])->asArray()->all();
        $building_info = [];
        $group_info = [];
        foreach ($data as $k => $v) {
            if (!in_array($v['group_id'],$group_info)) {
                $group_info[] = $v['group_id'];
                $building_info[] = [
                    'title' => $v['group_name'],
                    // 乘以负数 避免和children里的value重复 重复的话前端选择会有问题说 相同value的都会一起勾选说
                    'value' => $v['group_id']*'-1', 
                    'children' => [
                        ['title' => $v['group_name'].$v['name'],
                        'value' => $v['id']]
                    ]
                ];
            } else {
                foreach ($building_info as $kk => $vv) {
                    if ($vv['value'] == $v['group_id']*'-1') {
                        $building_info[$kk]['children'][] = ['title' => $v['group_name'].$v['name'], 'value' => $v['id']];
                    }
                }
            }
        }
        return $building_info;
    }

    // 基础信息验证
    public function checkStewardBaseInfo($params, $type = true)
    {
        if ($type ) {
            $flag = true;
            $scenario = 'add';
        } else {
            $flag = false;
            $scenario = 'edit';
        }
        $steward = new PsSteWard();
        $data = PsCommon::validParamArr($steward, $params, $scenario);
        if (!$data['status']) {
            throw new MyException($data['errorMsg']);
        }
        $this->checkMobile($params, $flag);
        $this->checkBuilding($params);
        return $steward;
    }

    // 检查用户是否存在
    public function checkSteward($params)
    {
        $steward = PsSteWard::find()->where(['community_id' => $params['community_id'],'id' => $params['id'],'is_del' => 1])->one();
        if (empty($steward)) {
            throw new MyException('用户不存在');
        }
        return $steward;
    }

    // 管家手机号验证
    public function checkMobile($params,$type = true)
    {
        $steward = PsSteWard::find()->where(['community_id' => $params['community_id'],'mobile' => $params['mobile'],'is_del' =>1])->one();
        if ($type) {
            if (!empty($steward)) {
                throw new MyException('手机号已存在');
            }
        } else {
            if (!empty($steward)) {
                if ($steward->id != $params['id']) {
                    throw new MyException('手机号已存在');
                }
            }
        }
    }

    // 楼幢信息检查
    public function checkBuilding($params)
    {
        if (empty($params['buildings']) || !is_array($params['buildings'])) {
            throw new MyException('楼幢格式错误');
        }

        foreach($params['buildings'] as $value){
            $steward = PsSteWard::find()->alias('s')->select('s.id,r.group_name,r.building_name')
                ->leftJoin(['r' => PsSteWardRelat::tableName()], 's.id = r.steward_id')->where(['s.is_del' => 1,'r.building_id' => $value])->asArray()->one();
            if (!empty($steward)) {
                if (isset($params['id'])) { //新增场景
                    if ($steward->id != $params['id']) {
                        throw new MyException($steward['group_name'].$steward['building_name'].'已存在管家');
                    }
                } else { //编辑场景
                    throw new MyException($steward['group_name'].$steward['building_name'].'已存在管家');
                }
            }
        }
    }

    // 计算好评率
    public function getPraiseRate($total, $praise)
    {
        if ($total != 0) {
            $number = (int)($praise/$total*100);
            return $number.'%';
        } else {
            return '0%';
        }
    }

    //获取管家评价列表
    public function stewardListOfC($params){
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
        foreach ($resultAll as $result){
            $result['create_at'] = date('Y-m-d H:i',$result['create_at']);
            $list[] = $result;
        }

        return $this->success(['list'=>$list,'total'=>$total]);
    }

    //管家公用的搜索
    public function _search($params){
        return PsSteWardEvaluate::find()
            ->where(['steward_id' => $params['id']])
            ->andFilterWhere(['=', 'community_id', $params['community_id']])
            ->andFilterWhere(['=', 'steward_type', $params['steward_type']]);
    }

    //获取管家详情
    public function stewardInfoOfC($params){
        $community_id = !empty($params['community_id']) ? $params['community_id'] : '';
        $user_id = !empty($params['user_id']) ? $params['user_id'] : '';
        $id = !empty($params['id']) ? $params['id'] : '';
        if (!$community_id || !$id || !$user_id) {
            return $this->failed('参数错误！');
        }
        //获取管家信息
        $steward = PsSteWard::find()->select('id,name,mobile,evaluate,praise')->where(['community_id' => $community_id,'is_del'=>'1','id'=>$id])->asArray()->one();
        if(empty($steward)){
            return $this->failed('管家不存在！');
        }
        $steward['praise_rate'] = !empty($steward['evaluate'])?floor($steward['praise'] / $steward['evaluate'] * 100):'0';
        //获取管家评价的标签排行榜,取前六条数据
        $result =  PsSteWardEvaluate::find()->alias('eval')
            ->select(['rela.tag_type as label_id,count(rela.id) as total'])
            ->leftJoin(["rela"=>PsSteWardTag::tableName()], "eval.id=rela.evaluate_id")
            ->where(['eval.steward_id' => $params['id']])
            ->andFilterWhere(['=', 'eval.community_id', $community_id])
            ->groupBy("rela.id")
            ->orderBy("total desc")
            ->limit("6")->asArray()->all();
        $label_list = [];
        if(!empty($result)){
            foreach ($result as $label){
                $label['name'] = $this->getStewardLabel($label['label_id']);
                $label_list[] = $label;
            }
        }
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

    //添加管家评价
    public function addStewardOfC($params){

        $trans = Yii::$app->db->beginTransaction();
        try{
            $addParams['community_id'] = !empty($params['community_id']) ? $params['community_id'] : '';
            $addParams['user_id'] = !empty($params['user_id']) ? $params['user_id'] : '';
            $addParams['user_name'] = !empty($params['user_name']) ? $params['user_name'] : '';
            $addParams['user_mobile'] = !empty($params['user_mobile']) ? $params['user_mobile'] : '';
            $addParams['room_id'] = !empty($params['room_id']) ? $params['room_id'] : '';
            $addParams['room_address'] = !empty($params['room_address']) ? $params['room_address'] : '';
            $addParams['label_id'] = !empty($params['label_id']) ? $params['label_id'] : '';
            $addParams['steward_id'] = !empty($params['steward_id']) ? $params['steward_id'] : '';
            $addParams['steward_type'] = !empty($params['steward_type']) ? $params['steward_type'] : '';     //1表扬 2批评
            $addParams['content'] = !empty($params['content']) ? $params['content'] : '';
            $addParams['avatar'] = !empty($params['avatar']) ? $params['avatar'] : '';

            $model = new PsSteWardEvaluate(['scenario'=>'add']);
            if($model->load($addParams,'')&&$model->validate()){
                //获得java会员信息
                $javaService = new JavaOfCService();
                $javaParams['token'] = $params['token'];
                $result = $javaService->memberBase($javaParams);
                $avatar = !empty($result['avatar'])?$result['avatar']:'';

                $info = '';
                foreach ($addParams['label_id'] as $label){
                    $info.=$this->getStewardLabel($label).',';
                }
                $info = substr($info, 0, -1);
                if(!$model->save()){
                    return $this->failed('新增失败！');
                }
                $content = !empty($content)?$info.','.$content:$info;
                PsSteWardEvaluate::updateAll(['content'=>$content,'avatar'=>$avatar],['id'=>$model->id]);
                //更新管家的评价数量
                $ward = PsSteWard::model()->find()->where(['id'=>$model->steward_id])->one();
                $ward->evaluate=$ward->evaluate+1;
                if($addParams['steward_type']==1){
                    $ward->praise=$ward->praise+1;
                }
                $ward->save();

                //保存标签
                $stewardTag = new PsSteWardTag();
                $tagInfo = [];
                foreach($addParams['label_id'] as $value){
                    $tagInfo[] = [$model->steward_id, $model->id,$value];
                }
                $stewardTag->yiiBatchInsert(['steward_id', 'evaluate_id', 'tag_type'], $tagInfo);
                $trans->commit();
                return $this->success(['id'=>$model->attributes['id']]);
            }else{
                $msg = array_values($model->errors)[0][0];
                return $this->failed($msg);
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }


    public static function getStewardLabel($index = 0)
    {
        //好评
        $praise =  [
            ["key" => "1", "name" => "态度好服务棒"],
            ["key" => "2", "name" => "神准时"],
            ["key" => "3", "name" => "服务规范"],
            ["key" => "4", "name" => "诚恳心善"],
            ["key" => "5", "name" => "专业细心"],
            ["key" => "6", "name" => "文明礼貌"],
            ["key" => "7", "name" => "全程跟进"],

        ];
        //差评
        $negative =  [
            ["key" => "50", "name" => "态度恶劣"],
            ["key" => "51", "name" => "响应速度慢"],
            ["key" => "52", "name" => "敷衍马虎"],
            ["key" => "53", "name" => "没有责任心"],
            ["key" => "54", "name" => "有待提高"],
            ["key" => "55", "name" => "服务不规范"],
        ];
        if(!empty($index)){
            //合并两个数组-数据查不到就设置为空   '-'
            $label_list = array_merge($praise,$negative);
            foreach ($label_list as $list){
                if($list['key']==$index){
                    return $list['name'];break;
                }
            }
            return '-';
        }else{
            $label['praise'] = $praise;
            $label['negative'] = $negative;
            return $label;
        }
    }

    /*
     * 用户管家列表
     */
    public function userStewardListOfC($params){

        if(empty($params['community_id'])){
            return $this->failed("小区id必填");
        }

        $javaService = new JavaOfCService();
        $javaParams['token'] = $params['token'];
        $javaResult = $javaService->myRoomList($javaParams);
        print_r($javaResult);die;
        $builds = [];
        if(!empty($javaResult['certifiedList'])){
            foreach($javaResult['certifiedList'] as $key=>$value){
                if($value['communityId'] == $params['community_id']){

                }
            }
        }
    }
}
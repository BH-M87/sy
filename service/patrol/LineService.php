<?php
/**
 * Created by PhpStorm.
 * User: zhangqiang
 * Date: 2019-08-12
 * Time: 16:11
 */

namespace service\patrol;


use app\models\PsCommunityModel;
use app\models\PsPatrolLine;
use app\models\PsPatrolLinePoints;
use app\models\PsPatrolPlan;
use app\models\PsPatrolPoints;
use app\models\PsPatrolTask;
use common\core\PsCommon;
use service\BaseService;
use service\rbac\OperateService;
use yii\base\Exception;
use Yii;

class LineService extends BaseService
{
    /**
     * 搜索条件
     */
    private function _searchDeal($data){
        $model = PsPatrolLine::find()
            ->alias('l')
            ->distinct(['l.id'])
            ->select(['l.*'])
            ->leftJoin(['lp'=>PsPatrolLinePoints::tableName()],'lp.line_id = l.id')
            ->leftJoin(['p'=>PsPatrolPoints::tableName()],'lp.point_id = p.id')
            ->where(['l.community_id' => $data['community_id'],'l.is_del'=>1,'p.is_del'=>1])
            ->andWhere(['like','p.name',PsCommon::get($data,'points_name')])
            ->andFilterWhere(['like','l.name',PsCommon::get($data,'name')]);
        $model->andFilterWhere(['like','l.head_name',PsCommon::get($data,'head')])->orFilterWhere(['like','l.head_moblie',PsCommon::get($data,'head')]);
        return $model;
    }

    /**
     * 巡更点列表
     * @param $data
     * @param $page
     * @param $pageSize
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getList($data, $page, $pageSize)
    {
        $offset = ($page - 1) * $pageSize;
        $list = self::_searchDeal($data)->offset($offset)->limit($pageSize)->orderBy('l.created_at desc')->asArray()->all();
        $total = self::_searchDeal($data)->count();
        if ($list) {
            $i = $total - ($page - 1) * $pageSize;
            foreach ($list as $key => $value) {
                //查找中这条线路下面所有已选择的巡更点
                $list[$key]['points_list'] = self::getChooseList($value['id']);
                $list[$key]['tid'] = $i;
                //验证这条巡更线路能否被编辑
                $line = self::_checkTaskByLineId($value['id']);
                $list[$key]['is_edit'] = $line['code'];
                $i--;
            }
        }
        $result['list'] = $list;
        $result['totals'] = $total;
        return $result;

    }


    /**
     * 钉钉获取巡更路线列表
     * @param $data
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function dingGetList($data, $page, $pageSize)
    {
        $totals = PsPatrolLine::find()
            ->where(['community_id' => $data['communitys']])
            ->andWhere(['is_del' => 1])
            ->count('id');
        $offset = ($page - 1) * $pageSize;
        $lines = PsPatrolLine::find()
            ->alias('p')
            ->leftJoin(['m' => PsCommunityModel::tableName()], 'p.community_id=m.id')
            ->select(['p.id', 'p.name', 'p.head_name', 'p.head_moblie', 'p.note', 'm.name as community_name'])
            ->where(['p.community_id' => $data['communitys']])
            ->andWhere(['p.is_del' => 1])
            ->orderBy('p.id desc')
            ->offset($offset)
            ->limit($pageSize)
            ->asArray()
            ->all();
        $re['totals'] = $totals;
        $re['list']   = $lines;
        return $re;
    }




    /**
     * 判断当前的线路id能否被编辑跟删除
     * @param $id
     * @return array
     */
    private function _checkTaskByLineId($id){
        $time = time();
        $task = PsPatrolTask::find()
            ->where(['line_id'=>$id])
            ->andFilterWhere(['<','range_start_time',$time])
            ->andFilterWhere(['>','range_end_time',$time])
            ->asArray()
            ->count('id');
        if($task > 0){
            return $this->failed('当前时间段不可编辑/删除');
        }else{
            return $this->success();
        }
    }

    //处理新增跟编辑必填的一些参数
    private function _checkDataDeal($data,$from ='1'){
        //物业后台校验巡更点必填
        if ($from == 1 && empty($data['points_list'])) {
            return $this->failed('巡更地点为必填');
        }
        $community_id = $data['community_id'];
        //校验是否有错误的巡更点
        if (!empty($data['points_list']) && is_array($data['points_list'])) {

            $points = PsPatrolPoints::find()
                ->where(['is_del' => 1,'community_id'=>$community_id])
                ->andWhere(['id' => $data['points_list']])
                ->asArray()->count();

            if(count($data['points_list']) != $points){
                return $this->failed('有错误巡更点，已删除或id不存在');
            }

        }

        //编辑的时候判断这个线路是否已经开始执行
        if(!empty($data['id'])){
            $check = self::_checkTaskByLineId($data['id']);
            if($check['code'] != 1){
                return $this->failed($check['msg']);
            }
        }
        return $this->success();
    }

    /**
     * 批量新增或删除
     * @param $line_id
     * @param $points
     */
    private function _dealLinePoints($line_id,$points,$type,$line = []){
        if($type == '1'){
            //批量新增
            if (!empty($points) && is_array($points)) {
                $t = Yii::$app->db->beginTransaction();
                try {
                    $attributes = [];
                    foreach ($points as $key) {
                        $attributes['line_id'][] = $line_id;
                        $attributes['point_id'][] = $key;
                    }
                    $lp = PsPatrolLinePoints::model()->batchInsert($attributes);//批量插入数据
                    if(!$lp){

                        throw new Exception("批量插入数据失败");
                    }
                    //因为存在新增的心路还没有被添加到计划里面，因此这里不做回调处理，不加进事务里面
                    TaskService::service()->changeTaskAddByLine($line_id,$points,2,$line);
                    $t->commit(); //提交数据
                    return $this->success();
                } catch (Exception $e) {
                    $t->rollBack();
                    return $this->failed($e->getMessage());
                }
            }
            //单个新增
            if (!empty($points) && is_string($points)) {

                $t = Yii::$app->db->beginTransaction();
                try {
                    $mod = new PsPatrolLinePoints();
                    $mod->line_id = $line_id;
                    $mod->point_id = $points;
                    if(!$mod->save()){
                        throw new Exception("新增失败");
                    }
                    //因为存在新增的心路还没有被添加到计划里面，因此这里不做回调处理，不加进事务里面
                    TaskService::service()->changeTaskAddByLine($line_id,$points,1,$line);
                    $t->commit(); //提交数据
                    return $this->success();
                } catch (Exception $e) {
                    $t->rollBack();
                    return $this->failed($e->getMessage());
                }
            }
        }
        if($type == '2'){
            //批量删除
            if(is_array($points)){
                $t = Yii::$app->db->beginTransaction();
                try {
                    PsPatrolLinePoints::deleteAll(['and','line_id = :line',['in','point_id',$points]],['line'=>$line_id]);
                    //因为存在任务已过期但是计划仍然可以删除的情况，这里就不加进事务里面
                    TaskService::service()->changeTaskDelByLine($line_id,$points,2);
                    $t->commit(); //提交数据
                    return $this->success();
                } catch (Exception $e) {
                    $t->rollBack();
                    return $this->failed($e->getMessage());
                }
            }
            //单个删除
            if(is_string($points)){
                $t = Yii::$app->db->beginTransaction();
                try {
                    if($points == 'all'){
                        PsPatrolLinePoints::deleteAll('line_id = :line',['line'=>$line_id]);
                        //因为存在任务已过期但是计划仍然可以删除的情况，这里就不加进事务里面
                        TaskService::service()->changeTaskDelByLine($line_id,$points,3);
                    } else{
                        $del = PsPatrolLinePoints::find()->where(['point_id'=>$points,'line_id'=>$line_id])->one();
                        if($del && !$del->delete()){
                            throw new Exception("删除失败");
                        }
                        //因为存在任务已过期但是计划仍然可以删除的情况，这里就不加进事务里面
                        TaskService::service()->changeTaskDelByLine($line_id,$points,1);
                    }
                    $t->commit(); //提交数据
                    return $this->success();
                } catch (Exception $e) {
                    $t->rollBack();
                    return $this->failed($e->getMessage());
                }
            }
        }
    }

    /**
     * 线路新增
     * @param $data
     * @param $operator_id
     * @param $operator_name
     * @param $from 1:来自物业后台，2来自钉钉
     * @return array
     */
    public function add($data,$operator_id,$operator_name, $from = '1',$userinfo=[]){
        $check = self::_checkDataDeal($data,$from);
        if($check['code'] != 1){
            return $this->failed($check['msg']);
        }
        //yii2事物
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $mod = new PsPatrolLine();
            $data['is_del'] = 1;
            $data['created_at'] = time();
            $data['operator_id'] = $operator_id;
            $data['operator_name'] = $operator_name;
            $mod->setAttributes($data);
            $mod->save();
            $line_id = $mod->id;
            if ($from == 1) {
                $res = self::_dealLinePoints($line_id,$data['points_list'],1,$data);
                if($res['code'] != 1){
                    return $this->failed($res['msg']);
                }
            }
            $res['record_id'] = $mod->id;
            if (!empty($userinfo)) {
                $content = "线路名称:" . $data['name']."负责人:".$data['head_name'];
                $operate = [
                    "community_id" => $data['community_id'],
                    "operate_menu" => "日常巡更",
                    "operate_type" => "巡更线路新增",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
            }

            $transaction->commit();
            return $this->success($res);
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->failed('保存失败'.$e);

        }

    }

    /**
     * 线路编辑
     * @param $data
     * @param $operator_id
     * @param $operator_name
     * @param $from 1:来自物业后台，2来自钉钉
     * @return array
     */
    public function edit($data,$operator_id,$operator_name,$from = '1',$userinfo=[]){

        $check = self::_checkDataDeal($data,$from);
        if($check['code'] != 1){
            return $this->failed($check['msg']);
        }
        $mod = PsPatrolLine::find()->where(['id'=>$data['id']])->one();
        if ($mod) {
            if ($mod->is_del != 1) {
                return $this->failed("此线路已被删除！");
            }
            if($mod->community_id != $data['community_id']){
                return $this->failed("巡更线路小区id不能变更！");
            }
            $data['operator_id'] = $operator_id;
            $data['operator_name'] = $operator_name;

            //巡更路线与巡更点关系修改
            if (is_array($data['points_list'])) {

                $line_id = $data['id'];
                $lp = PsPatrolLinePoints::find()->select(['point_id'])->where(['line_id' => $line_id])->asArray()->column();
                $points_list = $data['points_list'];
                $add_points = array_diff($points_list, $lp);
                $del_points = array_diff($lp, $points_list);

                if (!empty($add_points)) {
                    $res = self::_dealLinePoints($line_id, $add_points, 1, $data);//新增
                    if ($res['code'] == '0') {
                        return $this->failed($res['msg']);
                    }
                }
                if (!empty($del_points)) {
                    $res = self::_dealLinePoints($line_id, $del_points, 2);//删除
                    if ($res['code'] == '0') {
                        return $this->failed($res['msg']);
                    }
                }
            }

            $mod->setAttributes($data, false);
            if ($mod->save()) {
                $resArr['record_id'] = $mod->id;
                if (!empty($userinfo)) {
                    $content = "线路名称:" . $mod->name."负责人:".$mod->head_name;
                    $operate = [
                        "community_id" => $data['community_id'],
                        "operate_menu" => "日常巡更",
                        "operate_type" => "巡更线路编辑",
                        "operate_content" => $content,
                    ];
                    OperateService::addComm($userinfo, $operate);
                }
                return $this->success($resArr);
            } else {
                return $this->failed('保存失败');
            }
        } else {
            return $this->failed('此线路不存在！');
        }

    }

    /**
     * 线路删除
     * @param $id
     * @param $operator_id
     * @param $operator_name
     * @return array
     */
    public function deleteData($id,$operator_id,$operator_name,$userinfo=[]){
        $mod = PsPatrolLine::findOne($id);
        if ($mod) {
            //判断状态
            if ($mod->is_del != 1) {
                return $this->failed('此巡更线路已被删除！');
            }
            $plan = PsPatrolPlan::find()->where(['line_id'=>$id,'is_del'=>1])->asArray()->count();
            //如果有没被删除的计划调用了这个线路，则这个线路不能被删除
            if($plan > 0){
                return $this->failed("此线路关联着计划，不能被删除");
            }

            //编辑的时候判断这个线路是否已经开始执行
            $check = self::_checkTaskByLineId($id);
            if($check['code'] != 1){
                return $this->failed($check['msg']);
            }
            $t = Yii::$app->db->beginTransaction();
            try {
                $mod->is_del = 0;
                $mod->operator_id = $operator_id;
                $mod->operator_name = $operator_name;
                if ($mod->save()) {
                    $res = self::_dealLinePoints($id,'all',2);
                    if($res['code'] != 1){
                        throw new Exception($res['msg']);
                    }
                    $t->commit(); //提交数据
                    if (!empty($userinfo)) {
                        $content = "线路名称:" . $mod->name;
                        $operate = [
                            "community_id" => $mod->community_id,
                            "operate_menu" => "日常巡更",
                            "operate_type" => "巡更线路删除",
                            "operate_content" => $content,
                        ];
                        OperateService::addComm($userinfo, $operate);
                    }
                    return $this->success();
                }else{
                    throw new Exception("删除失败");
                }
            } catch (Exception $e) {
                $t->rollBack();
                return $this->failed($e->getMessage());
            }
        } else {
            return $this->failed('此巡更线路不存在');
        }
    }

    /**
     * 线路详情
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getDetail($id){
        $detail = PsPatrolLine::find()
            ->alias('line')
            ->leftJoin(['m' => PsCommunityModel::tableName()], 'line.community_id=m.id')
            ->select(['line.*', 'm.name as community_name'])
            ->where(['line.id'=>$id])
            ->asArray()
            ->one();
        if ($detail['is_del'] != 1) {
            return $this->failed('此巡更线路已被删除！');
        }
        if (!$detail) {
            return $this->failed('此巡更线路不存在！');
        } else {
            //线路详情处理
            $detail['created_at'] = $detail['created_at'] ? date("Y-m-d H:i",$detail['created_at']) : '';
            $choose_list = self::getChooseList($id);
            $detail['choose_list'] = $choose_list;
            $detail['unchoose_list'] = self::getUnChooseList($detail['community_id'],$id,$choose_list);
        }
        return $this->success($detail);
    }

    /**
     * 查询所有的巡更点
     * @param $line_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getPoints($line_id)
    {
        $detail = PsPatrolLine::find()
            ->select(['community_id', 'id', 'name', 'is_del'])
            ->where(['id' => $line_id])
            ->asArray()
            ->one();
        if (!$detail) {
            return $this->failed('此巡更线路不存在！');
        }

        $points = PsPatrolPoints::find()
            ->select(['id as key', 'name as title'])
            ->where(['community_id' => $detail['community_id']])
            ->andWhere(['is_del' => 1])
            ->asArray()
            ->all();

        $hasCheckPoints = $this->getLinePointIds($line_id);
        foreach ($points as $k => $val) {
            $points[$k]['is_checked'] = 0;
            if (in_array($val['key'], $hasCheckPoints)) {
                $points[$k]['is_checked'] = 1;
            }
        }
        return $this->success($points);
    }

    /**
     * 查询巡更路线已经选择的巡更点id
     * @param $line_id
     * @return array
     */
    public function getLinePointIds($line_id)
    {
        $ids = PsPatrolLinePoints::find()
            ->select(['point_id'])
            ->where(['line_id' => $line_id])
            ->asArray()
            ->column();
        return $ids;
    }

    /**
     * 巡更点列表--已选择
     * @param $line_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getChooseList($line_id){
        $list = PsPatrolLinePoints::find()
            ->alias('lp')
            ->leftJoin(['p' => PsPatrolPoints::tableName()], 'p.id=lp.point_id')
            ->select(['p.id as key','p.name as title'])
            ->where(['line_id'=>$line_id])
            ->andFilterWhere(['p.is_del'=>1])
            ->asArray()->all();
        if($list){
            return $list;
        }else{
            return [];
        }

    }


    /**
     * 巡更点列表--未选择
     * @param $community_id
     * @param string $line_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getUnChooseList($community_id,$line_id = '',$choose_list = []){
        $list = PsPatrolPoints::find()->select(['id as key','name as title'])->where(['community_id'=>$community_id,'is_del'=>1])->asArray()->all();
        if(empty($line_id)){
            return $list;
        }
        if(empty($choose_list)){
            $res = self::getChooseList($line_id);
        }else{
            $res = $choose_list;
        }
        return $this->get_diff_array_by_key($list,$res);
    }

    /**
     * 根据线路ID查询已配置的巡更点详情
     * @param $lineId
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getPointsByLineId($lineId)
    {
        return PsPatrolLinePoints::find()
            ->alias('lp')
            ->leftJoin(['p' => PsPatrolPoints::tableName()], 'p.id=lp.point_id')
            ->select(['p.id', 'p.name', 'p.location_name'])
            ->where(['lp.line_id' => $lineId])
            ->andWhere(['p.is_del' => 1])
            ->asArray()
            ->all();
    }

    /**
     * 以id-name的形式获取线路的下拉列表，用于巡更记录的筛选
     * @param $community_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getRecordLineList($community_id){
        $list = PsPatrolLine::find()->select(['id as line_id','name as line_name'])->where(['community_id'=>$community_id,'is_del'=>1])->asArray()->all();
        return $list;
    }
}
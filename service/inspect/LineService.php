<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/12
 * Time: 10:42
 */

namespace service\inspect;

use common\MyException;
use service\BaseService;
use Yii;

class LineService extends BaseService
{
    /**  物业后台接口 start */

    //新增
    public function add($params)
    {
        //TODO 统一验证
        $reqArr['created_at'] = time();
        $model = new PsInspectLine();
        $model->scenario = 'add';  # 设置数据验证场景为 新增
        $model->load($reqArr, '');   # 加载数据
        if ($model->validate()) {  # 验证数据
            $trans = Yii::$app->getDb()->beginTransaction();
            try {
                //查看巡检线路点名称是否重复
                $line = PsInspectLine::find()->where(['name' => $reqArr['name'], 'community_id' => $reqArr['community_id']])->one();
                if (!empty($line)) {
                    return $this->failed('巡检线路已存在!');
                }
                if (!is_array($reqArr['pointList'])) {
                    return $this->failed('巡检点格式错误!');
                }
                if (count($reqArr['pointList']) < 1) {
                    return $this->failed('巡检点不能为空!');
                }
                if ($model->save()) {  # 保存新增数据
                    foreach ($reqArr['pointList'] as $point_id) {
                        $point = PsInspectPoint::findOne($point_id);
                        if (empty($point)) {
                            return $this->failed('巡检点不存在!');
                        }
                        $pointArr['point_id'] = $point_id;
                        $pointArr['line_id'] = $model->id;
                        Yii::$app->db->createCommand()->insert('ps_inspect_line_point', $pointArr)->execute();
                    }
                }
                //提交事务
                $trans->commit();
                if (!empty($userinfo)) {
                    /*$content = "线路名称:".$reqArr['name'].'负责人:'.$reqArr['head_name'];
                    $operate = [
                        "community_id" =>$reqArr['community_id'],
                        "operate_menu" => "设备巡检",
                        "operate_type" => "巡检线路新增",
                        "operate_content" => $content,
                    ];
                    OperateService::addComm($userinfo, $operate);*/
                }
            } catch (\Exception $e) {
                $trans->rollBack();
                return $this->failed($e->getMessage());
            }
            return $this->success([]);
        }
    }

    //编辑
    public function edit($params)
    {
        //TODO 统一验证
        if (empty($reqArr['id'])) {
            throw new MyException('巡检线路id不能为空');
        }
        $model = PsInspectLine::findOne($reqArr['id']);
        if (empty($model)) {
            throw new MyException('巡检线路不存在!');
        }
        $model->scenario = 'edit';  # 设置数据验证场景为 新增
        $model->load($reqArr, '');   # 加载数据
        if ($model->validate()) {  # 验证数据
            $trans = Yii::$app->getDb()->beginTransaction();
            try {
                //查看巡检线路点名称是否重复
                $line = PsInspectLine::find()->where(['name' => $reqArr['name'], 'community_id' => $reqArr['community_id']])->andWhere(['!=', 'id', $reqArr['id']])->one();
                if (!empty($line)) {
                    throw new MyException('巡检线路已存在!');
                }
                if (!is_array($reqArr['pointList'])) {
                    throw new MyException('巡检点格式错误!');
                }
                if (count($reqArr['pointList']) < 1) {
                    throw new MyException('巡检点不能为空!');
                }
                if ($model->save()) {  # 保存新增数据
                    //先清空老数据
                    PsInspectLinePoint::deleteAll(['line_id' => $reqArr['id']]);
                    foreach ($reqArr['pointList'] as $point_id) {
                        $point = PsInspectPoint::findOne($point_id);
                        if (empty($point)) {
                            throw new MyException('巡检点不存在!');
                        }
                        $pointArr['point_id'] = $point_id;
                        $pointArr['line_id'] = $model->id;
                        Yii::$app->db->createCommand()->insert('ps_inspect_line_point', $pointArr)->execute();
                    }
                }
                //提交事务
                $trans->commit();
                if (!empty($userinfo)) {
                    $content = "线路名称:" . $reqArr['name'] . '负责人:' . $reqArr['head_name'];
                    $operate = [
                        "community_id" => $reqArr['community_id'],
                        "operate_menu" => "设备巡检",
                        "operate_type" => "巡检线路编辑",
                        "operate_content" => $content,
                    ];
                    OperateService::addComm($userinfo, $operate);
                }
            } catch (\Exception $e) {
                $trans->rollBack();
                return $this->failed($e->getMessage());
            }
            return $this->success([]);
        }
    }

    //详情
    public function view($params)
    {
        if (empty($reqArr['id'])) {
            throw new MyException('巡检线路id不能为空');
        }
        $result = PsInspectLine::find()->alias("line")
            ->where(['line.id' => $reqArr['id']])
            ->select(['line.id', 'comm.id as community_id', 'comm.name as community_name', 'line.name', 'line.head_name', 'line.head_mobile'])
            ->leftJoin("ps_community comm", "comm.id=line.community_id")
            ->asArray()->one();
        if (!empty($result)) {
            //获取对应的巡检点
            $line_point = PsInspectLinePoint::find()->alias("line_point")
                ->where(['line_point.line_id' => $reqArr['id']])
                ->select(['point.id', 'point.name'])
                ->leftJoin("ps_inspect_point point", "point.id=line_point.point_id")
                ->asArray()->all();
            $result['pointList'] = $line_point;
            return $this->success($result);
        }
        throw new MyException('巡检线路不存在');
    }

    //列表
    public function propertyList($params)
    {
        $page = PsCommon::get($params, 'page');
        $rows = PsCommon::get($params, 'rows');
        $query = self::lineSearch($params);
        $totals = $query->count();
        if ($totals == 0) {
            return $this->success(['list' => [], 'totals' => $totals]);
        }
        $list = $query
            ->select('A.id, A.community_id, A.name, A.head_name, A.head_mobile')
            ->orderBy('A.id desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();
        if (!empty($model)) {
            foreach ($model as $k => $v) {
                $model[$k]['pointList'] = PsInspectLinePoint::find()->alias("A")
                    ->where(['A.line_id' => $v['id']])
                    ->select(['B.id', 'B.name'])
                    ->leftJoin("ps_inspect_point B", "B.id = A.point_id")
                    ->asArray()->all();
            }
        }
        return $this->success(['list' => $list, 'totals' => $totals]);
    }
    //删除
    public function del($params)
    {
        if (empty($reqArr['id'])) {
            throw new MyException('巡检线路id不能为空');
        }
        //查询线路是否有配置巡检点
        $planPoint = PsInspectPlan::find()->where(['line_id' => $reqArr['id']])->all();
        if (!empty($planPoint)) {
            throw new MyException('请先修改对应计划！');
        }
        $info = PsInspectLine::find()->select('name,head_name')->where(['id' => $reqArr['id']])->asArray()->one();
        $result = PsInspectLine::deleteAll(['id' => $reqArr['id']]);
        if (!empty($result)) {
            //删除对于关系
            PsInspectLinePoint::deleteAll(['line_id' => $reqArr['id']]);
            if (!empty($userinfo)) {
                $name = $info['name'] ?? "";
                $head_name = $info['head_name'] ?? "";
                $content = "线路名称:" . $name . '负责人:' . $head_name;
                $operate = [
                    "community_id" => $reqArr['community_id'],
                    "operate_menu" => "设备巡检",
                    "operate_type" => "巡检线路删除",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
            }
            return $this->success($result);
        }
        throw new MyException('删除失败，巡检线路不存在');
    }

    // 巡检线路 搜索
    private static function lineSearch($params)
    {
        $model = PsInspectLine::find()->alias("A")->distinct()
            ->leftJoin("ps_inspect_line_point B", "A.id = B.line_id")
            ->filterWhere(['like', 'A.head_name', PsCommon::get($params, 'head_name')])
            ->orFilterWhere(['like', 'A.head_mobile', PsCommon::get($params, 'head_name')])
            ->andFilterWhere(['=', 'B.point_id', PsCommon::get($params, 'point_id')])
            ->andFilterWhere(['=', 'A.community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'A.id', PsCommon::get($params, 'line_id')]);
        return $model;
    }

    /**  物业后台接口 end */

    /**  钉钉接口 start */

    /**  钉钉接口 end */

    /**  公共接口 start */

    /**  公共接口 end */
}
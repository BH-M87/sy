<?php
namespace service\inspect;

use Yii;

use common\core\PsCommon;
use common\MyException;

use service\BaseService;

use app\models\PsInspectLine;
use app\models\PsInspectLinePoint;
use app\models\PsInspectPoint;

class LineService extends BaseService
{
    /**  物业后台接口 start */

    // 新增
    public function add($p, $userInfo = [])
    {
        $p['created_at'] = time();
        self::checkCommon($p, $userInfo, 'add');
    }

    // 编辑
    public function edit($p, $userInfo = [])
    {
        self::checkCommon($p, $userInfo, 'update');
    }

    protected static function checkCommon($p, $userInfo = [], $scenario = 'add')
    {
        $model = new PsInspectLine();
        $p = $model->validParamArr($p, $scenario);
        if ($scenario == 'update') {
            $model = PsInspectLine::findOne($p['id']);
            if (empty($model)) {
                throw new MyException('巡检线路不存在!');
            }
        } else {
            unset($p['id']);
        }
        if (!is_array($p['pointList'])) {
            throw new MyException('巡检点格式错误!');
        }
        if (count($p['pointList']) < 1) {
            throw new MyException('巡检点不能为空!');
        }
        //查看巡检线路点名称是否重复
        $query = PsInspectLine::find()->where(['name' => $p['name'], 'community_id' => $p['community_id']]);
        if ($scenario == 'update') {
            $line = $query->andWhere(['!=', 'id', $p['id']])->one();
        } else {
            $line = $query->one();
        }
        if (!empty($line)) {
            throw new MyException('巡检线路已存在!');
        }
        $model->setAttributes($p);
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            if ($model->save()) {  # 保存新增数据
                //先清空老数据
                if ($scenario == 'update') {
                    PsInspectLinePoint::deleteAll(['line_id' => $p['id']]);
                }
                foreach ($p['pointList'] as $point_id) {
                    $point = PsInspectPoint::findOne($point_id);
                    if (empty($point)) {
                        throw new MyException('巡检点不存在!');
                    }
                    $pointArr['point_id'] = $point_id;
                    $pointArr['line_id'] = $model->id;
                    Yii::$app->db->createCommand()->insert('ps_inspect_line_point', $pointArr)->execute();
                }
            } else {
                throw new MyException('操作失败');
            }
            //提交事务
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
        return true;
    }

    //详情
    public function view($params)
    {
        if (empty($params['id'])) {
            throw new MyException('巡检线路id不能为空');
        }
        $model = self::lineOne($params['id']);
        $result = $model->toArray();
        if (!empty($result)) {
            //获取对应的巡检点
            $line_point = PsInspectLinePoint::find()->alias("line_point")
                ->where(['line_point.line_id' => $params['id']])
                ->select(['point.id', 'point.name'])
                ->leftJoin("ps_inspect_point point", "point.id=line_point.point_id")
                ->asArray()->all();
            $result['pointList'] = $line_point;
            return $result;
        }
        throw new MyException('巡检线路不存在');
    }

    //列表
    public function lineList($params)
    {
        $page = PsCommon::get($params, 'page');
        $rows = PsCommon::get($params, 'rows');
        $query = self::lineSearch($params);
        $totals = $query->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => $totals];
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
        return ['list' => $list, 'totals' => $totals];
    }

    //删除
    public function del($params, $userInfo = [])
    {
        if (empty($params['id'])) {
            throw new MyException('巡检线路id不能为空');
        }
        //查询线路是否有配置巡检点
        $planPoint = PlanService::planOne('','','','id',$params['id']);
        if (!empty($planPoint)) {
            throw new MyException('请先修改对应计划！');
        }
        $info = PsInspectLine::find()->select('name,head_name')->where(['id' => $params['id']])->asArray()->one();
        $result = PsInspectLine::deleteAll(['id' => $params['id']]);
        if (!empty($result)) {
            //删除对于关系
            PsInspectLinePoint::deleteAll(['line_id' => $params['id']]);
            if (!empty($userinfo)) {
                $name = $info['name'] ?? "";
                $head_name = $info['head_name'] ?? "";
            }
            return true;
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

    public static function lineOne($id, $select = "")
    {
        $select = $select ?? ['line.id', 'comm.id as community_id', 'comm.name as community_name', 'line.name', 'line.head_name', 'line.head_mobile'];
        return PsInspectLine::find()->alias("line")
            ->where(['line.id' => $id])
            ->select($select)
            ->leftJoin("ps_community comm", "comm.id=line.community_id")
            ->one();
    }

    //巡检线路列表-线路新增页面使用
    public function getlineList($params)
    {
        $arr = PsInspectLine::find()->where(['community_id' => $params['community_id']])
            ->select(['id', 'name'])->orderBy('id desc')->asArray()->all();
        return ['list' => $arr];
    }
    /**  物业后台接口 end */

    /**  钉钉接口 start */

    //巡检线路列表
    public function getList($params)
    {
        $page = !empty($params['page']) ? $params['page'] : 1;
        $rows = !empty($params['rows']) ? $params['rows'] : 5;
        $arr = PsInspectLine::find()->alias("line")
            ->where(['community_id' => $params['communitys']])
            ->select(['line.id', 'comm.name as community_name', 'line.name', 'line.head_name', 'line.head_mobile'])
            ->leftJoin("ps_community comm", "comm.id=line.community_id")
            ->orderBy('line.id desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();
        return ['list' => $arr];
    }

    /**  钉钉接口 end */
}
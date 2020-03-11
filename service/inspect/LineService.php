<?php
namespace service\inspect;

use Yii;

use common\core\PsCommon;
use common\MyException;

use service\BaseService;
use service\property_basic\JavaService;

use app\models\PsInspectLine;
use app\models\PsInspectLinePoint;
use app\models\PsInspectPoint;

class LineService extends BaseService
{
    /**  物业后台接口 start */

    // 新增
    public function add($p, $userInfo = [])
    {
        $p['createAt'] = time();
        self::checkCommon($p, $userInfo, 'add');
    }

    // 编辑
    public function edit($p, $userInfo = [])
    {
        $p['img'] = !empty($p['img']) ? $p['img'] : '';
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
        }

        if (!is_array($p['point'])) {
            throw new MyException('巡检点格式错误，传数组!');
        }

        if (count($p['point']) < 1) {
            throw new MyException('巡检点不能为空!');
        }

        // 查看巡检线路点名称是否重复
        $query = PsInspectLine::find()->where(['name' => $p['name'], 'communityId' => $p['communityId']]);
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
                // 先清空老数据
                if ($scenario == 'update') {
                    PsInspectLinePoint::deleteAll(['lineId' => $p['id']]);
                }

                foreach ($p['point'] as $point_id) 
                {
                    $point = PsInspectPoint::findOne($point_id);
                    if (empty($point)) {
                        throw new MyException('巡检点不存在!');
                    }
                    $pointArr['pointId'] = $point_id;
                    $pointArr['lineId'] = $model->id;
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

    // 详情
    public function view($p)
    {
        if (empty($p['id'])) {
            throw new MyException('巡检线路id不能为空');
        }

        $r = self::lineOne($p['id'])->toArray();
        if (!empty($r)) {
            // 获取对应的巡检点
            $r['pointList'] = PsInspectLinePoint::find()->alias("A")->select(['B.id', 'B.name'])
                ->leftJoin("ps_inspect_point B", "B.id = A.pointId")
                ->where(['A.lineId' => $p['id']])
                ->asArray()->all();

            return $r;
        }
        throw new MyException('巡检线路不存在');
    }

    // 列表
    public function lineList($p)
    {
        $page = PsCommon::get($p, 'page');
        $rows = PsCommon::get($p, 'rows');
        $query = self::lineSearch($p);
        $totals = $query->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => $totals];
        }

        $list = $query
            ->orderBy('A.id desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $arr = PsInspectLinePoint::find()->alias("A")->select(['B.id', 'B.name'])
                    ->leftJoin("ps_inspect_point B", "B.id = A.pointId")
                    ->where(['A.lineId' => $v['id']])
                    ->asArray()->all();
                $v['point'] = '';
                if ($arr) {
                    foreach ($arr as $key => $val) {
                        $point .= $val['name'] . ',';
                    }
                    $v['point'] = substr($point, 0, -1);
                }
                $community = JavaService::service()->communityDetail(['token' => $p['token'], 'id' => $v['communityId']]);
                $v['communityName'] = $community['communityName'];  
            }
        }
        return ['list' => $list, 'totals' => $totals];
    }

    // 删除
    public function del($p, $userInfo = [])
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (is_array($p['id']) && !empty($p['id'])) {
                foreach ($p['id'] as $k => $v) {
                    $model = PsInspectLine::findOne($v);
                    if (empty($model)) {
                        throw new MyException('巡检线路不存在');
                    }

                    // 查询线路是否有配置巡检点
                    $planPoint = PlanService::planOne('','','','id',$v);
                    if (!empty($planPoint)) {
                        throw new MyException('请先修改对应计划！');
                    }

                    $r = PsInspectLine::deleteAll(['id' => $v]);

                    if (!empty($r)) { // 删除对于关系
                        PsInspectLinePoint::deleteAll(['lineId' => $v]);
                    }
                }

                $transaction->commit();
                return true;
            } else {
                throw new MyException('巡检线路id不能为空');
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            return $e->getMessage();
        }
    }

    // 巡检线路 搜索
    private static function lineSearch($p)
    {
        $model = PsInspectLine::find()->alias("A")->distinct()
            ->leftJoin("ps_inspect_line_point B", "A.id = B.lineId")
            ->andFilterWhere(['=', 'B.pointId', PsCommon::get($p, 'pointId')])
            ->andFilterWhere(['=', 'A.communityId', PsCommon::get($p, 'communityId')])
            ->andFilterWhere(['in', 'A.communityId', PsCommon::get($p, 'communityList')])
            ->andFilterWhere(['like', 'A.name', PsCommon::get($p, 'name')])
            ->andFilterWhere(['=', 'A.id', PsCommon::get($p, 'lineId')]);
        return $model;
    }

    public static function lineOne($id, $select = "")
    {
        $select = $select ?? '*';
        return PsInspectLine::find()->alias("line")
            ->where(['line.id' => $id])
            ->select($select)
            ->one();
    }

    // 巡检线路列表-线路新增页面使用
    public function getlineList($p)
    {
        $arr = PsInspectLine::find()->andFilterWhere(['communityId' => $p['communityId']])
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
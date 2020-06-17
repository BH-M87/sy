<?php
namespace service\park;

use Yii;
use yii\db\Query;
use yii\base\Exception;
use common\MyException;
use common\core\PsCommon;
use common\core\Curl;

use service\BaseService;

use service\property_basic\JavaService;

use app\models\PsParkSet;

class SetService extends BaseService
{
    // 新增
    public function addSet($p, $userInfo)
    {
        return self::_saveSet($p, 'add', $userInfo);
    }

    // 编辑
    public function editSet($p, $userInfo)
    {
        return self::_saveSet($p, 'edit', $userInfo);
    }

    public function _saveSet($p, $scenario, $userInfo)
    {
        if ($scenario == 'edit') {
            $model = PsParkSet::findOne($p['id']);
            if (empty($model)) {
                throw new MyException('数据不存在!');
            }
        }

        $community = JavaService::service()->communityDetail(['token' => $p['token'], 'id' => $p['community_id']]);

        $param['id'] = $p['id'];
        $param['community_id'] = $p['community_id'];
        $param['community_name'] = $community['communityName'];
        $param['cancle_num'] = $p['cancle_num'];
        $param['late_at'] = $p['late_at'];
        $param['due_notice'] = $p['due_notice'];
        $param['black_num'] = $p['black_num'];
        $param['appointment'] = $p['appointment'];
        $param['appointment_unit'] = $p['appointment_unit'];
        $param['lock'] = $p['lock'];
        $param['lock_unit'] = $p['lock_unit'];
        $param['min_time'] = $p['min_time'];
        $param['integral'] = $p['integral'];

        $model = new PsParkSet(['scenario' => $scenario]);

        if (!$model->load($param, '') || !$model->validate()) {
            throw new MyException($this->getError($model));
        }

        if (!$model->saveData($scenario, $param)) {
            throw new MyException($this->getError($model));
        }

        $id = $scenario == 'add' ? $model->attributes['id'] : $p['id'];

        return ['id' => $id];
    }

    // 详情
    public function showSet($p)
    {
        $r = PsParkSet::find()->asArray()->one();
        if (!empty($r)) {
            return $r;
        }

        throw new MyException('数据不存在!');
    }

    // 黑名单 列表
    public function listBlack($p)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : '1';
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : '10';

        $totals = self::searchBlack($p)->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::searchBlack($p)
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

    // 黑名单 列表参数过滤
    private static function searchBlack($p)
    {
        $startAt = !empty($p['startAt']) ? strtotime($p['startAt']) : '';
        $endAt = !empty($p['endAt']) ? strtotime($p['endAt'] . '23:59:59') : '';

        $m = PsParkBlack::find()
            ->filterWhere(['like', 'name', $p['name']])
            ->andFilterWhere(['>=', 'startAt', $startAt])
            ->andFilterWhere(['<=', 'endAt', $endAt]);
        return $m;
    }
}
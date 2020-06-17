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
        $param['if_one'] = $p['if_one'];
        $param['if_visit'] = $p['if_visit'];
        $param['cancle_num'] = $p['cancle_num'];
        $param['late_at'] = $p['late_at'];
        $param['due_notice'] = $p['due_notice'];
        $param['end_at'] = $p['end_at'];
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
}
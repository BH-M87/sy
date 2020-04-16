<?php
namespace service\visit;

use Yii;
use yii\db\Query;
use yii\base\Exception;

use common\MyException;
use common\core\F;
use common\core\PsCommon;

use service\BaseService;
use service\property_basic\JavaService;
use service\common\QrcodeService;

use app\models\PsRoomVisitor;

class VisitService extends BaseService
{
	// 访客 列表
    public function list($p)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : '1';
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : '10';

        $totals = self::visitorSearch($p, 'id')->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $list = self::visitorSearch($p)->offset(($p['page'] - 1) * $p['rows'])->limit($p['rows'])
            ->orderBy('id desc')->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $v['visit_at'] = date('Y-m-d', $v['visit_at']);
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 列表参数过滤
    private static function visitorSearch($p, $filter = '')
    {
        $filter = $filter ?? "*";
        $m = PsRoomVisitor::find()
            ->select($filter)
            ->filterWhere(['like', 'name', PsCommon::get($p, 'name')])
            ->andFilterWhere(['=', 'user_id', PsCommon::get($p, 'user_id')])
            ->andFilterWhere(['=', 'communityId', PsCommon::get($p, 'community_id')])
            ->andFilterWhere(['=', 'status', PsCommon::get($p, 'status')]);
        return $m;
    }

	// 访客 详情
    public function dingdingShow($p)
    {
        $r = PsRoomVisitor::find()->where(['id' => $p['id']])->asArray()->one();
        if (!empty($r)) {
        	$r['type'] = 1;
            if ($r['visit_at'] < strtotime(date('Y-m-d'), time())) {
            	$r['type'] = 3;
            }

            $r['sex'] = $r['sex'] == 2 ? '女' : '男';
            $r['visit_at'] = date('Y-m-d', $r['visit_at']);
        } else {
        	$r['type'] = 2;
        }

        return $r;
    }

    // 访客 详情
    public function show($p)
    {
        $r = PsRoomVisitor::find()->where(['id' => $p['id']])->asArray()->one();
        if (!empty($r)) {
            $r['sex'] = $r['sex'] == 2 ? '女' : '男';
            $r['visit_at'] = date('Y-m-d', $r['visit_at']);

            return $r;
        }

        throw new MyException('访客不存在!');
    }

    // 确认放行
    public function pass($p)
    {
        $r = PsRoomVisitor::find()->where(['id' => $p['id']])->asArray()->one();
        if (!empty($r)) {
            PsRoomVisitor::updateAll(['pass_at' => time(), 'status' => 2], ['id' => $p['id']]);
            return ['id' => $p['id']];
        }

        throw new MyException('访客不存在!');
    }

    // 访客 密码验证
    public function password($p)
    {
        $r = PsRoomVisitor::find()->where(['password' => $p['password'], 'communityId' => $p['community_id']])->asArray()->one();
        if (!empty($r)) {
            $r['sex'] = $r['sex'] == 2 ? '女' : '男';
            $r['visit_at'] = date('Y-m-d', $r['visit_at']);
            return $r;
        }

        throw new MyException('访客不存在!');
    }

	// 访客 新增
    public function add($p)
    {
        $p['visit_at'] = !empty($p['visit_at']) ? strtotime($p['visit_at']) : '';
        $p['password'] = rand(100,999).rand(100,999);

        $m = new PsRoomVisitor(['scenario' => 'add']);

        if (!$m->load($p, '') || !$m->validate()) {
            return PsCommon::responseFailed($this->getError($m));
        }

        if (!$m->saveData($scenario, $p)) {
            return PsCommon::responseFailed($this->getError($m));
        }

        $id = $m->attributes['id'];

        self::createQrcode($m, $id);

        return ['id' => $id];
    }

    // 生成二维码图片
    private static function createQrcode($model, $id)
    {
        $savePath = F::imagePath('visit');
        $logo = Yii::$app->basePath . '/web/img/lyllogo.png'; // 二维码中间的logo
        $url = Yii::$app->getModule('property')->params['ding_web_host'] . '#/scanList?type=scan&id=' . $id;
        
        $imgUrl = QrcodeService::service()->generateCommCodeImage($savePath, $url, $id, $logo, $model); // 生成二维码图片
        
        PsRoomVisitor::updateAll(['qrcode' => $imgUrl], ['id' => $id]);
    }
}
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
use service\common\CsvService;

use app\models\PsRoomVisitor;
use app\models\PsOutOrder;

class VisitService extends BaseService
{ 
    public static $outStatus = ['1' => '待确认', '2' => '已确认', '3' => '已放行', '4' => '已作废'];
    public static $outMemberType = ['1' => '业主', '2' => '家人', '3' => '租客'];

    // ----------------------------------     出门单     ----------------------------

    // 访客 列表
    public function listOut($p)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : '1';
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : '10';

        $totals = self::outSearch($p, 'id')->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $model = self::outSearch($p);

        $list = $model->orderBy('id desc')
            ->offset(($p['page'] - 1) * $p['rows'])->limit($p['rows'])->asArray()->all();

        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $v['release_at'] = !empty($v['release_at']) ? date('Y-m-d H:i', $v['release_at']) : '';
                $v['application_at'] = !empty($v['application_at']) ? date('Y-m-d H:i', $v['application_at']) : '';
                $v['statusMsg'] = self::$outStatus[$v['status']];
                $v['member_type_msg'] = self::$outMemberType[$v['member_type']];
                // 小区名称调Java
                $community = JavaService::service()->communityDetail(['token' => $p['token'], 'id' => $v['community_id']]);
                $v['community_name'] = $community['communityName'];
            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 列表参数过滤
    private static function outSearch($p, $filter = '')
    {
        $application_start = !empty($p['application_start']) ? strtotime($p['application_start']) : '';
        $application_end = !empty($p['application_end']) ? strtotime($p['application_end'] . '23:59:59') : '';
        $release_start = !empty($p['release_start']) ? strtotime($p['release_start']) : '';
        $release_end = !empty($p['release_end']) ? strtotime($p['release_end'] . '23:59:59') : '';

        $filter = $filter ?? "*";
        $m = PsOutOrder::find()
            ->select($filter)
            ->filterWhere(['in', 'community_id', PsCommon::get($p, 'communityList')])
            ->andFilterWhere(['=', 'community_id', PsCommon::get($p, 'community_id')])
            ->andFilterWhere(['>=', 'release_at', $release_start])
            ->andFilterWhere(['<=', 'release_at', $release_end])
            ->andFilterWhere(['=', 'status', PsCommon::get($p, 'status')])
            ->andFilterWhere(['>=', 'application_at', $application_start])
            ->andFilterWhere(['<=', 'application_at', $application_end]);
        return $m;
    }

    // 出门单详情 详情
    public function showOut($p)
    {
        $r = PsOutOrder::find()->where(['id' => $p['id']])->asArray()->one();
        if (!empty($r)) {
            $r['statusMsg'] = self::$outStatus[$r['status']];
            $r['member_type_msg'] = self::$outMemberType[$r['member_type']];
            // 小区名称调Java
            $community = JavaService::service()->communityDetail(['token' => $p['token'], 'id' => $r['community_id']]);
            $r['community_name'] = $community['communityName'];
            $r['release_at'] = !empty($r['release_at']) ? date('Y-m-d', $r['release_at']) : '';
            $r['application_at'] = !empty($r['application_at']) ? date('Y-m-d', $r['application_at']) : '';
            $r['create_at'] = !empty($r['create_at']) ? date('Y-m-d', $r['create_at']) : '';
            $r['content_img'] = !empty($r['content_img']) ? explode(',', $r['content_img']) : '';

            return $r;
        }

        throw new MyException('数据不存在!');
    }

    // 确认放行
    public function passOut($p)
    {
        $r = PsOutOrder::find()->where(['id' => $p['id']])->asArray()->one();
        if (!empty($r)) {
            if ($r['status'] == 1) {
                throw new MyException('出门单待确认!');
            } 

            if ($r['status'] == 3) {
                throw new MyException('出门单已放行!');
            }

            if ($r['status'] == 4) {
                throw new MyException('出门单已作废!');
            } 

            if (date('Y-m-d', $r['application_at']) != date('Y-m-d', time())) {
                throw new MyException('未到申请日期!');
            }

            PsOutOrder::updateAll(['release_at' => time(), 'status' => 3, 'release_id' => $p['user_id'], 'release_name' => $p['user_name']], ['id' => $p['id']]);
            return ['id' => $p['id']];
        }

        throw new MyException('出门单不存在!');
    }

    // 作废/确认
    public function statusOut($p)
    {
        $id = $p['id'];
        $r = PsOutOrder::find()->where(['id' => $id])->asArray()->one();
        if (!empty($r)) {
            if ($p['status'] == 2) { // 确认生成二维码和出门单号
                if ($r['status'] == 2) {
                    throw new MyException('出门单已确认，不要重复操作');
                }

                $savePath = F::imagePath('visit');
                $logo = Yii::$app->basePath . '/web/img/lyllogo.png'; // 二维码中间的logo
                $url = Yii::$app->getModule('property')->params['ding_web_host'] . '#/scanList?type=outOrder&id=' . $id;

                $imgUrl = QrcodeService::service()->generateCommCodeImage($savePath, $url, $id, $logo); // 生成二维码图片
        
                PsOutOrder::updateAll([
                    'check_at' => time(), 
                    'status' => 2, 
                    'check_id' => $p['create_id'], 
                    'check_name' => $p['create_name'],
                    'qr_url' => $imgUrl,
                    'code' => rand(100,999).rand(100,999),
                ], ['id' => $id]);

                $data['keyword1'] = ['value'=>'审核状态:'];
                $data['keyword2'] = ['value'=>'标题:'];
                $data['keyword3'] = ['value'=>'温馨提示:'];
                $data['keyword4'] = ['value'=>'申请人:'.$r['application_name']];
                $params['to_user_id'] = $r['ali_user_id'];
                $params['form_id'] = $r['ali_form_id'];
                $params['page'] = '1';
                $params['data'] = json_encode($data);

                $service = new AlipaysTemplateService();
                $result = $service->sendMessage($params);
            } else if ($p['status'] == 4) {
                if ($r['status'] == 4) {
                    throw new MyException('出门单已作废，不要重复操作');
                }

                PsOutOrder::updateAll(['status' => 4], ['id' => $id]);
            } else {
                throw new MyException('状态错误!');
            }
            
            return ['id' => $p['id']];
        }

        throw new MyException('验证失败，出门单不存在!');
    }

    // 出门单 密码验证
    public function codeOut($p)
    {
        $application_start = strtotime(date('Y-m-d', time()));
        $application_end = strtotime(date('Y-m-d', time()) . '23:59:59');

        if (empty($p['code'])) {
            throw new MyException('出门单号不能为空!');
        }

        $r = PsOutOrder::find()->select('id')
            ->where(['code' => $p['code'], 'community_id' => $p['community_id']])
            ->andWhere(['>=', 'application_at', $application_start])
            ->andWhere(['<=', 'application_at', $application_end])
            ->asArray()->one();
        if (!empty($r)) {
            return $r;
        }

        throw new MyException('出门单号不存在!');
    }

    // ----------------------------------     访客通行     ----------------------------

	// 访客 列表
    public function list($p)
    {
        $p['page'] = !empty($p['page']) ? $p['page'] : '1';
        $p['rows'] = !empty($p['rows']) ? $p['rows'] : '10';

        $totals = self::visitorSearch($p, 'id')->count();
        if ($totals == 0) {
            return ['list' => [], 'totals' => 0];
        }

        $model = self::visitorSearch($p);
        
        if (empty($p['use_as'])) {
            $model->offset(($p['page'] - 1) * $p['rows'])->limit($p['rows']);
        }

        $list = $model->orderBy('id desc')->asArray()->all();

        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                if ($v['visit_at'] < strtotime(date('Y-m-d'), time()) || $v['status'] == 2) {
                    $v['type'] = 2; // 可以再次邀约
                } else {
                    $v['type'] = 1;
                }

                $v['visit_at'] = date('Y-m-d', $v['visit_at']);
                $v['pass_at'] = !empty($v['pass_at']) ? date('Y-m-d H:i', $v['pass_at']) : '';
                $v['statusMsg'] = $v['status'] == 2 ? '已到访' : '未到访';
                $v['sexMsg'] = $v['sex'] == 2 ? '女' : '男';

            }
        }

        return ['list' => $list, 'totals' => (int)$totals];
    }

    // 列表参数过滤
    private static function visitorSearch($p, $filter = '')
    {
        $start_at = !empty($p['start_at']) ? strtotime($p['start_at']) : '';
        $end_at = !empty($p['end_at']) ? strtotime($p['end_at'] . '23:59:59') : '';

        $filter = $filter ?? "*";
        $m = PsRoomVisitor::find()
            ->select($filter)
            ->filterWhere(['like', 'name', PsCommon::get($p, 'name')])
            ->andFilterWhere(['like', 'roomName', PsCommon::get($p, 'roomName')])
            ->andFilterWhere(['=', 'member_id', PsCommon::get($p, 'user_id')])
            ->andFilterWhere(['=', 'communityId', PsCommon::get($p, 'communityId')])
            ->andFilterWhere(['=', 'communityId', PsCommon::get($p, 'community_id')])
            ->andFilterWhere(['in', 'communityId', PsCommon::get($p, 'communityList')])
            ->andFilterWhere(['=', 'groupId', PsCommon::get($p, 'groupId')])
            ->andFilterWhere(['=', 'buildingId', PsCommon::get($p, 'buildingId')])
            ->andFilterWhere(['=', 'unitId', PsCommon::get($p, 'unitId')])
            ->andFilterWhere(['=', 'room_id', PsCommon::get($p, 'room_id')])
            ->andFilterWhere(['=', 'status', PsCommon::get($p, 'status')])
            ->andFilterWhere(['>=', 'visit_at', $start_at])
            ->andFilterWhere(['<=', 'visit_at', $end_at]);
        return $m;
    }

    // 导出
    public function export($p, $user = [])
    {
        $p['use_as'] = "export";
        $r = $this->list($p);
        
        $config = [
            ['title' => '访客姓名', 'field' => 'name'],
            ['title' => '性别', 'field' => 'sexMsg'],
            ['title' => '联系电话', 'field' => 'mobile'],
            ['title' => '车牌号', 'field' => 'car_number'],
            ['title' => '到访时间', 'field' => 'visit_at'],
            ['title' => '被访人', 'field' => 'roomName'],
            ['title' => '被访地址', 'field' => 'fullName'],
            ['title' => '到访状态', 'field' => 'statusMsg'],
            ['title' => '实际到访时间', 'field' => 'pass_at'],
        ];

        $filename = CsvService::service()->saveTempFile(1, $config, $r['list'], 'visitor');
        $downUrl = F::downloadUrl($filename, 'temp', 'visitor.csv');

        return ["down_url" => $downUrl];
    }

	// 访客 详情
    public function dingdingShow($p)
    {
        $r = PsRoomVisitor::find()->where(['id' => $p['id'], 'communityId' => $p['community_id']])->asArray()->one();
        if (!empty($r)) {
        	$r['type'] = 1;
            if ($r['visit_at'] < strtotime(date('Y-m-d'), time()) || $r['status'] == 2) {
            	$r['type'] = 3;
            } else if (date('Y-m-d', $r['visit_at']) != date('Y-m-d', time())) {
                $r['type'] = 4;
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
            $r['sexMsg'] = $r['sex'] == 2 ? '女' : '男';
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
            if ($r['visit_at'] < strtotime(date('Y-m-d'), time()) || $r['status'] == 2) {
                throw new MyException('验证失败，该通行证不存在!');
            } 

            if (date('Y-m-d', $r['visit_at']) != date('Y-m-d', time())) {
                throw new MyException('未到访问日期!');
            }

            PsRoomVisitor::updateAll(['pass_at' => time(), 'status' => 2], ['id' => $p['id']]);
            return ['id' => $p['id']];
        }

        throw new MyException('验证失败，该通行证不存在!');
    }

    // 访客 密码验证
    public function password($p)
    {
        $r = PsRoomVisitor::find()->where(['password' => $p['password'], 'communityId' => $p['community_id']])->asArray()->one();
        if (!empty($r)) {
            if ($r['visit_at'] < strtotime(date('Y-m-d'), time()) || $r['status'] == 2) {
                throw new MyException('访客码已失效!');
            }

            if (date('Y-m-d', $r['visit_at']) != date('Y-m-d', time())) {
                throw new MyException('未到访问日期!');
            }

            $r['sex'] = $r['sex'] == 2 ? '女' : '男';
            $r['visit_at'] = date('Y-m-d', $r['visit_at']);

            return $r;
        }

        throw new MyException('验证失败，该通行证不存在!');
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
<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/12
 * Time: 10:42
 */

namespace service\inspect;

use app\models\PsInspectLinePoint;
use common\MyException;
use app\models\PsDevice;
use app\models\PsDeviceCategory;
use app\models\PsInspectPoint;
use common\core\F;
use common\core\PsCommon;
use service\BaseService;
use service\rbac\OperateService;
use Yii;

class PointService extends BaseService
{
    /**  物业后台接口 start */

    //巡检点新增
    public function add($params, $userInfo)
    {
        self::checkCommon($params, $userInfo, 'add');
    }

    //巡检点编辑
    public function edit($params, $userInfo)
    {
        self::checkCommon($params, $userInfo, 'update');
    }

    protected static function checkCommon($params, $userInfo = [], $scenario = 'add')
    {
        $model = new PsInspectPoint();
        $params = $model->validParamArr($params, $scenario);
        if ($scenario == 'update') {
            $model = PsInspectPoint::findOne($params['id']);
            if (empty($model)) {
                throw new MyException('巡检点不存在!');
            }
        } else {
            $params['created_at'] = time();
            unset($params['id']);
        }
        // 验证分类是否存在
        $categoryModel = self::deviceCategoryOne($params['category_id'], 'id');
        if (!$categoryModel) {
            throw new MyException('设备分类不存在!');
        }
        // 验证设备是否存在
        $deviceModel = self::deviceOne($params['device_id'], $params['community_id'], 'category_id,name,device_no');
        if (!$deviceModel) {
            throw new MyException('设备不存在!');
        }
        $device = $deviceModel->toArray();
        if ($params['category_id'] != $device['category_id']) {
            throw new MyException('设备对应的分类和设备分类不一致!');
        }
        // 当选择需要定位时判断是否有经纬度
        if ($params['need_location'] == 1) {
            if (empty($params['location_name']) || empty($params['lon']) || empty($params['lat'])) {
                throw new MyException('定位经纬度与位置不能为空!');
            }
        } else { // 不需要定位
            $params['lat'] = '';
            $params['lon'] = '';
            $params['location_name'] = '';
        }
        $params['category_id'] = $device['category_id'];//设备分类id
        $params['device_name'] = $device['name'];       //设备名称
        $params['device_no'] = $device['device_no'];    //设备编号
        //查看巡检点名称是否重复
        $point = PsInspectPoint::find()->where(['name' => $params['name'], 'community_id' => $params['community_id']])->one();
        if (!empty($point)) {
            throw new MyException('巡检点已存在!');
        }
        $model->setAttributes($params, false);
        if ($model->save()) {  # 保存新增数据
            $id = $model->id;
            //TODO 二维码后续添加
            //self::createQrcode($id);
            if (!empty($userInfo)) {
                //TODO 日志好了就打开
                //self::addLog($userInfo, $params['name'], $params['community_id'], $scenario);
            }
            return true;
        } else {
            throw new MyException('操作失败');
        }
    }

    //生成二维码图片
    private static function createQrcode($id)
    {
        $savePath = F::imagePath('inspect');
        $logo = Yii::$app->basePath . '/web/img/lyllogo.png';//二维码中间的logo
        $url = Yii::$app->getModule('property')->params['ding_web_host'] . '#/scanList?type=scan&id=' . $id;
        //CommunityService::service()->generateCommCodeImage($savePath, $url, $id, $logo, $mod);//生成二维码图片
    }

    //统一日志新增
    private static function addLog($userInfo, $name, $community_id, $operate_type = "")
    {
        switch ($operate_type) {
            case 'add':
                $operate_name = '新增';
                break;
            case 'update':
                $operate_name = '编辑';
                break;
            case 'del':
                $operate_name = '删除';
                break;
            default:
                return;
        }
        $content = "巡检点名称:" . $name;
        $operate = [
            "community_id" => $community_id,
            "operate_menu" => "设备巡检",
            "operate_type" => "巡检点" . $operate_name,
            "operate_content" => $content,
        ];
        OperateService::addComm($userInfo, $operate);
    }

    //巡检点详情
    public function view($params)
    {
        if (empty($params['id'])) {
            throw new MyException('巡检点id不能为空');
        }
        $result = PsInspectPoint::find()->alias("point")
            ->where(['point.id' => $params['id']])
            ->select(['point.id', 'point.device_id', 'point.category_id', 'comm.id as community_id', 'comm.name as community_name', 'point.name', 'point.device_name', 'point.location_name', 'point.need_location', 'point.need_photo', 'point.lon', 'point.lat', 'point.code_image'])
            ->leftJoin("ps_community comm", "comm.id=point.community_id")
            ->asArray()->one();
        if (!empty($result)) {
            $categoryInfo = PsDeviceCategory::findOne($result['category_id']);
            $result['category_name'] = $categoryInfo->name;
            $result['lon'] = $result['lon'] ?? 0.00;
            $result['lat'] = $result['lat'] ?? 0.00;
            return $result;
        }
        throw new MyException('巡检点不存在!');
    }

    //巡检点删除
    public function del($params, $userInfo)
    {
        if (empty($params['id'])) {
            throw new MyException('巡检点id不能为空');
        }
        if (PsInspectLinePoint::find()->where(['point_id' => $params['id']])->exists()) {
            throw new MyException('请先修改巡检线路');
        }
        $result = PsInspectPoint::deleteAll(['id' => $params['id']]);
        if (!empty($result)) {
            if (!empty($userInfo)) {
                $name = PsInspectPoint::find()->select('name')->where(['id' => $params['id']])->scalar();
                $name = $name ?? "";
                //self::addLog($userInfo, $name, $params['community_id'], 'del');
            }
            return true;
        }
        throw new MyException('删除失败，巡检点不存在');
    }

    //巡检点列表
    public function pointList($params)
    {
        $totals = self::pointSearch($params)->count();
        if ($totals == 0) {
            return $this->success(['list' => [], 'totals' => 0]);
        }
        $list = self::pointSearch($params)
            ->select('id, community_id, name, device_name, location_name, need_photo, need_location')
            ->orderBy('id desc')
            ->offset(($params['page'] - 1) * $params['rows'])
            ->limit($params['rows'])
            ->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['location_name'] = $v['need_location'] == 1 ? $v['location_name'] : '不需要定位';
                $list[$k]['need_photo'] = $v['need_photo'] == 1 ? '是' : '否';
            }
        }
        $result = [
            'list' => $list,
            'totals' => (int)$totals
        ];
        return $result;
    }

    //巡检点下拉
    public function getPoint($params)
    {
        $checked = PsCommon::get($params, 'checked');
        $model = PsInspectPoint::find()->select('id, name')
            ->filterWhere(['=', 'community_id', PsCommon::get($params, 'community_id')])
            ->asArray()->all();
        if (!empty($model)) {
            foreach ($model as $k => $v) {
                $point = PsInspectLinePoint::find()
                    ->filterWhere(['=', 'line_id', PsCommon::get($params, 'line_id') ? $params['line_id'] : 0])
                    ->andFilterWhere(['=', 'point_id', $v['id']])->one();
                $model[$k]['key'] = $v['id'];
                $model[$k]['title'] = $v['name'];
                unset($model[$k]['id']);
                unset($model[$k]['name']);

                if (!empty($checked) && empty($point)) {
                    unset($model[$k]);
                }
            }
        }
        return array_values($model);
    }

    //下载二维码
    public function downloadCode($params, $systemType)
    {
        $data = $this->view($params);

        if (empty($data['data']['code_image'])) {
            return PsCommon::responseFailed("二维码不存在！");
        }

        $savePath = F::imagePath('inspect'); // 图片保存的位置
        $img_name = $data['data']['id'] . '.png';
        $fileName = $data['data']['name'] . '.png';

        if (!file_exists($savePath . $img_name)) { // 文件不存在，去七牛下载
            F::curlImage($data['data']['code_image'], F::imagePath('inspect'), $img_name);
        }

        if (!file_exists($savePath . $img_name)) { // 下载未成功
            return PsCommon::responseFailed('二维码不存在');
        }

        $downUrl = F::downloadUrl($systemType, 'inspect/' . $img_name, 'qrcode', $fileName);

        return ['down_url' => $downUrl];
    }

    //获取设备分类
    protected static function deviceCategoryOne($id, $select = '')
    {
        $select = $select ?? '*';
        return PsDeviceCategory::find()->select($select)->where(['id' => $id])->one();
    }

    //获取设备信息
    protected static function deviceOne($id, $community_id, $select = '')
    {
        $select = $select ?? '*';
        return PsDevice::find()
            ->select($select)
            ->where(['id' => $id])
            ->andWhere(['community_id' => $community_id])->one();
    }

    //列表参数过滤
    private static function pointSearch($params)
    {
        $model = PsInspectPoint::find()
            ->filterWhere(['like', 'name', PsCommon::get($params, 'name')])
            ->andFilterWhere(['=', 'need_location', PsCommon::get($params, 'need_location')])
            ->andFilterWhere(['=', 'need_photo', PsCommon::get($params, 'need_photo')])
            ->andFilterWhere(['=', 'community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'device_id', PsCommon::get($params, 'device_id')]);
        return $model;
    }
    /**  物业后台接口 end */

    /**  钉钉接口 start */

    /**  钉钉接口 end */

    /**  公共接口 start */

    /**  公共接口 end */
}
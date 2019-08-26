<?php
/**
 * Created by PhpStorm.
 * User: chenkelang
 * Date: 2018/6/6
 * Time: 10:55
 */
namespace service\inspect;

use app\models\PsDevice;
use app\models\PsDeviceCategory;
use app\models\PsDeviceAccident;
use app\models\PsDeviceRepair;
use app\models\PsInspectPoint;
use common\core\PsCommon;
use common\core\F;
use service\rbac\OperateService;
use Yii;

class DeviceService extends BaseService
{
    //自动验证小区
    public function validaCommunit($reqArr)
    {
        $communityId = !empty($reqArr['community_id']) ? $reqArr['community_id'] : 0;
        if (!$communityId) {
            return $this->failed("小区id不能为空！");
        }
        return $this->success();
    }

    // 设备类型列表
    public function getDeviceTypeList($reqArr)
    {
        $valida = self::validaCommunit($reqArr);

        if (!$valida['code']) {
            return $valida;
        }
        // 获取默认分类
        $default = PsDeviceCategory::find()
            ->select(['id as key', 'id as value', 'name as label', 'parent_id'])
            ->where(['community_id' => 0])
            ->andWhere(['type' => 1])->orderBy('id desc')->asArray()->all();

        // 获取最顶级的分类
        $resultAll = PsDeviceCategory::find()
            ->select(['id as key', 'id as value', 'name as label', 'parent_id'])
            ->where(['community_id' => $reqArr['community_id']])->orderBy('id desc')->asArray()->all();

        $result = self::getCateGory(array_merge($resultAll, $default));

        return $this->success($result);
    }

    //将分类组装成树型数组
    public function getCateGory($items, $pk = 'key', $pid = 'parent_id', $child = 'children', $root = 0)
    {
        $tree = $packData = [];
        foreach ($items as $data) {
            $packData[$data[$pk]] = $data;
        }
        foreach ($packData as $key => $val) {
            if ($val[$pid] == $root) {
                //代表跟节点, 重点一
                $tree[] = &$packData[$key];
            } else {
                //找到其父类,重点二
                $packData[$val[$pid]][$child][] = &$packData[$key];
            }
        }
        return $tree;
    }

    //设备列表-钉钉端专用，因为不需要查分类
    public function getDDDeviceList($reqArr)
    {
        $arrList = [];
        //获取分类
        $resultAll = PsDeviceCategory::find()->select(['id', 'name'])->andWhere(['or', ['=', 'community_id', $reqArr['community_id']], ['=', 'community_id', 0]])->asArray()->all();
        if (!empty($resultAll)) {
            foreach ($resultAll as $result) {
                $deviceAll = PsDevice::find()->alias("device")
                    ->where(['device.community_id' => $reqArr['community_id'], 'category_id' => $result['id']])
                    ->select(['id', 'name'])
                    ->asArray()->all();
                if (!empty($deviceAll)) {
                    $arr['category_id'] = $result['id'];
                    $arr['category_name'] = $result['name'];
                    //说明需要查询巡检点选择的设备
                    $deviceList = [];
                    foreach ($deviceAll as $device) {
                        $device['is_checked'] = 0;
                        if (!empty($reqArr['point_id'])) {
                            $point = PsInspectPoint::find()->where(['id' => $reqArr['point_id']])->asArray()->one();
                            if (!empty($point) && $point['device_id'] == $device['id']) {
                                $device['is_checked'] = 1;//说明选择了当前设备
                            }
                        }
                        $deviceList[] = $device;
                    }
                    $arr['device_list'] = $deviceList;
                    $arrList[] = $arr;
                }
            }
            return $this->success($arrList);
        }
        return $this->failed("设备不存在！");
    }

    //设备列表-物业端专用
    public function getDeviceList($reqArr)
    {
        $valida = self::validaCommunit($reqArr);
        if (!$valida['code']) {
            return $valida;
        }
        if (empty($reqArr['category_id'])) {
            return $this->failed("设备分类不能为空！");
        }
        $resultAll = PsDevice::find()->select(['id', 'name'])->where(['community_id' => $reqArr['community_id'], 'category_id' => $reqArr['category_id']])->asArray()->all();
        if (!empty($resultAll)) {
            return $this->success($resultAll);
        }
        return $this->failed("设备不存在！");
    }

    //获取设备名称
    public function getDeviceName($id)
    {
        $model = PsDevice::find()->select(['name'])->where(['id' => $id])->asArray()->one();
        return $model['name'];
    }

    //获取设备编号
    public function getDeviceNo($id)
    {
        $model = PsDevice::find()->select(['device_no'])->where(['id' => $id])->asArray()->one();
        return $model['device_no'];
    }

    // +------------------------------------------------------------------------------------
    // |----------------------------------     设备分类     --------------------------------
    // +------------------------------------------------------------------------------------

    // 设备分类 新增
    public function deviceCategoryAdd($param, $userinfo = [])
    {
        return $this->_saveDeviceCategory($param, 'add', $userinfo);
    }

    // 设备分类 编辑
    public function deviceCategoryEdit($param, $userinfo = [])
    {
        return $this->_saveDeviceCategory($param, 'edit', $userinfo);
    }

    // 设备分类 新增 编辑
    private function _saveDeviceCategory($param, $scenario, $userinfo = [])
    {
        if (!empty($param['id'])) {
            $device = PsDeviceCategory::findOne($param['id']);

            if (!$device) {
                return $this->failed('数据不存在！');
            }

            if (!empty($device['type'])) {
                return $this->failed('默认设备类别不可编辑');
            }

            if ($device['community_id'] != $param['community_id']) {
                return $this->failed('您没有权限');
            }

            if ($param['id'] == $param['parent_id']) {
                return $this->failed('上级分类不能是自己');
            }
        }

        if (!empty($param['parent_id'])) {
            // level 1为管理员，自动创建
            $parentLevel = PsDeviceCategory::find()->select('level')->where(['id' => $param['parent_id']])->scalar();
            if (!$parentLevel) {
                return $this->failed('上级部门不存在');
            }

            $param['level'] = $parentLevel + 1;
        } else {
            $param['level'] = 1;
        }

        $model = new PsDeviceCategory(['scenario' => $scenario]);

        if (!$model->load($param, '') || !$model->validate()) {
            return $this->failed($this->getError($model));
        }

        if (!$model->saveData($scenario, $param)) {
            return $this->failed($this->getError($model));
        }
        if (!empty($userinfo)){
            if ($scenario == 'add'){
                $type = "新增";
            }else{
                $type = "编辑";
            }
            $content = "设备名称:". $param['name'];
            $operate = [
                "community_id" =>$param['community_id'],
                "operate_menu" => "设备管理",
                "operate_type" => "设备类目".$type,
                "operate_content" => $content,
            ];
            OperateService::addComm($userinfo, $operate);
        }
        return $this->success();
    }

    // 设备分类 搜索
    private function _deviceCategorySearch($param)
    {
        $model = PsDeviceCategory::find()
            ->filterWhere(['=', 'community_id', PsCommon::get($param, 'community_id')])
            ->andFilterWhere(['=', 'parent_id', PsCommon::get($param, 'parent_id')]);

        return $model;
    }

    // 设备分类 列表
    public function deviceCategoryList($param)
    {
        $page = PsCommon::get($param, 'page');
        $rows = PsCommon::get($param, 'rows');
        
        // 获取默认分类
        $default = PsDeviceCategory::find()
            ->select(['id as key', 'id as value', 'name as label', 'parent_id', 'type', 'note'])
            ->where(['community_id' => 0])
            ->andWhere(['type' => 1])->orderBy('id desc')->asArray()->all();

        // 获取最顶级的分类
        $resultAll = PsDeviceCategory::find()
            ->select(['id as key', 'id as value', 'name as label', 'parent_id', 'type', 'note'])
            ->where(['community_id' => $param['community_id']])->orderBy('id desc')->asArray()->all();

        $result = self::getCateGory(array_merge($resultAll, $default));

        return $this->success($result);
    }

    // 设备分类 总数
    public function deviceCategoryCount($param)
    {
        $param['parent_id'] = 0;
        return $this->_deviceCategorySearch($param)->count() + 9;
    }

    // 设备分类 详情
    public function deviceCategoryShow($param)
    {
        if (empty(PsCommon::get($param, 'id'))) {
            return $this->failed('ID不能为空！');
        }

        $model = PsDeviceCategory::find()
            ->where(['=', 'id', PsCommon::get($param, 'id')])
            ->asArray()->one();

        if (empty($model)) {
            return $this->failed('数据不存在');
        }

        if ($model['community_id'] != $param['community_id']) {
            return $this->failed('您没有权限');
        }

        return $this->success($model);
    }

    // 设备分类 删除
    public function deviceCategoryDelete($param, $userinfo = [])
    {
        if (empty(PsCommon::get($param, 'id'))) {
            return $this->failed('ID不能为空！');
        }

        $model = PsDeviceCategory::findOne($param['id']);

        if (!$model) {
            return $this->failed('数据不存在');
        }

        if (!empty($model->type)) {
            return $this->failed('默认设备类别不可删除');
        }

        if ($param['community_id'] != $model->community_id) {
            return $this->failed('没有权限删除此数据');
        }

        if (PsDevice::find()->where(['category_id' => $model->id])->exists()) {
            return $this->failed('请先删除类别下对应设备');
        }

        if (PsDeviceCategory::find()->where(['parent_id' => $model->id])->exists()) {
            return $this->failed('请先删除子类别');
        }

        if (PsDeviceCategory::deleteAll('id = :id', [':id' => $model->id])) {
            if (!empty($userinfo)){
                $content = "设备名称:". $model->name;
                $operate = [
                    "community_id" =>$param['community_id'],
                    "operate_menu" => "设备管理",
                    "operate_type" => "设备删除",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
            }
            return $this->success();
        }

        return $this->failed();
    }

    // +------------------------------------------------------------------------------------
    // |----------------------------------     设备     ------------------------------------
    // +------------------------------------------------------------------------------------

    // 设备 新增
    public function deviceAdd($param, $userinfo = [])
    {
        return $this->_deviceSave($param, 'add',$userinfo);
    }

    // 设备 编辑
    public function deviceEdit($param, $userinfo = [])
    {
        return $this->_deviceSave($param, 'edit',$userinfo);
    }

    // 设备 新增 编辑
    private function _deviceSave($param, $scenario, $userinfo = [])
    {
        if (!empty($param['id'])) {
            $device = PsDevice::findOne($param['id']);
            if (!$device) {
                return $this->failed('数据不存在！');
            }

            if ($device['community_id'] != $param['community_id']) {
                return $this->failed('您没有权限');
            }
        }

        if (!PsDeviceCategory::find()->where(['id' => $param['category_id']])->exists()) {
            return $this->failed('分类不存在！');
        }

        $model = new PsDevice(['scenario' => $scenario]);

        if (!$model->load($param, '') || !$model->validate()) {
            return $this->failed($this->getError($model));
        }

        if (!$model->saveData($scenario, $param)) {
            return $this->failed($this->getError($model));
        }
//        if ($scenario == 'add') {
//            //设备新增成功后，推送到监控页面 @shenyang v4.4数据监控版本
//            WebSocketClient::getInstance()->send(MonitorService::MONITOR_DEVICE, $param['community_id']);
//        }
        if (!empty($userinfo)){
            if ($scenario == 'add'){
                $type = "新增";
            }else{
                $type = "编辑";
            }
            $content = "设备名称:". $param['name']. ' 设备编号:'. $param['device_no'];
            $operate = [
                "community_id" =>$param['community_id'],
                "operate_menu" => "设备管理",
                "operate_type" => "设备台账".$type,
                "operate_content" => $content,
            ];
            OperateService::addComm($userinfo, $operate);
        }
        return $this->success();
    }

    // 设备 搜索
    private function _deviceSearch($params)
    {
        $model = PsDevice::find()
            ->filterWhere(['like', 'name', PsCommon::get($params, 'name')])
            ->andFilterWhere(['=', 'id', PsCommon::get($params, 'device_id')])
            ->andFilterWhere(['like', 'device_no', PsCommon::get($params, 'device_no')])
            ->andFilterWhere(['=', 'community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'status', PsCommon::get($params, 'status')])
            ->andFilterWhere(['=', 'category_id', PsCommon::get($params, 'category_id')]);

        return $model;
    }

    // 设备 列表
    public function deviceList($params)
    {
        $page = PsCommon::get($params, 'page');
        $rows = PsCommon::get($params, 'rows');
        $page = !empty($page) ? $page : 1;
        $rows = !empty($rows) ? $rows : 10;
        $model = $this->_deviceSearch($params)
            ->select('id, community_id, name, category_id, device_no, install_place, status, supplier, leader, plan_scrap_at')
            ->orderBy('id desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();

        if (!empty($model)) {
            foreach ($model as $k => $v) {
                $model[$k]['status'] = $v['status'] == 1 ? '运行' : '报废';
                $model[$k]['category_name'] = PsDeviceCategory::find()->select('name')->where(['id' => $v['category_id']])->scalar();
            }
        }

        return $model;
    }

    // 设备 总数
    public function deviceCount($params)
    {
        return $this->_deviceSearch($params)->count();
    }

    // 设备 详情
    public function deviceShow($params)
    {
        if (empty(PsCommon::get($params, 'id'))) {
            return $this->failed('ID不能为空！');
        }

        $model = PsDevice::find()
            ->where(['=', 'id', PsCommon::get($params, 'id')])
            ->asArray()->one();

        if (empty($model)) {
            return $this->failed('数据不存在');
        }

        if ($model['community_id'] != $params['community_id']) {
            return $this->failed('您没有权限');
        }

        $model['status_name'] = $model['status'] == 1 ? '运行' : '报废';
        $model['category_name'] = PsDeviceCategory::find()->select('name')->where(['id' => $model['category_id']])->scalar();

        return $this->success($model);
    }

    // 设备  删除
    public function deviceDelete($params, $userinfo = [])
    {
        if (empty(PsCommon::get($params, 'id'))) {
            return $this->failed('ID不能为空！');
        }

        $model = PsDevice::findOne($params['id']);

        if (!$model) {
            return $this->failed('数据不存在');
        }

        if ($params['community_id'] != $model->community_id) {
            return $this->failed('没有权限删除此数据');
        }

//        if (PsInspectPoint::find()->where(['device_id' => $model->id])->exists()) {
//            return $this->failed('请先删除巡检点');
//        }

        if (PsDevice::deleteAll('id = :id', [':id' => $model->id])) {
//            //设备删除成功后，推送到监控页面 @shenyang v4.4数据监控版本
//            WebSocketClient::getInstance()->send(MonitorService::MONITOR_DEVICE, $params['community_id']);

            if (!empty($userinfo)){
                $content = "设备名称:". $model->name." 设备编号:". $model->device_no;
                $operate = [
                    "community_id" =>$params['community_id'],
                    "operate_menu" => "设备管理",
                    "operate_type" => "设备删除",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
            }
            return $this->success();
        }

        return $this->failed();
    }

    // 设备 下拉
    public function deviceDropDown($params)
    {
        $model = $this->_deviceSearch($params)
            ->select('id, name, device_no')
            ->orderBy('id desc')
            ->asArray()->all();

        return $model;
    }

    // +------------------------------------------------------------------------------------
    // |----------------------------------     设备保养登记     ----------------------------
    // +------------------------------------------------------------------------------------

    // 设备保养登记 新增
    public function deviceRepairAdd($param, $userinfo = [])
    {
        return $this->_deviceRepairSave($param, 'add', $userinfo);
    }

    // 设备保养登记 编辑
    public function deviceRepairEdit($param, $userinfo = [])
    {
        return $this->_deviceRepairSave($param, 'edit', $userinfo);
    }

    // 设备保养登记 新增 编辑
    private function _deviceRepairSave($param, $scenario, $userinfo = [])
    {
        if (!empty($param['id'])) {
            $deviceRepair = PsDeviceRepair::findOne($param['id']);
            if (!$deviceRepair) {
                return $this->failed('数据不存在！');
            }

            if ($deviceRepair['community_id'] != $param['community_id']) {
                return $this->failed('您没有权限');
            }
        }

        if (!PsDeviceCategory::find()->where(['id' => $param['category_id']])->exists()) {
            return $this->failed('分类不存在！');
        }

        $device = PsDevice::find()->select('name, device_no')->where(['id' => $param['device_id']])->one();

        if (empty($device)) {
            return $this->failed('设备不存在！');
        }

        $param['device_name'] = $device['name'];
        $param['device_no'] = $device['device_no'];
        $param['start_at'] = strtotime($param['start_at']);
        $param['end_at'] = strtotime($param['end_at']);
        $param['check_at'] = strtotime($param['check_at']);

        $model = new PsDeviceRepair(['scenario' => $scenario]);

        if (!$model->load($param, '') || !$model->validate()) {
            return $this->failed($this->getError($model));
        }

        if (!$model->saveData($scenario, $param)) {
            return $this->failed($this->getError($model));
        }
        if (!empty($userinfo)){
            if ($scenario == 'add'){
                $type = "新增";
            }else{
                $type = "编辑";
            }
            $content = "设备名称:". $param['device_name'].'设备编号'. $param['device_no'] .' 设备保养人:' . $param['repair_person'];
            $operate = [
                "community_id" =>$param['community_id'],
                "operate_menu" => "设备管理",
                "operate_type" => "设备保养登记".$type,
                "operate_content" => $content,
            ];
            OperateService::addComm($userinfo, $operate);
        }
        return $this->success();
    }

    // 设备保养登记 搜索
    private function _deviceRepairSearch($params)
    {
        $start_at = !empty(PsCommon::get($params, 'start_at')) ? strtotime(PsCommon::get($params, 'start_at').' 0:0:0') : '';
        $end_at = !empty(PsCommon::get($params, 'end_at')) ? strtotime(PsCommon::get($params, 'end_at').'23:59:59') : '';

        $model = PsDeviceRepair::find()
            ->filterWhere(['like', 'repair_person', PsCommon::get($params, 'repair_person')])
            ->andFilterWhere(['>=', 'start_at', $start_at])
            ->andFilterWhere(['<=', 'end_at', $end_at])
            ->andFilterWhere(['=', 'community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'status', PsCommon::get($params, 'status')])
            ->andFilterWhere(['=', 'device_id', PsCommon::get($params, 'device_id')]);

        return $model;
    }

    // 设备保养登记 列表
    public function deviceRepairList($params)
    {
        $page = PsCommon::get($params, 'page');
        $rows = PsCommon::get($params, 'rows');

        $model = $this->_deviceRepairSearch($params)
            ->select('id, community_id, device_name, device_no, repair_person, start_at, end_at, status, check_person, check_at')
            ->orderBy('id desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();

        if (!empty($model)) {
            foreach ($model as $k => $v) {
                $model[$k]['status'] = $v['status'] == 1 ? '合格' : '不合格';
                $model[$k]['start_at'] = date('Y.m.d', $v['start_at']);
                $model[$k]['end_at'] = date('Y.m.d', $v['end_at']);
                $model[$k]['check_at'] = date('Y.m.d', $v['check_at']);
            }
        }

        return $model;
    }

    // 设备保养登记 总数
    public function deviceRepairCount($params)
    {
        return $this->_deviceRepairSearch($params)->count();
    }

    // 设备保养登记 详情
    public function deviceRepairShow($params)
    {
        if (empty(PsCommon::get($params, 'id'))) {
            return $this->failed('ID不能为空！');
        }

        $model = PsDeviceRepair::find()
            ->where(['=', 'id', PsCommon::get($params, 'id')])
            ->asArray()->one();

        if (empty($model)) {
            return $this->failed('数据不存在');
        }

        if ($model['community_id'] != $params['community_id']) {
            return $this->failed('您没有权限');
        }         
  
        $model['start_at'] = date('Y-m-d', $model['start_at']);
        $model['end_at'] = date('Y-m-d', $model['end_at']);
        $model['check_at'] = date('Y-m-d', $model['check_at']);
        $model['status_name'] = $model['status'] == 1 ? '合格' : '不合格';
        $model['category_name'] = PsDeviceCategory::find()->select('name')->where(['id' => $model['category_id']])->scalar();

        return $this->success($model);
    }

    // 设备保养登记  删除
    public function deviceRepairDelete($params,$userinfo='')
    {
        if (empty(PsCommon::get($params, 'id'))) {
            return $this->failed('ID不能为空！');
        }

        $model = PsDeviceRepair::findOne($params['id']);

        if (!$model) {
            return $this->failed('数据不存在');
        }

        if ($params['community_id'] != $model->community_id) {
            return $this->failed('没有权限删除此数据');
        }

        if (PsDeviceRepair::deleteAll('id = :id', [':id' => $model->id])) {
            if (!empty($userinfo)){
                $content = "设备名称:". $model->device_name." 设备编号:". $model->device_no;
                $operate = [
                    "community_id" =>$params['community_id'],
                    "operate_menu" => "设备管理",
                    "operate_type" => "设备保养登记删除",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
            }
            return $this->success();
        }

        return $this->failed();
    }

    // +------------------------------------------------------------------------------------
    // |----------------------------------     重大事故记录     ----------------------------
    // +------------------------------------------------------------------------------------

    // 重大事故记录 新增
    public function deviceAccidentAdd($param, $userinfo=[])
    {
        return $this->_deviceAccidentSave($param, 'add', $userinfo);
    }

    // 重大事故记录 编辑
    public function deviceAccidentEdit($param, $userinfo=[])
    {
        return $this->_deviceAccidentSave($param, 'edit', $userinfo);
    }

    // 重大事故记录 新增 编辑
    private function _deviceAccidentSave($param, $scenario, $userinfo = [])
    {
        if (!empty($param['id'])) {
            $device = PsDeviceAccident::findOne($param['id']);
            if (!$device) {
                return $this->failed('数据不存在！');
            }

            if ($device['community_id'] != $param['community_id']) {
                return $this->failed('您没有权限');
            }
        }

        if (!PsDeviceCategory::find()->where(['id' => $param['category_id']])->exists()) {
            return $this->failed('分类不存在！');
        }

        if (!PsDevice::find()->where(['id' => $param['device_id']])->exists()) {
            return $this->failed('设备不存在！');
        }

        $param['happen_at'] = strtotime($param['happen_at'] . ':00');
        $param['scene_at'] = strtotime($param['scene_at'] . ':00');

        $model = new PsDeviceAccident(['scenario' => $scenario]);

        if (!$model->load($param, '') || !$model->validate()) {
            return $this->failed($this->getError($model));
        }

        if (!$model->saveData($scenario, $param)) {
            return $this->failed($this->getError($model));
        }
        if (!empty($userinfo)){
            if ($scenario == 'add'){
                $type = "新增";
            }else{
                $type = "编辑";
            }
            $scene_person = empty($param['scene_person']) ? "" : " 出场人:".$param['scene_person'];
            $confirm_person = empty($param['confirm_person']) ? "" :  " 确认人:".$param['confirm_person'];
            $content = "设备ID:". $param['device_id']. $scene_person. $confirm_person;
            $operate = [
                "community_id" =>$param['community_id'],
                "operate_menu" => "设备管理",
                "operate_type" => "重大事故记录".$type,
                "operate_content" => $content,
            ];
            OperateService::addComm($userinfo, $operate);
        }
        return $this->success();
    }

    // 重大事故记录 搜索
    private function _deviceAccidentSearch($params)
    {
        $model = PsDeviceAccident::find()
            ->filterWhere(['like', 'scene_person', PsCommon::get($params, 'scene_person')])
            ->andFilterWhere(['=', 'community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'device_id', PsCommon::get($params, 'device_id')]);

        return $model;
    }

    // 重大事故记录 列表
    public function deviceAccidentList($params)
    {
        $page = PsCommon::get($params, 'page');
        $rows = PsCommon::get($params, 'rows');

        $model = $this->_deviceAccidentSearch($params)
            ->select('id, community_id, device_id, confirm_person, happen_at, describe, result')
            ->orderBy('id desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();

        if (!empty($model)) {
            foreach ($model as $k => $v) {
                $device = PsDevice::find()->select('name, device_no')->where(['id' => $v['device_id']])->one();
                $model[$k]['device_name'] = $device['name'];
                $model[$k]['device_no'] = $device['device_no'];
                $model[$k]['happen_at'] = date('Y.m.d', $v['happen_at']);
            }
        }

        return $model;
    }

    // 重大事故记录 总数
    public function deviceAccidentCount($params)
    {
        return $this->_deviceAccidentSearch($params)->count();
    }

    // 重大事故记录 详情
    public function deviceAccidentShow($params)
    {
        if (empty(PsCommon::get($params, 'id'))) {
            return $this->failed('ID不能为空！');
        }

        $model = PsDeviceAccident::find()
            ->where(['=', 'id', PsCommon::get($params, 'id')])
            ->asArray()->one();

        if (empty($model)) {
            return $this->failed('数据不存在');
        }

        if ($model['community_id'] != $params['community_id']) {
            return $this->failed('您没有权限');
        }

        $model['scene_at'] = date('Y-m-d H:i', $model['scene_at']);
        $model['happen_at'] = date('Y-m-d H:i', $model['happen_at']);
        $model['device_name'] = PsDevice::find()->select('name')->where(['id' => $model['device_id']])->scalar();
        $model['category_name'] = PsDeviceCategory::find()->select('name')->where(['id' => $model['category_id']])->scalar();

        return $this->success($model);
    }

    // 重大事故记录  删除
    public function deviceAccidentDelete($params,$userinfo=[])
    {
        if (empty(PsCommon::get($params, 'id'))) {
            return $this->failed('ID不能为空！');
        }

        $model = PsDeviceAccident::findOne($params['id']);

        if (!$model) {
            return $this->failed('数据不存在');
        }

        if ($params['community_id'] != $model->community_id) {
            return $this->failed('没有权限删除此数据');
        }

        if (PsDeviceAccident::deleteAll('id = :id', [':id' => $model->id])) {
            if (!empty($userinfo)){
                $content = "设备ID:". $model->device_id." 重大交通事故记录ID:".$params['id'];
                $operate = [
                    "community_id" =>$params['community_id'],
                    "operate_menu" => "设备管理",
                    "operate_type" => "重大事故记录删除",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
            }
            return $this->success();
        }

        return $this->failed();
    }

    //获取公共的设备分类(community_id=0)
    public function getCommonCategory()
    {
        return PsDeviceCategory::find()->select('id, name')
            ->where(['community_id' => 0])
            ->asArray()->all();
    }
}
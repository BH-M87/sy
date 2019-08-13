<?php
/**
 * Created by PhpStorm.
 * User: zhangqiang
 * Date: 2019-08-12
 * Time: 16:00
 */
namespace service\patrol;

use app\models\PsCommunityModel;
use app\models\PsPatrolPoints;
use app\models\PsPatrolTask;
use common\core\F;
use service\BaseService;
use service\manage\CommunityService;
use service\rbac\OperateService;
use Yii;

class PointService extends BaseService
{
    public $location = [
        1 => ['key' => '1', 'value' => '需要定位'],
        2 => ['key' => '2', 'value' => '不需要定位'],
    ];
    public $photo = [
        1 => ['key' => '1', 'value' => '需要拍照'],
        2 => ['key' => '2', 'value' => '不需要拍照'],
    ];
    /**
     *  列表搜索条件的筛选
     * @param $data
     */
    const 日常巡更 = "日常巡更";

    private function _searchDeal($data)
    {
        $mod = PsPatrolPoints::find()->where(['community_id' => $data['community_id']]);
        $mod->andFilterWhere(['need_location' => $data['need_location'], 'need_photo' => $data['need_photo'], 'is_del' => 1]);
        $mod->andFilterWhere(['like', 'name', $data['name']]);
        return $mod;
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
        $list = self::_searchDeal($data)->offset($offset)->limit($pageSize)->orderBy('created_at desc')->asArray()->all();
        $total = self::_searchDeal($data)->count();
        if ($list) {
            $i = $total - ($page - 1) * $pageSize;
            foreach ($list as $key => $value) {
                //是否需要定位
                $list[$key]['location'] = ($value['need_location'] == '1') ? $value['location_name'] : '不需要定位';
                //是否需要拍照
                $list[$key]['photo'] = ($value['need_photo'] == '1') ? '是' : '否';
                $list[$key]['tid'] = $i;
                $i--;
            }
        }else{
            $list = [];
        }
        $result['list'] = $list;
        $result['totals'] = $total;
        return $result;

    }

    /**
     * 钉钉获取巡更点列表
     * @param $data
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function dingGetList($data, $page, $pageSize)
    {
        $totals = PsPatrolPoints::find()
            ->where(['community_id' => $data['communitys']])
            ->andWhere(['is_del' => 1])
            ->count('id');

        $offset = ($page - 1) * $pageSize;
        $points = PsPatrolPoints::find()
            ->alias('p')
            ->leftJoin(['m' => PsCommunityModel::tableName()], 'p.community_id=m.id')
            ->select(['p.id', 'p.name', 'p.need_location', 'p.need_photo',
                'p.code_image as code_img', 'p.location_name', 'p.note', 'm.name as community_name'])
            ->where(['p.community_id' => $data['communitys']])
            ->andWhere(['p.is_del' => 1])
            ->orderBy('p.id desc')
            ->offset($offset)
            ->limit($pageSize)
            ->asArray()
            ->all();
        foreach ($points as $key => $val) {
            $points[$key]['need_location_label'] = ($val['need_location'] == '1') ? '需要' : '不需要';
            $points[$key]['need_photo_label'] = ($val['need_photo'] == '1') ? '需要' : '不需要';
        }
        $re['totals'] = $totals;
        $re['list'] = $points;
        return $re;

    }

    /**
     * 判断当前巡更点能否被删除
     * @param $id
     * @return array
     */
    private function _checkTaskByPointId($id)
    {
        $time = time();
        $task = PsPatrolTask::find()
            ->where(['point_id' => $id])
            ->andFilterWhere(['<', 'range_start_time', $time])
            ->andFilterWhere(['>', 'range_end_time', $time])
            ->asArray()->count();
        if ($task > 0) {
            return $this->failed('当前时间段不可编辑/删除');
        } else {
            return $this->success();
        }
    }

    /**
     * 新增编辑时 验证一些特殊信息
     * @param $data
     * @return array
     */
    private function _checkDataDeal($data)
    {
        if ($data['need_location'] == '1') {
            if (empty($data['location_name']) || empty($data['lon']) || empty($data['lat'])) {
                return $this->failed('定位信息不全');
            }
        } else {
            unset($data['location_name']);
            unset($data['lon']);
            unset($data['lat']);
        }
        $id = $data['id'];
        if ($id) {
            $check = self::_checkTaskByPointId($id);
            if ($check['code'] != 1) {
                return $this->failed($check['msg']);
            }
        }
        return $this->success($data);
    }

    //生成二维码图片
    private function createQrcode($mod)
    {
        $id = $mod->id;
        $savePath = F::imagePath('patrol');
        $logo = Yii::$app->basePath . '/web/img/lyllogo.png';//二维码中间的logo
        $url = Yii::$app->getModule('property')->params['ding_web_host'] . '#/workingAdd?type=scan&id=' . $id;
        CommunityService::service()->generateCommCodeImage($savePath, $url, $id, $logo, $mod);//生成二维码图片
    }

    /**
     * 巡更点新增
     * @param $data
     * @param $operator_id
     * @param $operator_name
     * @return array
     */
    public function add($data, $operator_id, $operator_name, $userinfo = [])
    {
        $check = self::_checkDataDeal($data);
        if ($check['code'] != 1) {
            return $this->failed($check['msg']);
        }
        $new_data = $check['data'];
        $mod = new PsPatrolPoints();
        $new_data['created_at'] = time();
        $new_data['operator_id'] = $operator_id;
        $new_data['operator_name'] = $operator_name;
        $mod->setAttributes($new_data);
        if ($mod->save()) {
            $id = $mod->id;
            //生成二维码图片
            $this->createQrcode($mod);
            $res['record_id'] = $id;
            if (!empty($userinfo)) {
                $content = "巡检点名称:" . $data['name'];
                $operate = [
                    "community_id" => $data['community_id'],
                    "operate_menu" => "日常巡更",
                    "operate_type" => "巡更点新增",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
            }
            return $this->success($res);
        } else {
            return $this->failed('保存失败');
        }
    }

    /**
     * 巡更点编辑
     * @param $data
     * @param $operator_id
     * @param $operator_name
     * @return array
     */
    public function edit($data, $operator_id, $operator_name, $userinfo = [])
    {
        $check = self::_checkDataDeal($data);
        if ($check['code'] != 1) {
            return $this->failed($check['msg']);
        }
        $new_data = $check['data'];
        if ($new_data['need_location'] == 2) {
            $new_data['location_name'] = '';
            $new_data['lon'] = '';
            $new_data['lat'] = '';
        }
        $mod = PsPatrolPoints::findOne($data['id']);
        if ($mod) {
            if ($mod->is_del != 1) {
                return $this->failed('此巡更点状态有误，已被删除');
            }
            if ($mod->community_id != $data['community_id']) {
                return $this->failed("巡更点小区id不能变更！");
            }
            $new_data['operator_id'] = $operator_id;
            $new_data['operator_name'] = $operator_name;
            $mod->setAttributes($new_data, false);
            if ($mod->save()) {
                $id = $mod->id;
                //生成二维码图片
                $this->createQrcode($mod);
                $res['record_id'] = $id;
                if (!empty($userinfo)) {
                    $content = "巡检点名称:" . $mod->name;
                    $operate = [
                        "community_id" => $data['community_id'],
                        "operate_menu" => "日常巡更",
                        "operate_type" => "巡更点编辑",
                        "operate_content" => $content,
                    ];
                    OperateService::addComm($userinfo, $operate);
                }
                return $this->success($res);
            } else {
                return $this->failed('保存失败');
            }
        } else {
            return $this->failed('id无效，数据不存在');
        }
    }

    /**
     * 删除巡更点
     * @param $id
     * @return array
     */
    public function deleteData($id, $operator_id, $operator_name, $userinfo = [])
    {
        $mod = PsPatrolPoints::findOne($id);
        if ($mod) {
            if ($mod->is_del != 1) {
                return $this->failed('此巡更点已被删除');
            }
            //删除巡更点的时候判断这个巡更点是否正在任务中
            $check = self::_checkTaskByPointId($id);
            if ($check['code'] != 1) {
                return $this->failed($check['msg']);
            }
            $mod->is_del = 0;
            $mod->operator_id = $operator_id;
            $mod->operator_name = $operator_name;
            if ($mod->save()) {
                //删除巡更点对应的任务
                TaskService::service()->changeTaskDelByPoint($id);
                $res['record_id'] = $id;
                if (!empty($userinfo)) {
                    $content = "巡检点名称:" . $mod->name;
                    $operate = [
                        "community_id" => $mod->community_id,
                        "operate_menu" => "日常巡更",
                        "operate_type" => "巡更点删除",
                        "operate_content" => $content,
                    ];
                    OperateService::addComm($userinfo, $operate);
                }
                return $this->success($res);
            } else {
                return $this->failed('删除失败');
            }
        } else {
            return $this->failed('巡更点不存在');
        }
    }

    /**
     * 巡更点详情
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getDetail($id)
    {
        $detail = PsPatrolPoints::find()
            ->alias('p')
            ->leftJoin(['m' => PsCommunityModel::tableName()], 'p.community_id=m.id')
            ->select(['p.*', 'm.name as community_name'])
            ->where(['p.id' => $id])
            ->asArray()
            ->one();
        if ($detail) {
            //是否需要定位
            $detail['location'] = ($detail['need_location'] == '1') ? $detail['location_name'] : '不需要定位';
            $detail['need_location_label'] = $detail['need_location'] == '1' ? '需要' : '不需要';
            //是否需要拍照
            $detail['photo'] = ($detail['need_photo'] == '1') ? '是' : '否';
            $detail['need_photo_label'] = $detail['need_photo'] == '1' ? '需要' : '不需要';
        } else {
            return $this->failed('巡更点不存在');
        }
        return $detail;
    }

    /**
     * 下载二维码
     * @param $id
     * @return mixed
     */
    public function getQrCode($id)
    {
        $res = PsPatrolPoints::find()->where(['id' => $id, 'is_del' => 1])->asArray()->one();
        return $res;
    }
}
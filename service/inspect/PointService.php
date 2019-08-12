<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/12
 * Time: 10:42
 */

namespace service\inspect;

use app\common\MyException;
use app\models\PsDevice;
use app\models\PsDeviceCategory;
use app\models\PsInspectPoint;
use common\core\F;
use service\BaseService;
use Yii;

class PointService extends BaseService
{
    /**  物业后台接口 start */

    //巡检点新增
    public function add($params, $userInfo)
    {
        // 验证分类是否存在
        $categroy = PsDeviceCategory::find()
            ->where(['id' => $params['category_id']])
            ->asArray()->one();
        if (empty($categroy)) {
            throw new MyException('设备分类不存在!');
        }

        // 验证设备是否存在
        $device = PsDevice::find()
            ->where(['id' => $params['device_id']])
            ->andWhere(['community_id' => $params['community_id']])->asArray()->one();
        if (empty($device)) {
            throw new MyException('设备不存在!');
        }

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
        $params['created_at'] = time();

        $model = new PsInspectPoint();
        $model->scenario = 'add';  # 设置数据验证场景为 新增
        $model->load($params, '');   # 加载数据
        if ($model->validate()) {  # 验证数据
            //查看巡检点名称是否重复
            $point = PsInspectPoint::find()->where(['name' => $params['name'], 'community_id' => $params['community_id']])->one();
            if (!empty($point)) {
                throw new MyException('巡检点已存在!');
            }
            if ($model->save()) {  # 保存新增数据
                $id = $model->id;
                $savePath = F::imagePath('inspect');
                $logo = Yii::$app->basePath . '/web/img/lyllogo.png';//二维码中间的logo
                $url = Yii::$app->getModule('lylapp')->params['ding_web_host'] . '#/scanList?type=scan&id=' . $id;

                //TODO 后续添加
                //CommunityService::service()->generateCommCodeImage($savePath, $url, $id, $logo, $model);//生成二维码图片


                if (!empty($userInfo)){

                    //TODO 日志新增
                    /*$content = "巡检点名称:".$params['name'];
                    $operate = [
                        "community_id" =>$params['community_id'],
                        "operate_menu" => "设备巡检",
                        "operate_type" => "巡检点新增",
                        "operate_content" => $content,
                    ];*/
                }
                return $this->success([]);
            }
        }
    }

    //巡检点编辑
    public function edit($params, $userInfo)
    {

    }

    //巡检点详情
    public function view($params)
    {

    }

    //巡检点详情
    public function del($params, $userInfo)
    {

    }

    //巡检点列表
    public function pointList($params)
    {

    }

    //巡检点下拉
    public function getPoint()
    {
        
    }

    /**  物业后台接口 end */

    /**  钉钉接口 start */

    /**  钉钉接口 end */

    /**  公共接口 start */

    /**  公共接口 end */
}
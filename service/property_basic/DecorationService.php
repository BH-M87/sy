<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/10/20
 * Time: 9:55
 */
namespace service\property_basic;

use app\models\PsDecorationPatrol;
use app\models\PsDecorationRegistration;
use service\BaseService;
use service\rbac\OperateService;

Class DecorationService extends BaseService
{

    /*
     * 装修登记新增
     */
    public function add($params, $userInfo)
    {
        $model = new PsDecorationRegistration(['scenario' => 'add']);
        if ($model->load($params, '') && $model->validate()) {
            if (!$model->save()) {
                return $this->failed('新增失败！');
            }
            //添加日志
            $content = "小区：".$model->community_name.",房屋地址：" . $model->address;
            $operate = [
                "community_id" => $params['community_id'],
                "operate_menu" => "装修登记",
                "operate_type" => "登记新增",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success(['id' => $model->attributes['id']]);
        } else {
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 巡检记录新增
     */
    public function patrolAdd($params, $userInfo){
        $model = new PsDecorationPatrol(['scenario' => 'add']);
        $params['patrol_name'] = $userInfo['truename'];
        $params['patrol_id'] = $userInfo['id'];
        if ($model->load($params, '') && $model->validate()) {
            if (!$model->save()) {
                return $this->failed('新增失败！');
            }
            //添加日志
            $content = "小区：".$model->community_name.",房屋地址：" . $model->address.",巡检人：".$model->patrol_name;
            $operate = [
                "community_id" => $params['community_id'],
                "operate_menu" => "装修登记",
                "operate_type" => "巡检记录新增",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success(['id' => $model->attributes['id']]);
        } else {
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 装修登记列表
     */
    public function getList($params){
        $model = new PsDecorationRegistration();
        $result = $model->getList($params);
        if(!empty($result['list'])) {
            foreach ($result['list'] as $key => $value) {
                $result['list'][$key]['status_msg'] = !empty($value['status']) ? $model->statusMsg[$value['status']] : "";
                $result['list'][$key]['create_at_msg'] = !empty($value['create_at']) ? date('Y-m-d H:i:s', $value['status']) : "";
            }
        }
        return $this->success($result);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/10/20
 * Time: 9:55
 */
namespace service\property_basic;

use app\models\PsDecorationRegistration;
use service\BaseService;
use service\rbac\OperateService;

Class DecorationService extends BaseService
{

    /*
     * 新增电话
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
                "operate_type" => "装修登记新增",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success(['id' => $model->attributes['id']]);
        } else {
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }
}
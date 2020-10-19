<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/10/19
 * Time: 15:21
 * Desc: 常用电话
 */
namespace service\property_basic;


use app\models\PsPhone;
use service\BaseService;
use service\rbac\OperateService;

Class PhoneService extends BaseService
{

    /*
     * 新增电话
     */
    public function add($params,$userInfo){
        $model = new PsPhone(['scenario'=>'add']);
        if($model->load($params,'')&&$model->validate()){
            if(!$model->save()){
                return $this->failed('新增失败！');
            }
            //添加日志
            $content = "联系人名称:" . $model->contact_name;
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "常用电话",
                "operate_type" => "新增电话",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success(['id'=>$model->attributes['id']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 修改电话
     */
    public function edit($params,$userInfo){
        $model = new PsPhone(['scenario'=>'edit']);
        if($model->load($params,'')&&$model->validate()){
            if(!$model->edit($params)){
                return $this->failed('修改失败！');
            }
            //添加日志
            $content = "联系人名称:" . $model->contact_name;
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "常用电话",
                "operate_type" => "修改电话",
                "operate_content" => $content,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success(['id'=>$model->attributes['id']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }
}
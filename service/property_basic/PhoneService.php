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

        $editParams['id'] = !empty($params['id'])?$params['id']:'';
        $editParams['community_id'] = !empty($params['community_id'])?$params['community_id']:'';
        $editParams['community_name'] = !empty($params['community_name'])?$params['community_name']:'';
        $editParams['contact_name'] = !empty($params['contact_name'])?$params['contact_name']:'';
        $editParams['contact_phone'] = !empty($params['contact_phone'])?$params['contact_phone']:'';
        $editParams['type'] = !empty($params['type'])?$params['type']:'';
        if($model->load($editParams,'')&&$model->validate()){
            if(!$model->edit($editParams)){
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

    /*
     * 列表
     */
    public function getList($params){
        $model = new PsPhone();
        $result = $model->getList($params);
        if(!empty($result['list'])){
            foreach($result['list'] as $key=>$value){
                $result['list'][$key]['type_msg'] = !empty($value['type'])?$model->typeMsg[$value['type']]:'';
                $result['list'][$key]['create_msg'] = !empty($value['create_at'])?date('Y-m-d',$value['create_at']):'';
            }
        }
        return $this->success($result);
    }

    /*
     * 删除
     */
    public function del($params){
        $model = new PsPhone(['scenario'=>'del']);
        $editParams['id'] = !empty($params['id'])?$params['id']:'';
        $editParams['community_id'] = !empty($params['community_id'])?$params['community_id']:'';
        if($model->load($editParams,'')&&$model->validate()){
            if(!$model::deleteAll($editParams)){
                return $this->failed("删除失败");
            }
            return $this->success();
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 钉钉端列表
     */
    public function getListOfDing($params){
        $data = [];
        $model = new PsPhone();
        //小区服务
        $communityCondition = $params;
        $communityCondition['type'] = 1;
        $communityResult = $model->getList($communityCondition);
        if(!empty($communityResult['list'])){
            $data[] = [
                'msg' => '小区服务电话',
                'list' => $communityResult['list'],
            ];
        }
        //公共服务
        $commonCondition = $params;
        $commonCondition['type'] = 2;
        $commonResult = $model->getList($commonCondition);
        if(!empty($commonResult['list'])){
            $data[] = [
                'msg' => '公共服务电话',
                'list' => $commonResult['list'],
            ];
        }
        return $this->success($data);
    }
}
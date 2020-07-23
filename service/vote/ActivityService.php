<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/23
 * Time: 11:09
 * Desc: 投票活动
 */
namespace service\vote;

use app\models\VtActivity;
use app\models\VtActivityBanner;
use app\models\VtActivityGroup;
use app\models\VtPlayer;
use service\BaseService;
use Yii;
use yii\db\Exception;

Class ActivityService extends BaseService {

    //新建活动
    public function add($params){
        $trans = Yii::$app->db->beginTransaction();
        try{
            $addParams['code'] = !empty($params['code'])?$params['code']:'';
            $addParams['name'] = !empty($params['name'])?$params['name']:'';
            $addParams['start_at'] = !empty($params['start_at'])?strtotime($params['start_at']):'';
            $addParams['end_at'] = !empty($params['end_at'])?strtotime($params['end_at']):'';
            $addParams['content'] = !empty($params['content'])?$params['content']:'';
            $addParams['group_status'] = !empty($params['group_status'])?$params['group_status']:'';
            if($addParams['group_status']==1){
                if(empty($params['group_name'])){
                    return $this->failed("分组名称不能为空");
                }
                if(!is_array($params['group_name'])){
                    return $this->failed("分组名称是数组格式");
                }
            }
            if(!empty($params['banner'])){
                if(!is_array($params['group_name'])){
                    return $this->failed("活动banner是数组格式");
                }
            }

            $model = new VtActivity(['scenario'=>'add']);
            if($model->load($addParams,'')&&$model->validate()){
                if(!$model->saveData()){
                    throw new Exception('活动新建失败！');
                }
                if($model->attributes['group_status']==1){
                    foreach($params['group_name'] as $key=>$value){
                        $groupModel = new VtActivityGroup(['scenario'=>'add']);
                        $groupParams['name'] = !empty($value['name'])?$value['name']:'';
                        $groupParams['activity_id'] = $model->attributes['id'];
                        if($groupModel->load($groupParams,'')&&$groupModel->validate()){
                            if(!$groupModel->saveData()){
                                throw new Exception('新增分组失败！');
                            }
                        }else{
                            $msg = array_values($groupModel->errors)[0][0];
                            throw new Exception($msg);
                        }
                    }
                }
                if(!empty($params['banner'])){
                    foreach($params['banner'] as $key=>$value){
                        $bannerModel = new VtActivityBanner(['scenario'=>'add']);
                        $bannerParams['activity_id'] = $model->attributes['id'];
                        $bannerParams['img'] = !empty($value['img'])?$value['img']:'';
                        $bannerParams['link_url'] = !empty($value['link_url'])?$value['link_url']:'';
                        if($bannerModel->load($bannerParams,'')&&$bannerModel->validate()){
                            if(!$bannerModel->saveData()){
                                throw new Exception('新增分组失败！');
                            }
                        }else{
                            $msg = array_values($bannerModel->errors)[0][0];
                            throw new Exception($msg);
                        }
                    }
                }

                $trans->commit();
                return $this->success(['id'=>$model->attributes['id']]);
            }else{
                $msg = array_values($model->errors)[0][0];
                throw new Exception($msg);
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    //新建活动编辑
    public function edit($params){
        $trans = Yii::$app->db->beginTransaction();
        try{
            $updateParams['id'] = !empty($params['id'])?$params['id']:'';
            $updateParams['name'] = !empty($params['name'])?$params['name']:'';
            $updateParams['start_at'] = !empty($params['start_at'])?strtotime($params['start_at']):'';
            $updateParams['end_at'] = !empty($params['end_at'])?strtotime($params['end_at']):'';
            $updateParams['content'] = !empty($params['content'])?$params['content']:'';
            $updateParams['group_status'] = !empty($params['group_status'])?$params['group_status']:'';
            if($updateParams['group_status']==1){
                if(empty($params['group_name'])){
                    return $this->failed("分组名称不能为空");
                }
                if(!is_array($params['group_name'])){
                    return $this->failed("分组名称是数组格式");
                }
            }
            if(!empty($params['banner'])){
                if(!is_array($params['group_name'])){
                    return $this->failed("活动banner是数组格式");
                }
            }

            $model = new VtActivity(['scenario'=>'edit']);
            if($model->load($updateParams,'')&&$model->validate()){
                if(!$model->edit($updateParams)){
                    throw new Exception('活动新建失败！');
                }
                //删除banner
                if(!VtActivityBanner::deleteAll(['activity_id'=>$model->attributes['id']])){
                    throw new Exception('删除banner失败！');
                }
                if($model->attributes['group_status']==1){
                    foreach($params['group_name'] as $key=>$value){
                        if($value['id']){
                            //修改分组
                            $groupModel = new VtActivityGroup(['scenario'=>'edit']);
                            $groupParams['id'] = !empty($value['id'])?$value['id']:'';
                            $groupParams['name'] = !empty($value['name'])?$value['name']:'';
                            $groupParams['activity_id'] = $model->attributes['id'];
                            if($groupModel->load($groupParams,'')&&$groupModel->validate()){
                                if(!$groupModel->edit($groupParams)){
                                    throw new Exception('修改分组失败！');
                                }
                            }else{
                                $msg = array_values($groupModel->errors)[0][0];
                                throw new Exception($msg);
                            }
                        }else{
                            //新增分组
                            $groupModel = new VtActivityGroup(['scenario'=>'add']);
                            $groupParams['name'] = !empty($value['name'])?$value['name']:'';
                            $groupParams['activity_id'] = $model->attributes['id'];
                            if($groupModel->load($groupParams,'')&&$groupModel->validate()){
                                if(!$groupModel->saveData()){
                                    throw new Exception('新增分组失败！');
                                }
                            }else{
                                $msg = array_values($groupModel->errors)[0][0];
                                throw new Exception($msg);
                            }
                        }
                    }
                }else{
                    //关联的选手分组设置空
                    VtPlayer::updateAll(['group_id'=>0],'activity_id=:activity_id and group_id>:group_id',[":activity_id"=>$model->attributes['id'],":group_id"=>0]);
                }
                if(!empty($params['banner'])){
                    foreach($params['banner'] as $key=>$value){
                        $bannerModel = new VtActivityBanner(['scenario'=>'add']);
                        $bannerParams['activity_id'] = $model->attributes['id'];
                        $bannerParams['img'] = !empty($value['img'])?$value['img']:'';
                        $bannerParams['link_url'] = !empty($value['link_url'])?$value['link_url']:'';
                        if($bannerModel->load($bannerParams,'')&&$bannerModel->validate()){
                            if(!$bannerModel->saveData()){
                                throw new Exception('新增分组失败！');
                            }
                        }else{
                            $msg = array_values($bannerModel->errors)[0][0];
                            throw new Exception($msg);
                        }
                    }
                }

                $trans->commit();
                return $this->success(['id'=>$model->attributes['id']]);
            }else{
                $msg = array_values($model->errors)[0][0];
                throw new Exception($msg);
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    //活动列表
    public function getList($params){
        $model = new VtActivity();
        $result = $model->getList($params);
        if(!empty($result['list'])){
            $nowTime = time();
            foreach($result['list'] as $key=>$value){
                $result['list'][$key]['create_at_msg'] = !empty($value['create_at'])?date('Y-m-d H:i:s',$value['create_at']):'';
                $result['list'][$key]['status_msg'] = '进行中';
                if($nowTime<$value['start_at']){
                    $result['list'][$key]['status_msg'] = '未开始';
                }elseif($nowTime>$value['end_at']){
                    $result['list'][$key]['status_msg'] = '已结束';
                }
            }
        }
        return $this->success($result);
    }

    //活动详情
    public function getDetail($params){
        $model = new VtActivity(['scenario'=>'detail']);
        if($model->load($params,'')&&$model->validate()){
            $detail = $model->getDetail($params);
            $detail['start_at_msg'] = !empty($detail['start_at'])?date('Y-m-d H:i:s',$detail['start_at']):'';
            $detail['end_at_msg'] = !empty($detail['end_at'])?date('Y-m-d H:i:s',$detail['end_at']):'';
            return $this->success($detail);
        }else{
            $msg = array_values($model->errors)[0][0];
            throw new Exception($msg);
        }
    }
}
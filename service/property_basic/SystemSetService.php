<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/5/18
 * Time: 14:19
 * Desc: 系统设置
 */
namespace service\property_basic;

use app\models\PsSystemSet;
use service\BaseService;
use yii\base\Exception;

class SystemSetService extends BaseService {

    //配置详情
    public function getDetail($params){
        $model = new PsSystemSet(['scenario'=>'detail']);
        $detailParams['company_id'] = !empty($params['corp_id'])?$params['corp_id']:'';
        if($model->load($detailParams,'') && $model->validate()){
            $result = $model->getDetail($detailParams);
            if(empty($result)){
                $result = [
                    'payment_set' => 1,
                    'notice_content' => '',
                ];
            }
            return $this->success($result);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //配置编辑
    public function edit($params){
        try{
            $model = new PsSystemSet(['scenario'=>'detail']);
            $detailParams['company_id'] = !empty($params['corp_id'])?$params['corp_id']:'';
            if($model->load($detailParams,'') && $model->validate()){
                $result = $model->getDetail($detailParams);
                $editParams['company_id'] = !empty($params['corp_id'])?$params['corp_id']:'';
                $editParams['payment_set'] = !empty($params['payment_set'])?$params['payment_set']:1;
                $editParams['notice_content'] = !empty($params['notice_content'])?$params['notice_content']:'';
                if(empty($result)){
                    //新增
                    $editResult = self::addOrEdit($editParams,'add');
                }else{
                    //修改

                    $editResult = self::addOrEdit($editParams,'edit');
                }
                return $this->success($editResult);
            }else{
                $msg = array_values($model->errors)[0][0];
                return $this->failed($msg);
            }
        }catch (Exception $e){
            return $this->failed($e->getMessage());
        }
    }

    //新增修改
    public function addOrEdit($params,$scenario){
        $model = new PsSystemSet(['scenario'=>$scenario]);
        if($model->load($params,'') && $model->validate()){
            if($scenario=='add'){
                if(!$model->save()){
                    throw new Exception('新增失败');
                }
            }else{
                if(!$model->edit($params)){
                    throw new Exception('修改失败');
                }
            }
            return ['success'=>true];
        }else{
            $msg = array_values($model->errors)[0][0];
            throw new Exception($msg);
        }
    }

    //兑换预览
    public function preview($params){

    }
}
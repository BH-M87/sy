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
            $addParams['start_at'] = !empty($params['start_at'])?$params['start_at']:'';
            $addParams['end_at'] = !empty($params['end_at'])?$params['end_at']:'';
            $addParams['content'] = !empty($params['content'])?$params['content']:'';
            $addParams['group_status'] = !empty($params['group_status'])?$params['group_status']:'';

            $model = new VtActivity(['scenario'=>'add']);
            if($model->load($addParams,'')&&$model->validate()){
                if(!$model->saveData()){
                    throw new Exception('活动新建失败！');
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

}
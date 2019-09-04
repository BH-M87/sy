<?php
/**
 * User: ZQ
 * Date: 2019/9/4
 * Time: 11:27
 * For: ****
 */

namespace service\street;


use app\models\StNotice;
use app\models\StNoticeUser;
use common\core\PsCommon;
use common\MyException;

class NoticeService extends BaseService
{

    public function searchList($data)
    {
        $type = PsCommon::get($data,'type');
        $title = PsCommon::get($data,'title');
        $date_start = PsCommon::get($data,'date_start');
        $date_end = PsCommon::get($data,'date_end');
        $model = StNotice::find()->andFilterWhere(['type'=>$type])->andFilterWhere(['title'=>$title]);
        //如果搜索了发布时间
        if($date_start && $date_end){
            $start_time = strtotime($date_start." 00:00:00");
            $end_time = strtotime($date_end." 23:59:59");
            $model->andFilterWhere(['>=','create_at',$start_time])
                ->andFilterWhere(['<=','create_at',$end_time]);
        }
        return $model;
    }

    public function getList($data,$page,$pageSize)
    {
        $model = $this->searchList($data);
        $offset = ($page - 1) * $pageSize;
        $list = $model->offset($offset)->limit($pageSize)->orderBy('id desc')->asArray()->all();
        $totals = $model->count();
        $result['list'] = $list ? $list : [];
        $result['totals'] = $totals;
        return $result;
    }

    public function add($data,$user_info)
    {
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            //新增消息，获取id
            $id = $this->addNotice($data,$user_info);
            //每个发送对象，发送一个信息
            $this->addNoticeUser($data,$id);
            $transaction->commit();
            return $id;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->failed('新增失败'.$e);

        }
    }

    //新增消息，获取id
    public function addNotice($data,$user_info)
    {
        //判断这个通知是否已存在

        $organization_type = $user_info['node_type'];
        $organization_id = $user_info['dept_id'];
        $operator_id = $user_info['id'];
        $operator_name = $user_info['username'];
        $accessory = PsCommon::get($data,'accessory_file');
        $accessory_file = !empty($accessory) ? implode(',',$accessory) : '';

        $model = new StNotice();
        $model->organization_type = $organization_type;
        $model->organization_id = $organization_id;
        $model->type = PsCommon::get($data,'type',0);
        $model->title = PsCommon::get($data,'title');
        $model->describe = PsCommon::get($data,'describe');
        $model->content = PsCommon::get($data,'content');
        $model->operator_id = $operator_id;
        $model->operator_name = $operator_name;
        $model->accessory_file = $accessory_file;
        $model->create_at = time();
        if(!$model->save()){
            throw new MyException($model->getErrors());
        }
        return $model->id;

    }

    //每个发送对象，发送一个信息
    public function addNoticeUser($data,$id)
    {
        $receive_user_list = PsCommon::get($data,'receive_user_list',[]);
        if($receive_user_list){
            $saveData = [];
            foreach($receive_user_list as $key =>$value){
                $saveData['notice_id'][] = $id;
                $saveData['receive_user_id'][] = $value;
                $saveData['receive_user_name'][] = $id;
                $saveData['is_send'][] = 1;
                $saveData['is_read'][] = 1;
                $saveData['create_at'][] = time();
                $saveData['send_at'][] = 0;
            }
            StNoticeUser::model()->batchInsert($saveData);
        }

    }


}
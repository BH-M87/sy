<?php
/**
 * User: ZQ
 * Date: 2019/9/4
 * Time: 11:27
 * For: 通知通报
 */

namespace service\street;


use app\models\StNotice;
use app\models\StNoticeUser;
use common\core\PsCommon;
use common\MyException;

class NoticeService extends BaseService
{
    public $type_info = ['1'=>'通知', '2'=>'消息'];

    /**
     * 列表
     * @param $data
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getList($data,$page,$pageSize)
    {
        $model = $this->searchList($data);
        $offset = ($page - 1) * $pageSize;
        $list = $model->offset($offset)->limit($pageSize)->orderBy('id desc')->asArray()->all();
        $totals = $model->count();
        if($list){
            foreach($list as $key=>$value){
                $list[$key]['type_info'] = ['id'=>$value['type'],'name'=>$this->type_info[$value['type']]];
                $list[$key]['receive_user_list'] = $this->getUserInfoByNoticeId($value['id']);
                $un_read = $this->getUnReadInfoByNoticeId($value['id']);
                $list[$key]['un_read_num'] = $un_read['un_read_num'];
                $list[$key]['un_read_user_list'] = $un_read['un_read_user_list'];
            }
        }else{
            $list = [];
        }
        $result['list'] = $list;
        $result['totals'] = $totals;
        return $result;
    }

    /**
     * 搜索查询
     * @param $data
     * @return $this
     */
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
            $model = $model->andFilterWhere(['>=','create_at',$start_time])
                ->andFilterWhere(['<=','create_at',$end_time]);
        }
        return $model;
    }

    /**
     * 获取这个通知下面的所有接收对象
     * @param $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getUserInfoByNoticeId($id)
    {
        $list = StNoticeUser::find()->select(['receive_user_id as user_id','receive_user_name as user_name'])
            ->where(['notice_id'=>$id])->asArray()->all();
        if($list){
            return $list;
        }else{
            return [];
        }
    }

    /**
     * 获取这个通知下面所有的未读信息
     * @param $id
     * @return mixed
     */
    public function getUnReadInfoByNoticeId($id)
    {
        $list = StNoticeUser::find()
            ->select(['receive_user_id as user_id','receive_user_name as user_name'])
            ->where(['notice_id'=>$id,'is_read'=>1])
            ->asArray()->all();
        $result['un_read_num'] = count($list);
        $result['un_read_user_list'] = $list ? $list : [];
        return $result;
    }


    /**
     * 新增通知通报
     * @param $data
     * @param $user_info
     * @return array|int
     */
    public function add($data,$user_info)
    {
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            //新增消息，获取id
            $id = $this->addNotice($data,$user_info);
            //每个发送对象，发送一个信息
            $receive_user_list = PsCommon::get($data,'receive_user_list',[]);
            if($receive_user_list){
                $this->addNoticeUser($receive_user_list,$id);
            }
            $transaction->commit();
            return $id;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new MyException( '新增失败:'.$e->getMessage());

        }
    }

    //新增消息，获取id
    public function addNotice($data,$user_info)
    {
        $organization_type = $user_info['node_type'];
        $organization_id = $user_info['dept_id'];
        $operator_id = $user_info['id'];
        $operator_name = $user_info['username'];
        $accessory = PsCommon::get($data,'accessory_file');
        $accessory_file = !empty($accessory) ? implode(',',$accessory) : '';
        $type = PsCommon::get($data,'type',0);
        $title = PsCommon::get($data,'title');

        //判断这个通知是否已存在
        $models = StNotice::find()->where(['organization_id'=>$organization_id,'organization_type'=>$organization_type,
            'type'=>$type,'title'=>$title])->asArray()->one();
        if($models){
            throw new MyException('该通知标题已存在');
        }
        $model = new StNotice();
        $model->organization_type = $organization_type;
        $model->organization_id = $organization_id;
        $model->type = $type;
        $model->title = $title;
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

    //给发送对象发通知
    public function addNoticeUser($list,$id)
    {
        $saveData = [];
        foreach($list as $key =>$value){
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

    //编辑发送通知的发送对象
    public function edit($data,$user_info)
    {
        //新增加的发送对象
        $newReceiveList = PsCommon::get($data,'receive_user_list',[]);
        $id = $data['id'];
        $noticeUserInfo = $this->getUserInfoByNoticeId($id);
        //原先存在的发送对象
        $oldReceiveList = $noticeUserInfo ? array_column($noticeUserInfo,'user_id') : [];
        //比较两个数组，获取交集
        $intersect = array_intersect($newReceiveList,$oldReceiveList);
        //比较交集跟第一个数组，确定要新增的数据
        $difference1 = array_diff($newReceiveList,$intersect);
        if($difference1){
            $this->addNoticeUser($difference1,$id);
        }
        //比较交集跟第二个数组，确定要删除的数据
        $difference2 = array_diff($oldReceiveList,$intersect);
        if($difference2){
            StNoticeUser::deleteAll(['receive_user_id'=>$difference2,'notice_id'=>$id]);
        }
        return "编辑成功";
    }



}
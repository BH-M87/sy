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
use app\models\UserInfo;
use common\core\F;
use common\core\PsCommon;
use common\MyException;

class NoticeService extends BaseService
{
    public $type_info = ['1' => '通知', '2' => '消息'];

    /**
     * 列表
     * @param $data
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getList($data, $page, $pageSize)
    {
        $model = $this->searchList($data);
        $offset = ($page - 1) * $pageSize;
        $list = $model->offset($offset)->limit($pageSize)->orderBy('id desc')->asArray()->all();
        $totals = $model->count();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['type_info'] = ['id' => $value['type'], 'name' => $this->type_info[$value['type']]];
                $list[$key]['receive_user_list'] = $this->getUserInfoByNoticeId($value['id']);
                $un_read = $this->getUnReadInfoByNoticeId($value['id']);
                $list[$key]['un_read_num'] = $un_read['un_read_num'];
                $list[$key]['un_read_user_list'] = $un_read['un_read_user_list'];
                $list[$key]['create_at'] = date("Y-m-d H:i:s",$value['create_at']);
            }
        } else {
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
        $type = PsCommon::get($data, 'type');
        $title = PsCommon::get($data, 'title');
        $date_start = PsCommon::get($data, 'date_start');
        $date_end = PsCommon::get($data, 'date_end');
        $model = StNotice::find()->andFilterWhere(['type' => $type])
            ->andFilterWhere(['like','title',$title]);
        //如果搜索了发布时间
        if ($date_start && $date_end) {
            $start_time = strtotime($date_start . " 00:00:00");
            $end_time = strtotime($date_end . " 23:59:59");
            $model = $model->andFilterWhere(['>=', 'create_at', $start_time])
                ->andFilterWhere(['<=', 'create_at', $end_time]);
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
        $list = StNoticeUser::find()->alias('nu')
            ->select(['mu.receive_user_id as user_id', 'mu.receive_user_name as user_name'])
            ->leftJoin(['u'=>UserInfo::tableName()],'u.id = mu.receive_user_id')
            ->where(['mu.notice_id' => $id])
            ->andWhere(['>','mu.receive_user_id',0])
            ->asArray()->all();
        if ($list) {
            return $list;
        } else {
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
            ->select(['receive_user_id as user_id', 'receive_user_name as user_name'])
            ->where(['notice_id' => $id, 'is_read' => 1])
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
    public function add($data, $user_info)
    {
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            //新增消息，获取id
            $id = $this->addNotice($data, $user_info);
            //每个发送对象，发送一个信息
            $receive_user_list = PsCommon::get($data, 'receive_user_list', []);
            $result = $this->addNoticeUser($receive_user_list, $id);
            $transaction->commit();
            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new MyException('新增失败:' . $e->getMessage());

        }
    }

    //新增消息，获取id
    public function addNotice($data, $user_info)
    {
        $organization_type = $user_info['node_type'];
        $organization_id = $user_info['dept_id'];
        $operator_id = $user_info['id'];
        $operator_name = $user_info['username'];
        $accessory = PsCommon::get($data, 'accessory_file');
        $accessory_file = !empty($accessory) ? implode(',', $accessory) : '';
        $type = PsCommon::get($data, 'type', 0);
        $title = PsCommon::get($data, 'title');

        //判断这个通知是否已存在
        $models = StNotice::find()->where(['organization_id' => $organization_id, 'organization_type' => $organization_type,
            'type' => $type, 'title' => $title])->asArray()->one();
        if ($models) {
            throw new MyException('该通知标题已存在');
        }
        $model = new StNotice();
        $model->organization_type = $organization_type;
        $model->organization_id = $organization_id;
        $model->type = $type;
        $model->title = $title;
        $model->describe = PsCommon::get($data, 'describe');
        $model->content = PsCommon::get($data, 'content');
        $model->operator_id = $operator_id;
        $model->operator_name = $operator_name;
        $model->accessory_file = $accessory_file;
        $model->create_at = time();
        if (!$model->save()) {
            throw new MyException($model->getErrors());
        }
        return $model->id;

    }

    //给发送对象发通知
    public function addNoticeUser($list, $id)
    {
        $saveData = [];
        foreach ($list as $key => $value) {
            $saveData['notice_id'][] = $id;
            $saveData['receive_user_id'][] = $value;
            $saveData['receive_user_name'][] = UserService::service()->getUserNameById($value);
            $saveData['is_send'][] = 1;
            $saveData['is_read'][] = 1;
            $saveData['create_at'][] = time();
            $saveData['send_at'][] = 0;
        }
        StNoticeUser::model()->batchInsert($saveData);
        $detail = StNotice::find()->where(['id'=>$id])->asArray()->one();
        //发送钉钉信息
        $result = DingMessageService::service()->send($id,$list,$detail['title'],$detail['organization_id'],$detail['operator_name'],$detail['create_at']);
        return $result;

    }

    //编辑发送通知的发送对象
    public function edit($data, $user_info)
    {
        //新增加的发送对象
        $newReceiveList = PsCommon::get($data, 'receive_user_list', []);
        $id = $data['id'];
        $noticeUserInfo = $this->getUserInfoByNoticeId($id);
        //原先存在的发送对象
        $oldReceiveList = $noticeUserInfo ? array_column($noticeUserInfo, 'user_id') : [];
        //比较两个数组，获取交集
        $intersect = array_intersect($newReceiveList, $oldReceiveList);
        //yii2事物
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $result = [];
            //比较交集跟第一个数组，确定要新增的数据
            $difference1 = array_diff($newReceiveList, $intersect);
            if ($difference1) {
                $result = $this->addNoticeUser($difference1, $id);
            }
            //比较交集跟第二个数组，确定要删除的数据
            $difference2 = array_diff($oldReceiveList, $intersect);
            if ($difference2) {
                StNoticeUser::deleteAll(['receive_user_id' => $difference2, 'notice_id' => $id]);
            }
            $transaction->commit();
            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new MyException('编辑失败：' . $e->getMessage());

        }

    }

    /**
     * 查看详情
     * @param $data
     * @return array|null|\yii\db\ActiveRecord
     */
    public function detail($data)
    {
        $id = $data['id'];
        $detail = StNotice::find()->where(['id'=>$id])->asArray()->one();
        if($detail){
            $detail['type_info'] = ['id'=>$detail['type'],'name'=>$this->type_info[$detail['type']]];
            $detail['receive_user_list'] = $this->getUserInfoByNoticeId($id);
            $accessory_file = $detail['accessory_file'];
            $detail['accessory_file'] = $this->getOssUrlByKey($accessory_file);
            $detail['create_at'] = date("Y-m-d H:i",$detail['create_at']);
            $detail['operator_group_name'] = UserService::service()->getDepartmentNameById($detail['organization_id']);
        }else{
            $detail = [];
        }
        return $detail;
    }

    /**
     * 删除通知
     * @param $data
     * @return string
     * @throws MyException
     */
    public function delete($data)
    {
        $id = $data['id'];
        $detail = StNotice::find()->where(['id'=>$id])->asArray()->one();
        if(empty($detail)){
            throw new MyException('该通知已被删除');
        }
        //yii2事物
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            //删除通知
            StNotice::deleteAll(['id'=>$id]);
            //删除给所有对象发送的通知s
            StNoticeUser::deleteAll(['notice_id'=>$id]);
            $transaction->commit();
            return "删除成功";
        } catch (\Exception $e) {
            $transaction->rollBack();
            return "删除失败:".$e->getMessage();

        }

    }

    /**
     * 获取公共参数
     * @param $data
     * @return array
     */
    public function getCommon()
    {
        return $this->returnIdNameToCommon($this->type_info);
    }

    /**
     * 发送通知提醒
     * @param $data
     * @return mixed
     * @throws MyException
     */
    public function remind($data)
    {
        $id = $data['id'];
        $detail = StNotice::find()->where(['id'=>$id])->asArray()->one();
        if(empty($detail)){
            throw new MyException('该通知不存在');
        }
        $unReadUserList = $this->getUnReadInfoByNoticeId($id);
        $un_read_user_list = $unReadUserList['un_read_user_list'];
        if($un_read_user_list){
            $userList = array_column($un_read_user_list,'user_id');
            $result = DingMessageService::service()->send($id,$userList,$detail['title'],$detail['organization_id'],$detail['operator_name'],$detail['create_at']);
            return $result;
        }else{
            throw new MyException("没有可发送的消息对象");
        }

    }

    /**
     * 钉钉端获取我的通知列表
     * @param $data
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getMyList($data,$page, $pageSize)
    {
        $user_id = $data['user_id'];
        $model = StNoticeUser::find()->alias('u')
            ->leftJoin(['n'=>StNotice::tableName()],'u.notice_id = n.id')
            ->where(['u.receive_user_id'=>$user_id]);
        $offset = ($page - 1) * $pageSize;
        $list = $model->select(['n.id','n.type','n.describe','n.operator_id','n.operator_name','n.organization_id','n.create_at','u.is_read','n.title'])
            ->offset($offset)->limit($pageSize)->orderBy('n.id desc')->asArray()->all();
        $count = $model->count();
        if($list){
            foreach($list as $key =>$value){
                $list[$key]['type_info'] = ['id'=>$value['type'],'name'=>$this->type_info[$value['type']]];
                $list[$key]['operator_group_name'] = UserService::service()->getDepartmentNameById($value['organization_id']);
                $list[$key]['create_at'] = date("Y-m-d H:i",$value['create_at']);
            }
        }else{
            $list = [];
        }
        $result['list'] = $list;
        $result['totals'] = $count;
        return $result;
    }

    /**
     * 钉钉端查看我的通知详情
     * @param $data
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getMydetail($data)
    {
        //\Yii::info("dingDetail:".json_encode($data),"api");
        $detail = $this->detail($data);
        $is_read = StNoticeUser::find()->select(['is_read'])->where(['notice_id'=>$data['id'],'receive_user_id'=>$data['user_id']])->asArray()->scalar();
        if($is_read == 1){
            //更新这条记录已读
            StNoticeUser::updateAll(['is_read'=>2],['notice_id'=>$data['id'],'receive_user_id'=>$data['user_id']]);
        }
        return $detail;
    }

    public function fix($data,$type=1)
    {
        switch($type){
            case "1":
                $list = StNoticeUser::find()->where("1=1")->asArray()->all();
                if($list){
                    foreach($list as $key =>$value){
                        $receive_user_name = UserService::service()->getUserNameById($value['receive_user_id']);
                        StNoticeUser::updateAll(['receive_user_name'=>$receive_user_name],['id'=>$value['id']]);
                    }
                }
                break;

        }
    }


}
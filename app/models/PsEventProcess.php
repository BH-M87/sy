<?php

namespace app\models;

class PsEventProcess extends BaseModel 
{
    public static function tableName()
    {
        return 'ps_event_process';
    }

    public function rules()
    {
        return [
            [['event_id','status','content', 'create_id','create_name'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [["id",'event_id', 'status','create_at'], 'integer'],
            [['create_id','create_name'], 'string',"max"=>20],
            [['content'], 'string',"max"=>1000],
            [['create_id','create_name','content'], 'trim'],
            [["create_at"],"default",'value' => time(),'on'=>['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id' => 'id',
              'event_id' => '事件id',
              'status' => '处置状态；1-已签收 2-已办结 3-已驳回 4-已结案',
              'content' => '处置内容',
              'process_img' => '处置图片',
              'create_id' => '处置用户id',
              'create_name' => '处置用户名称',
              'create_at' => '处置时间',
        ];
    }

    /***
     * 新增
     * @return true|false
     */
    public function saveData()
    {
        return $this->save();
    }
}
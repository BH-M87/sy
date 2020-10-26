<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/5/22
 * Time: 9:36
 * Desc: 兑换记录
 */
namespace app\models;

class PsEventComment extends BaseModel {



    public static function tableName()
    {
        return 'ps_event_comment';
    }

    public function rules()
    {
        return [
            // 所有场景
            [['event_id','comment','create_id','create_name'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [["id",'event_id', 'create_at'], 'integer'],
            [['create_id','create_name'], 'string',"max"=>20],
            [['comment'], 'string',"max"=>1000],
            [['create_id','create_name','comment'], 'trim'],
            [['event_id'], 'eventExist', 'on' => ['add']], //事件是否存在
            [["create_at"],"default",'value' => time(),'on'=>['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id' => 'id',
              'event_id' => '事件id',
              'comment' => '评价内容',
              'create_id' => '评价用户id',
              'create_name' => '评价用户名称',
              'create_at' => '评价时间',
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

    /*
     * 事件是否存在
     */
    public function eventExist($attribute){
        if(!empty($this->event_id)){
            $res = PsEvent::find()->select(['id'])->where('id=:id',[':id'=>$this->event_id])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "该事件不存在！");
            }
        }
    }
}
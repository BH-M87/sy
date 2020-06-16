<?php

namespace app\models;

class PsParkMessage extends BaseModel
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_park_reservation';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id','community_name','user_id','status','content'], 'required','on'=>'add'],
            [['content'], 'string', 'max' => 500],
            [['create_at','update_at'], 'default', 'value' => time(),'on'=>['add']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'              => 'ID',
            'community_id'    => '小区',
            'community_name'  => '小区名称',
            'user_id'         => '用户id',
            'status'       => '消息类型',
            'content'         => '消息内容',
            'create_at'       => '创建时间',
            'update_at'       => '修改时间',
        ];
    }

    /***
     * 新增
     * @return bool
     */
    public function saveData()
    {
        return $this->save();
    }
}

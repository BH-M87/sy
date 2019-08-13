<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_repair".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $repair_no
 * @property integer $room_user_id
 * @property string $room_username
 * @property integer $repair_type
 * @property string $repair_content
 * @property integer $expired_repair_type
 * @property string $repair_imgs
 * @property integer $expired_repair_time
 * @property integer $status
 * @property string $day
 * @property integer $create_at
 */
class PsRepair extends BaseModel
{
    /**
     * @inheritdoc
     */
    public $repair_id;
    public static function tableName()
    {
        return 'ps_repair';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id','repair_type_id','repair_content','expired_repair_time'],'required','message'=>'{attribute}不能为空!','on'=>'create'],
            [['repair_content'], 'string', 'max' => 200, 'message'=>'{attribute}不能超过200个字符!', 'on' => 'create'],
            [['community_id'], 'required','message' => '{attribute}不能为空!', 'on' => ['list']],
            [['repair_id'],'required','message'=>'{attribute}不能为空','on'=>['make-complete']],
            [['repair_content'],'required','message'=>'{attribute}不能为空','on'=>['make-complete']],
            [['community_id'],'required','message'=>'{attribute}不能为空','on'=>['statistic','statistic-type','statistic-score']],
            ['day', 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区id',
            'repair_no' => 'Repair No',
            'member_id' => 'Room User ID',
            'appuser_id'   => 'app 用户id',
            'room_username' => 'Room Username',
            'repair_type_id' => '报修类型',
            'repair_content' => '报修内容',
            'expired_repair_type' => 'Expired Repair Type',
            'repair_imgs' => 'Repair Imgs',
            'expired_repair_time' => '期望上门时间',
            'status' => 'Status',
            'create_at' => 'Create At',
            'repair_id'=> "工单id",
        ];
    }

    public function getRepairType()
    {
        return $this->hasOne(PsRepairType::className(), ['id' => 'repair_type_id']);
    }

    public function getRepairBill()
    {
        return $this->hasOne(PsRepairBill::className(), ['repair_id' => 'id']);
    }
}

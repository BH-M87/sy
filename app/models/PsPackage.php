<?php

namespace app\models;

use app\common\core\Regular;
use Yii;

/**
 * This is the model class for table "ps_package".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $room_id
 * @property integer $delivery_id
 * @property string $tracking_no
 * @property string $receiver
 * @property string $mobile
 * @property integer $note
 * @property integer $status
 * @property integer $create_at
 * @property integer $receive_at
 */
class PsPackage extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_package';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['room_id', 'community_id', 'delivery_id', 'note', 'status', 'create_at', 'receive_at'], 'integer'],
            [['delivery_id', 'community_id', 'tracking_no', 'receiver', 'mobile', 'note', 'status', 'create_at'], 'required'],
            [['tracking_no', 'receiver'], 'string', 'max' => 20],
            ['tracking_no', 'match', 'pattern'=> Regular::letterOrNumber(1, 20), 'message'=>'运单号只能20位以内的字母或数字'],
            [['mobile'], 'string', 'max' => 11, 'message'=>'手机格式不正确'],
            ['mobile', 'match', 'pattern'=> Regular::phone(), 'message'=>'手机格式不正确'],
            ['status', 'in', 'range' => [1, 2]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区ID',
            'room_id' => '户号',
            'delivery_id' => '快递公司ID',
            'tracking_no' => '运单号',
            'receiver' => '接收人',
            'mobile' => '接收人手机号',
            'note' => '快递备注',
            'status' => '状态',
            'create_at' => '送达时间',
            'receive_at' => '领取时间',
        ];
    }
}

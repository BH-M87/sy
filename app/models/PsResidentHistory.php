<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_resident_history".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $member_id
 * @property integer $room_id
 * @property integer $audit_id
 * @property integer $room_user_id
 * @property integer $status
 * @property integer $operator_id
 * @property integer $operator_name
 * @property integer $create_at
 */
class PsResidentHistory extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_resident_history';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'member_id', 'room_id', 'status', 'operator_id', 'operator_name', 'create_at'], 'required'],
            [['community_id', 'member_id', 'room_id', 'audit_id', 'room_user_id', 'status', 'operator_id', 'operator_name', 'create_at'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'member_id' => 'Member ID',
            'room_id' => 'Room ID',
            'audit_id' => 'Audit ID',
            'room_user_id' => 'Room User ID',
            'status' => 'Status',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'create_at' => 'Create At',
        ];
    }

    public function add($params)
    {
        $model = new self();
        $model->load($params, '');
        $model->create_at = time();
        return $model->save();
    }

    //添加审核通过的变更历史
    public function addHistory($params, $userInfo, $roomUser=false)
    {
        if ($roomUser) {
            $data = [
                'community_id' => $params['community_id'],
                'member_id' => $params['member_id'],
                'room_id' => $params['room_id'],
                'room_user_id' => $params['id'],
                'status' => $params['status'] == 3 ? 4 : 1,
                'operator_id' => $userInfo['id'],
                'operator_name' => $userInfo['name'],
            ];
        } else {
            $data = [
                'community_id' => $params['community_id'],
                'member_id' => $userInfo['id'],
                'room_id' => $params['room_id'],
                'audit_id' => $params['id'],
                'status' => $params['status'] == 0 ? 2 : 3,
                'operator_id' => $userInfo['id'],
                'operator_name' => $userInfo['name'],
            ];
        }

        return PsResidentHistory::model()->add($data);
    }
}

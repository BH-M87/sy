<?php
namespace app\models;

class VtActivityView extends BaseModel
{
    public static function tableName()
    {
        return 'vt_activity_view';
    }

    public function rules()
    {
        return [
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'activity_code' => '活动code',
            'member_id' => '用户id',
            'create_at' => '新增时间',
        ];
    }
}
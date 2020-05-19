<?php
namespace app\models;

use yii\behaviors\TimestampBehavior;

use common\core\Regular;

class PsRoomVoteRecord extends BaseModel
{
    public static $type = ['1'  => '赞成', '2'  => '反对', '3'  => '弃权'];

    public static function tableName()
    {
        return 'ps_room_vote_record';
    }

    public function rules()
    {
        return [
            [['roomFullName', 'communityId', 'buildingFullName', 'unitFullName', 'memberId', 'memberName','type', 'room_vote_id'],'required','message'=>'{attribute}不能为空!','on' => ['add']],
            [['communityName', 'groupName', 'buildingName', 'unitName', 'roomName', 'memberName', 'memberMobile', 'buildingId'], 'string', 'max' => 30],
            [['roomArea'], 'number'],
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'room_vote_id' => '投票id',
            'communityId' => '小区id',
            'communityName' => '小区名称',
            'groupName' => '苑期区名称',
            'buildingFullName' => '幢全称',
            'buildingName' => '幢名称',
            'buildingId' => '幢id',
            'unitFullName' => '单元全称',
            'unitName' => '单元名称',
            'roomFullName' => '房屋全称',
            'roomName' => '室名称',
            'roomArea' => '房屋面积',
            'memberId' => '住户id',
            'memberName' => '住户姓名',
            'memberMobile' => '住户手机',
            'type' => '投票',
            'create_at' => '添加时间',
        ];
    }

    // 新增 编辑
    public function saveData($scenario, $p)
    {
        if ($scenario == 'edit') {
            self::updateAll($p, ['id' => $p['id']]);
            return true;
        }
        return $this->save();
    }
}

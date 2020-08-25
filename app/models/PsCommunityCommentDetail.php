<?php
namespace app\models;

use Yii;

class PsCommunityCommentDetail extends BaseModel
{
    public static function tableName()
    {
        return 'ps_community_comment_detail';
    }

    public function rules()
    {
        return [
            [['comment_id', 'community_id', 'community_name', 'group_id', 'building_id', 'unit_id', 'room_id', 'member_id', 'avatar', 'name', 'mobile', 'score', 'comment_year', 'comment_month', 'fullName'], 'required'],
            [['score'], 'number'],
            [['avatar'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 30],
            [['mobile'], 'string', 'max' => 11],
            [['content'], 'string', 'max' => 150],
            ['score', 'in', 'range' => [1, 2, 3, 4, 5], 'message' => '{attribute}有误', 'on' => ['add']],
            ['created_at', 'default', 'value' => time(), 'on' => 'add'],
            [['member_id', 'community_id'], 'existData', 'on' => ['add', 'edit']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'comment_id' => 'comment_id',
            'community_id' => '小区ID',
            'community_name' => '小区名称',
            'group_id' => '苑期区ID',
            'building_id' => '楼栋ID',
            'unit_id' => '单元ID',
            'room_id' => '房屋ID',
            'fullName' => '房屋全称',
            'member_id' => '住户ID',
            'avatar' => '住户头像',
            'name' => '住户姓名',
            'mobile' => '住户手机',
            'comment_year' => '评分年份',
            'comment_month' => '评分月份',
            'score' => '评分',
            'content' => '内容',
            'created_at' => '评分时间',
        ];
    }

    // 判断数据是否已存在
    public function existData()
    {   
        $beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
        $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));

        $model = self::getDb()->createCommand("SELECT id from ps_community_comment_detail 
            where community_id = :community_id and member_id = :member_id and created_at >= :start_at and created_at <= :end_at")
            ->bindValue(':community_id', $this->community_id)
            ->bindValue(':member_id', $this->member_id)
            ->bindValue(':start_at', $beginThismonth)
            ->bindValue(':end_at', $endThismonth)
            ->queryOne();

        if (!empty($model)) {
            $this->addError('', (int)date('m', time())."月服务已评价！");
        }
    }
}
